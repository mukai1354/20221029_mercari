<?php
// 2021/08/04 issue236 二重送信防止のロジックが出荷依頼登録以外は実装されていない demachi
// 2021/07/30 issue215 出荷依頼登録で発送元住所と発送元氏名をデフォルトの値から変更して出荷依頼してもDBに登録される値はデフォルトの値になる demachi
// 2021/07/30 issue231 出荷依頼登録での出荷依頼Noの採番ロジック変更とテーブルshipping_requestのカラム追加 demachi
require_once('../common/function.php');
require_once('../db/DB.php');

// 一日加算した日付
date_default_timezone_set('Asia/Tokyo');
get_header();

// "出荷依頼"ボタンが押された際実施
if (isset($_POST['shipping_request'])) {
    // --------------------------------------------------issue236 start-------------------------------------------------------
    // トークンの確認
    $is_token_valid = is_token_valid('token', 'token_in_shipping_request_registration');
    if (!$is_token_valid) {
        // --------------------------------------------------issue236 end---------------------------------------------------------
        die('ダブりでの送信を検知したので１回分のみ出荷登録しました。');
    }
}

// --------------------------------------------------issue236 start-------------------------------------------------------
// トークンの発行
$token = get_csrf_token();
// --------------------------------------------------issue236 end---------------------------------------------------------
//トークンをセッション変数にセット
$_SESSION["token_in_shipping_request_registration"] = $token;

// "表示"ボタン押下時、あいまい検索を実施する
// 商品所有者と商品検索ワードで、所有者の商品データを検索し、表示する
$owner_id = h($_SESSION['login_owner_id']);

// 変数初期化
$day = date("Y-m-d", strtotime("1 day"));
// ------------------------------------------------------------issue231 start----------------------------------------------------
// $day2 = "";
// ------------------------------------------------------------issue231 end------------------------------------------------------
$goods_name = "";
$initial_goods_id = "";
$initial_color = "";
$initial_size = "";
$initial_picture = "../common/images/image_00.png";
$shipping_id = "3"; // 発送方法のデフォルト値を設定
$packing_id = "";
$zip_code = "";
$address = "";
$destination_name = "";
$other_name = "";
$shipment_source = "";
$shipment_source_name = "";
$shipment_id_value = 1;
$json_array = "";
$login_role = $_SESSION['login_role'];
$remarks = "";

// postメソッドの際は入力された項目を残す
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $owner_id = getpstStrs("owner_id");
    $shipment_id_value = getpstStrs("shipment_id"); // 出荷数量
    $select1_id = getpstStrs("select1_id"); // $goods_color_size
    $shipping_id = getpstStrs("shipping_id"); // 発送方法
    $packing_id = getpstStrs("packing_id"); // 指定梱包材
    $zip_code = getpstStrs("zip_code"); // 郵便番号
    $address = getpstStrs("address"); // 住所
    $destination_name = getpstStrs("destination_name"); // 送付先氏名
    $other_name = getpstStrs("other_name"); // その他氏名
    $shipment_source = getpstStrs("shipment_source"); // 発送元住所
    $shipment_source_name = getpstStrs("shipment_source_name"); // 発送元氏名
    $day = getpstStrs("regist_shipping_scheduled_day"); // 日付
    // -----------------------------------------------------------------------issue231 start-------------------------------------------------
    //$day2 = str_replace("-", "", $day)."%";
    // -----------------------------------------------------------------------issue231 end---------------------------------------------------
    $remarks = getpstStrs("remarks");
}

try {
    $db = DB::getDB();
    $stmt = $db->prepare("SELECT owner_id, owner FROM m_staff");
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $owner = '';
    foreach ($result as $varr) {
        $owner .= "\t\t\t\t\t<option value=\"" . $varr['owner_id'] . "\"";
        if ($varr['owner_id'] == $owner_id) {
            $owner .= " selected";
        }
        $owner .= ">" . $varr['owner'] . "</option>\n";
    }
    $db = NULL;
    $stmt = NULL;
} catch (PDOException $e) {
    die('エラー：' . $e->getMessage());
}

// あいまい検索
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $goods_name = getpstStrs("goods_name");
    $db = NULL;
    $sql = "";
    $stmt = NULL;
    $goods_result = null;

    try {
        $db = DB::getDB();
        $sql = "SELECT owner_id, goods_id, color_size_id, goods_name, ifnull(color, \"なし\") as color, ifnull(size, \"なし\") as size , picture FROM m_goods";
        $sql .= " WHERE owner_id = :owner_id";
        $sql .= " AND goods_name collate utf8_unicode_ci LIKE :goods_name"; // 全角半角を区別しないようにする
        $sql .= " AND deleted_flag = FALSE";

        $stmt = $db->prepare($sql);
    } catch (PDOException $e) {
        die('エラー：' . $e->getMessage());
    }

    $bind_goods_name = '%' . $goods_name . '%';
    $stmt->bindParam(':owner_id', $owner_id, PDO::PARAM_STR);
    $stmt->bindParam(':goods_name', $bind_goods_name, PDO::PARAM_STR);
    $stmt->execute();
    $goods_result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $db = NULL;
    $stmt = NULL;

    $goods_color_size = '';
    $counter_i = 0;
    $select_num = 0;

    if (!(empty($goods_result))) {
        foreach ($goods_result as $varr) {
            if ($select1_id == $varr['owner_id'] . $varr['color_size_id'] . $varr['goods_id']) {
                $goods_color_size .= "\t\t\t\t\t<option class=\"w-100\" value=\"{$varr['owner_id']}{$varr['color_size_id']}{$varr['goods_id']}\" selected>{$varr['goods_name']}:{$varr['color']}:{$varr['size']}</option>\n";
                $color_size_id = $varr['color_size_id'];
                $select_num = $counter_i;
            } else {
                $goods_color_size .= "\t\t\t\t\t<option value=\"{$varr['owner_id']}{$varr['color_size_id']}{$varr['goods_id']}\">{$varr['goods_name']}:{$varr['color']}:{$varr['size']}</option>\n";
            }
            $counter_i++;
        }
    } else {
        echo '商品名は見つかりませんでした';
    }
}

// "表示"ボタンが押された際実施
if (isset($_POST['display']) and (!(empty($goods_result)))) {
    $initial_array = $goods_result[0];
}

// "出荷依頼"ボタンが押された際実施
if (isset($_POST['shipping_request'])) {
    $initial_array = $goods_result[$select_num];

    // QRコードを保存
    // QRコードが張られていない場合は保存しない
    $image = $_FILES['convenience_store_qr_code'];
    if (!empty($image['name'])) {
        $filepath = pathinfo($image['name']); // ファイル名を取得する
        $extension = $filepath['extension']; // ファイル名から拡張子を切り出す
        $convenience_store_qr_code_image_name = "c_store_qr_" . date("YmdHis") . "." . $extension; // c_store_qr_日付時間秒.拡張子
        if (!move_uploaded_file($image['tmp_name'], '../common/images/' . $owner_id . '/qr/' . $convenience_store_qr_code_image_name)) {
            echo 'アップロードされたファイルの保存に失敗しました。';
        }
    } else {
        $convenience_store_qr_code_image_name = "";
    }

    $image = $_FILES['other_qr_code'];
    if (!empty($image['name'])) {
        $filepath = pathinfo($image['name']); // ファイル名を取得する
        $extension = $filepath['extension']; // ファイル名から拡張子を切り出す
        $other_qr_code_image_name = "other_qr_" . date("YmdHis") . "." . $extension; // other_qr_日付時間秒.拡張子
        if (!move_uploaded_file($image['tmp_name'], '../common/images/' . $owner_id . '/qr/' . $other_qr_code_image_name)) {
            echo 'アップロードされたファイルの保存に失敗しました。';
        }
    } else {
        $other_qr_code_image_name = "";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" and (!(empty($goods_result)))) {
    $initial_goods_name = $initial_array['goods_name'];
    $initial_goods_id = $initial_array['goods_id'];
    $initial_color = $initial_array['color'];
    $initial_size = $initial_array['size'];
    $initial_picture = "../common/images/" . $initial_array['owner_id'] . "/goods/" . $initial_array['picture'];
    $json_array = json_encode($goods_result);
}

// 発送方法の配列
$shipping_method = array(
    "1" => "ネコポス",
    "2" => "クリックポスト",
    "3" => "ゆうパケット",
    "4" => "定形外郵便",
    "5" => "宅急便コンパクト",
    "6" => "レターパックプラス",
    "7" => "宅急便",
    "8" => "その他",
    "9" => "ネコポス（集荷）",
);
// 発送方法と配送業者の対応
$relations_between_shipping_method_and_delivery_company = array(
    "ネコポス" => "ヤマト運輸",
    "クリックポスト" => "郵便局",
    "ゆうパケット" => "郵便局",
    "定形外郵便" => "郵便局",
    "宅急便コンパクト" => "ヤマト運輸",
    "レターパックプラス" => "郵便局",
    "宅急便" => "ヤマト運輸",
    "その他" => "佐川急便",
    "ネコポス（集荷）" => "ヤマト運輸",
);
$shipping = '';
foreach ($shipping_method as $key => $value) {
    if ($shipping_id == $key) {
        $shipping_id_value = $value;
        $delivery_company = $relations_between_shipping_method_and_delivery_company[$shipping_id_value];
        $shipping .= "\t\t\t\t\t<option value=\"$key\" selected>$value</option>\n";
    } else {
        $shipping .= "\t\t\t\t\t<option value=\"$key\">$value</option>\n";
    }
}

// 指定梱包材の配列
$designated_packing_material = array(
    "1" => "ビニール袋（大）",
    "2" => "ビニール袋（小）",
    "3" => "角２封筒",
    "4" => "Ａ５封筒",
    "5" => "箱",
    "6" => "その他"
);

$packing = '';
foreach ($designated_packing_material as $key => $value) {
    if ($packing_id == $key) {
        $packing .= "\t\t\t\t\t<option value=\"$key\" selected>$value</option>\n";
    } else {
        $packing .= "\t\t\t\t\t<option value=\"$key\">$value</option>\n";
    }
}

// --------------------------------------------------issue215 start-------------------------------------------------------
if (isset($_POST['display'])) {
    try {
        $db = DB::getDB();
        $stmt = $db->prepare("SELECT owner, address_1, address_2 FROM m_staff WHERE owner_id = :owner_id");
        $stmt->bindParam(':owner_id', $owner_id, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result !== false) {
            $shipment_source = $result['address_1'] . $result['address_2'];
            $shipment_source_name = $result['owner'];
        }
        $db = null;
        $stmt = null;
    } catch (PDOException $e) {
        die('エラー：' . $e->getMessage());
    }
}
// --------------------------------------------------issue215 end---------------------------------------------------------

// データベース更新
if (isset($_POST['shipping_request'])) {

    // --------------------------------------------------issue231 start-------------------------------------------------------
    //$shipping_request_no = date("Ymd") . "000";
    // --------------------------------------------------issue239 start-------------------------------------------------------
    $condition_of_shipping_request_no = str_replace("-", "", $day) . "%";
    // --------------------------------------------------issue239 end---------------------------------------------------------
    // --------------------------------------------------issue231 end---------------------------------------------------------
    // 最大のNo(YYYYMMDD+3桁数字)を抽出する
    try {
        // データベース接続
        $db = DB::getDB();
        // --------------------------------------------------issue239 start-------------------------------------------------------
        // --------------------------------------------------issue231 start-------------------------------------------------------
        $sql = <<< EOM
    SELECT MAX(shipping_request_no)
    FROM
    shipping_request
    WHERE
    owner_id = :owner_id
    AND
    shipping_request_no LIKE :condition_of_shipping_request_no;
EOM;
        // --------------------------------------------------issue231 end---------------------------------------------------------
        // --------------------------------------------------issue239 end---------------------------------------------------------

        $stmt = $db->prepare($sql);
        // --------------------------------------------------issue231 start-------------------------------------------------------
        $stmt->bindValue(':condition_of_shipping_request_no', $condition_of_shipping_request_no, PDO::PARAM_STR);
        // --------------------------------------------------issue231 end---------------------------------------------------------
        $stmt->bindParam(':owner_id', $owner_id, PDO::PARAM_STR);
        $stmt->execute();
        $result_staff = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $db = null;
        $stmt = null;
    } catch (PDOException $e) {
        die('エラー：' . $e->getMessage());
    }

    foreach ($result_staff[0] as $key => $value) {
        $max_shipping_request_no = $value;
    }

    $shipping_request_no = str_replace("-", "", $day) . "001";

    // No(YYYYMMDD000)以上のNoがあり、かつNoがNULLでなければ、
    // 末尾三桁を切り取り+1した後、三桁に戻す
    // --------------------------------------------------issue231 start-------------------------------------------------------
    $rest = "001"; // 出荷依頼Noの下位３桁
    // --------------------------------------------------issue231 end---------------------------------------------------------
    if (($max_shipping_request_no >= $shipping_request_no) and ($max_shipping_request_no != NULL)) {
        $rest = substr($max_shipping_request_no, -3);
        $rest = $rest + 1;
        $rest = sprintf('%03d', $rest);
        $shipping_request_no = substr($max_shipping_request_no, 0, -3) . $rest;
    }

    try {
        // データベース接続
        $db = DB::getDB();
        // --------------------------------------------------issue231 start-------------------------------------------------------
        $sql = "INSERT INTO
             shipping_request (
       owner_id,
             shipping_request_no,
             shipping_id,
         shipping_scheduled_day,
       goods_id,
       color_size_id,
             volume,
        delivery_company,
       delivery_plan,
             designated_packing_material,
       zip_code,
       address,
             destination_name,
       other_name,
       shipment_source,
       shipment_source_name,
       convenience_store_qr_code,
       other_qr_code,
        remarks,
        seq)
       VALUES(
       :owner_id,
             :shipping_request_no,
             :shipping_id,
       :regist_shipping_scheduled_day,
       :goods_id,
       :color_size_id,
             :shipment_id_value,
             :delivery_company,
       :shipping_id_vale,
             :packing_id,
       :zip_code,
       :address,
             :destination_name,
       :other_name,
       :shipment_source,
       :shipment_source_name,
             :convenience_store_qr_code,
       :other_qr_code,
        :remarks,
        :seq)";
        // --------------------------------------------------issue231 end---------------------------------------------------------

        $stmt = $db->prepare($sql);
        $stmt->bindParam(':owner_id', $owner_id, PDO::PARAM_STR);
        $stmt->bindParam(':shipping_request_no', $shipping_request_no, PDO::PARAM_STR);
        $stmt->bindParam(':shipping_id', $shipping_id, PDO::PARAM_STR);
        $stmt->bindParam(':regist_shipping_scheduled_day', $day, PDO::PARAM_STR);
        $stmt->bindParam(':goods_id', $initial_goods_id, PDO::PARAM_STR);
        $stmt->bindParam(':color_size_id', $color_size_id, PDO::PARAM_STR);
        $stmt->bindParam(':shipment_id_value', $shipment_id_value, PDO::PARAM_STR);
        $stmt->bindParam(':delivery_company', $delivery_company, PDO::PARAM_STR);
        $stmt->bindParam(':shipping_id_vale', $shipping_id_value, PDO::PARAM_STR);
        $stmt->bindParam(':packing_id', $packing_id, PDO::PARAM_STR);
        $stmt->bindParam(':zip_code', $zip_code, PDO::PARAM_STR);
        $stmt->bindParam(':address', $address, PDO::PARAM_STR);
        $stmt->bindParam(':destination_name', $destination_name, PDO::PARAM_STR);
        $stmt->bindParam(':other_name', $other_name, PDO::PARAM_STR);
        $stmt->bindParam(':shipment_source', $shipment_source, PDO::PARAM_STR);
        $stmt->bindParam(':shipment_source_name', $shipment_source_name, PDO::PARAM_STR);
        $stmt->bindParam(':convenience_store_qr_code', $convenience_store_qr_code_image_name, PDO::PARAM_STR);
        $stmt->bindParam(':other_qr_code', $other_qr_code_image_name, PDO::PARAM_STR);
        $stmt->bindParam(':remarks', $remarks, PDO::PARAM_STR);
        // --------------------------------------------------issue231 start-------------------------------------------------------
        $stmt->bindValue(':seq', (int)$rest, PDO::PARAM_INT);
        // --------------------------------------------------issue231 end---------------------------------------------------------
        $stmt->execute();
        log_message("[{$_SESSION['login_user_id']}]" . pdo_debugStrParams($stmt));
        $db = NULL;

        echo '<p style=\"font-weght: bold; font-size: 20px; color: red;\">更新が完了しました。</p>';
    } catch (Exception $e) {
        log_message("[{$_SESSION['login_user_id']}]" . $e->getMessage());
        log_message("[{$_SESSION['login_user_id']}]" . pdo_debugStrParams($stmt));
        die('エラー：' . $e->getMessage());
    }
}

?>
<main>
    <!-- 以下SP -->
    <div class="mb-5">
        <form method="post" action="shipment_request_registration.php" enctype="multipart/form-data">
            <div class="row mb-5">
                <div class="col-12">
                    <h1 class="page_title text-center mt-5">出荷依頼登録</h1>
                    <div class="select_owner_wrapper">
                        <?php if ($login_role == 1) : ?>
                            <input type="hidden" name="owner_id" value="<?php echo $_SESSION['login_owner_id']; ?>">
                        <?php else : ?>
                            <label>商品所有者：&ensp;
                                <select class="mr-auto" name="owner_id">
                                    <?php echo $owner; ?>
                                </select>
                            </label>
                            <br class="d-lg-none">
                        <?php endif; ?>
                        <label>出荷予定日：&ensp;<input class="mr-auto" type="date" name="regist_shipping_scheduled_day" value="<?php echo $day; ?>"></label>
                        <br class="d-lg-none">
                        <label>
                            商品名：&ensp;<input class="mr-auto" type="text" name="goods_name" id="goods_id" value="<?php echo $goods_name; ?>">
                        </label>
                        <br class="d-lg-none">
                        <input class="button " type="submit" name="display" value="表示">
                        <br class="d-lg-none">
                    </div>
                </div>
            </div>
            <?php if (!empty($_POST['display'])) : ?>
                <div class="row">
                    <div class="col-12">
                        <table class="table mb-5 shipment_request_registration_table">
                            <tbody>
                                <input id="product_code" class="table_input" type="hidden" name="goods_id" value="<?php echo $initial_goods_id; ?>">
                                <input id="color" class="table_input" type="hidden" name="color_id" value="<?php echo $initial_color; ?>">
                                <input id="size" class="table_input" type="hidden" name="size_id" value="<?php echo $initial_size; ?>">
                                <tr class="sp_column_name shipment_record_table_title">
                                    <th colspan="2">商品名：色：サイズ</th>
                                </tr>
                                <tr>
                                    <td colspan="2">
                                        <select id="select1" class="w-100 py-1" name="select1_id" onchange="selectbox_change();">
                                            <?php echo $goods_color_size; ?>
                                        </select>
                                        <div id="output"></div>
                                    </td>
                                </tr>
                                <tr class="sp_column_name shipment_record_table_title">
                                    <th colspan="2">商品名</th>
                                </tr>
                                <tr>
                                    <td id="goods_name" colspan="2"><?php echo $initial_goods_name; ?></td>
                                </tr>
                                <tr class="sp_column_name shipment_record_table_title">
                                    <th class="table_cell_half">色</th>
                                    <th class="table_cell_half">サイズ</th>
                                </tr>
                                <tr>
                                    <td id="color_td" class="table_cell_half"><?php echo $initial_color; ?></td>
                                    <td id="size_td" class="table_cell_half"><?php echo $initial_size; ?></td>
                                </tr>
                                <tr class="sp_column_name shipment_record_table_title">
                                    <th colspan="2">出荷数量</th>
                                </tr>
                                <tr>
                                    <td colspan="2">
                                        <input class="table_input text-center" type="number" name="shipment_id" min="1" value="1">
                                    </td>
                                </tr>
                                <tr class="sp_column_name shipment_record_table_title">
                                    <th colspan="2">発送方法</th>
                                </tr>
                                <tr>
                                    <td colspan="2">
                                        <select class="w-100 py-1" name="shipping_id">
                                            <?php echo $shipping; ?>
                                        </select>
                                    </td>
                                </tr>
                                <tr class="sp_column_name shipment_record_table_title">
                                    <th colspan="2">商品画像</th>
                                </tr>
                                <tr>
                                    <td colspan="2">
                                        <p class="text-center font-weight-bold">商品画像</p>
                                        <div class="goods_img_wrapper">
                                            <img id="image01" class="goods_img" name="picture_id" src="<?php echo $initial_picture; ?>" style="max-width:100%;">
                                        </div>
                                    </td>
                                </tr>
                                <tr class="sp_column_name shipment_record_table_title">
                                    <th colspan="2">コンビニ出荷用バーコード</th>
                                </tr>
                                <tr>
                                    <td colspan="2">
                                        <div class="user-icon-dnd-wrapper">
                                            <input type="file" name="convenience_store_qr_code" class="input_file" accept="image/*">
                                            <div class="preview_field"></div>
                                            <div class="drop_area">
                                                <p class="drag_and_drop">drag and drop</p>
                                            </div>
                                            <div class="icon_clear_button"></div>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="sp_column_name shipment_record_table_title">
                                    <th colspan="2">ヤマト営業所（郵便局）QRコード</th>
                                </tr>
                                <tr>
                                    <td colspan="2">
                                        <div class="user-icon-dnd-wrapper">
                                            <input type="file" name="other_qr_code" class="input_file" accept="image/*">
                                            <div class="preview_field"></div>
                                            <div class="drop_area">
                                                <p class="drag_and_drop">drag and drop</p>
                                            </div>
                                            <div class="icon_clear_button"></div>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="sp_column_name shipment_record_table_title">
                                    <th colspan="2">郵便番号</th>
                                </tr>
                                <tr>
                                    <td colspan="2">
                                        <label>〒 <input class="table_input w-auto" type="text" maxlength="9" name="zip_code" value="<?php echo $zip_code; ?>"></label>
                                    </td>
                                </tr>
                                <tr class="sp_column_name shipment_record_table_title">
                                    <th colspan="2">住所</th>
                                </tr>
                                <tr>
                                    <td colspan="2">
                                        <textarea class="table_input" name="address" value="<?php echo $address; ?>"></textarea>
                                    </td>
                                </tr>
                                <tr class="sp_column_name shipment_record_table_title">
                                    <th colspan="2">送付先氏名</th>
                                </tr>
                                <tr>
                                    <td colspan="2">
                                        <input class="table_input" type="text" name="destination_name" value="<?php echo $destination_name; ?>">
                                    </td>
                                </tr>
                                <tr class="sp_column_name shipment_record_table_title">
                                    <th colspan="2">発送元住所</th>
                                </tr>
                                <tr>
                                    <td colspan="2">
                                        <textarea class="table_input" name="shipment_source"><?php echo $shipment_source; ?></textarea>
                                    </td>
                                </tr>
                                <tr class="sp_column_name shipment_record_table_title">
                                    <th colspan="2">発送元氏名</th>
                                </tr>
                                <tr>
                                    <td colspan="2">
                                        <input class="table_input" type="text" name="shipment_source_name" value="<?php echo $shipment_source_name; ?>">
                                    </td>
                                </tr>
                                <tr class="sp_column_name shipment_record_table_title">
                                    <th colspan="2">その他氏名</th>
                                </tr>
                                <tr>
                                    <td colspan="2">
                                        <input class="table_input" type="text" name="other_name" value="<?php echo $other_name; ?>">
                                    </td>
                                </tr>
                                <tr class="sp_column_name shipment_record_table_title">
                                    <th colspan="2">備考欄</th>
                                </tr>
                                <tr>
                                    <td colspan="2">
                                        <textarea class="table_input" name="remarks" maxlength="255"></textarea>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="row">
                            <div class="col-12">
                                <input type="hidden" name="token" value="<?php echo $token; ?>">
                                <button id="shipping_request" class="button" type="submit" name="shipping_request" value="seach">出荷依頼</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </form>
    </div>
    <!-- 以上SP -->

</main>
<script type="text/javascript" src="../common/js/drag_and_drop.js"></script>
<!-- "商品名:色:サイズ"を変更したとき用のjavascript -->
<script>
    function selectbox_change() {
        const target = document.getElementById("output");
        const selindex = select1.selectedIndex;
        var js_array = JSON.parse('<?php echo $json_array; ?> ');

        var data_row = js_array[selindex];

        document.getElementById("product_code").value = data_row["goods_id"];
        document.getElementById("color").value = data_row["color"];
        document.getElementById("size").value = data_row["size"];
        document.getElementById("image01").src = '../common/images/' + data_row["owner_id"] + '/goods/' + data_row["picture"];
        document.getElementById("goods_name").innerHTML = "<td id='goods_name' colspan='2'>" + data_row["goods_name"] + "</td>";
        document.getElementById("color_td").innerHTML = "<td id='color_td' class='table_cell_half'>" + data_row["color"] + "</td>";
        document.getElementById("size_td").innerHTML = "<td id='size_td' class='table_cell_half'>" + data_row["size"] + "</td>";
    }
</script>

<?php

get_footer();
