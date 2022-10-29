<?php
require_once ("common/function.php");
require_once ('db/DB.php');

get_login_header();

// CSRF のトークン作成
$_SESSION['csrf_token'] = get_csrf_token();

$err_txt = '';
if (isset($_SESSION['error_status'])) {
    switch ($_SESSION['error_status']) {
        case 1:
            $err_txt = '<p class="error">IDまたはパスワードが異なります。</p>';
            break;
        case 2:
            $err_txt = '<p class="error">不正なリクエストです。</p>';
            break;
        case 3:
            $err_txt = '<p class="error">無効なユーザーです。</p>';
            break;
    }
}
// エラー情報初期化・リセット
$_SESSION["error_status"] = E_STATUS_DEFAULT;

?>
  <div class="row justify-content-center mb-5">
    <div class="col-auto">
      <main>
        <h1 class="page_title mb-5">ログイン</h1>
<?php
echo $err_txt;
?>
        <form action="login_check.php" method="post">
          <input name="csrf_token" type="hidden" value="
<?php
echo h($_SESSION['csrf_token'])?>">
          <table class="table" border="1">
            <tr>
              <th class="login_id">ID </th>
              <td><input name="id" type="text"></td>
            </tr>
            <tr>
              <th class="login_password">Password</th>
              <td><input name="password" type="password"></td>
            </tr>
          </table>
          <input class="button" type="submit" value="ログイン">
        </form>
      </main>
      <!-- /.main -->
    </div>
    <!-- /.content .col-12 -->
  </div>
  <!-- /.row -->
<?php

get_footer();