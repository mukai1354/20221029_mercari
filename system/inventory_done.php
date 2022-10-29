<?php
require_once('../common/function.php');
require_once('../db/DB.php');

get_header();

$array_posted_owner_id = h($_POST['owner_id']);
$array_posted_goods_id = h($_POST['goods_id']);
$array_posted_color_size_id = h($_POST['color_size_id']);
$array_posted_inventory_date = h($_POST['inventory_date']);
$array_posted_stock_quantity = h($_POST['stock_quantity']);
$array_posted_input_volume = h($_POST['input_volume']);

try {
    // データベース接続
    $db = DB::getDB();

    $sql = "INSERT INTO
            inventory_data(
            owner_id,
            goods_id,
            color_size_id,
            inventory_date,
            input_volume,
            difference_volume)
            VALUES(
            :owner_id,
            :goods_id,
            :color_size_id,
            :inventory_date,
            :input_volume,
            :difference_volume)
            ON DUPLICATE KEY UPDATE
            input_volume = VALUES(input_volume),
            difference_volume = VALUES(difference_volume) + difference_volume"; // 一日に二回以上棚卸入力する場合は、既に登録されている当日分の差異数量が実在庫数に反映されているため、その分を加算する。
    for ($i = 0; $i < count($array_posted_owner_id); $i++) {
        $difference_volume = $array_posted_input_volume[$i] - $array_posted_stock_quantity[$i];

        $stmt = $db->prepare($sql);
        $stmt->bindParam(':owner_id', $array_posted_owner_id[$i], PDO::PARAM_STR);
        $stmt->bindParam(':goods_id', $array_posted_goods_id[$i], PDO::PARAM_STR);
        $stmt->bindParam(':color_size_id', $array_posted_color_size_id[$i], PDO::PARAM_STR);
        $stmt->bindParam(':inventory_date', $array_posted_inventory_date[$i], PDO::PARAM_STR);
        $stmt->bindParam(':input_volume', $array_posted_input_volume[$i], PDO::PARAM_STR);
        $stmt->bindParam(':difference_volume', $difference_volume, PDO::PARAM_STR);
        $stmt->execute();
        log_message("[{$_SESSION['login_user_id']}]" . pdo_debugStrParams($stmt));
    }
    $db = null;
    echo '棚卸入力が完了しました。';
} catch (PDOException $e) {
    log_message("[{$_SESSION['login_user_id']}]" . $e->getMessage());
    log_message("[{$_SESSION['login_user_id']}]" . pdo_debugStrParams($stmt));
    die('エラー：' . $e->getMessage());
}

get_footer();
