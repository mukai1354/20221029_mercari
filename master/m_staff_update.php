<?php
require_once ('../common/function.php');
require_once ('../db/DB.php');

get_header();

// エラー情報リセット
$_SESSION["error_status"] = E_STATUS_DEFAULT;

// m_staff_update.phpからのPOSTを変数に代入
$owner_id = getpstStrs('owner_id');

// 初期化
$owner = '';
$zip_code = '';
$address_1 = '';
$address_2 = '';
$tel = '';
$fax = '';
$email = '';

if ($owner_id != '') {
    try {

        // データベース接続
        $db = DB::getDB();
        $sql = "SELECT
        owner_id,
        owner,
        zip_code,
        address_1,
        address_2,
        tel,
        fax,
        email
        FROM
        m_staff
        WHERE
        owner_id = :owner_id
                AND deleted_flag = false";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':owner_id', $owner_id, PDO::PARAM_STR);
        $stmt->execute();

        $db = NULL;
    } catch (PDOException $e) {
        die('エラー：' . $e->getMessage());
    }

    $result = $stmt->fetchAll();
    // print_r($result);
    if ($result != NULL) {
        $owner = $result[0]['owner'];
        $zip_code = $result[0]['zip_code'];
        $address_1 = $result[0]['address_1'];
        $address_2 = $result[0]['address_2'];
        $tel = $result[0]['tel'];
        $fax = $result[0]['fax'];
        $email = $result[0]['email'];
    }
    $error = '存在しない商品所有者コードです。';
}
?>
  <div id="row justify-content-center">
    <div class="col-auto">
      <div id="main">
      <main role="main">
        <h1 class="mt-5">商品所有者情報更新</h1>
        <form class="mb-5" action="m_staff_update.php" method="post">
          <label>商品所有者コード(4桁の半角小文字英数字):<input type="text" name="owner_id" id="owner_id" value="<?php echo $owner_id?>"></label>
          <button type="submit" name="action" value="seach">表示</button>
        </form>
        <form action="m_staff_update_check.php" method="post">
        <table class="table mx-auto w-50">
          <input type="hidden" name="owner_id" id="owner_id" value="<?php

    echo $owner_id?>">
        <tr>
          <th class="shipment_record_table_title align-middle"><label for="owner">商品所有者</label></th>
          <td><input type="text" name="owner" id="owner" value="<?php echo $owner?>"></td>
        </tr>
        <tr>
          <th class="shipment_record_table_title align-middle"><label for="zip_code">郵便番号</label></th>
          <td><input type="text" name="zip_code" id="zip_code" value="<?php echo $zip_code?>"></td>
        </tr>
        <tr>
          <th class="shipment_record_table_title align-middle"><label for="address_1">住所1(番地まで)</label></th>
          <td><input type="text" name="address_1" id="address_1" value="<?php

    echo $address_1?>"></td>
        </tr>
        <tr>
          <th class="shipment_record_table_title align-middle"><label for="address_2">住所2(建物名、部屋番号など)</label></th>
          <td><input type="text" name="address_2" id="address_2" value="<?php

    echo $address_2?>"></td>
        </tr>
        <tr>
          <th class="shipment_record_table_title align-middle"><label for="tel">電話番号(ハイフンなし)</label></th>
          <td><input type="text" name="tel" id="tel" value="<?php

    echo $tel?>"></td>
        </tr>
        <tr>
          <th class="shipment_record_table_title align-middle"><label for="fax">FAX番号(ハイフンなし)</label></th>
          <td><input type="text" name="fax" id="fax" value="<?php

    echo $fax?>"></td>
        </tr>
        <tr>
          <th class="shipment_record_table_title align-middle"><label for="email">メールアドレス</label></th>
          <td><input type="text" name="email" id="email" value="<?php

    echo $email?>"></td>
        </tr>
        </table>
        <input type="submit"  value="修正">
        <input type="submit" formaction="m_staff_delete_done.php" value="削除">
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