<?php
require_once ('../common/function.php');
require_once ('../db/DB.php');

get_header();

// m_staff_update.phpからのPOSTを変数に代入
$owner_id = h($_SESSION['owner_id']);
$owner = h($_SESSION['owner']);
$zip_code = h($_SESSION['zip_code']);
$address_1 = h($_SESSION['address_1']);
$address_2 = h($_SESSION['address_2']);
$tel = h($_SESSION['tel']);
$fax = h($_SESSION['fax']);
$email = h($_SESSION['email']);

try {
    // データベース接続
    $db = DB::getDB();

    $sql = "UPDATE
			m_staff SET
			owner = :owner,
			zip_code = :zip_code,
			address_1 = :address_1,
			address_2 = :address_2,
			tel = :tel,
			fax = :fax,
			email = :email
			WHERE
			owner_id = :owner_id";
    $stmt = $db->prepare($sql);

    $stmt->bindParam(':owner', $owner, PDO::PARAM_STR);
    $stmt->bindParam(':zip_code', $zip_code, PDO::PARAM_STR);
    $stmt->bindParam(':address_1', $address_1, PDO::PARAM_STR);
    $stmt->bindParam(':address_2', $address_2, PDO::PARAM_STR);
    $stmt->bindParam(':tel', $tel, PDO::PARAM_STR);
    $stmt->bindParam(':fax', $fax, PDO::PARAM_STR);
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->bindParam(':owner_id', $owner_id, PDO::PARAM_STR);
    $stmt->execute();
    log_message("[{$_SESSION['login_user_id']}]" . pdo_debugStrParams($stmt));

    $db = NULL;
    echo '更新が完了しました。';
} catch (PDOException $e) {
    log_message("[{$_SESSION['login_user_id']}]" . $e->getMessage());
    log_message("[{$_SESSION['login_user_id']}]" . pdo_debugStrParams($stmt));
    die('エラー：' . $e->getMessage());
}

get_footer();