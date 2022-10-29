<?php
require_once ('../common/function.php');
require_once ('../db/DB.php');

get_header();

// エラー情報リセット
$_SESSION["error_status"] = E_STATUS_DEFAULT;

// m_user_update.phpからのPOSTを変数に代入
$user_id = getpstStrs('user_id');

// 初期化
$user_name = '';
$role = '';
$owner_id = '';

if ($user_id != '') {
    try {

        // データベース接続
        $db = DB::getDB();
        $sql = "SELECT
        user_id,
        user_password,
        user_name,
        role,
        owner_id
        FROM
        m_user
        WHERE
        user_id = :user_id
                AND deleted_flag = false";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_STR);
        $stmt->execute();

        $db = NULL;
    } catch (PDOException $e) {
        die('エラー：' . $e->getMessage());
    }

    $result = $stmt->fetchAll();
    // print_r($result);
    if ($result != NULL) {
        $user_name = $result[0]['user_name'];
        $role = $result[0]['role'];
        $owner_id = $result[0]['owner_id'];
    }
    $error = '存在しないユーザーＩＤです。';
}
?>
  <div id="row justify-content-center">
    <div class="col-auto">
      <div id="main">
      <main role="main">
        <h1 class="mt-5">ユーザー情報更新</h1>
        <form class="mb-5" action="m_user_update.php" method="post">
          <label for="user_id">ユーザーID(半角小文字英数字)</label>
          <input type="text" name="user_id" id="user_id" value="<?php
    echo $user_id?>">
          <button type="submit" name="action" value="seach">表示</button>
        </form>
        <form action="m_user_update_check.php" method="post">
        <table class="table mx-auto w-50">
          <input type="hidden" name="user_id" id="user_id" value="<?php
    echo $user_id?>">
        <tr>
          <th class="shipment_record_table_title"><label for="user_name">ユーザー名</label></th>
          <td><input type="text" name="user_name" id="user_name" value="<?php
    echo $user_name?>"></td>
        </tr>
        <tr>
          <th class="shipment_record_table_title"><label for="role">役割</label></th>
          <td><input type="text" name="role" id="role" value="<?php
    echo $role?>"></td>
        </tr>
        <tr>
          <th class="shipment_record_table_title"><label for="owner_id">商品所有者コード</label></th>
          <td><input type="text" name="owner_id" id="owner_id" value="<?php
    echo $owner_id?>"></td>
          </tr>
          <tr>
          <th class="shipment_record_table_title"><label for="now_password">現在のパスワード（必須）</label></th>
          <td><input type="password" name="now_password" id="now_password"></td>
        </tr>
        <tr>
          <th class="shipment_record_table_title"><label for="next_password">新しいパスワード（任意）</label></th>
          <td><input type="password" name="next_password" id="next_password"></td>
        </tr>
        <tr>
          <th class="shipment_record_table_title"><label for="next_password_confirm">テスト新しいパスワード（任意・確認用）</label></th>
          <td><input type="password" name="next_password_confirm" id="next_password_confirm"></td>
        </tr>
        </table>
          <input type="submit" formaction="m_user_update_check.php" value="更新">
          <input type="submit" formaction="m_user_delete_done.php" value="削除">

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