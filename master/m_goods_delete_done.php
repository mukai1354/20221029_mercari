<?php
require_once ('../common/function.php');
require_once ('../db/DB.php');

get_header();

// m_goods_update.phpからのPOSTを変数に代入
$row_index = h($_POST['checkbox_index']);
$owner_id = h($_POST['owner_id']);
$goods_id = h($_POST['goods_id']);
$color_size_id = h($_POST['color_size_id']);

try {
    $db = DB::getDB();
    $sql = "UPDATE
			m_goods SET
            deleted_flag = true
			WHERE
			owner_id = :owner_id AND
            goods_id = :goods_id AND
            color_size_id = :color_size_id";
    foreach ($row_index as $index) {
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':owner_id', $owner_id[$index], PDO::PARAM_STR);
        $stmt->bindParam(':goods_id', $goods_id[$index], PDO::PARAM_STR);
        $stmt->bindParam(':color_size_id', $color_size_id[$index], PDO::PARAM_STR);
        $stmt->execute();
        log_message("[{$_SESSION['login_user_id']}]" . pdo_debugStrParams($stmt));
    }
    $db = NULL;
    echo '削除が完了しました。';
} catch (PDOException $e) {
    log_message("[{$_SESSION['login_user_id']}]" . $e->getMessage());
    log_message("[{$_SESSION['login_user_id']}]" . pdo_debugStrParams($stmt));
    die('エラー：' . $e->getMessage());
}

get_footer();