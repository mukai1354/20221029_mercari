<?php
// 2021/08/21 issue253 商品登録成功時にメッセージを出す demachi
// 2021/08/05 issue236 二重送信防止のロジックが出荷依頼登録以外は実装されていない demachi
require_once('../common/function.php');
require_once('../db/DB.php');

get_header();

// エラー情報リセット
$_SESSION["error_status"] = E_STATUS_DEFAULT;

// --------------------------------------------------issue253 start-------------------------------------------------------
// m_goods_doneからのメッセージを表示
if(isset($_SESSION['message_from_m_goods_done'])){
    echo '<p style=\"font-weght: bold; font-size: 20px; color: red;\">' . $_SESSION['message_from_m_goods_done'] . '</p>';
    unset($_SESSION['message_from_m_goods_done']);
}
// --------------------------------------------------issue253 end---------------------------------------------------------

// --------------------------------------------------issue236 start-------------------------------------------------------
// トークンの発行
$token = get_csrf_token();
//トークンをセッション変数にセット
$_SESSION["token_in_m_goods"] = $token;
// --------------------------------------------------issue236 end---------------------------------------------------------

$owner_id = h($_SESSION['login_owner_id']);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $owner_id = h($_POST['owner_id']);
}

$login_role = h($_SESSION['login_role']);

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
$owner = "";
foreach ($result_staff as $varr) {
    if ($owner_id == $varr['owner_id']) {
        $owner .= "\t\t\t\t\t<option value=\"{$varr['owner_id']}\" selected>{$varr['owner']}</option>\n";
    } else {
        $owner .= "\t\t\t\t\t<option value=\"{$varr['owner_id']}\">{$varr['owner']}</option>\n";
    }
}

$existing_max_record_goods_id = "";
$new_goods_id = "";
$new_color_size_id = "";
try {
    // データベース接続
    $db = DB::getDB();
    //SKUマスタから、選択された商品所有者の商品の中で、最も商品名コードが大きいレコードを1件だけ取得
    $stmt = $db->prepare("SELECT goods_id
                FROM m_goods
                WHERE owner_id = :owner_id
                ORDER BY goods_id DESC
                LIMIT 1");
    $stmt->bindParam(':owner_id', $owner_id, PDO::PARAM_STR);
    $stmt->execute();
    $result_goods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $db = null;
    $stmt = null;
} catch (PDOException $e) {
    die('エラー：' . $e->getMessage());
}
//既に登録された商品があれば、最も商品名コードが大きいレコードが選択されているので
//そのレコードの商品名コードを変数に代入
if (count($result_goods) == 1) {
    $existing_max_record_goods_id = $result_goods[0]["goods_id"];
} else {
    $existing_max_record_goods_id = "0000";
}
$new_goods_id = str_pad((int)$existing_max_record_goods_id + 1, 4, 0, STR_PAD_LEFT);
$new_color_size_id = "01";
?>
<link href="../common/css/drag_and_drop.css" rel="stylesheet">
<script type="text/javascript" src="../common/js/drag_and_drop.js"></script>
<!-- 以下SP -->
<main>
    <div class="d-lg-none">
        <div class="row">
            <div class="col">
                <h1 class="page_title">商品情報登録</h1>
                <!-- 商品所有者選択 -->
                <?php if($login_role == 1 || $login_role == 5): ?>
                <?php else: ?>
                <div class="select_owner_wrapper">
                    <form action="m_goods.php" method="post">
                        <div class="d-flex justify-content-around">
                            <label class="mb-3">商品所有者：&ensp;
                                <select class="m-1" name="owner_id">
                                    <?php echo $owner; ?>
                                </select>
                            </label>
                        </div>
                        <div class="row">
                            <div class="col mx-auto">
                                <input class="button" type="submit" name="change_owner" value="商品所有者変更">
                            </div>
                        </div>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php if($login_role =="1" || $login_role =="5" || !empty($_POST["change_owner"])): ?>
        <div id="button_wrapper" class="row">
            <div class="col-12">
                <div class="button_wrapper">
                    <h2 class="h6 text-center mb-3">商品情報登録</h2>
                    <div class="row">
                        <div class="col-6">
                            <form action="m_goods_modal.php" method="post" target="_blank">
                                <input type="hidden" name="owner_id" value="<?php echo $owner_id;?>">
                                <button type="submit" class="submit button">既存商品検索</button>
                            </form>
                        </div>
                        <div class="col-6">
                            <button type="submit" class="submit button" form="registration_sp">登録</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col">
            <?php if($owner_id): ?>
                <form id="registration_sp" action="m_goods_done.php" method="post" enctype="multipart/form-data">
                    <!-- ------------------------------------------issue236 start-------------------------------------------- -->
                    <input type="hidden" name="token" value="<?php echo $token;?>">
                    <!-- ------------------------------------------issue236 end-------------------------------------------- -->
                    <input type="hidden" name="owner_id" value="<?php echo $owner_id;?>">
                    <table id="original_sp" class="table">
                        <caption class="caption">No.&ensp;1</caption>
                        <tbody>
                          <!-- 商品コード渡す -->
                          <input type="hidden" class="goods_id" name="goods_id[]" value="<?php echo $new_goods_id; ?>">
                          <!-- 色サイズコード渡す -->
                          <input type="hidden" class="color_size_id" name="color_size_id[]" value="<?php echo $new_color_size_id; ?>">
                            <!-- <tr class="sp_column_name">
                                <th class="table_cell_half">商品名コード</th>
                                <th class="table_cell_half">色サイズコード</th>
                            </tr> -->
                            <!-- <tr>
                                <td class="table_cell_half"><input type="text" class="goods_id table_input" name="goods_id[]" value="<?php echo $new_goods_id; ?>" readonly="readonly"></td>
                                <td class="table_cell_half"><input type="text" class="color_size_id table_input" name="color_size_id[]" value="<?php echo $new_color_size_id; ?>" readonly="readonly"></td>
                            </tr> -->
                            <tr class="sp_column_name">
                                <th class="required" colspan="2">商品名&ensp;<span class="required_message">※必須</span></th>
                            </tr>
                            <tr>
                                <td colspan="2"><input type="text" class="goods_name table_input" name="goods_name[]" required></td>
                            </tr>
                            <tr class="sp_column_name">
                                <th class="table_cell_half">色</th>
                                <th class="table_cell_half">サイズ</th>
                            </tr>
                            <tr>
                                <td class="table_cell_half"><input type="text" class="color table_input" name="color[]"></td>
                                <td class="table_cell_half"><input type="text" class="size table_input" name="size[]"></td>
                            </tr>
                            <tr class="sp_column_name">
                                <th colspan="2">商品画像</th>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    <div class="user-icon-dnd-wrapper">
                                        <input type="file" name="picture[]" class="input_file" accept="image/*">
                                        <div class="preview_field"></div>
                                        <div class="drop_area">
                                            <p class="drag_and_drop">drag and drop</p>
                                        </div>
                                        <div class="icon_clear_button"></div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <!-- 以下繰り返し -->
                    <?php for ($i = 2; $i <= 6; $i++): ?>
                    <table class="table sp_table_even">
                        <caption class="caption">No.&ensp;
                            <?php echo $i; ?>
                        </caption>
                        <tbody>
                            <input type="hidden" class="goods_id table_input" name="goods_id[]">
                            <input type="hidden" class="color_size_id table_input" name="color_size_id[]">
                            <!-- <tr class="sp_column_name">
                                <th class="table_cell_half">商品名コード</th>
                                <th class="table_cell_half">色サイズコード</th>
                            </tr> -->

<!--                             <tr class="first_row"> -->
                                <!-- 商品名コード渡す -->
                                <!-- 色サイズコード渡す -->
                                <!-- <td class="table_cell_half"><input type="text" class="goods_id table_input" name="goods_id[]" readonly="readonly"></td>
                                <td class="table_cell_half"><input type="text" class="color_size_id table_input" name="color_size_id[]" readonly="readonly"></td> -->
<!--                             </tr> -->
                            <tr>
                                <td class="table_cell_half">
                                    <button type="button" class="same_sp button">同一商品</button>
                                </td>
                                <td class="table_cell_half">
                                    <button type="button" class="cancel_sp button">取消</button>
                                </td>
                            </tr>
                            <tr class="sp_column_name">
                                <th class="required" colspan="2">商品名&ensp;<span class="required_message">※必須</span></th>
                            </tr>
                            <tr class="second_row">
                                <td colspan="2"><input type="text" class="goods_name table_input" name="goods_name[]"></td>
                            </tr>
                            <tr class="sp_column_name">
                                <th class="table_cell_half">色</th>
                                <th class="table_cell_half">サイズ</th>
                            </tr>
                            <tr class="third_row">
                                <td class="table_cell_half"><input type="text" class="color table_input" name="color[]"></td>
                                <td class="table_cell_half"><input type="text" class="size table_input" name="size[]"></td>
                            </tr>
                            <tr class="sp_column_name">
                                <th colspan="2">商品画像</th>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    <div class="user-icon-dnd-wrapper">
                                        <input type="file" name="picture[]" class="input_file" accept="image/*">
                                        <div class="preview_field"></div>
                                        <div class="drop_area">
                                            <p class="drag_and_drop">drag and drop</p>
                                        </div>
                                        <div class="icon_clear_button"></div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <?php endfor; ?>
                    <!-- 以上繰り返し -->
                </form>
                <?php endif; ?>
                <input type="hidden" id="inserted_goods_id_by_modal_sp">
                <input type="hidden" id="inserted_color_size_id_by_modal_sp">
                <input type="hidden" id="inserted_goods_name_by_modal_sp">
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- 以上SP -->

















    <!-- 以下PC -->
    <div id="contents" class="col-12 mx-auto d-none d-lg-block">
        <div class="inner">
            <div id="main">
                <h1 class="mt-5">商品情報登録</h1>
                <?php if($login_role == 1): ?>
                <?php else: ?>
                <form action="m_goods.php" method="post">
                    <select name="owner_id">
                        <?php echo $owner; ?>
                    </select>
                    <button class="submit ml-5" type="submit" name="change_owner" value="change_owner">商品所有者変更</button><br><br>
          <!-- <textarea id=" bulk_insert_text" rows="4" cols="40" placeholder="商品データを入力してください"></textarea>
                        <button type="button" id="bulk_insert">一括読込</button> -->
                </form>
                <?php endif; ?>
                <?php if($login_role =="1" || $login_role =="5" || !empty($_POST["change_owner"])): ?>
                <div class="d-flex mb-3">
                    <form action="m_goods_modal.php" method="post" target="_blank">
                        <input type="hidden" name="owner_id" value="<?php echo $owner_id;?>">
                        <button type="submit" class="submit">既存商品検索</button>
                    </form>
                    <button type="submit" class="submit ml-5" form="registration">登録</button>
                </div>
                <table class="border-0 m_goods_table">
                    <thead class="shipment_record_table_title">
                        <tr>
                            <th class="w-4rem" scope="col">No.</th>
                            <th scope="col">商品名コード</th>
                            <th scope="col">色サイズコード</th>
                            <th scope="col">商品名</th>
                            <th scope="col">色</th>
                            <th scope="col">サイズ</th>
                            <th scope="col">商品画像</th>
                        </tr>
                    </thead>
                    <tbody>
                        <form id="registration" action="m_goods_done.php" method="post" enctype="multipart/form-data">
                            <!-- ------------------------------------------issue236 start-------------------------------------------- -->
                            <input type="hidden" name="token" value="<?php echo $token;?>">
                            <!-- ------------------------------------------issue236 end-------------------------------------------- -->
                            <input type="hidden" name="owner_id" value="<?php echo $owner_id;?>">
                            <tr id="original">
                                <td class="px-2 mx-0">1</td>
                                <td class="px-2"><input type="text" class="goods_id input" name="goods_id[]" value="<?php echo $new_goods_id; ?>" readonly="readonly">
                                </td>
                                <td class="px-2"><input type="text" class="color_size_id input" name="color_size_id[]" value="<?php echo $new_color_size_id; ?>" readonly="readonly"></td>
                                <td class="px-2"><input type="text" class="goods_name input" name="goods_name[]" required></td>
                                <td class="px-2"><input type="text" class="color input" name="color[]"></td>
                                <td class="px-2"><input type="text" class="size input" name="size[]"></td>
                                <td class="px-2">
                                    <div class="user-icon-dnd-wrapper my-2 mx-auto">
                                        <input type="file" name="picture[]" class="input_file input" accept="image/*">
                                        <div class="preview_field"></div>
                                        <div class="drop_area">drag and drop</div>
                                        <div class="icon_clear_button"></div>
                                    </div>
                                </td>
                            </tr>
                            <?php for ($i = 2; $i <= 10; $i++): ?>
                            <tr class="tr-even">
                                <td class="px-2">
                                    <?php echo $i; ?>
                                </td>
                                <td class="px-2 pb-2"><input type="text" class="goods_id input" name="goods_id[]" readonly="readonly"><br>
                                    <div>
                                        <button type="button" class="same">同一商品</button>
                                        <button type="button" class="cancel">取消</button>
                                    </div>
                                </td>
                                <td class="px-2 pb-2"><input type="text" class="color_size_id input" name="color_size_id[]" readonly="readonly"></td>
                                <td class="px-2 pb-2"><input type="text" class="goods_name input" name="goods_name[]" readonly="readonly"></td>
                                <td class="px-2 pb-2"><input type="text" class="color input" name="color[]"></td>
                                <td class="px-2 pb-2"><input type="text" class="size input" name="size[]"></td>
                                <td class="px-2 pb-2">
                                    <div class="user-icon-dnd-wrapper mt-2 mx-auto">
                                        <input type="file" name="picture[]" class="input_file" accept="image/*">
                                        <div class="preview_field"></div>
                                        <div class="drop_area">drag and drop</div>
                                        <div class="icon_clear_button"></div>
                                    </div>
                                </td>
                            </tr>
                            <?php endfor; ?>
                        </form>
                    </tbody>
                </table>
                <!-- あいまい検索からのデータを格納する場所 -->
                <input type="hidden" id="inserted_goods_id_by_modal">
                <input type="hidden" id="inserted_color_size_id_by_modal">
                <input type="hidden" id="inserted_goods_name_by_modal">
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
<script>
$(document).ready(function() {

    // テーブルの上の行の「商品名コード」をコピー
    // テーブルの上の行の「色サイズコ―ド」をコピーして1加算
    // テーブルの上の行の「商品名」をコピー
    $('button.same').on('click', function() {
        var goods_id = $(this).closest("tr").prev("tr").find(".goods_id").val();
        var color_size_id = $(this).closest("tr").prev("tr").find(".color_size_id").val();
        var goods_name = $(this).closest("tr").prev("tr").find(".goods_name").val();
        color_size_id = Number(color_size_id) + 1;
        $(this).closest("tr").find(".goods_id").val(goods_id);
        $(this).closest("tr").find(".color_size_id").val(getZeroPadding(color_size_id, 2));
        $(this).closest("tr").find(".goods_name").val(goods_name);
    });
    // ボタンがクリックされた行のデータをクリア
    $('button.cancel').on('click', function() {
        $(this).closest("tr").find(".goods_id").val("");
        $(this).closest("tr").find(".color_size_id").val("");
        $(this).closest("tr").find(".goods_name").val("");
        $(this).closest("tr").find(".color").val("");
        $(this).closest("tr").find(".size").val("");
        $(this).closest("tr").find(".icon_clear_button").click();
    });
    // 既存商品検索用ウィンドウから、changeイベントを発火させて、テーブルに各値を入れる
    $('#inserted_goods_name_by_modal').change(function() {
        let inserted_goods_id = $("#inserted_goods_id_by_modal").val();
        let inserted_color_size_id = $("#inserted_color_size_id_by_modal").val();
        let inserted_goods_name = $("#inserted_goods_name_by_modal").val();
        let new_color_size_id = getZeroPadding(Number(inserted_color_size_id) + 1, 2);
        $("#original").find(".goods_id").val(inserted_goods_id);
        $("#original").find(".color_size_id").val(new_color_size_id);
        $("#original").find(".goods_name").val(inserted_goods_name);
    });


//以上PC

//以下SP
//同一商品
    $('button.same_sp').on('click', function() {
        var goods_id = $(this).parents('table.table').prev('table.table').find('.goods_id').val();
        var color_size_id = $(this).parents('table.table').prev('table.table').find('.color_size_id').val();
        var goods_name = $(this).parents('table.table').prev('table.table').find('.goods_name').val();
        color_size_id = Number(color_size_id) + 1;
        $(this).parents('table').find('.goods_id').val(goods_id);
        $(this).parents('table').find('.color_size_id').val(getZeroPadding(color_size_id, 2));
        $(this).parents('tr').nextAll('.second_row').find('.goods_name').val(goods_name);
    });

    $('button.cancel_sp').on('click', function() {
        $(this).parents('table.table').find('.goods_id').val('');
        $(this).parents('table.table').find('.color_size_id').val('');
        $(this).parents('table.table').find('.goods_name').val('');
        $(this).parents('table.table').find('.color').val('');
        $(this).parents('table.table').find('.size').val('');
        $(this).parents('table.table').find('icon_clear_button').click();
    });

 // 既存商品検索用ウィンドウから、changeイベントを発火させて、テーブルに各値を入れる
    $('#inserted_goods_name_by_modal_sp').change(function() {
        let inserted_goods_id = $("#inserted_goods_id_by_modal_sp").val();
        let inserted_color_size_id = $("#inserted_color_size_id_by_modal_sp").val();
        let inserted_goods_name = $("#inserted_goods_name_by_modal_sp").val();
        let new_color_size_id = getZeroPadding(Number(inserted_color_size_id) + 1, 2);
        $("#original_sp").find(".goods_id").val(inserted_goods_id);
        $("#original_sp").find(".color_size_id").val(new_color_size_id);
        $("#original_sp").find(".goods_name").val(inserted_goods_name);
    });
});
</script>
<?php

get_footer();