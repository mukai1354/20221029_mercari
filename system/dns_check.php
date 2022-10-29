<?php
require_once ('../common/function.php');
require_once ('../db/DB.php');
get_header();

//変数初期化
$shipping_scheduled_day = "";//出荷予定日
$owner_items = [];
$owner_item = [];
//出荷予定日のデフォルト（登録当日）
$today = date("Y-m-d");

if(!empty($_POST["date"])) {
    $shipping_scheduled_day = $_POST["date"];
} else {
    $shipping_scheduled_day = $today;
}

//商品所有者情報取得
try {
    $db = DB::getDB();
    $sql = <<< EOM
        SELECT
        owner_id,
        owner
        FROM
        m_staff
        WHERE
        deleted_flag = 0
        ORDER BY
        owner_id ASC;
EOM;
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $owners = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $db = null;
    $stmt = null;
} catch (Exception $e) {
    die('エラー：' . $e->getMessage());
}
?>
<main role="main">
    <div class="row">
        <div class="col-12 mb-5">
            <h1 class="my-5">DNS専用確認ページ</h1>
            <form action="dns_check.php" method="post">
                <label>年月日：<input type="date" name="date" value="<?php echo $shipping_scheduled_day; ?>"></label>
                <input type="submit" name="display" value="表示">
            </form>
            <!-- 表示ボタン押下時に表示 -->
            <?php if(!empty($_POST["display"])): ?>
            <!-- 各所有者ごとに値を取得 -->
            <?php foreach($owners as $owners2): ?>
            <?php
                $owner = $owners2["owner"];
                $owner_id = $owners2["owner_id"];
            ?>

            <table class="mb-5">
                <caption class="font-weight-bold"><?php echo $owner. "様&ensp;(" . $owner_id . ")"; ?></caption>
                <thead class="shipment_record_table_title">
                    <tr>
                        <th class="align-middle w-5rem">No.</th>
                        <th class="align-middle w-20rem">管理コード</th>
                        <th class="align-middle w-20rem">商品名</th>
                        <th class="align-middle w-10rem">色</th>
                        <th class="align-middle w-10rem">サイズ</th>
                        <th class="align-middle w-3rem">数量</th>
                        <th class="align-middle w-20rem">備考</th>
                        <th class="align-middle" style="width:12rem;">出荷依頼No.</th>
                    </tr>
                </thead>
                <tbody>
<?php
try {
    $db = DB::getDB();
    $sql = <<< EOM
            SELECT
            sh.owner_id,
            sh.shipping_request_no,
            sh.goods_id,
            sh.color_size_id,
            sh.volume,
            sh.shipping_id,
            sh.destination_name,
            sh.shipping_results_day,
            sh.remarks,
            mg.goods_name,
            mg.color,
            mg.size,
            us.user_id,
            us.user_name
            FROM
            shipping_request AS sh
            LEFT OUTER JOIN
            m_goods AS mg
            ON
            sh.owner_id = mg.owner_id
            AND
            sh.goods_id = mg.goods_id
            AND
            sh.color_size_id = mg.color_size_id
            LEFT OUTER JOIN
            m_user AS us
            ON
            sh.owner_id = us.owner_id
            WHERE
            sh.owner_id = :owner_id
            AND
            sh.shipping_scheduled_day = :shipping_scheduled_day
            AND
            sh.volume > 0
            ORDER BY
            sh.goods_id ASC,
            sh.color_size_id ASC,
            sh.record_day ASC;
EOM;
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':owner_id', $owner_id, PDO::PARAM_STR);
    $stmt->bindParam(':shipping_scheduled_day', $shipping_scheduled_day, PDO::PARAM_STR);
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $db = null;
    $stmt = null;
} catch (Exception $e) {
    die('エラー：' . $e->getMessage());
}

foreach($items as $item) {
    //変数初期化

    //取得した値を変数に格納
    $owner_id = $item["owner_id"];
    $shipping_request_no = $item["shipping_request_no"];
    $goods_id = $item["goods_id"];
    $color_size_id = $item["color_size_id"];
    $volume = $item["volume"];
    $shipping_id = $item["shipping_id"];
    $remarks =  $item["remarks"];
    $user_id = $item["user_id"];
    $user_name = $item["user_name"];
    $goods_name = $item["goods_name"];
    $color = $item["color"];
    $size = $item["size"];
    //各変数を配列に格納
    $owner_item = [
        "owner_id" => $owner_id,
        "shipping_request_no" => $shipping_request_no,
        "goods_id" => $goods_id,
        "color_size_id" => $color_size_id,
        "volume" => $volume,
        "shipping_id" => $shipping_id,
        "remarks" => $remarks,
        "user_id" => $user_id,
        "user_name" => $user_name,
        "goods_name" => $goods_name,
        "size" => $size,
        "color" => $color
    ];

    $owner_items[] = $owner_item;
}

$ii = 1;
?>
                    <?php foreach($owner_items as $owner_items2): ?>
                    <tr class="tr-even">
                        <td class="px-2 align-middle w-5rem"><?php echo $ii; ?></td><!--  -->
                        <td class="px-2 align-middle w-20rem"><?php echo $owner_items2["user_id"] . "-" . $owner_items2["goods_id"] . "-" . $owner_items2["color_size_id"]; ?></td><!-- 管理コード -->
                        <td class="px-2 align-middle w-20rem"><?php echo $owner_items2["goods_name"]; ?></td><!-- 商品名 -->
                        <td class="px-2 align-middle w-10rem"><?php echo $owner_items2["color"]; ?></td><!-- 色 -->
                        <td class="px-2 align-middle w-10rem"><?php echo $owner_items2["size"]; ?></td><!-- サイズ -->
                        <td class="px-2 align-middle w-3rem"><?php echo $owner_items2["volume"]; ?></td><!-- 数量 -->
                        <td class="px-2 align-middle w-20rem"><?php echo $owner_items2["remarks"]; ?></td><!-- 備考 -->
                        <td class="px-2 align-middle" style="width:12rem;"><?php echo $owner_items2["shipping_request_no"]; ?></td><!-- 出荷依頼No. -->
                    </tr>
                    <?php $ii++; ?>
                    <?php endforeach; ?>
                    <?php $owner_items = []; ?>
                </tbody>
            </table>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</main>