<?php
require_once('../common/function.php');
require_once('../db/DB.php');

get_header();

// 現在時刻の「年月」を取得
$now = new DateTime();
$this_year_month = $now->format('Y-m');
$login_role = $_SESSION['login_role'];

$posted_owner_id = h($_SESSION['login_owner_id']);
$posted_year_month = $this_year_month;	// 「年月」の初期値を代入
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $posted_owner_id = $_POST['owner_id'];
    $posted_year_month = $_POST['year_month'];
}

// postされた「年月」のDatePeriodを取得
$begin = new DateTime('first day of ' . $posted_year_month);
if ($posted_year_month === $this_year_month) {
    $end = $now;
} else {
    $end = new DateTime('last day of ' . $posted_year_month);
    $end = $end->modify('+1 day');	// 当該「年月」の最後の日もDatePeriodに含まれるようにする
}
$interval = new DateInterval('P1D');
$daterange = new DatePeriod($begin, $interval, $end);

$array_inventory_changes_in_posted_year_month = array();	// 各SKUごとの在庫推移を格納する配列
$all_goods_total_inventory_stock_volume_in_posted_year_month = 0;	// postされたowner_idの商品所有者の、当該「年月」の累計在庫数

try {
    // データベース接続
    $db = DB::getDB();
    //テーブルm_staffからowner_id, ownerを取得
    $stmt = $db->prepare("SELECT owner_id, owner FROM m_staff");
    $stmt->execute();
    $result_staff = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $db = null;
    $stmt = null;
} catch (PDOException $e) {
    die('エラー：' . $e->getMessage());
}
$option_tags_for_owner_id = "";
foreach ($result_staff as $varr) {
    if ($posted_owner_id === $varr['owner_id']) {
        $option_tags_for_owner_id .= "\t\t\t\t\t<option value=\"{$varr['owner_id']}\" selected>{$varr['owner']}</option>\n";
    } else {
        $option_tags_for_owner_id .= "\t\t\t\t\t<option value=\"{$varr['owner_id']}\">{$varr['owner']}</option>\n";
    }
}

try {
    $db = DB::getDB();
    // 各SKUの必要なフィールドを取得。postされたonwer_idの、postされた「年月」の、各SKUの「月初時点在庫」の「数量」を取得。
    $sql = <<< EOD
SELECT
g.owner_id,
g.goods_id,
g.color_size_id,
g.goods_name,
g.color,
g.size,
user_id,
mst.volume as mst_volume
FROM
m_goods as g
LEFT OUTER JOIN
month_stock as mst
ON
g.owner_id = mst.owner_id
AND
g.goods_id = mst.goods_id
AND
g.color_size_id = mst.color_size_id
AND
mst.years_months = :years_months
LEFT JOIN
m_user
ON
g.owner_id = m_user.owner_id
WHERE
g.owner_id = :owner_id
AND
g.deleted_flag = false
ORDER BY
g.goods_id ASC,
g.color_size_id ASC;
EOD;
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':owner_id', $posted_owner_id, PDO::PARAM_STR);
    $date_time_format = 'Y-m';
    $date_for_posted_year_month = DateTime::createFromFormat($date_time_format, $posted_year_month);
    $stmt->bindValue(':years_months', $date_for_posted_year_month->format('Ym'), PDO::PARAM_STR);
    $stmt->execute();
    $array_inventory_changes_in_posted_year_month = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 各SKUごとに在庫推移を格納していく
    foreach ($array_inventory_changes_in_posted_year_month as &$data_row) {
        $owner_id = $posted_owner_id;
        $goods_id = $data_row['goods_id'];
        $color_size_id = $data_row['color_size_id'];

        if (is_null($data_row['mst_volume'])) {
            $mst_volume = 0;
        } else {
            $mst_volume = (int)$data_row['mst_volume'];
        }
        $inventory_stock_volume_on_previous_day = $mst_volume;
        $total_stock_volume_in_posted_year_month = 0;
        $total_shipping_volume_in_posted_year_month = 0;
        $total_inventory_diff_volume_in_posted_year_month = 0;
        $total_inventory_stock_volume_in_posted_year_month = 0;

        // 各日付ごとに在庫推移を格納していく
        foreach ($daterange as $that_day) {
            $stock_volume_on_that_day = 0;
            $shipping_volume_on_that_day = 0;
            $inventory_diff_volume_on_that_day = 0;
            $inventory_stock_volume_on_that_day = 0;

            // 当該日の入荷量を取得
            $sql = " SELECT IFNULL(SUM(stock_volume), 0)";
            $sql .= " FROM stock_results";
            $sql .= " WHERE owner_id = :owner_id";
            $sql .= " AND goods_id = :goods_id";
            $sql .= " AND color_size_id = :color_size_id";
            $sql .= " AND stock_results_day = :that_day";
            $sql .= " GROUP BY stock_results_day";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':owner_id', $owner_id, PDO::PARAM_STR);
            $stmt->bindParam(':goods_id', $goods_id, PDO::PARAM_STR);
            $stmt->bindParam(':color_size_id', $color_size_id, PDO::PARAM_STR);
            $stmt->bindValue(':that_day', $that_day->format('Y-m-d'), PDO::PARAM_STR);
            $stmt->execute();
            $stock_volume_on_that_day = (int)$stmt->fetchColumn();

            // 当該日の出荷量を取得
            $sql = " SELECT IFNULL(SUM(volume), 0)";
            $sql .= " FROM shipping_request";
            $sql .= " WHERE owner_id = :owner_id";
            $sql .= " AND goods_id = :goods_id";
            $sql .= " AND color_size_id = :color_size_id";
            $sql .= " AND shipping_results_day = :that_day";
            $sql .= " AND completion_flag = '2'";
            $sql .= " GROUP BY shipping_results_day";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':owner_id', $owner_id, PDO::PARAM_STR);
            $stmt->bindParam(':goods_id', $goods_id, PDO::PARAM_STR);
            $stmt->bindParam(':color_size_id', $color_size_id, PDO::PARAM_STR);
            $stmt->bindValue(':that_day', $that_day->format('Y-m-d'), PDO::PARAM_STR);
            $stmt->execute();
            $shipping_volume_on_that_day = (int)$stmt->fetchColumn();

            // 当該日の棚卸差異数量を取得
            $sql = " SELECT difference_volume";
            $sql .= " FROM inventory_data";
            $sql .= " WHERE owner_id = :owner_id";
            $sql .= " AND goods_id = :goods_id";
            $sql .= " AND color_size_id = :color_size_id";
            $sql .= " AND inventory_date = :that_day";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':owner_id', $owner_id, PDO::PARAM_STR);
            $stmt->bindParam(':goods_id', $goods_id, PDO::PARAM_STR);
            $stmt->bindParam(':color_size_id', $color_size_id, PDO::PARAM_STR);
            $stmt->bindValue(':that_day', $that_day->format('Y-m-d'), PDO::PARAM_STR);
            $stmt->execute();
            $result_inventory_diff_volume_on_that_day = $stmt->fetchColumn();
            if ($result_inventory_diff_volume_on_that_day === false) {
                $inventory_diff_volume_on_that_day = 0;
            } else {
                $inventory_diff_volume_on_that_day = (int)$result_inventory_diff_volume_on_that_day;
            }

            // 当該日の在庫数量を取得
            $inventory_stock_volume_on_that_day = $inventory_stock_volume_on_previous_day + $stock_volume_on_that_day - $shipping_volume_on_that_day + $inventory_diff_volume_on_that_day;
            $data_row[$that_day->format('Y-m-d')] = array('stock_volume' => $stock_volume_on_that_day,
                'shipping_volume' => $shipping_volume_on_that_day,
                'inventory_diff_volume' => $inventory_diff_volume_on_that_day,
                'inventory_stock_volume' => $inventory_stock_volume_on_that_day);

            $total_stock_volume_in_posted_year_month += $stock_volume_on_that_day;
            $total_shipping_volume_in_posted_year_month += $shipping_volume_on_that_day;
            $total_inventory_diff_volume_in_posted_year_month += $inventory_diff_volume_on_that_day;
            $total_inventory_stock_volume_in_posted_year_month += $inventory_stock_volume_on_that_day;

            $inventory_stock_volume_on_previous_day = $inventory_stock_volume_on_that_day;
        }
        $data_row['total_stock_volume'] = $total_stock_volume_in_posted_year_month;
        $data_row['total_shipping_volume'] = $total_shipping_volume_in_posted_year_month;
        $data_row['total_inventory_diff_volume'] = $total_inventory_diff_volume_in_posted_year_month;
        $data_row['total_inventory_stock_volume'] = $total_inventory_stock_volume_in_posted_year_month;
        $all_goods_total_inventory_stock_volume_in_posted_year_month += $total_inventory_stock_volume_in_posted_year_month;
    }
    unset($data_row);

    $db = null;
    $stmt = null;
} catch (PDOException $e) {
    die('エラー：' . $e->getMessage());
}
?>
<div id="contents">
    <div class="inner">
        <div id="main">
            <main class="mb-5" role="main">
                <h1 class="mt-30px">在庫推移表</h1>
                <div class="">
                    <form action="inventory_changes.php" method="post">
                        <div class="w-50 mx-auto d-flex justify-content-around">
                            <?php if($login_role == 1): ?>
                            <input type="hidden" name="owner_id" value="<?php echo $_SESSION['login_owner_id']; ?>">
                            <?php else: ?>
                            <label>商品所有者:<select name="owner_id" required><?php echo $option_tags_for_owner_id;?></select></label>
                            <?php endif; ?>
                            <label>対象年月:<input type="month" name="year_month" max="<?php echo $this_year_month;?>" value="<?php echo $posted_year_month;?>"required></label>
                            <input class="mt-0" name="submit" type="submit" value="表示">
                        </div>
                    </form>
                </div>
                <?php if(!empty($_POST['submit'])): ?>
                <p class="mx-auto">累計在庫数:<?php echo $all_goods_total_inventory_stock_volume_in_posted_year_month;?></p>
                <table class="border-none mx-auto table_scroll">
                    <tbody>
                        <?php foreach ($array_inventory_changes_in_posted_year_month as $data_row):
                        $goods_name = $data_row['goods_name'];
                        $size = $data_row['size'];
                        $color = $data_row['color'];
                        $product_management_id = $data_row['user_id'].'_'.$data_row['goods_id'].'_'.$data_row['color_size_id'];
                        if (is_null($data_row['mst_volume'])) {
                            $mst_volume = 0;
                        } else {
                            $mst_volume = (int)$data_row['mst_volume'];
                        }
                        $total_stock_volume_in_posted_year_month = $data_row['total_stock_volume'];
                        $total_shipping_volume_in_posted_year_month = $data_row['total_shipping_volume'];
                        $total_inventory_diff_volume_in_posted_year_month = $data_row['total_inventory_diff_volume'];
                        $total_inventory_stock_volume_in_posted_year_month = $data_row['total_inventory_stock_volume'];
                        ?>
                        <!-- 以下1行目 -->
                        <tr>
                            <th class="shipment_record_table_title px-2 align-middle w-10rem">管理コード</th>
                            <th class="shipment_record_table_title px-2 align-middle w-20rem" colspan="2">商品名</th>

                            <th class="bg-color-lightgray w-4rem"></th>
                            <th class="bg-color-aqua px-2 w-4rem">期首</th>
                            <?php foreach ($daterange as $that_day):?>
                            <th class="bg-color-aqua px-2"><?php echo $that_day->format('d');?></th>
                            <?php endforeach;?>
                            <th class="bg-color-aqua px-2">計</th>
                        </tr>
                        <!-- 以上1行目 -->

                        <!-- 以下2行目 -->
                        <tr class="mb-5">
                            <th class="px-2 align-middle" rowspan="4"><?php echo $product_management_id; ?></th>
                            <th class="px-2 align-middle" rowspan="2" colspan="2"><?php echo $goods_name;?></th>
                            <th>入荷</th>
                            <td class="bg-color-lightgray"></td>
                            <?php foreach ($daterange as $that_day): $stock_volume_on_that_day = $data_row[$that_day->format('Y-m-d')]['stock_volume'];?>
                            <td class="px-2 align-middle"><?php echo $stock_volume_on_that_day;?></td>
                            <?php endforeach;?>
                            <td class="px-2 align-middle"><?php echo $total_stock_volume_in_posted_year_month;?></td>
                        </tr>
                        <!-- 以上2行目 -->

                        <!-- 以下3行目 -->
                        <tr>
                            <th>出荷</th>
                            <td class="bg-color-lightgray"></td>
                            <?php foreach ($daterange as $that_day): $shipping_volume_on_that_day = $data_row[$that_day->format('Y-m-d')]['shipping_volume'];?>
                            <td class="px-2 align-middle"><?php echo $shipping_volume_on_that_day;?></td>
                            <?php endforeach;?>
                            <td class="px-2 align-middle"><?php echo $total_shipping_volume_in_posted_year_month;?></td>
                        </tr>
                        <!-- 以上3行目 -->

                        <!-- 以下4行目 -->
                        <tr>
                            <th class="shipment_record_table_title mx-2">サイズ</th>
                            <th class="shipment_record_table_title mx-2">色</th>
                            <th class="px-2">棚卸</th>
                            <td class="bg-color-lightgray"></td>
                            <?php foreach ($daterange as $that_day): $inventory_diff_volume_on_that_day = $data_row[$that_day->format('Y-m-d')]['inventory_diff_volume'];?>
                            <td class="px-2 align-middle"><?php echo $inventory_diff_volume_on_that_day;?></td>
                            <?php endforeach;?>
                            <td class="px-2 align-middle"><?php echo $total_inventory_diff_volume_in_posted_year_month;?></td>
                        </tr>
                        <!-- 以上4行目 -->

                        <!-- 以下5行目 -->
                        <tr>
                            <td class="px-2"><?php echo $size;?></td>
                            <td class="px-2"><?php echo $color;?></td>
                            <th class="px-2 align-middle">在庫</th>
                            <td class="px-2 align-middle"><?php echo $mst_volume;?></td>
                            <?php foreach ($daterange as $that_day): $inventory_stock_volume_on_that_day = $data_row[$that_day->format('Y-m-d')]['inventory_stock_volume'];?>
                            <td class="px-2 align-middle"><?php echo $inventory_stock_volume_on_that_day;?></td>
                            <?php endforeach;?>
                            <td class="px-2 align-middle"><?php echo $total_inventory_stock_volume_in_posted_year_month;?></td>
                        </tr>
                        <!-- 以上5行目 -->
                        <tr>
                            <td class="border-0 py-3" colspan="37"></td>
                        </tr>
                        <?php endforeach;?>
                    </tbody>
                </table>
                <?php endif; ?>
            </main>
        </div>
        <!-- /#main -->
        <div id="sub">
        </div>
        <!-- /#sub -->
    </div>
    <!-- /.inner-->
</div>
<!-- /#contents -->
<?php

get_footer();
