<?php
require_once('../common/function.php');
require_once('../db/DB.php');
get_header();

$select_day = "";
$still = "";
$done = "";
$submit = "";
$shipping_code = "";
$shipping_id = "";
$name = "";
$goods_name = "";
$color = "";
$size = "";
$zip_code = "";
$address = "";
$shipping_request_no = "";
$no = "";
$no2 = "";
$destination_name = "";
$remarks = "";
$sql = "";


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
            sh.shipping_id,
            sh.shipping_scheduled_day,
            sh.completion_flag,
            sh.zip_code,
            sh.address,
            sh.destination_name,
            sh.remarks,
            sh.volume,
            sh.shipping_request_no,
            sh.seq,
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
            (us.role = "1" OR us.role = "4")
            AND
            sh.shipping_scheduled_day = :select_day
            AND
            sh.owner_id = us.owner_id
            AND
            sh.goods_id = go.goods_id
            AND
            sh.color_size_id = go.color_size_id
            AND
            volume > 0
            AND
            sh.completion_flag = "1"
            ORDER BY
            sh.owner_id ASC,
            record_day;
EOM;

        //sh.owner_id ASC,の下に
        //sh.shipping_scheduled_day ASC,
        //sh.goods_id ASC,
        //sh.color_size_id ASC
        //を追記

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
            sh.shipping_id,
            sh.shipping_scheduled_day,
            sh.completion_flag,
            sh.zip_code,
            sh.address,
            sh.destination_name,
            sh.remarks,
            sh.volume,
            sh.shipping_request_no,
            sh.seq,
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
            (us.role = "1" OR us.role = "4")
            AND
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
<style>
table {
      border-collapse: collapse;
}

th, td {
    border: none!important;
}
</style>

<div id="contents">
    <div class="inner">
        <div id="main">
            <main role="main" class="mb-5">
                <h1 class="mt-30px">出荷準備</h1>
                <form action="shipping_prepare2.php" method="post">
                    <input class="mr-5"  type="date" name="date" value="<?php echo $select_day; ?>">
                    <input type="submit" name="submit" value="表示">
                </form>
                <?php if($submit):?>
                <table class="mx-auto mb-5" style="border-collapse: collapse;">
                    <caption>未出荷</caption>
                    <thead class="shipment_record_table_title">
                        <tr>
                            <th class="align-middle px-2 w-5rem" rowspan="2">No.</th>
                            <th class="align-middle px-2 w-20rem">管理コード</th>
                            <th class="align-middle px-2 w-10rem">色</th>
                            <th class="align-middle px-2 w-5rem" rowspan="2">数量</th>
                            <th class="align-middle px-2 w-15rem">発送方法</th>
                            <th class="align-middle px-2 w-20rem" rowspan="2">備考欄</th>
                            <th class="align-middle px-2 w-10rem">郵便番号</th>
                            <th class="align-middle px-2 w-15rem" rowspan="2">商品所有者</th>
                            <th class="align-middle px-2 w-10rem" rowspan="2">出荷依頼No.</th>
                        </tr>
                        <tr>
                            <th class="align-middle px-2">商品名</th>
                            <th class="align-middle px-2">サイズ</th>
                            <th class="align-middle px-2">送付先指名</th>
                            <th class="align-middle px-2">住所</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($still as $still2): $i = 1;?>
                            <?php foreach ($still2 as $still3): ?>
                            <?php
                                $shipping_code = $still3["user_id"]."-".$still3["goods_id"]."-".$still3["color_size_id"];
                                $name = $still3["user_name"];
                                $goods_name = $still3["goods_name"];
                                $color = $still3["color"];
                                $volume = $still3["volume"];
                                $size = $still3["size"];
                                $zip_code = $still3["zip_code"];
                                $address = $still3["address"];
                                $destination_name = $still3["destination_name"];
                                $remarks = $still3["remarks"];
                                $shipping_request_no = $still3["shipping_request_no"];
                                $no = $still3["seq"];
                                switch ($still3["shipping_id"]) {
                                    case "1":
                                        $shipping_id = "ネコポス";
                                        break;

                                    case "2":
                                        $shipping_id = "クリックポスト";
                                        break;

                                    case "3":
                                        $shipping_id = "ゆうパケット";
                                        break;

                                    case "4":
                                        $shipping_id = "定形外郵便";
                                        break;

                                    case "5":
                                        $shipping_id = "宅急便コンパクト";
                                        break;

                                    case "6":
                                        $shipping_id = "レターパックプラス";
                                        break;

                                    case "7":
                                        $shipping_id = "宅急便";
                                        break;

                                    case "8":
                                        $shipping_id = "その他";
                                        break;
                                }
                            ?>
                            <tr class="tr_even">

                                <td class="px-2 align-middle" rowspan="2"><?php echo $no; ?></td>
                                <td class="px-2 align-middle"><?php echo  $shipping_code; ?></td>
                                <td class="px-2 align-middle"><?php echo $color; ?></td>
                                <td class="px-2 align-middle" rowspan="2"><?php echo $volume; ?></td>
                                <td class="px-2 align-middle"><?php echo $shipping_id ?></td>
                                <td class="px-2 align-middle" rowspan="2"><?php echo $remarks ?></td>
                                <td class="px-2 align-middle"><?php echo $zip_code; ?></td>
                                <td class="px-2 align-middle" rowspan="2"><?php echo $name ?></td>
                                <td class="px-2 align-middle" rowspan="2"><?php echo $shipping_request_no ?></td>

                            </tr>
                            <tr class="tr_even2">
                                <td class="align-middle px-2"><?php echo $goods_name; ?></td>
                                <td class="align-middle px-2"><?php echo $size ?></td>
                                <td class="align-middle px-2"><?php echo $destination_name ?></td>
                                <td class="align-middle px-2"><?php echo $address ?></td>
                            </tr>
                            <?php $i++; ?>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <table class="mx-auto">
                    <caption>出荷済</caption>
                    <thead class="shipment_record_table_title">
                        <tr>
                            <th class="align-middle px-2 w-5rem" rowspan="2">No.</th>
                            <th class="align-middle px-2 w-20rem">出荷ID</th>
                            <th class="align-middle px-2 w-10rem">色</th>
                            <th class="align-middle px-2 w-5rem" rowspan="2">数量</th>
                            <th class="align-middle px-2 w-15rem">発送方法</th>
                            <th class="align-middle px-2 w-20rem" rowspan="2">備考欄</th>
                            <th class="align-middle px-2 w-10rem">郵便番号</th>
                            <th class="align-middle px-2 w-15rem" rowspan="2">商品所有者</th>
                            <th class="align-middle px-2 w-10rem" rowspan="2">出荷依頼No.</th>
                        </tr>
                        <tr>
                            <th class="align-middle px-2">商品名</th>
                            <th class="align-middle px-2">サイズ</th>
                            <th class="align-middle px-2">送付先指名</th>
                            <th class="align-middle px-2">住所</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($done as $done2): $i = 1;?>
                            <?php foreach ($done2 as $done3): ?>
                            <?php
                            $no2 = $done3["seq"];
                            $shipping_code = $done3["user_id"]."-".$done3["goods_id"]."-".$done3["color_size_id"];
                            $name = $done3["user_name"];
                            $goods_name = $done3["goods_name"];
                            $color = $done3["color"];
                            $volume = $done3["volume"];
                            $size = $done3["size"];
                            $zip_code = $done3["zip_code"];
                            $address = $done3["address"];
                            $destination_name = $done3["destination_name"];
                            $remarks = $done3["remarks"];
                            $shipping_request_no = $done3["shipping_request_no"];
                            switch ($done3["shipping_id"]) {
                                case "1":
                                    $shipping_id = "ネコポス";
                                    break;

                                case "2":
                                    $shipping_id = "クリックポスト";
                                    break;

                                case "3":
                                    $shipping_id = "ゆうパケット";
                                    break;

                                case "4":
                                    $shipping_id = "定形外郵便";
                                    break;

                                case "5":
                                    $shipping_id = "宅急便コンパクト";
                                    break;

                                case "6":
                                    $shipping_id = "レターパックプラス";
                                    break;

                                case "7":
                                    $shipping_id = "宅急便";
                                    break;

                                case "8":
                                    $shipping_id = "その他";
                                    break;
                            }
                            ?>
                            <tr class="tr_even">
                                <td class="align-middle px-2 w-5rem" rowspan="2"><?php echo $no2; ?></td>
                                <td class="align-middle px-2 w-5rem"><?php echo  $shipping_code; ?></td>
                                <td class="align-middle px-2 w-5rem"><?php echo $color; ?></td>
                                <td class="align-middle px-2 w-5rem" rowspan="2"><?php echo $volume; ?></td>
                                <td class="align-middle px-2 w-5rem"><?php echo $shipping_id ?></td>
                                <td class="align-middle px-2 w-5rem" rowspan="2"><?php echo $remarks ?></td>
                                <td class="align-middle px-2 w-5rem"><?php echo $zip_code; ?></td>
                                <td class="align-middle px-2 w-5rem" rowspan="2"><?php echo $name ?></td>
                                <td class="align-middle px-2 w-10rem" rowspan="2"><?php echo $shipping_request_no ?></td>
                            </tr>
                            <tr class="tr_even2">
                                <td class="align-middle px-2"><?php echo $goods_name; ?></td>
                                <td class="align-middle px-2"><?php echo $size ?></td>
                                <td class="align-middle px-2"><?php echo $destination_name ?></td>
                                <td class="align-middle px-2"><?php echo $address ?></td>
                            </tr>
                            <?php $i++; ?>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif;?>
            </main>
            <?php get_footer(); ?>
        </div>
    </div>
</div>
