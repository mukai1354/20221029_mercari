<?php
require_once('../common/function.php');
require_once('../db/DB.php');

get_header();

// m_user_update_check.phpからのPOSTを変数に代入
$user_id = h($_SESSION['user_id']);
$user_password = strechedPassword(h($_SESSION['password']));
$user_name = h($_SESSION['name']);
$role = h($_SESSION['role']);
$owner_id = h($_SESSION['owner_id']);

try {
    // データベース接続
    $db = DB::getDB();
    $sql = "SELECT " . "COUNT(*) AS `cnt`" . "FROM " . "`m_user` " . " WHERE  " . "`user_id` = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute(array(
        $user_id
    ));
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    // print_r( $result );
    if ($result['cnt'] == 0) {
        $sql = "INSERT INTO
			m_user (
			user_id,
			user_password,
			user_name,
			role,
			owner_id)
			VALUES(
			:user_id,
			:user_password,
			:user_name,
			:role,
			:owner_id)";
        $stmt = $db->prepare($sql);

        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_STR);
        $stmt->bindParam(':user_password', $user_password, PDO::PARAM_STR);
        $stmt->bindParam(':user_name', $user_name, PDO::PARAM_STR);
        $stmt->bindParam(':role', $role, PDO::PARAM_STR);
        if ($owner_id === '') {
            $stmt->bindValue('owner_id', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam(':owner_id', $owner_id, PDO::PARAM_STR);
        }
        $stmt->execute();
        log_message("[{$_SESSION['login_user_id']}]" . pdo_debugStrParams($stmt));

        $db = null;
        echo '登録が完了しました。';
    } else {
        echo "ユーザーIDが重複しています。<br>" . "<a href=\"" . get_home_url('/master/m_user.php') . "\">戻る</a>";
    }
} catch (PDOException $e) {
    log_message("[{$_SESSION['login_user_id']}]" . $e->getMessage());
    log_message("[{$_SESSION['login_user_id']}]" . pdo_debugStrParams($stmt));
    die('エラー：' . $e->getMessage());
}

get_footer();
