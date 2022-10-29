<?php
// 2021/08/21 issue254 submitボタンをダブルクリックした際に、エラーメッセージのみが表示される demachi
// 2021/08/21 issue253 商品登録成功時にメッセージを出す demachi
// 2021/08/05 issue236 二重送信防止のロジックが出荷依頼登録以外は実装されていない demachi
require_once ('../common/function.php');
require_once ('../db/DB.php');

// セッション開始
session_start();
// --------------------------------------------------issue253 start-------------------------------------------------------
// session_regenerate_id(true);
// --------------------------------------------------issue253 end---------------------------------------------------------

// --------------------------------------------------issue236 start-------------------------------------------------------
// トークンの確認
$is_token_valid = is_token_valid('token', 'token_in_m_goods');
if (!$is_token_valid) {
    // --------------------------------------------------issue254 start-------------------------------------------------------
    $_SESSION['message_from_m_goods_done'] = 'ダブりでの送信を検知したので１回分のみ商品登録しました。';
    header('location: ./m_goods.php');
    exit;
    // --------------------------------------------------issue254 end---------------------------------------------------------
}
// --------------------------------------------------issue236 end---------------------------------------------------------

$image_id = 1;

// m_goods.phpからのPOSTを変数に代入
$owner_id = h($_POST['owner_id']);
$goods_id = getpstStrs('goods_id');
$color_size_id = getpstStrs('color_size_id');
$goods_name = getpstStrs('goods_name');
$color = getpstStrs('color');
$size = getpstStrs('size');

try { 
    // データベース接続
    $db = DB::getDB();
    $i = 0;
    while ($goods_id[$i] !== '') {
        $image = "";
        $upload_path = "";
        // ファイルが選択されていれば$imageにファイル名を代入
        if (! empty($_FILES['picture']['name'][$i])) {
            // ファイル名をユニーク化（参照：m_goods_update_done.php）
            $image = $owner_id . $goods_id[$i] . $color_size_id[$i] . sprintf('%04d', $image_id ++);
            $image .= '.' . substr(strrchr($_FILES['picture']['name'][$i], '.'), 1); // アップロードされたファイルの拡張子を取得
            $upload_path = '../common/images/' . $owner_id . '/goods/';
            move_uploaded_file($_FILES['picture']['tmp_name'][$i], $upload_path . $image); // imagesディレクトリにファイル保存
        }
        $sql = "INSERT INTO
					m_goods (
					owner_id,
					goods_id,
                    color_size_id,
					goods_name,
					color,
					size,
					picture)
					VALUES(
					:owner_id,
					:goods_id,
                    :color_size_id,
					:goods_name,
					:color,
					:size,
					:picture)";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':owner_id', $owner_id, PDO::PARAM_STR);
        $stmt->bindParam(':goods_id', $goods_id[$i], PDO::PARAM_STR);
        $stmt->bindParam(':color_size_id', $color_size_id[$i], PDO::PARAM_STR);
        $stmt->bindParam(':goods_name', $goods_name[$i], PDO::PARAM_STR);
        if($color[$i] === ''){
            $stmt->bindValue(':color', NULL, pdo::PARAM_NULL);
        }else{
            $stmt->bindParam(':color', $color[$i], PDO::PARAM_STR);
        }
        if($size[$i] === ''){
            $stmt->bindValue(':size', NULL, pdo::PARAM_NULL);
        }else{
            $stmt->bindParam(':size', $size[$i], PDO::PARAM_STR);
            }
        if($image === ''){
            $stmt->bindValue(':picture', NULL, pdo::PARAM_NULL);
        }else{
            $stmt->bindParam(':picture', $image, PDO::PARAM_STR);
        }
        $stmt->execute();
        log_message("[{$_SESSION['login_user_id']}]" . pdo_debugStrParams($stmt));
        $i ++;
    }
    $db = NULL;
    // --------------------------------------------------issue253 start-------------------------------------------------------
    $_SESSION['message_from_m_goods_done'] = '商品登録が完了しました。';
    // --------------------------------------------------issue253 end---------------------------------------------------------
    header('location: ./m_goods.php');
    exit;
} catch (Exception $e) {
    log_message("[{$_SESSION['login_user_id']}]" . $e->getMessage());
    log_message("[{$_SESSION['login_user_id']}]" . pdo_debugStrParams($stmt));
    die('エラー：' . $e->getMessage());
}