<?php
require_once('../common/function.php');
require_once('../db/DB.php');

get_header();

$login_role = $_SESSION['login_role'];

$submit = null;
if(isset($_POST['submit'])) {
    $submit = $_POST['submit'];
}

// 現在時刻の「年月」を取得
$now = new DateTime();
$this_year_month = $now->format('Y-m');

$posted_owner_id = $_SESSION['login_owner_id'];
$posted_year_month = $this_year_month;	// 「年月」の初期値を代入
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $posted_owner_id = $_POST['owner_id'];
    $posted_year_month = $_POST['year_month'];
}

$first_day_in_posted_year_month = new DateTime('first day of ' . $posted_year_month);
if ($posted_year_month === $this_year_month) {
    $last_day_in_posted_year_month = $now;
} else {
    $last_day_in_posted_year_month = new DateTime('last day of ' . $posted_year_month);
}

try {
    // データベース接続
    $db = DB::getDB();
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

$array_stock_results_in_posted_year_month = array();
try {
    // データベース接続
    $db = DB::getDB();
    $sql = <<< EOM
        SELECT
        T1.owner_id,
        T1.goods_id,
        T1.color_size_id,
        stock_results_day,
        stock_volume,
        picture,
        goods_name,
        color,
        size,
        user_id
        FROM
        stock_results T1
        INNER JOIN
        m_goods T2
        USING(owner_id, goods_id, color_size_id)
        LEFT OUTER JOIN
        m_user
        USING(owner_id)
        WHERE
        T1.owner_id = :owner_id
        AND
        stock_results_day
        BETWEEN
        :first_day_in_posted_year_month
        AND
        :last_day_in_posted_year_month
        AND
        T2.deleted_flag = false
        ORDER BY
        stock_results_day DESC,
        goods_id ASC,
        color_size_id ASC
EOM;

    $stmt = $db->prepare($sql);
    $stmt->bindParam(':owner_id', $posted_owner_id, PDO::PARAM_STR);
    $stmt->bindValue(':first_day_in_posted_year_month', $first_day_in_posted_year_month->format('Y-m-d'), PDO::PARAM_STR);
    $stmt->bindValue(':last_day_in_posted_year_month', $last_day_in_posted_year_month->format('Y-m-d'), PDO::PARAM_STR);
    $stmt->execute();
    $array_stock_results_in_posted_year_month = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if($array_stock_results_in_posted_year_month === false){
        $array_stock_results_in_posted_year_month = array();
    }
    $db = null;
} catch (PDOException $e) {
    die('エラー：' . $e->getMessage());
}
$table_sp = "";
$tbody = '';
$stock_results_day = '';
$goods_id = '';
$color_size_id = '';
$stock_volume = '';
$picture = '';
$goods_name = '';
$color = '';
$size = '';
for ($i = 0; $i < count($array_stock_results_in_posted_year_month); $i ++) {

    $no = $i + 1;
    $user_id = $array_stock_results_in_posted_year_month[$i]['user_id'];
    $stock_results_day = $array_stock_results_in_posted_year_month[$i]['stock_results_day'];
    $goods_id = $array_stock_results_in_posted_year_month[$i]['goods_id'];
    $color_size_id = $array_stock_results_in_posted_year_month[$i]['color_size_id'];
    $stock_volume = $array_stock_results_in_posted_year_month[$i]['stock_volume'];
    $picture = $array_stock_results_in_posted_year_month[$i]['picture'];
    $goods_name = $array_stock_results_in_posted_year_month[$i]['goods_name'];
    $color = $array_stock_results_in_posted_year_month[$i]['color'];
    $size = $array_stock_results_in_posted_year_month[$i]['size'];
    $product_management_id = $user_id."_".$goods_id."_".$color_size_id;
    $img_src = "../common/images/" . $posted_owner_id . "/goods/" . $picture;
    if(isset($picture)) {
    $img = "<img src=\"{$img_src}\" class=\"goods_img\">";
    } else {
        $img = "<img src=\"../common/images/no_image.jpg\" class=\"goods_img\">";
    }
    $table_sp .= "
        <table class=\"table sp_table_even\">
            <caption class=\"caption\">No.&ensp;{$no}</caption>
            <tbody>
                    <input type=\"hidden\" class=\"goods_id table_input\" value=\"{$goods_id}\">
                    <input type=\"hidden\" class=\"color_size_id table_input\" value=\"{$color_size_id}\">
                    <input type=\"hidden\" class=\"goods_name table_input\" readonly=\"readonly\" value=\"{$goods_name}\">
                    <input type=\"hidden\" class=\"color table_input\" readonly=\"readonly\" value=\"{$color}\">
                    <input type=\"hidden\" class=\"size table_input\" readonly=\"readonly\" value=\"{$size}\">
                    <input type=\"hidden\" class=\"color table_input\" readonly=\"readonly\" value=\"{$stock_results_day}\">
                    <input type=\"hidden\" class=\"size table_input\" readonly=\"readonly\" value=\"{$stock_volume}\">
                <tr class=\"sp_column_name\">
                    <th colspan=\"2\">商品名</th>
                </tr>
                <tr class=\"second_row\">
                    <td colspan=\"2\">{$goods_name}</td>
                </tr>
                <tr class=\"sp_column_name\">
                    <th class=\"table_cell_half\">色</th>
                    <th class=\"table_cell_half\">サイズ</th>
                </tr>
                <tr class=\"third_row\">
                    <td class=\"table_cell_half\">{$color}</td>
                    <td class=\"table_cell_half\">{$size}</td>
                </tr>
                <tr class=\"sp_column_name\">
                    <th class=\"table_cell_half\">入荷実績日</th>
                    <th class=\"table_cell_half\">入荷数量</th>
                </tr>
                <tr class=\"third_row\">
                    <td class=\"table_cell_half\">{$stock_results_day}</td>
                    <td class=\"table_cell_half\">{$stock_volume}</td>
                </tr>
                <tr class=\"sp_column_name\">
                    <th colspan=\"2\">商品画像</th>
                </tr>
                <tr>
                    <td colspan=\"2\">
                        <div class=\"goods_img_wrapper\">
                            {$img}
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>";

    $tbody .= "<tr class=\"tr_even\">
              <td class=\"px-2 align-middle\" rowspan=\"2\">{$no}</td>
              <td class=\"px-2 align-middle\" rowspan=\"2\">{$product_management_id}</td>
              <td class=\"px-2 align-middle\">{$goods_id}</td>
              <td class=\"px-2 align-middle\" colspan=\"2\">{$color_size_id}</td>
              <td rowspan=\"2\">{$img}</td>
              <td class=\"px-2 align-middle\" rowspan=\"2\">{$stock_results_day}</td>
              <td class=\"px-2 align-middle\" rowspan=\"2\">{$stock_volume}</td>
            </tr>
            <tr class=\"tr_even2\">
              <td class=\"px-2 align-middle\">{$goods_name}</td>
              <td class=\"px-2 align-middle\">{$color}</td>
              <td class=\"px-2 align-middle\">{$size}</td>
            </tr>
    ";


}
?>
<!-- 以下SP -->
<main>
    <div class="row d-lg-none">
        <div class="col-12 mx-auto">
            <h1 class="page_title">入荷実績照会</h1>
            <!-- 商品所有者選択 -->
            <div class="select_owner_wrapper">
                <form method="post">
                    <?php if($login_role == 1): ?>
                    <input type="hidden" name="owner_id" value="<?php echo $_SESSION['login_owner_id']; ?>">
                    <?php else: ?>
                    <div class="d-flex select_owner">
                        <p class="d-inline-block select_owner_text ml-auto">商品所有者：</p>
                        <select class="mr-auto" name="owner_id">
                            <?php echo $option_tags_for_owner_id; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    <div class="d-flex select_owner">
                        <p class="d-inline-block select_owner_text ml-auto">対象月：</p>
                        <input class="mr-auto" type="month" name="year_month" max="<?php echo $this_year_month;?>" value="<?php echo $posted_year_month;?>" required>
                    </div>
                    <div class="row">
                        <div class="col mx-auto">
                            <input class="button" type="submit" name="submit" value="表示">
                        </div>
                    </div>
                </form>
            </div>
            <?php if(isset($submit)){
                echo $table_sp;
            }
            ?>
        </div>
    </div>
    <!-- 以上SP -->
    <!-- 以下PC -->
    <div id="contents" class="d-none d-lg-block">
        <div class="inner">
            <div id="main" class="mb-5">
                <h1 class="mt-4">入荷実績照会</h1>
                <form action="" method="post">
                    <?php if($login_role == 1): ?>
                    <input type="hidden" name="owner_id" value="<?php echo $_SESSION['login_owner_id']; ?>">
                    <?php else: ?>
                    <label class="mr-4">
                             商品所有者：
                        <select name="owner_id" required>
                            <?php echo $option_tags_for_owner_id;?>
                        </select>
                    </label>
                    <?php endif; ?>
                    <label class="mr-4">入荷予定日：<input type="month" name="year_month" max="<?php echo $this_year_month;?>" value="<?php echo $posted_year_month;?>" required></label>
                    <input type="submit" name="submit" value="表示">
                </form>
                <?php if(isset($submit)): ?>
                <table class="mx-auto">
                    <caption>入荷実績照会</caption>
                    <thead class="shipment_record_table_title">
                        <tr>
                            <th class="px-2 align-middle" scope="col" rowspan="2">No</th>
                            <th class="px-2 align-middle" scope="col" rowspan="2">管理ID</th>
                            <th class="px-2 align-middle" scope="col">商品名コード</th>
                            <th class="px-2 align-middle" scope="col" colspan="2">色サイズコード</th>
                            <th class="px-2 align-middle" scope="col" rowspan="2">商品画像</th>
                            <th class="px-2 align-middle" scope="col" rowspan="2">入荷実績日</th>
                            <th class="px-2 align-middle" scope="col" rowspan="2">入荷数量</th>
                        </tr>
                        <tr>
                            <th class="px-2 align-middle" scope="col">商品名</th>
                            <th class="px-2 align-middle" scope="col">色</th>
                            <th class="px-2 align-middle" scope="col">サイズ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php echo $tbody;?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
            <!-- /#main -->
            <div id="sub">
            </div>
            <!-- /#sub -->
        </div>
        <!-- /.inner-->
    </div>
    <!-- /#contents -->
    <!-- 以上PC -->
</main>
<?php

get_footer();