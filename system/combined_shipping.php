<?php
require_once('../common/function.php');
require_once('../db/DB.php');

get_header();
date_default_timezone_set('Asia/Tokyo');

//デフォルトの出荷予定日（登録日の翌日、inputタグ用）
$date = date('Y-m-d', strtotime('+1 day'));

//変数の初期化
$owners = ""; //商品所有者（プルダウン用）
$owner_id = ""; //ログインしたid
$shipping_scheduled_day = ""; //出荷予定日
$login_owner_id = $_SESSION["login_owner_id"];


$ii = 0;

if (!empty($_POST["owner_id"])) {
    $owner_id = $_POST["owner_id"];
}


//商品名からあいまい検索
if (!empty($_POST["search"])) {
    try {
        $searched_goods_name = "%" . $_POST["searched_goods_name"] . "%";
        $sql = <<< EOM
SELECT
owner_id,
goods_name,
goods_id,
color_size_id,
color,
size,
picture
FROM
m_goods
WHERE
goods_name
LIKE
:searched_goods_name
AND
owner_id = :owner_id;
EOM;
        $db = DB::getDB();
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':searched_goods_name', $searched_goods_name, PDO::PARAM_STR);
        $stmt->bindParam(':owner_id', $owner_id, PDO::PARAM_STR);
        $stmt->execute();
        $search_result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $db = NULL;
        $stmt = NULL;
    } catch (Exception $e) {
        die('エラー：' . $e->getMessage());
    }
}

if (!empty($_POST["shipp"])) {
    $shipping_scheduled_day = $_POST["shipping_scheduled_day"];
}


//sessionに追加した商品のデータを代入（仮）
$_SESSION["color"] = $_POST["color"];
$posted_color = $_SESSION["color"];

$_SESSION["size"] = $_POST["size"];
$posted_size = $_SESSION["size"];

$_SESSION["volume"] = $_POST["volume"];
$posted_volume = $_SESSION["volume"];

$_SESSION["convenience_store_qr_code"] = $_POST["convenience_store_qr_code"];
$posted_convenience_store_qr_code = $_SESSION["convenience_store_qr_code"];

$_SESSION["other_qr_code"] = $_POST["other_qr_code"];
$posted_other_qr_code = $_SESSION["other_qr_code"];

$_SESSION["zip_code"] = $_POST["zip_code"];
$posted_zip_code = $_SESSION["zip_code"];

$_SESSION["destination_name"] = $_POST["destination_name"];
$posted_destination_name = $_SESSION["destination_name"];

$_SESSION["shipment_source_name"] = $_POST["shipment_source_name"];
$posted_shipment_source_name = $_SESSION["shipment_source_name"];

$_SESSION["remarks"] = $_POST["remarks"];
$posted_remarks = $_SESSION["remarks"];

$_SESSION[""] = $_POST[""];
$posted_ = $_SESSION[""];

$_SESSION[""] = $_POST[""];
$posted_ = $_SESSION[""];

$_SESSION[""] = $_POST[""];
$posted_ = $_SESSION[""];

$_SESSION[""] = $_POST[""];
$posted_ = $_SESSION[""];

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




//商品所有者情報取得
try {
    $db = DB::getDB();
    $stmt = $db->prepare("SELECT owner_id, owner FROM m_staff");
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($result as $varr) {
        $owners .= "\t\t\t\t\t<option value=\"" . $varr['owner_id'] . "\"";
        if ($varr['owner_id'] == $login_owner_id) {
            $owners .= " selected";
        }
        $owners .= ">" . $varr['owner'] . "</option>\n";
    }
    $db = NULL;
    $stmt = NULL;
} catch (PDOException $e) {
    die('エラー：' . $e->getMessage());
}



?>
<div id="contents">
    <div class="inner">
        <div id="main">
            <main role="main" class="mb-5">
                <h1 class="mt-5">出荷依頼登録（同梱）</h1>
                <form action="combined_shipping.php" method="post">
                    <div class="mb-5">
                        <?php if ($_SESSION["login_role"] === "1" || $_SESSION["login_role"] === "5") : ?>
                            <input type="hidden" name="owner_id" value="<?php echo $_SESSION["login_owner_id"] ?>">
                        <?php else : ?>
                            <label>商品所有者：<select name="owner_id"><?php echo $owners; ?></select></label>
                        <?php endif; ?>
                        <label class="ml-auto">出荷予定日<input type="date" name="shipping_scheduled_day" value="<?php echo $date; ?>"></label>
                        <label class="mx-5">商品名:<input type="text" name="searched_goods_name"></label>
                        <input class="mr-auto" type="submit" name="search" value="検索">
                    </div>
                    <!-- 検索ボタン押下時 -->
                    <?php if (!empty($_POST["search"])) : ?>
                        <div>
                            <label>商品：
                                <select id="select_goods" class="w-20rem" name="select_goods">
                                    <!-- あいまい検索で取得したデータを変数に代入 -->
                                    <?php foreach ($search_result as $result_items) : ?>
                                        <?php
                                        $result_owner_id = $result_items["owner_id"];
                                        $result_goods_name = $result_items["goods_name"];
                                        $result_goods_id = $result_items["goods_id"];
                                        $result_color_size_id = $result_items["color_size_id"];
                                        $result_color = $result_items["color"];
                                        $result_size = $result_items["size"];
                                        $result_picture = $result_items["picture"];
                                        ?>
                                        <option value="<?php echo $result_owner_id . ',' . $result_goods_name . ',' . $result_goods_id . ',' . $result_color_size_id . ',' . $result_color . ',' . $result_size . ',' . $result_picture; ?>"><?php echo $result_goods_name . "：" . $result_color . "：" . $result_size; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <button id="decision" type="button">決定</button>
                        </div>
                    <?php endif; ?>
                    <table class="table_scroll border-0">
                        <thead class="shipment_record_table_title">
                            <tr>
                                <th class="px-2 align-middle" rowspan="2">削除</th>
                                <th class="px-2 align-middle" rowspan="2">No.</th>
                                <th class="px-2 align-middle">商品：色：サイズ</th>
                                <th class="px-2 align-middle">色</th>
                                <th class="px-2 align-middle">出荷数量</th>
                                <th class="px-2 align-middle" rowspan="2">商品画像</th>
                                <th class="px-2 align-middle" rowspan="2">コンビニ出荷用バーコード</th>
                                <th class="px-2 align-middle" rowspan="2">ヤマト営業所（郵便局）QRコード</th>
                                <th class="px-2 align-middle">郵便番号</th>
                                <th class="px-2 align-middle">送付先氏名</th>
                                <th class="px-2 align-middle">発送元氏名</th>
                                <th class="px-2 align-middle" rowspan="2">備考欄</th>
                            </tr>
                            <tr>
                                <th class="px-2 align-middle">商品名</th>
                                <th class="px-2 align-middle">サイズ</th>
                                <th class="px-2 align-middle">配送プラン</th>
                                <th class="px-2 align-middle">住所</th>
                                <th class="px-2 align-middle">発送元住所</th>
                                <th class="px-2 align-middle">その他氏名</th>
                            </tr>
                        </thead>
                        <tbody>
                            <input type="hidden" name="combined_shipping_flag" value="1">
                            <input type="hidden" name="goods_id[]">
                            <tr class="tr-even">
                                <td class="px-2 align-middle" rowspan="2"><input class="delete" type="checkbox"></td><!-- 削除 -->
                                <td class="px-2 align-middle" rowspan="2"><?php echo $jj + 1 ?></td><!-- No. -->
                                <th class="px-2 align-middle py-2"><input type="text" size="25"></th><!-- 商品：色：サイズ -->
                                <td class="px-2 align-middle py-2"><input type="text" name="color[]" value=""></td><!-- 色 -->
                                <td class="px-2 align-middle py-2"><input type="number" name="volume[]" min="1" value="1" size="4"></td><!-- 出荷数量 -->
                                <td class="px-2 align-middle" rowspan="2"><img src="/mercari/common/images/1000/goods/10000005010001.jpg"></td><!-- 商品画像 -->
                                <td class="px-2 align-middle" rowspan="2">
                                    <div class="user-icon-dnd-wrapper">
                                        <input type="file" name="convenience_store_qr_code[]" class="input_file" accept="image/*">
                                        <div class="preview_field"></div>
                                        <div class="drop_area">
                                            <p class="drag_and_drop">drag and drop</p>
                                        </div>
                                        <div class="icon_clear_button"></div>
                                    </div>
                                </td><!-- コンビニ出荷用バーコード -->
                                <td class="px-2 align-middle" rowspan="2">
                                    <div class="user-icon-dnd-wrapper">
                                        <input type="file" name="other_qr_code[]" class="input_file" accept="image/*">
                                        <div class="preview_field"></div>
                                        <div class="drop_area">
                                            <p class="drag_and_drop">drag and drop</p>
                                        </div>
                                        <div class="icon_clear_button"></div>
                                    </div>
                                </td><!-- ヤマト営業所（郵便局）QRコード -->
                                <td class="px-2 align-middle py-2"><input type="text" name="zip_code[]" size="9" maxlength="9"></td><!-- 郵便番号 -->
                                <td class="px-2 align-middle py-2"><input type="text" name="destination_name[]"></td><!-- 送付先氏名 -->
                                <td class="px-2 align-middle py-2"><input type="text" name="shipment_source_name[]"></td><!-- 発送元氏名 -->
                                <td class="px-2 align-middle" rowspan="2"><textarea name="remarks[]" rows="3" cols="15"></textarea></td><!-- 備考欄 -->
                            </tr>
                            <tr class="tr-even2">
                                <td class="px-2 align-middle py-2"><input type="text" name="goods_name[]" value=""></td><!-- 商品名 -->
                                <td class="px-2 align-middle py-2"><input type="text" name="size[]" value=""></td><!-- サイズ -->
                                <td class="px-2 align-middle">
                                    <select name="delivery_plan">
                                        <?php foreach ($shipping_method as $key => $plan) : ?>
                                            <option value="<?php echo $plan; ?>"><?php echo $plan; ?>
                                            <option>
                                            <?php endforeach; ?>
                                    </select>
                                </td><!-- 配送プラン -->
                                <td class="px-2 align-middle py-2"><textarea rows="3" cols="15"></textarea></td><!-- 住所 -->
                                <td class="px-2 align-middle py-2"><textarea rows="3" cols="15"></textarea></td><!-- 発送元住所 -->
                                <td class="px-2 align-middle py-2"><input type="text" name="" value=""></td><!-- その他氏名 -->
                            </tr>
                        </tbody>
                    </table>
                </form>
            </main>
        </div>
    </div>
</div>
<script>
    //商品選択時、tbody閉じタグ直前に選択した商品の行を追加
    //商品横決定ボタン
    let decision = document.getElementById("decision");
    //決定ボタンクリック時に以下の処理を行う
    decision.addEventListener("click", function() {
        //select内の選択されているoptionのvalueを取得
        let item = document.getElementById("select_goods").value;
        //valueの値（”1000”,"ワンピース","0001"・・・）をコンマごとに分解・配列化して再代入
        item = item.split(",");
        //各変数に配列の値を代入
        let ownerId = item[0];
        let goodsName = item[1];
        let goodsId = item[2];
        let colorSizeId = item[3];
        let color = item[4];
        let size = item[5];
        let picture = item[6];
        //各変数を適用した行を追加する
        //
        //

    })

    //削除ボタンを押したときにチェックされた行を削除
</script>
<?php
get_footer();
