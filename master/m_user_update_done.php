<?php
require_once ('../common/function.php');
require_once ('../db/DB.php');

get_header();

// m_user_update_check.phpからのPOSTを変数に代入
$user_id = h($_SESSION['user_id']);
$user_name = h($_SESSION['name']);
$role = h($_SESSION['role']);
$owner_id = h($_SESSION['owner_id']);
$now_password = h($_SESSION['now_password']);
$next_password = h($_SESSION['next_password']);

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
			user_name = :name,
			role = :role,
			owner_id = :owner_id,
            user_password = :next_password
			WHERE
			user_id = :user_id";

        $stmt = $db->prepare($sql);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_STR);
        $stmt->bindParam(':name', $user_name, PDO::PARAM_STR);
        $stmt->bindParam(':role', $role, PDO::PARAM_STR);
        if ($owner_id === '') {
            $stmt->bindValue('owner_id', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam(':owner_id', $owner_id, PDO::PARAM_STR);
        }
        $update_password = "";
        if (empty($next_password)) {
            $update_password = strechedPassword($now_password);
        } else {
            $update_password = strechedPassword($next_password);
        }
        $stmt->bindParam(':next_password', $update_password, PDO::PARAM_STR);
        $stmt->execute();
        log_message("[{$_SESSION['login_user_id']}]" . pdo_debugStrParams($stmt));

        $db = NULL;
        echo '更新が完了しました。';
    } else {
        echo "パスワードが違います。<br>" . "<a href=\"" . get_home_url('/master/m_user_update.php') . "\">戻る</a>";
    }
} catch (PDOException $e) {
    log_message("[{$_SESSION['login_user_id']}]" . $e->getMessage());
    log_message("[{$_SESSION['login_user_id']}]" . pdo_debugStrParams($stmt));
    die('エラー：' . $e->getMessage());
}

get_footer();