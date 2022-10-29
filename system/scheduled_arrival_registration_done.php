<?php
require_once('../common/function.php');
require_once('../db/DB.php');

get_header();

$operation = getpstStrs('operation');
$owner_id = getpstStrs('owner_id');
$goods_id = getpstStrs('goods_id');
$color_size_id = getpstStrs('color_size_id');
$quantity = getpstStrs('quantity');
$schedule_day = getpstStrs('schedule_day');
$regist_schedule_day = getpstStrs('regist_stock_schedule_day');

if ($goods_id === "") {
    die("商品が選択されていません。");
}

try {
    $db = DB::getDB();
    $sql = "";
    $stmt = null;
    $i = 0;
    if ($operation == "insert") {
        if ($regist_schedule_day == "") {
            die("入荷予定日を入力してください。");
        }
        while ($i <  count($goods_id)) {
            if ($quantity[$i] !== "" && $quantity[$i] != "0") {
                $volume = intval($quantity[$i]);
                $sql = "INSERT INTO stock_schedule";
                $sql .= " (owner_id, goods_id, color_size_id, stock_schedule_day, stock_schedule_volume)";
                $sql .= " VALUES (:owner_id, :goods_id, :color_size_id, :stock_schedule_day, :stock_schedule_volume)";
                $stmt = $db->prepare($sql);
                $stmt->bindParam(':owner_id', $owner_id, PDO::PARAM_STR);
                $stmt->bindParam(':goods_id', $goods_id[$i], PDO::PARAM_STR);
                $stmt->bindParam(':color_size_id', $color_size_id[$i], PDO::PARAM_STR);
                $stmt->bindParam(':stock_schedule_day', $regist_schedule_day, PDO::PARAM_STR);
                $stmt->bindParam(':stock_schedule_volume', $volume, PDO::PARAM_STR);
                $stmt->execute();
                log_message("[{$_SESSION['login_user_id']}]" . pdo_debugStrParams($stmt));
            }
            $i ++;
        }
    } elseif ($operation == "update") {
        while ($i <  count($goods_id)) {
            $volume = intval($quantity[$i]);
            if ($volume > 0) {
                if ($regist_schedule_day == "") {
                    die("入荷予定日を入力してください。");
                }
                $sql = "UPDATE stock_schedule SET";
                $sql .= " stock_schedule_day = :regist_schedule_day,";
                $sql .= " stock_schedule_volume = :stock_schedule_volume";
                $sql .= " WHERE";
                $sql .= " owner_id = :owner_id AND goods_id = :goods_id AND color_size_id = :color_size_id";
                $sql .= " AND stock_schedule_day = :stock_schedule_day";
                $stmt = $db->prepare($sql);
                $stmt->bindParam(':regist_schedule_day', $regist_schedule_day, PDO::PARAM_STR);
                $stmt->bindParam(':stock_schedule_volume', $volume, PDO::PARAM_STR);
            } else {
                $sql = "DELETE FROM stock_schedule";
                $sql .= " WHERE";
                $sql .= " owner_id = :owner_id AND goods_id = :goods_id AND color_size_id = :color_size_id";
                $sql .= " AND stock_schedule_day = :stock_schedule_day";
                $stmt = $db->prepare($sql);
            }
            $stmt->bindParam(':owner_id', $owner_id, PDO::PARAM_STR);
            $stmt->bindParam(':goods_id', $goods_id[$i], PDO::PARAM_STR);
            $stmt->bindParam(':color_size_id', $color_size_id[$i], PDO::PARAM_STR);
            $stmt->bindParam(':stock_schedule_day', $schedule_day[$i], PDO::PARAM_STR);
            $stmt->execute();
            log_message("[{$_SESSION['login_user_id']}]" . pdo_debugStrParams($stmt));
            $i ++;
        }
    }

    $db = null;
} catch (Exception $e) {
    log_message("[{$_SESSION['login_user_id']}]" . $e->getMessage());
    log_message("[{$_SESSION['login_user_id']}]" . pdo_debugStrParams($stmt));
    die('エラー：' . $e->getMessage());
}

echo '登録が完了しました。';

get_footer();
