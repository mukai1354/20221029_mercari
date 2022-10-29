<?php
require_once ('../common/function.php');
require_once ('../db/DB.php');
$day = date("Y-m-d");
get_header();
// エラー情報リセット
$_SESSION["error_status"] = E_STATUS_DEFAULT;
// 変数初期化
$goods_name = "";
$shipping_id = "";
$packing_id = "";
$zip_code = "";
$address = "";
$other_name = "";
$shipment_source = "";
$shipment_source_name = "";
$tbody = "";
$table_sp="";
// ログインした人のowner_id
$owner_id = h($_SESSION['login_owner_id']);
try {
    // データベース接続
    $db = DB::getDB();
    // 最後のレコードからorder_noを取得
    $stmt = $db->prepare("SELECT owner_id, owner FROM m_staff");
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $db = NULL;
    $stmt = NULL;
} catch (PDOException $e) {
    die('エラー：' . $e->getMessage());
}
// ログインした人のowner_idがデフォルトで選択される
$owner = '';
foreach ($result as $varr) {
    if ($varr['owner_id'] == $owner_id) {
        $owner .= "\t\t\t\t\t<option value=\"{$varr['owner_id']}\" selected>{$varr['owner']}</option>\n";
    } else {
        $owner .= "\t\t\t\t\t<option value=\"{$varr['owner_id']}\">{$varr['owner']}</option>\n";
    }
}
// 発送方法の配列
$shipping_method = array(
    "1" => "ゆうゆうメルカリ便",
    "2" => "クリックポスト",
    "3" => "ネコポス",
    "4" => "宅急便コンパクト",
    "5" => "ゆうパケットポスト",
    "6" => "ゆうパケット",
    "7" => "レターパックライト",
    "8" => "レターパックプラス",
    "9" => "その他"
);
// 指定梱包材の配列
$designated_packing_material = array(
    "1" => "ビニール袋（大）",
    "2" => "ビニール袋（小）",
    "3" => "角２封筒",
    "4" => "Ａ５封筒",
    "5" => "箱",
    "6" => "その他"
);
// "表示"ボタンが押された際実施
if (isset($_POST['display'])) {
    // 選択された日付を代入する
    $day = getpstStrs("date_select");
    // "shipping_request"から選択された日付より前のshipping_scheduled_dayに
    // 該当するすべての値を取得する
    try {
        // データベース接続
        $db = DB::getDB();
        $sql = "SELECT *
                FROM shipping_request
                WHERE shipping_scheduled_day >= '" . $day . "'
                AND completion_flag = '0'
                AND volume > 0";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $db = null;
        $stmt = null;
    } catch (PDOException $e) {
        die('エラー：' . $e->getMessage());
    }
    $counter_i = 0;
    $tbody = "";
    foreach ($result as $varr) {
        $counter_i ++;
        $goods_id = $varr['goods_id'];
        $volume = $varr['volume'];
        $convenience_store_qr_code = "../common/images/" . $varr['owner_id'] . "/qr/" . $varr['convenience_store_qr_code'];
        $other_qr_code = "../common/images/" . $varr['owner_id'] . "/qr/" . $varr['other_qr_code'];
        $zip_code = $varr['zip_code'];
        $destination_name = $varr['destination_name'];
        $other_name = $varr['other_name'];
        $shipment_source = $varr['shipment_source'];
        $shipping_scheduled_day = $varr['shipping_scheduled_day'];
        $registration_time = $varr['record_day']; // !!!!!!要確認
        $alert = ""; // !!!!!!!!!後日作成
        $address = $varr['address'];
        $shipment_source_name = $varr['shipment_source_name'];
        $owner_id = $varr['owner_id'];
        $color_size_id = $varr['color_size_id'];
        // DBで指定された指定梱包材を選択
        $packing_id = $varr['designated_packing_material'];
        $packing = '';
        foreach ($designated_packing_material as $key => $value) {
            if ($packing_id == $key) {
                $packing .= "\t\t\t\t\t<option class=\"table_input\" value=\"$key\" selected>$value</option>\n";
            } else {
                $packing .= "\t\t\t\t\t<option class=\"table_input\" value=\"$key\">$value</option>\n";
            }
        }
        // DBで指定された発送方法を選択
        $shipping_id = $varr['shipping_id'];
        $shipping = '';
        foreach ($shipping_method as $key => $value) {
            if ($shipping_id == $key) {
                $shipping .= "\t\t\t\t\t<option class=\"table_input\" value=\"$key\" selected>$value</option>\n";
            } else {
                $shipping .= "\t\t\t\t\t<option class=\"table_input\" value=\"$key\">$value</option>\n";
            }
        }
        // DBの"m_goods"テーブルから色、商品名、サイズ、商品画像を取得する
        try {
            // データベース接続
            $db = DB::getDB();
            $sql = "SELECT *
                FROM m_goods
                WHERE owner_id = '" . $owner_id . "'
                AND goods_id = '" . $goods_id . "'
                AND color_size_id = '" . $color_size_id . "' ";
            $stmt = $db->prepare($sql);
            $stmt->execute();
            $result_m_goods = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $db = null;
            $stmt = null;
        } catch (PDOException $e) {
            die('エラー：' . $e->getMessage());
        }
        foreach ($result_m_goods as $varr2) {
            $color = $varr2['color'];
            $goods_name = $varr2['goods_name'];
            $size = $varr2['size'];
            $picture = "../common/images/" . $varr2['owner_id'] . "/goods/" . $varr2['picture'];
        }
        // アラート
        if ($shipping_scheduled_day == date("Y-m-d")) {
            $alert .= "当日出荷です　";
        } elseif ($shipping_scheduled_day < date("Y-m-d")) {
            $alert .= "出荷日を過ぎています　";
        }

        $table_sp .= <<< EOD
<table class="table mb-5 sp_table_even">
    <caption class="caption h4 font-weight-bold">No.&ensp;{$counter_i}</caption>
    <tbody>
        <input class="table_input" type="hidden" name="goods_code_{$counter_i}" id="goods_code_id" type="text" value="{$goods_id}">
        <tr class="sp_column_name" colspan="2">
            <th colspan="2">商品名</th>
        </tr>
        <tr>
            <td colspan="2">
                <input class="table_input" type="hidden" name="goods_name_{$counter_i}" value="{$goods_name}">
                {$goods_name}
            </td>
        </tr>
        <tr class="sp_column_name">
            <th class="table_cell_half">色</th>
            <th class="table_cell_half">サイズ</th>
        </tr>
        <tr>
            <td class="table_cell_half"><input class="table_input" type="hidden" name="color_{$counter_i}" value="{$color}" readonly>{$color}</td>
            <td class="table_cell_half"><input class="table_input" type="hidden" name="size_{$counter_i}" value="{$size}" readonly>{$size}</td>
        </tr>
        <tr class="sp_column_name" colspan="2">
            <th colspan="2">商品画像</th>
        </tr>
        <tr>
            <td colspan="2">
                <div class="goods_img_wrapper">
                    <img class="goods_img" name="picture_{$counter_i}" src="{$picture}" alt="{$goods_name}">
                </div>
            </td>
        </tr>
        <tr class="sp_column_name" colspan="2">
            <th colspan="2">出荷数量</th>
        </tr>
        <tr>
            <td colspan="2">
                <input class="table_input" type="number" name="volume_{$counter_i}" value="{$volume}">
            </td>
        </tr>
        <tr class="sp_column_name" colspan="2">
            <th colspan="2">発送方法</th>
        </tr>
        <tr>
            <td colspan="2">
                <select class="table_input w-100" name="shipping_{$counter_i}">
                    {$shipping}
                </select>
            </td>
        </tr>
        <tr class="sp_column_name" colspan="2">
            <th colspan="2">コンビニ出荷用バーコード</th>
        </tr>
        <tr>
            <td colspan="2">
                <div class="goods_img_wrapper">
                    <img id="convenience_store_qr_code" class="goods_img" name="convenience_store_qr_code_{$counter_i}" src="{$convenience_store_qr_code}">
                </div>
            </td>
        </tr>
        <tr class="sp_column_name" colspan="2">
            <th colspan="2">ヤマト営業所（郵便局）QRコード</th>
        </tr>
        <tr>
            <td colspan="2">
                <div class="goods_img_wrapper">
                    <img id = "other_qr_code" class="goods_img" name="other_qr_code_{$counter_i}" src="{$other_qr_code}">
                </div>
            </td>
        </tr>
        <tr class="sp_column_name">
            <th colspan="2">指定梱包材</th>
        </tr>
        <tr>
            <td colspan="2">
                <select class="table_input" name="packing_{$counter_i}">
                    {$packing}
                </select>
            </td>
        </tr>
        <tr class="sp_column_name" colspan="2">
            <th colspan="2">郵便番号</th>
        </tr>
        <tr>
            <td colspan="2">
                <input class="table_input" type="text" name="zip_code_{$counter_i}" value="{$zip_code}">
            </td>
        </tr>
        <tr class="sp_column_name" colspan="2">
            <th colspan="2">住所</th>
        </tr>
        <tr>
            <td colspan="2">
                <textarea class="table_input" name="address_{$counter_i}">{$address}</textarea>
            </td>
        </tr>
        <tr class="sp_column_name" colspan="2">
            <th colspan="2">送付先氏名</th>
        </tr>
        <tr>
            <td colspan="2">
                <input class="table_input" type="text" name="destination_name_{$counter_i}" value="{$destination_name}">
            </td>
        </tr>
        <tr class="sp_column_name" colspan="2">
            <th colspan="2">その他氏名</th>
        </tr>
        <tr>
            <td colspan="2">
            <input class="table_input" type="text" name="other_name_{$counter_i}" value="{$other_name}">
            </td>
        </tr>
        <tr class="sp_column_name" colspan="2">
            <th colspan="2">発送元住所</th>
        </tr>
        <tr>
            <td colspan="2">
                <textarea class="table_input" name="shipment_source_{$counter_i}">{$shipment_source}</textarea>
            </td>
        </tr>
        <tr class="sp_column_name" colspan="2">
            <th colspan="2">発送先氏名</th>
        </tr>
        <tr>
            <td colspan="2">
                <input class="table_input" type="text" name="shipment_source_name_{$counter_i}" value="{$shipment_source_name}">
            </td>
        </tr>
        <tr class="sp_column_name" colspan="2">
            <th colspan="2">出荷予定日</th>
        </tr>
        <tr>
            <td colspan="2">
                <input class="table_input" type="date" name="shipping_scheduled_day_{$counter_i}" value="{$shipping_scheduled_day}">
            </td>
        </tr>
        <tr class="sp_column_name" colspan="2">
            <th colspan="2">登録時間</th>
        </tr>
        <tr>
            <td colspan="2">
                <input class="table_input" type="text" name="registration_time_{$counter_i}" value="{$registration_time}" readonly>
            </td>
        </tr>
        <tr class="sp_column_name" colspan="2">
            <th colspan="2">アラート</th>
        </tr>
        <tr>
            <td colspan="2">
                <input class="table_input" type="text" name="alert_{$counter_i}" value="{$alert}" readonly>
            </td>
        </tr>
    </tbody>
</table>
EOD;

                    $tbody .= "                     <tr>
              <td rowspan=\"2\">{$counter_i}</td>
              <td><input name=\"goods_code_$counter_i\" id=\"goods_code_id\" type=\"text\" value=\"$goods_id\" readonly></td>
              <td><input type=\"text\" name=\"color_$counter_i\" value=\"$color\" readonly></td>
              <td rowspan=\"2\"><img id = \"picture\" name=\"picture_$counter_i\" src=\"$picture\" width=\"100%\" height=\"100%\"></td>
              <td><input type=\"number\" name=\"volume_$counter_i\" value=\"$volume\"></td>
              <td rowspan=\"2\"><img id = \"convenience_store_qr_code\" name=\"convenience_store_qr_code_$counter_i\" src=\"$convenience_store_qr_code\" width=\"100%\" height=\"100%\"></td>
              <td rowspan=\"2\"><img id = \"other_qr_code\" name=\"other_qr_code_$counter_i\" src=\"$other_qr_code\" width=\"100%\" height=\"100%\"></td>
              <td rowspan=\"2\">
                <select name=\"packing_$counter_i\">
$packing;
                </select>
              </td>
              <td><input type=\"text\" name=\"zip_code_$counter_i\" value=\"$zip_code\"></td>
              <td><input type=\"text\" name=\"destination_name_$counter_i\" value=\"$destination_name\"></td>
              <td rowspan=\"2\"><input type=\"text\" name=\"other_name_$counter_i\" value=\"$other_name\"></td>
              <td><input type=\"text\" name=\"shipment_source_$counter_i\" value=\"$shipment_source\"></td>
              <td rowspan=\"2\"><input type=\"date\" name=\"shipping_scheduled_day_$counter_i\" value=\"$shipping_scheduled_day\"></td>
              <td rowspan=\"2\"><input type=\"text\" name=\"registration_time_$counter_i\" value=\"$registration_time\" readonly></td>
              <td rowspan=\"2\"><input type=\"text\" name=\"alert_$counter_i\" value=\"$alert\"readonly></td>
            </tr>
            <tr>
              <td><input type=\"text\" name=\"goods_name_$counter_i\" value=\"$goods_name\"readonly></td>
              <td><input type=\"text\" name=\"size_$counter_i\" value=\"$size\" readonly></td>
              <td>
                <select name=\"shipping_$counter_i\">
$shipping;
                </select>
              </td>
              <td colspan=\"2\"><input type=\"text\" name=\"address_$counter_i\" value=\"$address\"></td>
              <td><input type=\"text\" name=\"shipment_source_name_$counter_i\" value=\"$shipment_source_name\"></td>
            </tr>
";
    }
}
// "確定"ボタンが押された際実施
if (isset($_POST['confirm'])) {
    // 選択された日付を代入する
    $day = getpstStrs("date_select");
    // "shipping_request"から選択された日付より前のshipping_scheduled_dayに
    // 該当するすべての値を取得する
    try {
        // データベース接続
        $db = DB::getDB();
        $sql = "SELECT *
                FROM shipping_request
                WHERE shipping_scheduled_day >= '" . $day . "'
                AND completion_flag = '0'";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $db = null;
        $stmt = null;
    } catch (PDOException $e) {
        die('エラー：' . $e->getMessage());
    }
    $counter_i = 0;
    $tbody = "";
    foreach ($result as $varr) {
        $counter_i ++;
        $goods_id = getpstStrs("goods_code_$counter_i");
        // $color = getpstStrs("color_$counter_i");
        // $picture = getpstStrs("picture_$counter_i");
        $volume = getpstStrs("volume_$counter_i");
        // $convenience_store_qr_code = getpstStrs("convenience_store_qr_code_$counter_i");
        $packing_id = getpstStrs("packing_$counter_i");
        $zip_code = getpstStrs("zip_code_$counter_i");
        $destination_name = getpstStrs("destination_name_$counter_i");
        $other_name = getpstStrs("other_name_$counter_i");
        $shipment_source = getpstStrs("shipment_source_$counter_i");
        $shipping_scheduled_day = getpstStrs("shipping_scheduled_day_$counter_i");
        // $alert = getpstStrs("alert_$counter_i");
        // $goods_name = getpstStrs("goods_name_$counter_i");
        // $size = getpstStrs("size_$counter_i");
        $shipping_id = getpstStrs("shipping_$counter_i");
        $address = getpstStrs("address_$counter_i");
        $shipment_source_name = getpstStrs("shipment_source_name_$counter_i");
        if($_SESSION['login_role'] == 1 || $_SESSION['login_role'] == 3) {
            $completion_flag = 0;
        } else {
            $completion_flag = 1;
        }
        $record_day = getpstStrs("registration_time_$counter_i");
        // DBの"m_goods"テーブルから色、商品名、サイズ、商品画像を取得する
        try {
            // データベース接続
            $db = DB::getDB();
            $sql = "UPDATE shipping_request
                    SET goods_id = :goods_id,
                        volume = :volume,
                        designated_packing_material = :packing_id,
                        zip_code = :zip_code,
                        destination_name = :destination_name,
                        other_name = :other_name,
                        shipment_source = :shipment_source,
                        shipping_scheduled_day = :shipping_scheduled_day,
                        shipping_id = :shipping_id,
                        address = :address,
                        shipment_source_name = :shipment_source_name,
                        completion_flag = :completion_flag
                    WHERE record_day = :record_day ";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':goods_id', $goods_id, PDO::PARAM_STR);
            $stmt->bindParam(':volume', $volume, PDO::PARAM_STR);
            $stmt->bindParam(':packing_id', $packing_id, PDO::PARAM_STR);
            $stmt->bindParam(':zip_code', $zip_code, PDO::PARAM_STR);
            $stmt->bindParam(':destination_name', $destination_name, PDO::PARAM_STR);
            $stmt->bindParam(':other_name', $other_name, PDO::PARAM_STR);
            $stmt->bindParam(':shipment_source', $shipment_source, PDO::PARAM_STR);
            $stmt->bindParam(':shipping_scheduled_day', $shipping_scheduled_day, PDO::PARAM_STR);
            $stmt->bindParam(':shipping_id', $shipping_id, PDO::PARAM_STR);
            $stmt->bindParam(':address', $address, PDO::PARAM_STR);
            $stmt->bindParam(':shipment_source_name', $shipment_source_name, PDO::PARAM_STR);
            $stmt->bindParam(':completion_flag', $completion_flag, PDO::PARAM_INT);
            $stmt->bindParam(':record_day', $record_day, PDO::PARAM_STR);
            $stmt->execute();
            log_message("[{$_SESSION['login_user_id']}]" . pdo_debugStrParams($stmt));
            $db = null;
            $stmt = null;
        } catch (PDOException $e) {
            log_message("[{$_SESSION['login_user_id']}]" . $e->getMessage());
            log_message("[{$_SESSION['login_user_id']}]" . pdo_debugStrParams($stmt));
            die('エラー：' . $e->getMessage());
        }
    }
    echo '更新が完了しました。';
}
?>
<!-- 以下SP -->
<main role="main">
    <div class="row mb-5 d-lg-none">
        <div class="col-12 mb-5">
            <h1 class="page_title">出荷依頼確認</h1>
            <form method="post" action="shipment_request_confirmation.php" enctype="multipart/form-data">
                <div class="d-flex mb-3">
                    <p class="d-inline-block select_owner_text ml-auto">商品所有者：</p>
                    <select class="mr-auto">
                        <?php echo $owner; ?>
                    </select>
                </div>
                <div class="d-flex mb-3">
                    <p class="d-inline-block select_owner_text ml-auto">対象日：</p>
                    <input class="mr-auto" type="date" name="date_select" value="<?php echo $day; ?>">
                </div>
                <button id="display" class="button mb-5" type="submit" name="display" value="seach">表示</button>
                <?php echo $table_sp; ?>
                <?php if(isset($_POST['display'])): ?>
                <button id="confirm" class="button" type="submit" name="confirm" value="seach">確定</button>
                <?php endif; ?>
            </form>
        </div>
    </div>
    <!-- 以上SP -->
    <div id="contents">
        <div class="inner">
            <div id="main">
                <!-- 以下PC -->
                <div class="d-none d-lg-block">
                    <h1>出荷依頼確認</h1>
                    <form method="post" action="shipment_request_confirmation.php" enctype="multipart/form-data">
                        <select>
                            <?php echo $owner; ?>
                        </select>
                        <input type="date" name="date_select" value="<?php echo $day; ?>">
                        <button id="display" type="submit" name="display" value="seach">表示</button>
                        <table border="1">
                            <caption></caption>
                            <thead class="shipment_record_table_title">
                                <tr>
                                    <th scope="col" rowspan="2">No</th>
                                    <th scope="col">商品コード</th>
                                    <th scope="col">色</th>
                                    <th class="w-150px" scope="col" rowspan="2">商品画像</th>
                                    <th scope="col">出荷数量</th>
                                    <th scope="col" rowspan="2">コンビニ出荷用バーコード</th>
                                    <th scope="col" rowspan="2">ヤマト営業所（郵便局）QRコード</th>
                                    <th scope="col" rowspan="2">指定梱包材</th>
                                    <th scope="col">郵便番号</th>
                                    <th scope="col">送付先氏名</th>
                                    <th scope="col" rowspan="2">その他氏名</th>
                                    <th scope="col">発送元住所</th>
                                    <th scope="col" rowspan="2">出荷予定日</th>
                                    <th scope="col" rowspan="2">登録時間</th>
                                    <th scope="col" rowspan="2">アラート</th>
                                </tr>
                                <tr>
                                    <th scope="col">商品名</th>
                                    <th scope="col">サイズ</th>
                                    <th scope="col">発送方法</th>
                                    <th scope="col" colspan="2">住所</th>
                                    <th scope="col">発送元氏名</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
    echo $tbody;
    ?>
                            </tbody>
                        </table>
                        <button id="confirm" type="submit" name="confirm" value="seach">確定</button>
                    </form>
                </div>
                <!-- 以上PC -->
            </div>
            <!-- /#main -->
            <div id="sub">
            </div>
            <!-- /#sub -->
        </div>
        <!-- /.inner-->
    </div>
    <!-- /#contents -->
</main>
<?php
get_footer();