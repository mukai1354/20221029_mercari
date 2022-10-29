<?php
require_once ('../common/function.php');
require_once ('../db/DB.php');

get_header();

// m_staff.phpからのPOSTを変数に代入
$owner_id = h($_POST['owner_id']);

try {
    // データベース接続
    $db = DB::getDB();

    $sql = "UPDATE
			m_goods SET
            deleted_flag = true
			WHERE
			owner_id = :owner_id";
    $stmt = $db->prepare($sql);

    $stmt->bindParam(':owner_id', $owner_id, PDO::PARAM_STR);
    $stmt->execute();
    log_message("[{$_SESSION['login_user_id']}]" . pdo_debugStrParams($stmt));

    $db = NULL;
    echo '削除が完了しました。';
} catch (PDOException $e) {
    log_message("[{$_SESSION['login_user_id']}]" . $e->getMessage());
    log_message("[{$_SESSION['login_user_id']}]" . pdo_debugStrParams($stmt));
    die('エラー：' . $e->getMessage());
}

get_footer();