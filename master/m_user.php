<?php
require_once ('../common/function.php');

get_header();

// エラー情報リセット
$_SESSION["error_status"] = E_STATUS_DEFAULT;

?>
  <div id="row justify-content-center">
    <div class="col-auto">
      <div id="main">
        <main role="main">
          <h1 class="my-5">ユーザー情報登録</h1>
          <form action="m_user_check.php" method="post">
            <table class="table w-50 mx-auto">
              <tr>
              <th class="shipment_record_table_title"><label for="user_id">ユーザーID(半角小文字英数字)</label></th>
              <td><input type="text" name="user_id" id="user_id"></td>
            </tr>
            <tr>
              <th class="shipment_record_table_title"><label for="user_password">パスワード</label></th>
              <td><input type="password" name="user_password" id="user_password"></td>
            </tr>
            <tr>
              <th class="shipment_record_table_title"><label for="confirm_password">パスワード(確認)</label></th>
              <td><input name="confirm_password" id="confirm_password" type="password"></td>
            </tr>
            <tr>
              <th class="shipment_record_table_title"><label for="user_name">ユーザー名</label></th>
              <td><input type="text" name="user_name" id="user_name"></td>
            </tr>
            <tr>
              <th class="shipment_record_table_title"><label for="role">役割</label></th>
              <td><input type="text" name="role" id="role"></td>
            </tr>
            <tr>
              <th class="shipment_record_table_title"><label for="owner_id">商品所有者コード</label></th>
              <td><input type="text" name="owner_id" id="owner_id"></td>
            </tr>
            </table>
            <input type="submit" name="">
          </form>
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