<?php
require_once ('../common/function.php');
require_once ('../db/DB.php');

get_header();

// m_user_update.phpからのPOSTを変数に代入
$user_id = h($_POST['user_id']);
$now_password = h($_POST['now_password']);

try {
    // データベース接続
    $db = DB::getDB();
    $sql = "SELECT " . "COUNT(*) AS `cnt`, user_password " . " FROM " . "`m_user` " . "WHERE  " . "`user_id` = ?";
    $sql .= " GROUP BY user_password";
    $stmt = $db->prepare($sql);
    $stmt->execute(array(
        $user_id
    ));
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $db_password = $result['user_password'];
    if ($result['cnt'] == 1 && password_verify($now_password, $db_password)) {
        $sql = "UPDATE
			m_user SET
            deleted_flag = true
			WHERE
			user_id = :user_id";

        $stmt = $db->prepare($sql);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_STR);
        $stmt->execute();
        log_message("[{$_SESSION['login_user_id']}]" . pdo_debugStrParams($stmt));
        $db = NULL;
        echo '削除が完了しました。';
    } else {
        echo "パスワードが違います。<br> " . "<a href=\"" . get_home_url('/master/m_user_update.php') . "\">戻る</a>";
    }
} catch (PDOException $e) {
    log_message("[{$_SESSION['login_user_id']}]" . $e->getMessage());
    log_message("[{$_SESSION['login_user_id']}]" . pdo_debugStrParams($stmt));
    die('エラー：' . $e->getMessage());
}

get_footer();