<?php
require_once ('../common/function.php');

get_header();

// m_user.phpからのPOSTを変数に代入
$user_id = h($_POST['user_id']);
$user_password = h($_POST['user_password']);
$confirm_password = h($_POST['confirm_password']);
$user_name = h($_POST['user_name']);
$role = h($_POST['role']);
$owner_id = h($_POST['owner_id']);

// エラー用変数を宣言、初期化
$error = "";

// 入力事項をひとまとめに
$input_contents = <<< EOM
				<table>
				<tr>
					<th>ユーザーID(半角小文字英数字)</th>
					<td>$user_id</td>
				</tr>
				<tr>
					<th>ユーザー名</th>
					<td>$user_name</td>
				</tr>
				<tr>
					<th>役割</th>
					<td>$role</td>
				</tr>
				<tr>
					<th>商品所有者コード</th>
					<td>$owner_id</td>
				</tr>
				</table>

EOM;

// $owner_idが空欄の場合
if ($user_id == "") {
    $error .= "ユーザーIDを入力してください。<br>";
}
// パスワード不一致
if ($user_password != $confirm_password) {
    $error .= "パスワードが不一致です。<br>";
}

// $user_nameが未記入の場合
if ($user_name == "") {
    $error .= "ユーザー名を入力してください。<br>";
}

// $roleが未記入の場合
if ($role == "") {
    $error .= "役割を入力してください。<br>";
}

// $owner_idが空欄の場合
if ($owner_id == "") {
    // $error .= "商品所有者コードを入力してください。<br>";
    // $owner_idが4桁の小文字英数字でない場合
} else if (! preg_match("/^[0-9a-z]{4}$/", $owner_id)) {
    $error .= "商品所有者コードは4桁の小文字アルファベットまたは数字です。<br>";
}
?>
	<div id="contents">
		<div class="inner">
			<div id="main">
			<main role="main">
				<h1>ユーザーマスタ</h1>
<?php
// 入力内容のエラーを表示
if ($error != "") {
    echo '以下の内容を確認してください<br>' . $error . '<button type="button" class="prev">戻る</button>';
} else {
    // セッションに値を設定
    $_SESSION['user_id'] = $user_id;
    $_SESSION['name'] = $user_name;
    $_SESSION['password'] = $user_password;
    $_SESSION['role'] = $role;
    $_SESSION['owner_id'] = $owner_id;
    // 登録内容の確認
    echo '以下の内容で登録します。<br>' . $input_contents;
    echo '<form action="m_user_done.php" method="post" accept-charset="utf-8"><button type="button" class="prev">修正する</button><input type="submit" value="登録する"></form>';
}
?>
			</main>
			</div>
			<!-- /#main -->
			<div id="sub">
			</div>
			<!-- /#sub -->
		</div>
		<!-- /.inner-->
	</div>
	<!-- /#contents -->
<?php

get_footer();