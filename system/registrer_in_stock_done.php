<?php
require_once ('../common/function.php');
require_once ('../db/DB.php');

get_header();

// registrer_in_stock.phpからのPOSTを変数に代入
$owner_id = h($_POST['owner_id']);
$stock_schedule_day = h($_POST['stock_schedule_day']);
$invoice_no = h($_POST['invoice_no']);
$advance_amount = h($_POST['advance_amount']);
$goods_id = getpstStrs('goods_id');
$color_size_id = getpstStrs('color_size_id');
$stock_volume = getpstStrs('stock_volume');

try {
    // データベース接続
    $db = DB::getDB();

    $db->beginTransaction();

    if ($invoice_no !== '') {
        // invoiceテーブルにレコードを挿入
        $sql = "INSERT INTO
                        invoice (
                        invoice_no,
                        owner_id,
                        advance_amount)
                        VALUES(
                        :invoice_no,
                        :owner_id,
                        :advance_amount)";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':invoice_no', $invoice_no, PDO::PARAM_STR);
        $stmt->bindParam(':owner_id', $owner_id, PDO::PARAM_STR);
        if ($advance_amount === '') {
            $stmt->bindValue(':advance_amount', 0, PDO::PARAM_INT);
        } else {
            $stmt->bindParam(':advance_amount', $advance_amount, PDO::PARAM_INT);
        }
        $stmt->execute();
        log_message("[{$_SESSION['login_user_id']}]" . pdo_debugStrParams($stmt));
    }
    for ($i = 0; $i < count($goods_id); $i ++) {
        // stock_resultsテーブルにレコードを挿入
        $sql = "INSERT INTO
          stock_results (
          owner_id,
          goods_id,
                    color_size_id,
          stock_schedule_day,
          stock_results_day,
          stock_volume,
          invoice_no)
          VALUES(
          :owner_id,
          :goods_id,
                    :color_size_id,
                    :stock_schedule_day,
                    curdate(),
          :stock_volume,
          :invoice_no)";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':owner_id', $owner_id, PDO::PARAM_STR);
        $stmt->bindParam(':goods_id', $goods_id[$i], PDO::PARAM_STR);
        $stmt->bindParam(':color_size_id', $color_size_id[$i], PDO::PARAM_STR);
        $stmt->bindParam(':stock_schedule_day', $stock_schedule_day, PDO::PARAM_STR);
        $stmt->bindParam(':stock_volume', $stock_volume[$i], PDO::PARAM_INT);
        if ($invoice_no === '') {
            $stmt->bindValue(':invoice_no', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam(':invoice_no', $invoice_no, PDO::PARAM_STR);
        }
        $stmt->execute();
        log_message("[{$_SESSION['login_user_id']}]" . pdo_debugStrParams($stmt));

        // stock_scheduleテーブルの「入荷実績フラグ」をtrueにする
        $sql = "UPDATE stock_schedule
                 SET stock_results_flag = true
                 WHERE owner_id = :owner_id
                 AND goods_id = :goods_id
                 AND color_size_id = :color_size_id
                 AND stock_schedule_day = :stock_schedule_day";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':owner_id', $owner_id, PDO::PARAM_STR);
        $stmt->bindParam(':goods_id', $goods_id[$i], PDO::PARAM_STR);
        $stmt->bindParam(':color_size_id', $color_size_id[$i], PDO::PARAM_STR);
        $stmt->bindParam(':stock_schedule_day', $stock_schedule_day, PDO::PARAM_STR);
        $stmt->execute();
        log_message("[{$_SESSION['login_user_id']}]" . pdo_debugStrParams($stmt));
    }
    $db->commit();
} catch (Exception $e) {
    log_message("[{$_SESSION['login_user_id']}]" . $e->getMessage());
    log_message("[{$_SESSION['login_user_id']}]" . pdo_debugStrParams($stmt));
    $db->rollBack();
    die('エラー：' . $e->getMessage());
}
$db = NULL;

echo '登録が完了しました。';
?>

<?php

get_footer();