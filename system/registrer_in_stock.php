<?php
require_once('../common/function.php');
require_once('../db/DB.php');

get_header();

$now = new DateTime();
$is_monthly_closed = false;
try {
    $db = DB::getDB();
    $is_monthly_closed = is_monthly_closed($db, $now->format('Ym'));
    $db = null;
} catch (Exception $e) {
    die('エラー：' . $e->getMessage());
}

// 直近の月曜日の日付を取得
$day = getMonday();

// エラー情報リセット
$_SESSION["error_status"] = E_STATUS_DEFAULT;
$owner_id = '';
$owner_id = h($_SESSION['login_owner_id']);

// registrer_in_stock.phpからのPOSTを変数に代入
$stock_schedule_day = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $owner_id = h($_POST['owner_id']);
    $stock_schedule_day = h($_POST['stock_schedule_day']);
    $day = $stock_schedule_day;
}

// 商品所有者の一覧を取得してHTMLで選択欄を作成
try {
    // データベース接続
    $db = DB::getDB();
    $stmt = $db->prepare("SELECT owner_id, owner FROM m_staff");
    $stmt->execute();
    $result_staff = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $db = null;
    $stmt = null;
} catch (PDOException $e) {
    die('エラー：' . $e->getMessage());
}
$owner = "";
foreach ($result_staff as $varr) {
    if ($owner_id == $varr['owner_id']) {
        $owner .= "\t\t\t\t\t<option value=\"{$varr['owner_id']}\" selected>{$varr['owner']}</option>\n";
    } else {
        $owner .= "\t\t\t\t\t<option value=\"{$varr['owner_id']}\">{$varr['owner']}</option>\n";
    }
}

// 入荷予定テーブルから、選択された商品所有者の、選択された日付の入荷予定を取得
$result_stock_schedule = array();
if ($owner_id != '' && $stock_schedule_day != '') {
    try {

        // データベース接続
        $db = DB::getDB();
        $sql = <<< EOM
            SELECT
            T1.owner_id,
            T1.goods_id,
            T1.color_size_id,
            stock_schedule_day,
            stock_schedule_volume,
            picture,
            goods_name,
            color,
            user_id,
            size
            FROM
            stock_schedule T1
            INNER JOIN
            m_goods T2
            USING(owner_id, goods_id, color_size_id)
            LEFT OUTER JOIN
            m_user
            using(owner_id)
            WHERE
            T1.owner_id = :owner_id
            AND
            stock_schedule_day = :stock_schedule_day
            AND
            stock_results_flag = false
            AND
            T2.deleted_flag = false
EOM;
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':owner_id', $owner_id, PDO::PARAM_STR);
        $stmt->bindParam(':stock_schedule_day', $stock_schedule_day, PDO::PARAM_STR);
        $stmt->execute();

        $db = null;
    } catch (PDOException $e) {
        die('エラー：' . $e->getMessage());
    }

    $result_stock_schedule = $stmt->fetchAll();
}

$tbody = '';
$owner_id = '';
$stock_schedule_day = '';
$goods_id = '';
$color_size_id = '';
$stock_schedule_volume = '';
$picture = '';
$goods_name = '';
$color = '';
$size = '';
for ($i = 0; $i < count($result_stock_schedule); $i ++) {

    // registrer_in_stock_done.phpにowenr_idとstock_schedule_dayをPOSTするために、ループの初回のみ<input>タグを挿入
    if ($i == 0) {
        $owner_id = $result_stock_schedule[0]['owner_id'];
        $stock_schedule_day = $result_stock_schedule[0]['stock_schedule_day'];
        $tbody .= "<input type=\"hidden\" name=\"owner_id\" value=\"{$owner_id}\">";
        $tbody .= "<input type=\"hidden\" name=\"stock_schedule_day\" value=\"{$stock_schedule_day}\">";
    }

    $no = $i + 1;
    $goods_id = $result_stock_schedule[$i]['goods_id'];
    $color_size_id = $result_stock_schedule[$i]['color_size_id'];
    $user_id = $result_stock_schedule[$i]['user_id'];
    $stock_schedule_volume = $result_stock_schedule[$i]['stock_schedule_volume'];
    $picture = $result_stock_schedule[$i]['picture'];
    $goods_name = $result_stock_schedule[$i]['goods_name'];
    $color = $result_stock_schedule[$i]['color'];
    $size = $result_stock_schedule[$i]['size'];
    $product_management_id = $user_id."_".$goods_id."_".$color_size_id;
    $img_src = "../common/images/" . $owner_id . "/goods/" . $picture;
    $img = "<img src=\"{$img_src}\" class=\"img-fluid max-height-100\">";

    $tbody .= "<tr class=\"tr_even\">
              <td class=\"px-2 align-middle\" rowspan=\"2\">{$no}</td>
              <td class=\"px-2 align-middle\" rowspan=\"2\">{$product_management_id}</td>
              <td class=\"px-2 align-middle\"><input type=\"text\" name=\"goods_id[]\" value=\"{$goods_id}\" readonly=\"readonly\"></td>
              <td class=\"px-2 align-middle\" colspan=\"2\"><input type=\"text\" name=\"color_size_id[]\" value=\"{$color_size_id}\" readonly=\"readonly\"></td>
              <td class=\"shipment_record_img\" rowspan=\"2\">{$img}</td>
              <td class=\"px-2 align-middle\" rowspan=\"2\">
                                <input class=\"text-right w-5rem\" type=\"number\" name=\"stock_volume[]\" min=\"0\" value=\"{$stock_schedule_volume}\">
                            </td>
            </tr>
            <tr class=\"tr_even2\">
              <td class=\"px-2 align-middle\">{$goods_name}</td>
              <td class=\"px-2 align-middle\">{$size}</td>
              <td class=\"px-2 align-middle\">{$color}</td>
            </tr>
    ";
}
?>
<?php if($is_monthly_closed):?>
<?php echo '月締め処理実行済みのため、入荷登録を行うことができません。';?>
<?php else:?>
  <div id="contents">
    <div class="inner">
      <div id="main">
      <main class="mb-5" role="main">
        <h1 class="mt-4">入荷登録</h1>
        <form action="" method="post">
            <label class="mr-4">商品所有者：&ensp;<select name="owner_id"><?php echo $owner; ?></select></label>
            <label class="mr-4">入荷予定日：&ensp;<input type="date" name="stock_schedule_day" value="<?php echo $day?>"></label>
            <input type="submit" value="商品表示" name="submit">
        </form>
        <?php if(!empty($_POST['submit'])): ?>
        <?php if(!empty($tbody)): ?>
        <form action="registrer_in_stock_done.php" method="post">
        <table class="mx-auto mb-5">
          <caption>入荷情報登録</caption>
          <thead class="shipment_record_table_title">
            <tr>
              <th class="px-2" scope="col" rowspan="2">No</th>
              <th class="px-2" scope="col" rowspan="2">管理ID</th>
              <th class="px-2" scope="col">商品名コード</th>
              <th class="px-2" scope="col" colspan="2">色サイズコード</th>
              <th class="w-150px" scope="col" rowspan="2">商品画像</th>
              <th class="w-10rem px-2" scope="col" rowspan="2">入荷数量</th>
            </tr>
            <tr>
              <th class="px-2" scope="col">商品名</th>
              <th class="px-2" scope="col">サイズ</th>
              <th class="px-2" scope="col">色</th>
            </tr>
          </thead>
          <tbody>
            <?php

    echo $tbody;
    ?>
          </tbody>
        </table>
        請求書No<input type="text" name="invoice_no">
        立替金額<input class="text-right" type="number" name="advance_amount" min="0">
        入荷実績日<?php

    echo date("Y/m/d") . '<br>'?>
        <input type="submit" value="登録">
        </form>
        <?php else: ?>
        <p class="h2 font-weight-bold text-center mt-5">本日の入荷はありません。</p>
        <?php endif; ?>
        <?php endif; ?>
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
<?php endif;?>
<?php

get_footer();
