<?php
require_once ('../common/function.php');
require_once ('../db/DB.php');

get_header();

// m_goods_update_check.phpからのPOSTを変数に代入
$owner_id = h($_POST['owner_id']);
$goods_id = h($_POST['goods_id']);
$color_size_id = h($_POST['color_size_id']);
$goods_name = h($_POST['goods_name']);
$color = h($_POST['color']);
$size = h($_POST['size']);
$image = $_FILES['picture'];
$saved_image = h($_POST['saved_image']);
$image_clear = h($_POST['icon_clear']);

$file_name = "";
$file_upload_success = true;
/*
 * 保存済みの画像を削除する
 * 1)保存されている画像があり、新規に画像をアップロードするとき
 * 2)保存されている画像があり、画像削除アイコンをクリックしているとき
 */
if (($saved_image != "" && $image["name"] != "") || ($saved_image != "" && $image_clear == "true")) {
    $file_upload_success = unlink("../common/images/" . $owner_id . '/goods/' . $saved_image);
} /*
   * 保存済みの画像のまま
   * 1)保存されている画像があり、新規にアップロードする画像が入力されていないとき
   */
else if ($saved_image != "" && $image["name"] == "") {
    $file_name = $saved_image;
}
/*
 * 新規に画像をアップロードする
 */
if ($image["name"] != "") {
    // ファイル名をユニーク化（参照：m_goods_done.php）して画像アップロード
    $file_name = $owner_id . $goods_id . $color_size_id . sprintf('%04d', 1);
    $file_name .= '.' . substr(strrchr($_FILES['picture']['name'], '.'), 1); // アップロードされたファイルの拡張子を取得
    $file_upload_success = move_uploaded_file($image['tmp_name'], '../common/images/' . $owner_id . '/goods/' . $file_name);
}
if ($file_upload_success) {
    try {
        // データベース接続
        $db = DB::getDB();
        $sql = "UPDATE m_goods
                SET
					goods_name = :goods_name,
					color = :color,
					size = :size,
					picture = :picture
			     WHERE
					owner_id = :owner_id AND
                    goods_id = :goods_id AND
                    color_size_id = :color_size_id";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':owner_id', $owner_id, PDO::PARAM_STR);
        $stmt->bindParam(':goods_id', $goods_id, PDO::PARAM_STR);
        $stmt->bindParam(':color_size_id', $color_size_id, PDO::PARAM_STR);
        $stmt->bindParam(':goods_name', $goods_name, PDO::PARAM_STR);
        if($color === ''){
            $stmt->bindValue(':color', NULL, pdo::PARAM_NULL);
        }else{
            $stmt->bindParam(':color', $color, PDO::PARAM_STR);
        }
        if($size === ''){
            $stmt->bindValue(':size', NULL, pdo::PARAM_NULL);
        }else{
            $stmt->bindParam(':size', $size, PDO::PARAM_STR);
            }
        if($file_name === ''){
            $stmt->bindValue(':picture', NULL, pdo::PARAM_NULL);
        }else{
            $stmt->bindParam(':picture', $file_name, PDO::PARAM_STR);
        }
        $stmt->execute();
        log_message("[{$_SESSION['login_user_id']}]" . pdo_debugStrParams($stmt));
        $db = NULL;
        echo '更新が完了しました。';
    } catch (Exception $e) {
        log_message("[{$_SESSION['login_user_id']}]" . $e->getMessage());
        log_message("[{$_SESSION['login_user_id']}]" . pdo_debugStrParams($stmt));
        $file_upload_success = false;
        die('エラー：' . $e->getMessage());
    }
}
if (! $file_upload_success) {
    echo '更新が失敗しました。';
}

get_footer();