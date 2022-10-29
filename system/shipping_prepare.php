<?php
require_once('../common/function.php');
require_once('../db/DB.php');
get_header();

$select_day = "";
$still = "";
$done = "";
$ii = 1;
$jj = 1;
$submit = "";
$owner_id = "";
$shipping_code = "";
$name = "";
$goods_name = "";
$color = "";
$size = "";
$qr1 = "";
$qr2 = "";
$remarks = "";


if(isset($_POST["date"])) {
    $select_day = $_POST["date"];
} else {
    $select_day = date('Y-m-d');
}

if(isset($_POST["submit"])) {
    $submit = $_POST["submit"];
}

if($submit) {
    try {
        $db = DB::getDB();
        $sql = <<< EOM
            SELECT
            sh.owner_id,
            sh.goods_id,
            sh.color_size_id,
            sh.shipping_scheduled_day,
            sh.convenience_store_qr_code,
            sh.other_qr_code,
            sh.completion_flag,
            sh.remarks,
            go.goods_name,
            go.color,
            go.size,
            us.user_id,
            us.user_name
            FROM
            shipping_request AS sh
            LEFT JOIN
            m_goods AS go
            ON
            sh.owner_id = go.owner_id
            LEFT JOIN
            m_user AS us
            ON
            sh.owner_id = us.owner_id
            WHERE
            sh.shipping_scheduled_day = :select_day
            AND
            sh.owner_id = us.owner_id
            AND
            sh.goods_id = go.goods_id
            AND
            sh.color_size_id = go.color_size_id
            AND
            sh.completion_flag = "1"
            ORDER BY
            sh.owner_id ASC,
            sh.shipping_scheduled_day ASC,
            sh.goods_id ASC,
            sh.color_size_id ASC
EOM;

        $stmt = $db->prepare($sql);
        $stmt->bindParam(':select_day', $select_day, PDO::PARAM_STR);
        $stmt->execute();
        $still = $stmt->fetchAll(PDO::FETCH_ASSOC|PDO::FETCH_GROUP);
        $db = null;
        $stmt = null;
    } catch (Exception $e) {
        echo $e;
    }

    //
    try {
        $db = DB::getDB();
        $sql = <<< EOM
            SELECT
            sh.owner_id,
            sh.goods_id,
            sh.color_size_id,
            sh.shipping_scheduled_day,
            sh.convenience_store_qr_code,
            sh.other_qr_code,
            sh.completion_flag,
            sh.remarks,
            go.goods_name,
            go.color,
            go.size,
            us.user_id,
            us.user_name
            FROM
            shipping_request AS sh
            LEFT JOIN
            m_goods AS go
            ON
            sh.owner_id = go.owner_id
            LEFT JOIN
            m_user AS us
            ON
            sh.owner_id = us.owner_id
            WHERE
            sh.shipping_scheduled_day = :select_day
            AND
            sh.owner_id = us.owner_id
            AND
            sh.goods_id = go.goods_id
            AND
            sh.color_size_id = go.color_size_id
            AND
            sh.completion_flag = "2"
            ORDER BY
            sh.owner_id ASC,
            sh.shipping_scheduled_day ASC,
            sh.goods_id ASC,
            sh.color_size_id ASC
EOM;

        $stmt = $db->prepare($sql);
        $stmt->bindParam(':select_day', $select_day, PDO::PARAM_STR);
        $stmt->execute();
        $done = $stmt->fetchAll(PDO::FETCH_ASSOC|PDO::FETCH_GROUP);
        $db = null;
        $stmt = null;
    } catch (Exception $e) {
        echo $e;
    }
}

?>


<div id="contents">
    <div class="inner">
        <div id="main">
            <main class="mb-5" role="main">
                <h1 class="mt-30px">出荷準備 1</h1>
                <form action="shipping_prepare.php" method="post">
                    <input class="mr-5" type="date" name="date" value="<?php echo $select_day; ?>">
                    <input type="submit" name="submit" value="表示">
                </form>
                <?php if($submit):?>
                <h2>未出荷</h2>
                <div class="container-fluid mb-5">
                    <div class="row justify-content-around">
                        <?php foreach ($still as $key => $still2): ?>
                            <?php $owner_id = $key; ?>
                            <?php foreach ($still2 as $still3): ?>
                            <?php
                                $shipping_code = $still3["user_id"]."-".$still3["goods_id"]."-".$still3["color_size_id"];
                                $name = $still3["user_name"];
                                $goods_name = $still3["goods_name"];
                                $color = $still3["color"];
                                $size = $still3["size"];
                                $qr1 = $still3["convenience_store_qr_code"];
                                $qr2 = $still3["other_qr_code"];
                                $remarks = $still3["remarks"];
                            ?>
                        <div class="col-3 mb-5">
                            <div class="container-fluid px-1">
                                <div class="row">
                                    <div class="col-12 px-0">
                                        <table>
                                            <caption><?php echo $ii; ?></caption>
                                            <tbody>
                                                <tr class="shipment_record_table_title">
                                                    <td colspan="2">出荷ID</td>
                                                </tr>
                                                <tr>
                                                    <td colspan="2"><?php echo $shipping_code; ?></td>
                                                </tr>
                                                <tr class="shipment_record_table_title">
                                                    <td colspan="2">商品名</td>
                                                </tr>
                                                <tr>
                                                    <td colspan="2"><?php echo $goods_name; ?></td>
                                                </tr>
                                                <tr class="shipment_record_table_title">
                                                    <td class="w-50">色</td>
                                                    <td class="w-50">サイズ</td>
                                                </tr>
                                                <tr>
                                                    <td class="w-50"><?php echo $color; ?></td>
                                                    <td class="w-50"><?php echo $size; ?></td>
                                                </tr>
                                                <tr class="shipment_record_table_title">
                                                    <td colspan="2">商品所有者</td>
                                                </tr>
                                                <tr>
                                                    <td colspan="2"><?php echo $name; ?></td>
                                                </tr>
                                                <tr class="shipment_record_table_title">
                                                    <td colspan="2">コンビニ出荷用バーコード</td>
                                                </tr>
                                                <tr>
                                                    <td colspan="2">
                                                        <div class="object_fit2">
                                                            <img class="shipping_prepare_qr" alt="<?php echo $qr1; ?>" src="../common/images/<?php echo $owner_id ?>/qr/<?php echo $qr1 ?>">
                                                        </div>
                                                    </td>
                                                </tr>
                                                <tr class="shipment_record_table_title">
                                                    <td colspan="2">ヤマト営業所（郵便局）QRコード</td>
                                                </tr>
                                                <tr>
                                                    <td colspan="2">
                                                        <div class="object_fit2">
                                                            <img class="shipping_prepare_qr" alt="<?php echo $qr2; ?>" src="../common/images/<?php echo $owner_id ?>/qr/<?php echo $qr2 ?>">
                                                        </div>
                                                    </td>
                                                </tr>
                                                <tr class="shipment_record_table_title">
                                                    <td colspan="2">備考欄</td>
                                                </tr>
                                                <tr>
                                                    <td colspan="2"><?php echo $remarks; ?></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                            <?php $ii++; ?>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                <h2 class="mt-5">出荷済</h2>
                <div class="container-fluid">
                    <div class="row justify-content-around">
                        <?php foreach ($done as $key => $done2): ?>
                            <?php $owner_id = $key; ?>
                            <?php foreach ($done2 as $done3): ?>
                            <?php
                                $shipping_code = $done3["user_id"]."-".$done3["goods_id"]."-".$done3["color_size_id"];
                                $name = $done3["user_name"];
                                $goods_name = $done3["goods_name"];
                                $color = $done3["color"];
                                $size = $done3["size"];
                                $qr1 = $done3["convenience_store_qr_code"];
                                $qr2 = $done3["other_qr_code"];
                                $remarks = $done3["remarks"];
                            ?>
                        <div class="col-3 mb-5">
                            <div class="container-fluid px-1">
                                <div class="row">
                                    <div class="col-12 px-0">
                                        <table>
                                            <caption><?php echo $jj + 1; ?></caption>
                                            <tbody>
                                                <tr class="shipment_record_table_title">
                                                    <td colspan="2">出荷ID</td>
                                                </tr>
                                                <tr>
                                                    <td colspan="2"><?php echo $shipping_code; ?></td>
                                                </tr>
                                                <tr class="shipment_record_table_title">
                                                    <td colspan="2">商品名</td>
                                                </tr>
                                                <tr>
                                                    <td colspan="2"><?php echo $goods_name; ?></td>
                                                </tr>
                                                <tr class="shipment_record_table_title">
                                                    <td class="w-50">色</td>
                                                    <td class="w-50">サイズ</td>
                                                </tr>
                                                <tr>
                                                    <td class="w-50"><?php echo $color; ?></td>
                                                    <td class="w-50"><?php echo $size; ?></td>
                                                </tr>
                                                <tr class="shipment_record_table_title">
                                                    <td colspan="2">商品所有者</td>
                                                </tr>
                                                <tr>
                                                    <td colspan="2"><?php echo $name; ?></td>
                                                </tr>
                                                <tr class="shipment_record_table_title">
                                                    <td colspan="2">コンビニ出荷用バーコード</td>
                                                </tr>
                                                <tr>
                                                    <td colspan="2">
                                                        <div class="object_fit2">
                                                            <img class="shipping_prepare_qr" alt="<?php echo $qr1; ?>" src="../common/images/<?php echo $owner_id ?>/qr/<?php echo $qr1 ?>">
                                                        </div>
                                                    </td>
                                                </tr>
                                                <tr class="shipment_record_table_title">
                                                    <td colspan="2">ヤマト営業所（郵便局）QRコード</td>
                                                </tr>
                                                <tr>
                                                    <td colspan="2">
                                                        <div class="object_fit2">
                                                            <img class="shipping_prepare_qr" alt="<?php echo $qr2; ?>" src="../common/images/<?php echo $owner_id ?>/qr/<?php echo $qr2 ?>">
                                                        </div>
                                                    </td>
                                                </tr>
                                                <tr class="shipment_record_table_title">
                                                    <td colspan="2">備考欄</td>
                                                </tr>
                                                <tr>
                                                    <td colspan="2"><?php echo $remarks; ?></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                            <?php $jj++; ?>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif;?>
            </main>
            <?php get_footer(); ?>
        </div>
    </div>
</div>