<?php
require_once ('../common/function.php');
require_once ('../db/DB.php');

get_header();

$posted_owner_ids = h($_POST['owner_id']);
$posted_goods_ids = h($_POST['goods_id']);
$posted_color_size_ids = h($_POST['color_size_id']);
$posted_row_indexes = h($_POST['checkbox_index']);

try {
    // データベース接続
    $db = DB::getDB();

    $sql = "UPDATE
			m_goods SET
            deleted_flag = false
			WHERE
			owner_id = :owner_id
            AND goods_id = :goods_id
            AND color_size_id = :color_size_id";
    foreach($posted_row_indexes as $row_index){
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':owner_id', $posted_owner_ids[$row_index], PDO::PARAM_STR);
        $stmt->bindParam(':goods_id', $posted_goods_ids[$row_index], PDO::PARAM_STR);
        $stmt->bindParam(':color_size_id', $posted_color_size_ids[$row_index], PDO::PARAM_STR);
        $stmt->execute();
        log_message("[{$_SESSION['login_user_id']}]" . pdo_debugStrParams($stmt));
    }
    $db = NULL;
    echo '復活が完了しました。';
} catch (PDOException $e) {
    log_message("[{$_SESSION['login_user_id']}]" . $e->getMessage());
    log_message("[{$_SESSION['login_user_id']}]" . pdo_debugStrParams($stmt));
    die('エラー：' . $e->getMessage());
}

get_footer();