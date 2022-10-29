<?php
require_once ('../common/function.php');

get_header();

// m_staff.phpからのPOSTを変数に代入
$owner_id = h($_POST['owner_id']);
$owner = h($_POST['owner']);
$zip_code = h($_POST['zip_code']);
$address_1 = h($_POST['address_1']);
$address_2 = h($_POST['address_2']);
$tel = h($_POST['tel']);
$fax = h($_POST['fax']);
$email = h($_POST['email']);

// エラー用変数を宣言、初期化
$error = "";

// 入力事項をひとまとめに
$input_contents = <<< EOM
				<table>
				<tr>
					<th>商品所有者コード</th>
					<td>$owner_id</td>
				</tr>
				<tr>
					<th>商品所有者</label></th>
					<td>$owner</td>
				</tr>
				<tr>
					<th>郵便番号</th>
					<td>$zip_code</td>
				</tr>
				<tr>
					<th>住所1(番地まで)</th>
					<td>$address_1</td>
				</tr>
				<tr>
					<th>住所2(建物名、部屋番号など)</th>
					<td>$address_2</td>
				</tr>
				<tr>
					<th>電話番号(ハイフンなし)</th>
					<td>$tel</td>
				</tr>
				<tr>
					<th>FAX番号(ハイフンなし)</th>
					<td>$fax</td>
				</tr>
				<tr>
					<th>メールアドレス</th>
					<td>$email</td>
				</tr>
				</table>

EOM;

// $owner_idが空欄の場合
if ($owner_id == "") {
    $error .= "商品所有者コードを入力してください。<br>";
    // $owner_idが4桁の小文字英数字でない場合
} else if (! preg_match("/^[0-9a-z]{4}$/", $owner_id)) {
    $error .= "商品所有者コードは4桁の小文字アルファベットまたは数字です。<br>";
}

// $ownerが未記入の場合
if ($owner == "") {
    $error .= "商品所有者を入力してください。<br>";
}

// $telが未記入の場合は処理なし、規定以外の記入の場合にエラーを返す
if ($tel == "") {
    $error .= "";
} else if (! preg_match("/^[0-9]{10,11}$/", $tel)) {
    $error .= "電話番号は10桁あるいは11桁の半角数字です。<br>";
}

// $fax_noが未記入の場合は処理なし、規定以外の記入の場合にエラーを返す
if ($fax == "") {
    $error .= "";
} else if (! preg_match("/^[0-9]{10}$/", $fax)) {
    $error .= "FAX番号は10桁の半角数字です。<br>";
}

// $postal_codeが未記入の場合は処理なし、規定以外の記入の場合にエラーを返す
if ($zip_code == "") {
    $error .= "";
} else if (! preg_match("/^[0-9]{7}$/", $zip_code)) {
    $error .= "郵便番号は7桁の半角数字です。<br>";
}

// $mail_idが未記入の場合は処理なし、規定以外の記入の場合にエラーを返す
if ($email == "") {
    $error .= "";
} else if (! preg_match("/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/", $email)) {
    $error .= "メールIDが正しくありません。<br>";
}

?>
	<div id="contents">
		<div class="inner">
			<div id="main">
			<main role="main">
				<h1>商品所有者マスタ</h1>
	<?php
// 入力内容のエラーを表示
if ($error != "") {
    echo '以下の内容を確認してください<br>' . $error . '<button type="button" class="prev">戻る</button>';
} else {
    // セッションに値を設定
    $_SESSION['owner_id'] = $owner_id;
    $_SESSION['owner'] = $owner;
    $_SESSION['zip_code'] = $zip_code;
    $_SESSION['address_1'] = $address_1;
    $_SESSION['address_2'] = $address_2;
    $_SESSION['tel'] = $tel;
    $_SESSION['fax'] = $fax;
    $_SESSION['email'] = $email;
    // 登録内容の確認
    echo '以下の内容で更新します。<br>' . $input_contents;
    echo '<form action="m_staff_update_done.php" method="post" accept-charset="utf-8"><button type="button" class="prev">修正する</button><input type="submit" value="更新する"></form>';
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