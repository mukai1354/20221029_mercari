<?php
// 2021/08/16 issue255 submitボタンをダブルクリックした際に、ログイン画面に飛ばされる demachi
require_once ("common/function.php");
require_once ("db/DB.php");

session_start();
// --------------------------------------------------issue255 start-------------------------------------------------------
// session_regenerate_id(true);
// --------------------------------------------------issue255 end---------------------------------------------------------

header("Content-type: text/html; charset=utf-8");

// パラメーター取得
$id = h($_POST['id']);
$password = h($_POST['password']);

// ログイン判定
try {
    // DB接続
    $db = DB::getDB();
    // プレースホルダで SQL 作成
    $sql = "SELECT " . "COUNT(*) AS `cnt`, " . "`user_id`, " . "`user_password`, " . "`user_name`, " . "`role`, " . "`owner_id` " . "FROM " . "`m_user` " . "WHERE  " . "`user_id` = :id";
    $sql .= " GROUP BY " . "`user_id`, " . "`user_password`, " . "`user_name`, " . "`role`, " . "`owner_id` ";

    $stmt = $db->prepare($sql);
    // パラメーターの型を指定
    $stmt->bindParam(':id', $id, PDO::PARAM_STR);
    // パラメーターを渡して SQL 実行
    $stmt->execute();
    $db = NULL;
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    // ログイン失敗 存在しない
    if ($result['cnt'] != 1) {
        $_SESSION["error_status"] = 1;
        redirect_err();
    }

    // ログイン失敗 無効ユーザー
    if ($result['role'] == 0) {
        $_SESSION["error_status"] = 3;
        redirect_err();
    }

    $user_id = $result['user_id'];
    $db_password = $result['user_password'];
    $user_name = $result['user_name'];
    $role = $result['role'];
    $owner_id = $result['owner_id'];
    // $reset = $result['reset'];

    /*
     * //パスワードリセット対応
     * if ($reset == 1) {
     * $_SESSION["error_status"] = 1;
     * header("HTTP/1.1 301 Moved Permanently");
     * header("Location: password_reset.php");
     * exit();
     * }
     */

    if (password_verify($password, $db_password)) {
        // ログイン成功

        // --------------------------------------------------issue255 start-------------------------------------------------------
        session_regenerate_id(true);
        // --------------------------------------------------issue255 end---------------------------------------------------------

        // セッションに ID を格納
        $_SESSION['login_user_id'] = $user_id;
        $_SESSION['login_user_name'] = $user_name;
        $_SESSION['login_role'] = $role;
        $_SESSION['login_owner_id'] = $owner_id;

        // HOMEへリダイレクト
        redirect_home();
    } else {
        // ログイン失敗
        $_SESSION["error_status"] = 1;
        redirect_err();
    }
} catch (Exception $e) {
    exit($e->getMessage());
}