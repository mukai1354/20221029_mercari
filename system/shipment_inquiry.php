<?php
require_once('../common/function.php');
require_once('../db/DB.php');

$day = date('Y-m-d');

get_header();

$owner_id = h($_SESSION['login_owner_id']);
$shipping_results_day = $day;
$login_role = $_SESSION['login_role'];

if ($_SERVER['REQUEST_METHOD'] === "POST") {
    $owner_id = $_POST['owner_id'];
    $shipping_results_day = $_POST['shipping_results_day'];
}

try {
    // データベース接続
    $db = DB::getDB();
    $stmt = $db->prepare("SELECT owner_id, owner FROM m_staff");
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $db = null;
    $stmt = null;
} catch (PDOException $e) {
    die('エラー：' . $e->getMessage());
}
$owner = '';
foreach ($result as $varr) {
    if ($owner_id == $varr['owner_id']) {
        $owner .= "\t\t\t\t\t<option value=\"{$varr['owner_id']}\" selected>{$varr['owner']}</option>\n";
    } else {
        $owner .= "\t\t\t\t\t<option value=\"{$varr['owner_id']}\">{$varr['owner']}</option>\n";
    }
}

$array_shipping_results = array();
try {
    $db = DB::getDB();
    $sql = <<< EOM
    SELECT
    st.owner,
    sh.goods_id,
    sh.color_size_id,
    g.picture,
    sh.volume,
    sh.convenience_store_qr_code,
    sh.other_qr_code,
    sh.designated_packing_material,
    sh.zip_code,
    sh.destination_name,
    sh.other_name,
    sh.shipment_source,
    sh.remarks,
    sh.shipping_request_no,
    g.goods_name,
    g.color,
    g.size,
    sh.shipping_id,
    sh.address,
    sh.shipment_source_name,
    sh.shipping_results_day,
    sh.packing_material,
    sh.delivery_company,
    sh.delivery_plan,
    sh.search_no,
    sh.postage,
    user_id
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
    WHERE
    (u.role = "1" OR u.role = "4")
    AND
    sh.completion_flag = '2'
    AND
    sh.volume > 0
    AND
    sh.owner_id = :owner_id
    AND
    sh.shipping_results_day = :shipping_results_day
    ORDER BY
    sh.shipping_request_no ASC
EOM;
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':owner_id', $owner_id, PDO::PARAM_STR);
    $stmt->bindParam(':shipping_results_day', $shipping_results_day, PDO::PARAM_STR);
    $stmt->execute();
    $array_shipping_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($array_shipping_results == false) {
        $array_shipping_results = array();
    }
    $db = null;
    $stmt = null;
} catch (PDOException $e) {
    die('エラー：' . $e->getMessage());
}
?>
<main class="mb-5" role="main">
    <!-- 以下SP -->
    <div class="d-lg-none mb-5">
        <div class="row">
            <div class="col-12 mb-5">
                <h1 class="page_title">出荷実績照会</h1>
                <form class="" action="shipment_inquiry.php" method="post">
                    <?php if($login_role == 1): ?>
                    <input type="hidden" name="owner_id" value="<?php echo $_SESSION['login_owner_id']; ?>">
                    <?php else: ?>
                    <div class="d-flex mb-3">
                        <p class="d-inline-block select_owner_text ml-auto">商品所有者：</p>
                        <select class="mr-auto" name="owner_id">
                            <?php echo $owner;?>
                        </select>
                    </div>
                    <?php endif; ?>
                    <div class="d-flex mb-3">
                        <p class="d-inline-block select_owner_text ml-auto">出荷実績日：</p>
                        <input class="mr-auto" type="date" name="shipping_results_day" value="<?php echo $shipping_results_day;?>">
                    </div>
                    <div>
                        <input class="button" type="submit" name="submit" value="表示">
                    </div>
                </form>
            </div>
        </div>
        <div class="row mb-5">
            <div class="col-12">
                <?php if(isset($_POST['submit'])):?>
                <?php $i = 1;?>
                <?php foreach ($array_shipping_results as $data_row) :
                            $goods_id = $data_row['goods_id'];
                            $color_size_id = $data_row['color_size_id'];
                            $goods_name = $data_row['goods_name'];
                            $color = $data_row['color'];
                            $size = $data_row['size'];
                            $picture = $data_row['picture'];
                            $volume = $data_row['volume'];
                            $convenience_store_qr_code = $data_row['convenience_store_qr_code'];
                            $other_qr_code = $data_row['other_qr_code'];
                            $designated_packing_material = $data_row['designated_packing_material'];
                            $zip_code = $data_row['zip_code'];
                            $destination_name = $data_row['destination_name'];
                            $other_name = $data_row['other_name'];
                            $shipment_source = $data_row['shipment_source'];
                            $goods_name = $data_row['goods_name'];
                            $size = $data_row['size'];
                            $shipping_id = $data_row['shipping_id'];
                            $address = $data_row['address'];
                            $shipment_source_name = $data_row['shipment_source_name'];
                            $shipping_results_day = $data_row['shipping_results_day'];
                            $packing_material = $data_row['packing_material'];
                            $delivery_company = $data_row['delivery_company'];
                            $delivery_plan = $data_row['delivery_plan'];
                            $search_no = $data_row['search_no'];
                            $postage = $data_row['postage'];
                            $remarks = $data_row['remarks'];
                            $shipping_request_no = $data_row['shipping_request_no'];
                            $no = (int) $data_row['seq'];
                            $img_tag_for_goods_image = "";
                            if (isset($picture)) {
                                $img_src = "../common/images/" . $owner_id . "/goods/" . $picture;
                                $img_tag_for_goods_image = "<img class=\"goods_img\" src=\"" . $img_src .   "\" class=\"table-td-img-product-image\">";
                            }

                            $img_tag_for_convenience_store_qr_code = "";
                            if (isset($convenience_store_qr_code)) {
                                $img_src = "../common/images/" . $owner_id . "/qr/" . $convenience_store_qr_code;
                                $img_tag_for_convenience_store_qr_code = "<img class=\"goods_img\" src=\"" . $img_src . "\" class=\"width-100px height-100px ob-fit-cover\">";
                            } else {
                                $img_tag_for_convenience_store_qr_code = "<img class=\"goods_img\" src=\"../common/images/no_image.jpg\">";
                            }

                            $img_tag_for_other_qr_code = "";
                            if (isset($other_qr_code)) {
                                $img_src = "../common/images/" . $owner_id . "/qr/" . $other_qr_code;
                                $img_tag_for_other_qr_code = "<img class=\"goods_img\" src=\"" . $img_src . "\" class=\"width-100px height-100px ob-fit-cover\">";
                            } else {
                                $img_tag_for_other_qr_code = "<img class=\"goods_img\" src=\"../common/images/no_image.jpg\">";
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

                            // 使用梱包材の配列
                            $packing_materials = array(
                                "1" => "ビニール袋（中）",
                                "2" => "ビニール袋（大）",
                                //"3" => "ビニール袋2（小）",
                                "4" => "角２封筒",
                                "5" => "Ａ５封筒",
                                "6" => "箱",
                                "7" => "その他"
                            );

                            ?>

                <table class="table sp_table_even">
                    <caption class="caption">No.
                        <?php echo $no;?>
                    </caption><!-- 行数 -->
                    <tbody>
                        <tr class="sp_column_name">
                            <th colspan="2">商品コード</th>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <?php echo $goods_id;?>
                            </td><!-- 商品名コード -->
                        </tr>
                        <tr class="sp_column_name">
                            <th colspan="2">商品名</th>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <?php echo $goods_name;?>
                            </td><!-- 商品名 -->
                        </tr>
                        <tr class="sp_column_name">
                            <th class="table_cell_half">色</th>
                            <th class="table_cell_half">サイズ</th>
                        </tr>
                        <tr>
                            <td class="table_cell_half">
                                <?php echo $color;?>
                            </td><!-- 色 -->
                            <td class="table_cell_half">
                                <?php echo $size;?>
                            </td><!-- サイズ -->
                        </tr>
                        <tr class="sp_column_name">
                            <th colspan="2">色サイズコード</th>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <?php echo $color_size_id?>
                            </td><!-- 色サイズコード -->
                        </tr>
                        <tr class="sp_column_name">
                            <th colspan="2">商品画像</th>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <div class="goods_img_wrapper">
                                    <?php echo $img_tag_for_goods_image;?>
                                    <!-- 商品画像 -->
                                </div>
                            </td>
                        </tr>
                        <tr class="sp_column_name">
                            <th colspan="2">出荷数量</th>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <?php echo $volume;?>
                            </td><!-- 出荷数量 -->
                        </tr>
                        <tr class="sp_column_name">
                            <th colspan="2">発送方法</th>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <?php echo $shipping_methods[$shipping_id];?>
                            </td><!-- 発送方法 -->
                        </tr>
                        <tr class="sp_column_name">
                            <th colspan="2">コンビニ出荷用バーコード</th>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <div class="goods_img_wrapper">
                                    <?php echo $img_tag_for_convenience_store_qr_code;?>
                                </div>
                            </td><!-- コンビニ出荷用バーコード -->
                        </tr>
                        <tr class="sp_column_name">
                            <th colspan="2">ヤマト営業所（郵便局）QRコード</th>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <div class="goods_img_wrapper">
                                    <?php echo $img_tag_for_other_qr_code;?>
                                </div>
                            </td><!-- ヤマト営業所（郵便局）QRコード -->
                        </tr>
<!--                         <tr class="sp_column_name"> -->
<!--                             <th colspan="2">指定梱包材</th> -->
<!--                         </tr> -->
<!--                         <tr> -->
<!--                             <td colspan="2"> -->
                                <!--<?php echo $designated_packing_materials[$designated_packing_material];?>-->
                            </td><!-- 指定梱包材 -->
                        </tr>
                        <tr class="sp_column_name">
                            <th colspan="2">郵便番号</th>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <?php echo $zip_code;?>
                            </td><!-- 郵便番号 -->
                        </tr>
                        <tr class="sp_column_name">
                            <th colspan="2">住所</th>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <?php echo $address;?>
                            </td><!-- 住所 -->
                        </tr>
                        <tr class="sp_column_name">
                            <th colspan="2">送付先氏名</th>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <?php echo $destination_name;?>
                            </td><!-- 送付先氏名 -->
                        </tr>
                        <tr class="sp_column_name">
                            <th colspan="2">その他氏名</th>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <?php echo $other_name;?>
                            </td><!-- その他氏名 -->
                        </tr>
                        <tr class="sp_column_name">
                            <th colspan="2">発送元住所</th>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <?php echo $shipment_source;?>
                            </td><!-- 発送元住所 -->
                        </tr>
                        <tr class="sp_column_name">
                            <th colspan="2">発送元氏名</th>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <?php echo $shipment_source_name;?>
                            </td><!-- 発送元氏名 -->
                        </tr>
                        <tr class="sp_column_name">
                            <th colspan="2">追跡番号</th>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <?php echo $search_no;?>
                            </td><!-- 追跡番号 -->
                        </tr>
                        <tr class="sp_column_name">
                            <th colspan="2">使用梱包材</th>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <?php echo $packing_materials[$packing_material];?>
                            </td><!-- 使用梱包材 -->
                        </tr>
                        <tr class="sp_column_name">
                            <th colspan="2">配送業者</th>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <?php echo $delivery_company;?>
                            </td><!-- 配送業者 -->
                        </tr>
                        <tr class="sp_column_name">
                            <th colspan="2">配送プラン</th>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <?php echo $delivery_plan;?>
                            </td><!-- 配送プラン -->
                        </tr>
                        <tr class="sp_column_name">
                            <th colspan="2">出荷実績日</th>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <?php echo $shipping_results_day;?>
                            </td><!-- 出荷実績日 -->
                        </tr>
                        <tr class="sp_column_name">
                            <th colspan="2">送料（手出し）</th>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <?php echo $postage;?>
                            </td><!-- 送料（手出し） -->
                        </tr>
                        <tr class="sp_column_name">
                            <th colspan="2">出荷依頼No.</th>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <?php echo $shipping_request_no;?>
                            </td><!-- 商品名コード -->
                        </tr>
                        <tr class="sp_column_name">
                            <th colspan="2">備考欄</th>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <?php echo $remarks;?>
                            </td><!-- 商品名コード -->
                        </tr>
                    </tbody>
                </table>
                <?php $i++ ?>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <!-- 以上SP -->
    <!-- 以下PC -->
    <div id="contents" class="d-none d-lg-block">
        <div class="inner">
            <div id="main">
                <h1 class="mt-30px">出荷実績照会</h1>
                <div class="mb-5">
                    <form action="shipment_inquiry.php" method="post">
                        <div class="w-50 mx-auto d-flex justify-content-around">
                            <?php if($login_role == 1): ?>
                            <input type="hidden" name="owner_id" value="<?php echo $_SESSION['login_owner_id']; ?>">
                            <?php else: ?>
                            <label>商品所有者:<select name="owner_id"><?php echo $owner;?></select></label>
                            <?php endif; ?>
                            <label>出荷実績日<input type="date" name="shipping_results_day" value="<?php echo $shipping_results_day;?>"></label>
                            <input class="mt-0" type="submit" value="表示" name="submit">
                        </div>
                    </form>
                </div>
                <?php if(!empty($_POST['submit'])): ?>
                <table class="border-none shipment_record_table table_scroll" style="width:2000px;">
                    <thead>
                        <tr class="shipment_record_table_title">
                            <th class="px-2 align-middle w-5rem" scope="col" rowspan="2">No.</th>
                            <th class="px-2 align-middle w-15rem" scope="col" rowspan="2">管理コード</th>
                            <th class="px-2 align-middle w-20rem" scope="col">商品コード</th>
                            <th class="px-2 align-middle w-20rem" scope="col" colspan="2">色サイズコード</th>
                            <th class="px-2 align-middle border-none width-30px bg-white"></th>
                            <th class="px-2 align-middle w-150px" scope="col" rowspan="2">商品画像</th>
                            <th class="px-2 align-middle border-none width-30px bg-white"></th>
                            <th class="px-2 align-middle w-10rem" scope="col">出荷数量</th>
                            <th class="px-2 align-middle border-none width-30px bg-white"></th>
                            <th class="px-2 align-middle w-250px" scope="col" rowspan="2">コンビニ出荷用バーコード</th>
                            <th class="px-2 align-middle w-250px" scope="col" rowspan="2">ヤマト営業所（郵便局）QRコード</th>
<!--                             <th class="px-2 align-middle border-none width-30px bg-white"></th> -->
<!--                             <th class="px-2 align-middle w-10rem" scope="col" rowspan="2">指定梱包材</th> -->
                            <th class="px-2 align-middle border-none width-30px bg-white"></th>
                            <th class="px-2 align-middle w-10rem" scope="col">郵便番号</th>
                            <th class="px-2 align-middle w-20rem" scope="col">送付先氏名</th>
                            <th class="px-2 align-middle w-20rem" scope="col" rowspan="2">その他氏名</th>
                            <th class="px-2 align-middle w-20rem" scope="col">発送元住所</th>
                            <th class="px-2 align-middle border-none width-30px bg-white"></th>
                            <th class="px-2 align-middle w-20rem" scope="col">追跡番号</th>
                            <th class="px-2 align-middle border-none width-30px bg-white"></th>
                            <th class="px-2 align-middle w-10rem" scope="col">配送業者</th>
                            <th class="px-2 align-middle border-none width-30px bg-white"></th>
                            <th class="px-2 align-middle w-15rem" scope="col">出荷実績日</th>
                            <th class="px-2 align-middle w-10rem" scope="col" rowspan="2">出荷依頼No.</th>
                            <th class="px-2 align-middle w-20rem" scope="col" rowspan="2">備考欄</th>
                        </tr>
                        <tr class="shipment_record_table_title">
                            <th class="px-2 align-middle" scope="col">商品名</th>
                            <th class="px-2 align-middle w-10rem" scope="col">色</th>
                            <th class="px-2 align-middle w-10rem" scope="col">サイズ</th>
                            <th class="px-2 align-middle border-none width-30px bg-white"></th>
                            <th class="px-2 align-middle border-none width-30px bg-white"></th>
                            <th class="px-2 align-middle" scope="col">発送方法</th>
                            <th class="px-2 align-middle border-none width-30px bg-white"></th>
                            <th class="px-2 align-middle border-none width-30px bg-white"></th>
<!--                             <th class="px-2 align-middle border-none width-30px bg-white"></th> -->
                            <th class="px-2 align-middle" scope="col" colspan="2">住所</th>
                            <th class="px-2 align-middle" scope="col">発送元氏名</th>
                            <th class="px-2 align-middle border-none width-30px bg-white"></th>
                            <th class="px-2 align-middle" scope="col">使用梱包材</th>
                            <th class="px-2 align-middle border-none width-30px bg-white"></th>
                            <th class="px-2 align-middle" scope="col">配送プラン</th>
                            <th class="px-2 align-middle border-none width-30px bg-white"></th>
                            <th class="px-2 align-middle" scope="col">送料（手出し）</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1;?>
                        <?php foreach ($array_shipping_results as $data_row) :
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
                            $designated_packing_material = $data_row['designated_packing_material'];
                            $zip_code = $data_row['zip_code'];
                            $destination_name = $data_row['destination_name'];
                            $other_name = $data_row['other_name'];
                            $shipment_source = $data_row['shipment_source'];
                            $goods_name = $data_row['goods_name'];
                            $size = $data_row['size'];
                            $shipping_id = $data_row['shipping_id'];
                            $address = $data_row['address'];
                            $shipment_source_name = $data_row['shipment_source_name'];
                            $shipping_results_day = $data_row['shipping_results_day'];
                            $packing_material = $data_row['packing_material'];
                            $delivery_company = $data_row['delivery_company'];
                            $delivery_plan = $data_row['delivery_plan'];
                            $search_no = $data_row['search_no'];
                            $postage = $data_row['postage'];
                            $shipping_request_no = $data_row['shipping_request_no'];
                            $no = (int) $data_row['seq'];
                            $remarks = $data_row['remarks'];
                            $product_management_id = $user_id."_".$goods_id."_".$color_size_id;
                            $img_tag_for_goods_image = "";
                            if (!empty($picture)) {
                                $img_src = "../common/images/" . $owner_id . "/goods/" . $picture;
                                $img_tag_for_goods_image = "<img src=\"" . $img_src .   "\" class=\"img-fluid max-height-100\">";
                            }

                            $img_tag_for_convenience_store_qr_code = "";
                            if (!empty($convenience_store_qr_code)) {
                                $img_src = "../common/images/" . $owner_id . "/qr/" . $convenience_store_qr_code;
                                $img_tag_for_convenience_store_qr_code = "<img src=\" $img_src\" class=\"object_fit\" alt=\"コンビニ出荷用バーコード\">";
              }

                            $img_tag_for_other_qr_code = "";
                            if (!empty($other_qr_code)) {
                                $img_src = "../common/images/" . $owner_id . "/qr/" . $other_qr_code;
                                $img_tag_for_other_qr_code = "<img src=\"$img_src\" class=\"object_fit\" alt=\"ヤマト営業所（郵便局）QRコード\">";
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

                            // 使用梱包材の配列
                            $packing_materials = array(
                                "1" => "ビニール袋（中）",
                                "2" => "ビニール袋（大）",
                                //"3" => "ビニール袋2（小）",
                                "4" => "角２封筒",
                                "5" => "Ａ５封筒",
                                "6" => "箱",
                                "7" => "その他"
                            );
                            ?>
                        <tr class="tr_even">
                            <td class="px-2 align-middle" rowspan="2">
                                <?php echo $no;?>
                                <!-- 行数 -->
                            </td>
                            <td class="px-2 align-middle" rowspan="2"><?php echo $product_management_id; ?></td>
                            <td class="px-2 align-middle">
                                <?php echo $goods_id;?>
                                <!-- 商品名コード -->
                            </td>
                            <td class="px-2 align-middle" colspan="2">
                                <?php echo $color_size_id?>
                                <!-- 色サイズコード -->
                            </td>
                            <td class="border-none width-30px"></td>
                            <td class="shipment_record_img" rowspan="2">
                                <?php echo $img_tag_for_goods_image;?>
                                <!-- 商品画像 -->
                            </td>
                            <td class="border-none width-30px"></td>
                            <td class="px-2 align-middle">
                                <?php echo $volume;?>
                                <!-- 出荷数量 -->
                            </td>
                            <td class="border-none width-30px"></td>
                            <td class="shipment_record_qr_img" rowspan="2">
                                <?php echo $img_tag_for_convenience_store_qr_code;?>
                                <!-- コンビニ出荷用バーコード -->
                            </td>
                            <td class="shipment_record_qr_img" rowspan="2">
                                <?php echo $img_tag_for_other_qr_code;?>
                                <!-- ヤマト営業所（郵便局）QRコード -->
                            </td>
<!--                             <td class="border-none width-30px"></td> -->
<!--                             <td class="px-2 align-middle" rowspan="2"> -->
                                <!--<?php echo $designated_packing_materials[$designated_packing_material];?>-->
                                <!-- 指定梱包材 -->
<!--                             </td> -->
                            <td class="border-none width-30px"></td>
                            <td class="px-2 align-middle">
                                <?php echo $zip_code;?>
                                <!-- 郵便番号 -->
                            </td>
                            <td class="px-2 align-middle">
                                <?php echo $destination_name;?>
                                <!-- 送付先氏名 -->
                            </td>
                            <td class="px-2 align-middle" rowspan="2">
                                <?php echo $other_name;?>
                                <!-- その他氏名 -->
                            </td>
                            <td class="px-2 align-middle">
                                <?php echo $shipment_source;?>
                                <!-- 発送元住所 -->
                            </td>
                            <td class="border-none width-30px"></td>
                            <td class="px-2 align-middle">
                                <?php echo $search_no;?>
                                <!-- 追跡番号 -->
                            </td>
                            <td class="border-none width-30px"></td>
                            <td class="px-2 align-middle">
                                <?php echo $delivery_company;?>
                                <!-- 配送業者 -->
                            </td>
                            <td class="border-none width-30px"></td>
                            <td class="px-2 align-middle">
                                <?php echo $shipping_results_day;?>
                                <!-- 出荷実績日 -->
                            </td>
                            <td class="px-2 align-middle" rowspan="2"><?php echo $shipping_request_no; ?></td>
                            <td class="px-2 align-middle" rowspan="2"><?php echo $remarks; ?></td>
                        </tr>
                        <tr class="tr_even2">
                            <td class="px-2 align-middle">
                                <?php echo $goods_name;?>
                                <!-- 商品名 -->
                            </td>
                            <td class="px-2 align-middle">
                                <?php echo $color;?>
                                <!-- 色 -->
                            </td>
                            <td class="px-2 align-middle">
                                <?php echo $size;?>
                                <!-- サイズ -->
                            </td>
                            <td class="border-none width-30px"></td>
                            <td class="border-none width-30px"></td>
                            <td class="px-2 align-middle">
                                <?php echo $shipping_methods[$shipping_id];?>
                                <!-- 発送方法 -->
                            </td>
                            <td class="border-none width-30px"></td>
                            <td class="border-none width-30px"></td>
<!--                             <td class="border-none width-30px"></td> -->
                            <td class="px-2 align-middle" colspan="2">
                                <?php echo $address;?>
                                <!-- 住所 -->
                            </td>
                            <td class="px-2 align-middle">
                                <?php echo $shipment_source_name;?>
                                <!-- 発送元氏名 -->
                            </td>
                            <td class="border-none width-30px"></td>
                            <td class="px-2 align-middle">
                                <?php echo $packing_materials[$packing_material];?>
                                <!-- 使用梱包材 -->
                            </td>
                            <td class="border-none width-30px"></td>
                            <td class="px-2 align-middle">
                                <?php echo $delivery_plan;?>
                                <!-- 配送プラン -->
                            </td>
                            <td class="border-none width-30px"></td>
                            <td class="px-2 align-middle">
                                <?php echo $postage;?>
                                <!-- 送料（手出し） -->
                            </td>
                        </tr>
                        <?php $i++; endforeach;?>
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
    <!-- 以上PC -->
</main>
<!-- /#contents -->
<?php

get_footer();