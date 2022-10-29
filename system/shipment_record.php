<?php
require_once('../common/function.php');
require_once('../db/DB.php');

$day = date('Y-m-d');

get_header();

$search_no = "";

// エラー情報リセット
$_SESSION["error_status"] = E_STATUS_DEFAULT;

$owner_id = h($_SESSION['login_owner_id']);
$shipping_scheduled_day = $day;
if ($_SERVER['REQUEST_METHOD'] === "POST") {
    $owner_id = $_POST['owner_id'];
    $shipping_scheduled_day = $_POST['shipping_scheduled_day'];
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
$owner .= "\t\t\t\t\t<option value=\"\"></option>\n";
foreach ($result as $varr) {
    if ($owner_id == $varr['owner_id']) {
        $owner .= "\t\t\t\t\t<option value=\"{$varr['owner_id']}\" selected>{$varr['owner']}</option>\n";
    } else {
        $owner .= "\t\t\t\t\t<option value=\"{$varr['owner_id']}\">{$varr['owner']}</option>\n";
    }
}

try {
    $db = DB::getDB();
    $sql = <<< EOM
        SELECT
        sh.owner_id,
        sh.shipping_scheduled_day,
        sh.shipping_request_no,
        sh.details_no,
        st.owner,
        sh.goods_id,
        sh.color_size_id,
        sh.packing_material,
        sh.delivery_company,
        sh.delivery_plan,
        sh.postage,
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
        sh.search_no,
        sh.search_no2,
        sh.seq,
        g.goods_name,
        g.color,
        g.size,
        sh.shipping_id,
        sh.address,
        sh.shipment_source_name,
        sh.shipping_scheduled_day,
        sh.record_day,
        user_id
        FROM shipping_request sh
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
        sh.completion_flag = '1'
        AND
        volume > 0
EOM;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($owner_id !== '') {
            $sql .= " AND sh.owner_id = :owner_id";
        }
        if ($shipping_scheduled_day !== '') {
            $sql .= " AND sh.shipping_scheduled_day = :shipping_scheduled_day";
        }
    }
    //$sql .= " ORDER BY sh.owner_id ASC, sh.shipping_scheduled_day ASC, sh.goods_id ASC, sh.color_size_id ASC";
    $sql .= " order by shipping_request_no ASC";
    $stmt = $db->prepare($sql);
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($owner_id !== '') {
            $stmt->bindParam(':owner_id', $owner_id, PDO::PARAM_STR);
        }
        if ($shipping_scheduled_day !== '') {
            $stmt->bindParam(':shipping_scheduled_day', $shipping_scheduled_day, PDO::PARAM_STR);
        }
    }
    $sql .= "ORDER BY sh.owner_id ASC";
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
            <main class="mb-5" role="main">
                <h1 class="mt-5">出荷実績入力</h1>
                <div>
                    <form action="shipment_record.php" method="post">
                        <div class="mx-auto my-5 w-50 d-flex justify-content-around">
                            <label>商品所有者:<select name="owner_id"><?php echo $owner;?></select></label>
                            <label>出荷予定日:<input type="date" name="shipping_scheduled_day" value="<?php echo $shipping_scheduled_day;?>"></label>
                            <input class="mt-0" type="submit" value="表示" name="submit">
                        </div>
                    </form>
                </div>
                <?php if(!empty($_POST['submit'])): ?>
                <form id="form" action="shipment_record_done.php" method="post" onsubmit="return checksubmit()">
                    <?php foreach ($shipping_request_result_grouped_by_owner_id as $owner_id => $data_rows): $i = 1; $owner = $data_rows[0]['owner'];?>
                    <table class="border-none shipment_record_table table_scroll">
                        <thead>
                            <tr class="shipment_record_table_title">
                                <th class="px-2 w-4rem" scope="col" rowspan="2"></th>
                                <th class="px-2 w-15rem align-middle" scope="col" rowspan="2">管理コード</th>
                                <th class="px-2 w-20rem align-middle" scope="col">商品名コード</th>
                                <th class="px-2 w-10rem align-middle" scope="col" colspan="2">色サイズコード</th>
                                <th class="px-2 border-none width-30px bg-white"></th>
                                <th class="px-2 w-15rem align-middle" scope="col">追跡番号</th>
                                <th class="px-2 border-none width-30px bg-white"></th>
                                <th class="px-2 align-middle w-15rem" scope="col" rowspan="2">使用梱包材</th>
                                <th class="px-2 border-none width-30px bg-white"></th>
                                <th class="px-2 w-15rem align-middle" scope="col">配送業者</th>
                                <th class="px-2 border-none width-30px bg-white"></th>
                                <th class="px-2 w-15rem align-middle" scope="col">出荷実績日</th>
                                <th class="px-2 border-none width-30px bg-white"></th>
                                <th class="px-2 w-150px align-middle" scope="col" rowspan="2">商品画像</th>
                                <th class="px-2 border-none width-30px bg-white"></th>
                                <th class="px-2 w-10rem align-middle" scope="col">出荷数量</th>
                                <th class="px-2 border-none width-30px bg-white"></th>
                                <th class="px-2 w-250px align-middle" scope="col" rowspan="2">コンビニ出荷用<br>バーコード</th>
                                <th class="px-2 w-250px align-middle" scope="col" rowspan="2">ヤマト営業所<br>（郵便局）<br>QRコード</th>
                                <th class="px-2 border-none width-30px bg-white"></th>
                                <!--                 <th class="w-10rem align-middle" scope="col" rowspan="2">指定梱包材</th> -->
                                <!--                 <th class="border-none width-30px bg-white"></th> -->
                                <th class="px-2 w-10rem align-middle" scope="col">郵便番号</th>
                                <th class="px-2 w-20rem align-middle" scope="col">送付先氏名</th>
                                <th class="px-2 w-20rem align-middle" scope="col" rowspan="2">その他氏名</th>
                                <th class="px-2 w-20rem align-middle" scope="col">発送元住所</th>
                                <th class="px-2 w-10rem align-middle" scope="col" rowspan="2">出荷依頼No.</th>
                                <th class="px-2 w-20rem align-middle" scope="col" rowspan="2">備考欄</th>
                            </tr>
                            <tr class="shipment_record_table_title">
                                <th class="px-2 align-middle" scope="col">商品名</th>
                                <th class="px-2 align-middle" scope="col">色</th>
                                <th class="px-2 align-middle" scope="col">サイズ</th>
                                <th class="px-2 border-none width-30px bg-white"></th>
                                <th class="px-2 align-middle" scope="col">追跡番号2</th>
                                <th class="px-2 border-none width-30px bg-white"></th>
                                <th class="px-2 border-none width-30px bg-white"></th>
                                <th class="px-2 align-middle" scope="col">配送プラン</th>
                                <th class="px-2 border-none width-30px bg-white"></th>
                                <th class="px-2 align-middle" scope="col">送料（手出し）</th>
                                <th class="px-2 border-none width-30px bg-white"></th>
                                <th class="px-2 border-none width-30px bg-white"></th>
                                <th class="px-2 align-middle" scope="col">発送方法</th>
                                <th class="px-2 border-none width-30px bg-white"></th>
                                <th class="px-2 border-none width-30px bg-white"></th>
                                <!--                 <th class="border-none width-30px bg-white"></th> -->
                                <th class="px-2 align-middle" scope="col" colspan="2">住所</th>
                                <th class="px-2 align-middle" scope="col">発送元氏名</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data_rows as $data_row) :
                            $shipping_request_no = $data_row['shipping_request_no'];
                            $no = (int) $data_row['seq'];
                            $details_no = $data_row['details_no'];
                            $goods_id = $data_row['goods_id'];
                            $color_size_id = $data_row['color_size_id'];
                            $user_id = $data_row['user_id'];
                            $goods_name = $data_row['goods_name'];
                            if(!empty($data_row['search_no'])) {
                                $search_no = $data_row['search_no'];
                            } else {
                                $search_no = "";
                            }

                            if(!empty($data_row['search_no2'])) {
                                $search_no2 = $data_row['search_no2'];
                            } else {
                                $search_no2 = "";
                            }
                            $shipping_day = $data_row['shipping_scheduled_day'];
                            $color = $data_row['color'];
                            $size = $data_row['size'];
                            $picture = $data_row['picture'];
                            $volume = $data_row['volume'];
                            $delivery_company = $data_row['delivery_company'];
                            $delivery_plan = $data_row['delivery_plan'];
                            $packing_material = $data_row['packing_material'];
                            $convenience_store_qr_code = $data_row['convenience_store_qr_code'];
                            $other_qr_code = $data_row['other_qr_code'];
                            $postage = $data_row['postage'];
                            //$designated_packing_material = $data_row['designated_packing_material'];
                            $zip_code = $data_row['zip_code'];
                            $destination_name = $data_row['destination_name'];
                            $other_name = $data_row['other_name'];
                            $shipment_source = $data_row['shipment_source'];
                            $goods_name = $data_row['goods_name'];
                            $size = $data_row['size'];
                            $shipping_id = $data_row['shipping_id'];
                            $address = $data_row['address'];
                            $shipment_source_name = $data_row['shipment_source_name'];
                            $shipping_scheduled_day = $data_row['shipping_scheduled_day'];
                            $product_management_id = $user_id."_".$goods_id."_".$color_size_id;
                            $img_tag_for_goods_image = "";
                            $remarks = $data_row['remarks'];
                            $shipping_request_no = $data_row['shipping_request_no'];
                            if (!empty($picture)) {
                                $img_src = "../common/images/" . $owner_id . "/goods/" . $picture;
                                $img_tag_for_goods_image = "<img src=\"" . $img_src .   "\" class=\"img-fluid max-height-100\">";
              }

              $img_tag_for_convenience_store_qr_code = "";
              if(!empty($convenience_store_qr_code)){
                $img_src = "../common/images/" . $owner_id . "/qr/" . $convenience_store_qr_code;
                $img_tag_for_convenience_store_qr_code = "<img class=\"object_fit\" src=\"" . $img_src . "\">";
              }

              $img_tag_for_other_qr_code = "";
              if(!empty($other_qr_code)){
                $img_src = "../common/images/" . $owner_id . "/qr/" . $other_qr_code;
                $img_tag_for_other_qr_code = "<img class=\"object_fit\" src=\"" . $img_src . "\">";
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

//               // 指定梱包材の配列
//               $designated_packing_materials = array(
//               "1" => "ビニール袋（中）",
//               "2" => "ビニール袋（大）",
//               "3" => "ビニール袋2（小）",
//               "4" => "角２封筒",
//               "5" => "Ａ５封筒",
//               "6" => "箱",
//               "7" => "その他"
//               );

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
                            $option_tags_for_packing_material = '';
                            foreach ($packing_materials as $key => $value) {
                                if ($packing_material == $key) {
                                    $option_tags_for_packing_material .= "\t\t\t\t\t<option value=\"$key\" selected>$value</option>\n";
                                } else {
                                    $option_tags_for_packing_material .= "\t\t\t\t\t<option value=\"$key\">$value</option>\n";
                                }
                            }

                            // 配送業者の配列
                            $delivery_companies = array(
                                'ヤマト運輸',
                                '郵便局',
                                '佐川急便'
                            );
                            if(empty($delivery_company)) {
                                $default_delivery_company = 'ヤマト運輸';
                            } else {
                                $default_delivery_company = $delivery_company;
                            }

                            $option_tags_for_delivery_company = '';
                            foreach ($delivery_companies as $value) {
                                if ($default_delivery_company === $value) {
                                    $option_tags_for_delivery_company .= "\t\t\t\t\t<option value=\"$value\" selected>$value</opton>\n";
                                } else {
                                    $option_tags_for_delivery_company .= "\t\t\t\t\t<option value=\"$value\">$value</opton>\n";
                                }
                            }

                            // 配送プランの配列
                            $delivery_plans = array(
                                "1" => "ネコポス",
                                "2" => "クリックポスト",
                                "3" => "ゆうパケット",
                                "4" => "定形外郵便",
                                "5" => "宅急便コンパクト",
                                "6" => "レターパックプラス",
                                "7" => "宅急便",
                                "8" => "その他"
                            );
                            $option_tags_for_delivery_plan = '';
                            foreach ($delivery_plans as $value) {
                                if($value === $delivery_plan) {
                                    $option_tags_for_delivery_plan .= "\t\t\t\t\t<option value=\"$value\" selected>$value</opton>\n";
                                }
                                $option_tags_for_delivery_plan .= "\t\t\t\t\t<option value=\"$value\">$value</opton>\n";
                            }
                            ?>
                            <input type="hidden" name="owner_id[]" value="<?php echo $owner_id;?>">
                            <input type="hidden" name="shipping_request_no[]" value="<?php echo $shipping_request_no;?>">
                            <input type="hidden" name="details_no[]" value="<?php echo $details_no;?>">
                            <tr class="tr_even">
                                <td class="px-2 align-middle goods_count w-4rem" rowspan="2">
                                    <?php echo $no;?>
                                </td>
                                <td class="px-2 align-middle" rowspan="2">
                                    <?php echo $product_management_id;?>
                                </td>
                                <td class="px-2 align-middle">
                                    <?php echo $goods_id;?>
                                </td>
                                <td class="px-2 align-middle" colspan="2">
                                    <?php echo $color_size_id?>
                                </td>
                                <td class="px-2 border-none width-30px bg-white"></td>
                                <td class="px-2 align-middle"><input type="text" class="search_no" name="search_no[]" value="<?php echo $search_no; ?>"></td>
                                <td class="border-none width-30px bg-white"></td>
                                <td class="px-2 align-middle" rowspan="2"><select class="packing_material" name="packing_material[]">
                                        <?php echo $option_tags_for_packing_material;?></select>
                                </td>
                                <td class="border-none width-30px bg-white"></td>
                                <td class="px-2 align-middle"><select class="delivery_company" name="delivery_company[]">
                                        <?php echo $option_tags_for_delivery_company;?></select>
                                </td>
                                <td class="px-2 border-none width-30px bg-white"></td>
                                <td class="px-2 align-middle"><input type="date" class="shipping_results_day" name="shipping_results_day[]" value="<?php echo $shipping_day;?>" required></td>
                                <td class="border-none width-30px bg-white"></td>
                                <td class="px-2 shipment_record_img" rowspan="2">
                                    <?php echo $img_tag_for_goods_image;?>
                                </td>
                                <td class="border-none width-30px bg-white"></td>
                                <td class="px-2 align-middle">
                                    <?php echo $volume;?>
                                </td>
                                <td class="border-none width-30px bg-white"></td>
                                <td class="shipment_record_qr_img" rowspan="2">
                                    <?php echo $img_tag_for_convenience_store_qr_code;?>
                                </td>
                                <td class="shipment_record_qr_img" rowspan="2">
                                    <?php echo $img_tag_for_other_qr_code;?>
                                </td>
                                <td class="border-none width-30px bg-white"></td>
                                <!-- <td class="align-middle" rowspan="2"><?php echo $designated_packing_materials["1"];?></td> -->
                                <!--                 <td class="border-none width-30px bg-white"></td> -->
                                <td class="px-2 align-middle">
                                    <?php echo $zip_code;?>
                                </td>
                                <td class="px-2 align-middle">
                                    <?php echo $destination_name;?>
                                </td>
                                <td class="px-2 align-middle" rowspan="2">
                                    <?php echo $other_name;?>
                                </td>
                                <td class="px-2 align-middle">
                                    <?php echo $shipment_source;?>
                                </td>
                                <td class="px-2 align-middle" rowspan="2"><?php echo $shipping_request_no ?></td>
                                <td class="px-2 align-middle" rowspan="2">
                                    <?php echo $remarks;?>
                                </td>
                            </tr>
                            <tr class="tr_even2">
                                <td class="px-2 align-middle">
                                    <?php echo $goods_name;?>
                                </td>
                                <td class="px-2 align-middle">
                                    <?php echo $color;?>
                                </td>
                                <td class="px-2 align-middle">
                                    <?php echo $size;?>
                                </td>
                                <td class="border-none width-30px bg-white"></td>
                                <td class="px-2 align-middle"><input type="text" class="search_no2" name="search_no2[]" value="<?php echo $search_no2; ?>"></td>
                                <td class="border-none width-30px bg-white"></td>
                                <td class="border-none width-30px bg-white"></td>
                                <td class="px-2 align-middle"><select class="delivery_plan" name="delivery_plan[]">
                                        <?php echo $option_tags_for_delivery_plan;?></select>
                                </td>
                                <td class="border-none width-30px bg-white"></td>
                                <td class="px-2 align-middle"><input type="number" class="postage" name="postage[]" min="0" value="<?php echo $postage; ?>"></td>
                                <td class="border-none width-30px bg-white"></td>
                                <td class="border-none width-30px bg-white"></td>
                                <td class="px-2 align-middle">
                                    <?php echo $shipping_methods[$shipping_id];?>
                                </td>
                                <td class="border-none width-30px bg-white"></td>
                                <td class="border-none width-30px bg-white"></td>
                                <!--                 <td class="border-none width-30px bg-white"></td> -->
                                <td class="px-2 align-middle" colspan="2">
                                    <?php echo $address;?>
                                </td>
                                <td class="px-2 align-middle">
                                    <?php echo $shipment_source_name;?>
                                </td>
                            </tr>
                            <?php $i++; endforeach;?>
                        </tbody>
                    </table>
                    <?php endforeach;?>
                    <?php if(!empty($shipping_request_result_grouped_by_owner_id)):?>
                    <div class="mt-5">
                        <input class="ml-auto mr-5" type="submit" name="save" value="一時保存">
                        <input id="submit" class="mr-auto" type="submit" name="submit" value="確定"> <!-- onClick="return check();" -->
                    </div>
                    <?php endif;?>
                    <?php endif; ?>
                </form>
            </main>
        </div>
        <!-- /#main -->
        <div id="sub">
        </div>
        <!-- /#sub -->
    </div>
    <!-- /.inner-->
</div>
<!-- 確定ボタンクリック時、追跡番号が未記入の商品があれば警告して処理を中止 -->
<script>
let submit = document.getElementById("submit");
let form = document.getElementById("form"); //確定ボタンの要素取得
let goodsCount = document.getElementsByClassName("goods_count").length; //商品数（行数）の取得
console.log(goodsCount);
console.log(submit);
//確定ボタンが押されたとき
submit.addEventListener("click", function() {
    form.onsubmit = function checksubmit() {
        //classがsearch_noのinputの値を取得
        let searchNumber = document.getElementsByClassName("search_no");
        //商品券数分以下の内容を繰り返し
        for (let ii = 0; ii < goodsCount; ii++) {
            let searchValue = searchNumber[ii].value;
            console.log(ii + 1 + "行目は" + searchValue);
            if (searchValue === "") {
                window.alert(ii + 1 + "行目の商品の追跡番号が記入されていません！");
                return false;
            }
        }
    }
});
</script>
<!-- /#contents -->
<?php

get_footer();