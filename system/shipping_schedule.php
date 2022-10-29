<?php
require_once('../common/function.php');
require_once('../db/DB.php');

$day = date('Y-m-d');
if(!empty($_POST["shipping_scheduled_day"])) {
    $day = $_POST["shipping_scheduled_day"];
}
get_header();
$no = "";
// エラー情報リセット
$_SESSION["error_status"] = E_STATUS_DEFAULT;
$login_owner_id = $_SESSION["login_owner_id"];
$login_role = $_SESSION["login_role"];
$shipping_scheduled_day = $day;
if ($_SERVER['REQUEST_METHOD'] === "POST") {
    $shipping_scheduled_day = $_POST['shipping_scheduled_day'];
}

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
    sh.zip_code,
    sh.address,
    sh.destination_name,
    sh.other_name,
    sh.shipment_source,
    sh.shipment_source_name,
    sh.convenience_store_qr_code,
    sh.other_qr_code,
    sh.remarks,
    sh.seq,
    st.owner,
    g.picture,
    g.goods_name,
    g.color,
    g.size,
    u.user_id
    FROM
    shipping_request sh
    INNER JOIN
    m_staff st
    ON
    sh.owner_id = st.owner_id
    INNER JOIN
    m_goods g
    ON
    sh.owner_id = g.owner_id
    AND
    sh.goods_id = g.goods_id
    AND
    sh.color_size_id = g.color_size_id
    LEFT OUTER JOIN
    m_user as u
    ON
    sh.owner_id = u.owner_id
    AND
    st.owner = u.user_name
    WHERE
    sh.shipping_scheduled_day = :shipping_scheduled_day
    AND
    sh.completion_flag = '1'
    AND
    sh.volume > 0\r\n
EOM;

    if($login_role === "1") {
        $sql .= "    AND sh.owner_id = :owner_id\r\n";
    }

    $sql .= <<< EOM
    ORDER BY
    sh.owner_id ASC,
    sh.seq ASC;
EOM;


    //sh.owner_id ASC,の下に
    //sh.goods_id ASC,
    //sh.color_size_id ASC
    //を追記すること


    $stmt = $db->prepare($sql);
    if($login_role === "1") {
        $stmt->bindParam(':owner_id', $login_owner_id, PDO::PARAM_STR);
    }
    $stmt->bindParam(':shipping_scheduled_day', $shipping_scheduled_day, PDO::PARAM_STR);
    $stmt->execute();
    $shipping_request_result_grouped_by_owner_id = $stmt->fetchAll(PDO::FETCH_ASSOC|PDO::FETCH_GROUP);
    $db = null;
    $stmt = null;
} catch (PDOException $e) {
    die('エラー：' . $e->getMessage());
}
?>
<div id="contents">
  <div class="inner">
    <div id="main">
      <main role="main">
        <h1 class="my-5">出荷予定一覧確認</h1>
        <div class="">
          <form action="shipping_schedule.php" method="post">
            <div class="d-flex">
                    <label class="ml-auto mr-5">出荷予定日:<input type="date" name="shipping_scheduled_day" value="<?php echo $day;?>"></label>
                    <input class="mr-auto mt-0" type="submit" value="表示">
            </div>
          </form>
        </div>
        <?php foreach ($shipping_request_result_grouped_by_owner_id as $owner_id => $data_rows): $i = 1; $owner = $data_rows[0]['owner'];?>
        <p class="text-left mt-30px">商品所有者:<?php echo $owner;?></p>
        <table class="border-none shipment_record_table">
          <thead class="shipment_record_table_title">
            <tr>
              <th class="w-2rem" scope="col" rowspan="2"></th>
              <th class="align-middle w-10rem" rowspan="2">管理コード</th>
              <th class="align-middle w-10rem" scope="col">商品名コード</th>
              <th class="align-middle w-10rem" scope="col" colspan="2">色サイズコード</th>
              <th class="border-none width-30px bg-white"></th>
              <th class="align-middle w-150px" scope="col" rowspan="2">商品画像</th>
              <th class="border-none width-30px bg-white"></th>
              <th class="align-middle w-10rem" scope="col">出荷数量</th>
              <th class="border-none width-30px bg-white"></th>
              <th class="align-middle w-250px" scope="col" rowspan="2">コンビニ出荷用<br>バーコード</th>
              <th class="align-middle w-250px" scope="col" rowspan="2">ヤマト営業所<br>(郵便局)QRコード</th>
              <th class="border-none width-30px bg-white"></th>
<!--               <th class="w-10rem" scope="col" rowspan="2">指定梱包材</th> -->
<!--               <th class="border-none width-30px bg-white"></th> -->
              <th class="align-middle w-10rem" scope="col">郵便番号</th>
              <th class="align-middle w-10rem" scope="col">送付先氏名</th>
              <th class="align-middle w-10rem" scope="col" rowspan="2">その他氏名</th>
              <th class="align-middle w-20rem" scope="col">発送元住所</th>
              <th class="align-middle w-10rem" scope="col" rowspan="2">出荷依頼No.</th>
              <th class="align-middle w-20rem" scope="col" rowspan="2">備考欄</th>
            </tr>
            <tr>
              <th class="align-middle" scope="col">商品名</th>
              <th class="align-middle" scope="col">色</th>
              <th class="align-middle" scope="col">サイズ</th>
              <th class="border-none width-30px bg-white"></th>
              <th class="border-none width-30px bg-white"></th>
              <th class="align-middle" scope="col">発送方法</th>
              <th class="border-none width-30px bg-white"></th>
              <th class="border-none width-30px bg-white"></th>
<!--               <th class="border-none width-30px bg-white"></th> -->
              <th class="align-middle" scope="col" colspan="2">住所</th>
              <th class="align-middle" scope="col">発送元氏名</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($data_rows as $data_row) :
                            $goods_id = $data_row['goods_id'];
                            $color_size_id = $data_row['color_size_id'];
                            $user_id = $data_row['user_id'];
                            $goods_name = $data_row['goods_name'];
                            $color = $data_row['color'];
                            $size = $data_row['size'];
                            $picture = $data_row['picture'];
                            $volume = $data_row['volume'];
                            $convenience_store_qr_code = $data_row['convenience_store_qr_code'];
                            $other_qr_code = $data_row['other_qr_code'];
                            $zip_code = $data_row['zip_code'];
                            $destination_name = $data_row['destination_name'];
                            $other_name = $data_row['other_name'];
                            $shipment_source = $data_row['shipment_source'];
                            $goods_name = $data_row['goods_name'];
                            $size = $data_row['size'];
                            $remarks = $data_row['remarks'];
                            $shipping_request_no = $data_row['shipping_request_no'];
                            $no = (int) $data_row['seq'];
                            $shipping_id = $data_row['shipping_id'];
                            $address = $data_row['address'];
                            $shipment_source_name = $data_row['shipment_source_name'];
                            $product_management_id = $user_id."_".$goods_id."_".$color_size_id;
                            $img_tag_for_goods_image = "";
                            if (!empty($picture)) {
                                $img_src = "../common/images/" . $owner_id . "/goods/" . $picture;
                                $img_tag_for_goods_image = "<img src=\"" . $img_src .	"\" class=\"img-fluid max-height-100\">";
                            }

                            $img_tag_for_convenience_store_qr_code = "";
                            if (!empty($convenience_store_qr_code)) {
                                $img_src = "../common/images/" . $owner_id . "/qr/" . $convenience_store_qr_code;
                                $img_tag_for_convenience_store_qr_code = "<img src=\"" . $img_src . "\" class=\"object_fit\">";
              }

                            $img_tag_for_other_qr_code = "";
                            if (!empty($other_qr_code)) {
                                $img_src = "../common/images/" . $owner_id . "/qr/" . $other_qr_code;
                                $img_tag_for_other_qr_code = "<img src=\"" . $img_src . "\" class=\"object_fit\">";
              }

              // 発送方法の配列
              $shipping_methods = array(
              "1" => "ネコポス",
              "2" => "クリックポスト",
              "3" => "ゆうパケット",
              "4" => "定形外郵便",
              "5" => "宅急便コンパクト",
              "6" => "レターパックプラス",
              "7" => "宅急便",
              "8" => "その他"
              );

              // 指定梱包材の配列
              $designated_packing_materials = array(
                "1" => "ビニール袋（大）",
                "2" => "ビニール袋（小）",
                "3" => "角２封筒",
                "4" => "Ａ５封筒",
                "5" => "箱",
                "6" => "その他"
              );
                            ?>
            <tr class="tr_even">
              <td class="align-middle" rowspan="2"><?php echo $no;?></td>
              <td class="align-middle" rowspan="2"><?php echo $product_management_id;?></td>
              <td class="align-middle"><?php echo $goods_id;?></td>
              <td class="align-middle" colspan="2"><?php echo $color_size_id?></td>
              <td class="border-none width-30px bg-white"></td>
              <td class="shipment_record_img" rowspan="2"><?php echo $img_tag_for_goods_image;?></td>
              <td class="border-none width-30px bg-white"></td>
              <td class="align-middle"><?php echo $volume;?></td>
              <td class="border-none width-30px bg-white"></td>
              <td class="shipment_record_qr_img" rowspan="2"><?php echo $img_tag_for_convenience_store_qr_code;?></td>
              <td class="shipment_record_qr_img" rowspan="2"><?php echo $img_tag_for_other_qr_code;?></td>
              <td class="border-none width-30px bg-white"></td>
              <!-- <td rowspan="2"><?php echo $designated_packing_materials[$designated_packing_material];?></td> -->
<!--               <td class="border-none width-30px bg-white"></td> -->
              <td class="align-middle"><?php echo $zip_code;?></td>
              <td class="align-middle"><?php echo $destination_name;?></td>
              <td class="align-middle" rowspan="2"><?php echo $other_name;?></td>
              <td class="align-middle"><?php echo $shipment_source;?></td>
              <td class="align-middle" rowspan="2"><?php echo $shipping_request_no;?></td>
              <td class="align-middle" rowspan="2"><?php echo $remarks;?></td>
            </tr>
            <tr class="tr_even2">
              <td class="align-middle"><?php echo $goods_name;?></td>
              <td class="align-middle"><?php echo $color;?></td>
              <td class="align-middle"><?php echo $size;?></td>
              <td class="border-none width-30px bg-white"></td>
              <td class="border-none width-30px bg-white"></td>
              <td class="align-middle"><?php echo $shipping_methods[$shipping_id];?></td>
              <td class="border-none width-30px bg-white"></td>
              <td class="border-none width-30px bg-white"></td>
<!--               <td class="border-none width-30px bg-white"></td> -->
              <td class="align-middle" colspan="2"><?php echo $address;?></td>
              <td class="align-middle"><?php echo $shipment_source_name;?></td>
            </tr>
            <?php $i++; endforeach;?>
          </tbody>
        </table>
        <?php endforeach;?>
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