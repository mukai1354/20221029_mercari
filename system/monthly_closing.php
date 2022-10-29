<?php
// 2021/08/21 issue257 テスト環境で月次処理を行ったが期首在庫が変っていないようである。 demachi
require_once ('../common/function.php');
require_once ('../db/DB.php');

date_default_timezone_set('Asia/Tokyo');

get_header();

// エラー情報リセット
$_SESSION["error_status"] = E_STATUS_DEFAULT;

$target_date_array = array(
    "-1" => date("Y年m月", strtotime("-1 month")),
    "-2" => date("Y年m月", strtotime("-2 month")),
    "-3" => date("Y年m月", strtotime("-3 month"))
);

$target_date = '';
foreach ($target_date_array as $key => $value) {
    $target_date .= "\t\t\t\t\t<option value=\"$key\">$value</option>\n";
}

// ボタンが押されたとき実施
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $target_date_key = (int) getpstStrs("target_date"); // 対象年月のKEY(-1,-2,-3)を取得
    $years_months = date("Ym", strtotime("$target_date_key month")); // "対象年月の値(202X0X等)"
    $first_day = date("Y-m-d", strtotime('first day of ' . "$target_date_key month")); // "対象年月の初日(YYYY-MM-D1)"
    $last_day = date("Y-m-d", strtotime('last day of ' . "$target_date_key month")); // "対象年月の末日(YYYY-MM-DD)"
    $target_date_key = (int) getpstStrs("target_date") + 1; // 対象年月の翌月のKEY(0, -1,-2)を取得
    $year_next_month = date("Ym", strtotime("$target_date_key month")); // "対象年月の翌月の値(YYYYMM)"
    $sql = "";
    $count_rows = 0;

    // "対象年月"が既にDBに存在しないか確認
    try {
        // データベース接続
        $db = DB::getDB();
        $sql = "SELECT COUNT( years_months )
                FROM m_deadline
                WHERE years_months=:years_months";

        $stmt = $db->prepare($sql);
        $stmt->bindParam(':years_months', $years_months, PDO::PARAM_STR);
        $stmt->execute();
        $count_rows = $stmt->fetchColumn(); // 対象年月が既にDBに存在すれば1になる

        // 対象年月が既にDBに存在する場合実施
        if ($count_rows == 1) {
            // データベース接続
            $sql = "SELECT deadline_status
                    FROM m_deadline
                    WHERE years_months=:years_months";

            $stmt = $db->prepare($sql);
            $stmt->bindParam(':years_months', $years_months, PDO::PARAM_STR);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($result as $varr) {
                $deadline_status = $varr['deadline_status'];
            }
        }

        // "対象年月"の翌月が既にDBに存在しないか確認

        // データベース接続
        $sql = "SELECT COUNT( years_months )
                FROM m_deadline
                WHERE years_months=:year_next_month";

        $stmt = $db->prepare($sql);
        $stmt->bindParam(':year_next_month', $year_next_month, PDO::PARAM_STR);
        $stmt->execute();
        $count_rows2 = $stmt->fetchColumn(); // 対象年月が既にDBに存在すれば1になる
        $db = NULL;
        $stmt = NULL;
    } catch (Exception $e) {
        die('エラー：' . $e->getMessage());
    }
}

// "締める"ボタンが押された際、実施
if (isset($_POST['deadline'])) {

    // 対象年月が既にDBに存在しない時、締め処理を実施
    if ($count_rows == 0) {
        // 選択した月の"m_deadline"をINSERT
        try {
            // データベース接続
            $db = DB::getDB();
            $sql = "INSERT INTO
      m_deadline (
      years_months,
            deadline_status)
      VALUES(
            :years_months,
            :deadline_status)";

            $stmt = $db->prepare($sql);
            $stmt->bindParam(':years_months', $years_months, PDO::PARAM_STR);
            $stmt->bindValue(':deadline_status', "1", PDO::PARAM_STR);
            $stmt->execute();
            log_message("[{$_SESSION['login_user_id']}]" . pdo_debugStrParams($stmt));

            // 対象年月の翌月が存在しない場合、翌月の"m_deadline"をINSERT
            if ($count_rows2 == 0) {
                $sql = "INSERT INTO
                  m_deadline (
                  years_months,
                        deadline_status)
                  VALUES(
                        :year_next_month,
                        :deadline_status)";

                $stmt = $db->prepare($sql);
                $stmt->bindParam(':year_next_month', $year_next_month, PDO::PARAM_STR);
                $stmt->bindValue(':deadline_status', "0", PDO::PARAM_STR);
                $stmt->execute();
                log_message("[{$_SESSION['login_user_id']}]" . pdo_debugStrParams($stmt));
            }
        } catch (Exception $e) {
            log_message("[{$_SESSION['login_user_id']}]" . $e->getMessage());
            log_message("[{$_SESSION['login_user_id']}]" . pdo_debugStrParams($stmt));
            die('エラー：' . $e->getMessage());
        }
    } elseif ($deadline_status == 0) {
        // 対象年月のdeadline_statusが0のとき、締め処理を実施
        try {
            // データベース接続
            $db = DB::getDB();
            $sql = "UPDATE m_deadline
                    SET deadline_status = 1
                    WHERE years_months=:years_months";

            $stmt = $db->prepare($sql);
            $stmt->bindParam(':years_months', $years_months, PDO::PARAM_STR);
            $stmt->execute();
            log_message("[{$_SESSION['login_user_id']}]" . pdo_debugStrParams($stmt));
        } catch (Exception $e) {
            log_message("[{$_SESSION['login_user_id']}]" . $e->getMessage());
            log_message("[{$_SESSION['login_user_id']}]" . pdo_debugStrParams($stmt));
            die('エラー：' . $e->getMessage());
        }
    } else {
        echo '指定された対象年月は既に締められています';
    }

    // 対象年月が作成されていなかった。もしくは$deadline_statusが0の場合実施
    if ($count_rows == 0 or $deadline_status == 0) {
        // テーブル"month_stock"を更新
        // テーブル"month_stock"のvolumeには実在個数を入れる
        try {
            // データベース接続
            $db = DB::getDB();
            // --------------------------------------------------issue257 start-------------------------------------------------------
            $sql = <<< EOM
                        SELECT
                        m_goods.owner_id,
                        m_goods.goods_id,
                        m_goods.color_size_id,
                        IFNULL(SUM(stock_volume), 0) AS stock_volume
                        FROM
                        m_goods
                        LEFT OUTER JOIN
                        stock_results
                        ON
                        m_goods.owner_id = stock_results.owner_id
                        AND
                        m_goods.goods_id = stock_results.goods_id
                        AND
                        m_goods.color_size_id = stock_results.color_size_id
                        AND
                        stock_results_day
                        BETWEEN
                        :first_day
                        AND
                        :last_day
                        WHERE
                        deleted_flag = 0
                        GROUP BY
                        m_goods.owner_id,
                        m_goods.goods_id,
                        m_goods.color_size_id
                        ORDER BY
                        m_goods.owner_id DESC,
                        m_goods.goods_id DESC,
                        m_goods.color_size_id DESC;
EOM;

            // --------------------------------------------------issue257 end---------------------------------------------------------
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':first_day', $first_day, PDO::PARAM_STR);
            $stmt->bindParam(':last_day', $last_day, PDO::PARAM_STR);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($result as $varr) {
                // 実在庫数を得る
                $stock_quantity = get_stock_quantity($db, $varr, $years_months, $first_day, $last_day);

                // month_stockに照会対象月内に存在する入荷実績をINSERT
                $sql = "INSERT IGNORE INTO
                      month_stock (
                      owner_id,
                            goods_id,
                            color_size_id,
                            years_months,
                            volume)
                      VALUES(
                            :owner_id,
                            :goods_id,
                            :color_size_id,
                            :years_months,
                            :volume)";

                $stmt = $db->prepare($sql);
                $stmt->bindParam(':owner_id', $varr['owner_id'], PDO::PARAM_STR);
                $stmt->bindParam(':goods_id', $varr['goods_id'], PDO::PARAM_STR);
                $stmt->bindParam(':color_size_id', $varr['color_size_id'], PDO::PARAM_STR);
                $stmt->bindParam(':years_months', $year_next_month, PDO::PARAM_STR); // 翌月の年月を入れる
                $stmt->bindParam(':volume', $stock_quantity, PDO::PARAM_STR); // 実在庫数
                $stmt->execute();
                log_message("[{$_SESSION['login_user_id']}]" . pdo_debugStrParams($stmt));
            } // foreachの括弧閉じる
            echo '締めが完了しました。';
            $db = NULL;
            $stmt = NULL;
        } catch (Exception $e) {
            log_message("[{$_SESSION['login_user_id']}]" . $e->getMessage());
            log_message("[{$_SESSION['login_user_id']}]" . pdo_debugStrParams($stmt));
            die('エラー：' . $e->getMessage());
        }
    } // if ($count_rows == 0 or $deadline_status == 0)の括弧閉じる
}

// "締めを解除する"ボタンが押された際、実施
if (isset($_POST['release'])) {
    // 対象年月がDBに存在し、かつdeadline_statusが1のとき締め解除処理を実施
    if ($count_rows == 1 and $deadline_status == 1) {
        // "m_deadline"のdeadline_statusを0に変更
        try {
            // データベース接続
            $db = DB::getDB();
            $sql = "UPDATE m_deadline
                    SET deadline_status = 0
                    WHERE years_months=:years_months";

            $stmt = $db->prepare($sql);
            $stmt->bindParam(':years_months', $years_months, PDO::PARAM_STR);
            $stmt->execute();
            log_message("[{$_SESSION['login_user_id']}]" . pdo_debugStrParams($stmt));

            // テーブル"month_stock"から対象年月の翌月のデータをDELETE
            // データベース接続
            $db = DB::getDB();
            $sql = "DELETE FROM month_stock
                    WHERE years_months=:year_next_month";

            $stmt = $db->prepare($sql);
            $stmt->bindParam(':year_next_month', $year_next_month, PDO::PARAM_STR);
            $stmt->execute();
            log_message("[{$_SESSION['login_user_id']}]" . pdo_debugStrParams($stmt));
            $db = NULL;
            $stmt = NULL;
            echo '締めを解除しました。';
        } catch (Exception $e) {
            log_message("[{$_SESSION['login_user_id']}]" . $e->getMessage());
            log_message("[{$_SESSION['login_user_id']}]" . pdo_debugStrParams($stmt));
            die('エラー：' . $e->getMessage());
        }
    } else {
        echo '指定された対象年月はまだ締められていません';
    }
}

?>
<div id="contents">
    <div class="inner">
        <div id="main">
            <main role="main" class="mb-5">
                <h1 class="mt-30px">月締め処理</h1>
                <form action="monthly_closing.php" method="post">
                    <label>対象年月:<select name="target_date">
                            <?php echo $target_date; ?></select></label>
                    <div style="width: 500px;">
                        <div class="mx-auto mt-5 mb-0">
                            <p>上記年月日を</p>
                            <button class="mt-0" type="submit" name="deadline" value="seach">締める</button>
                            <button class="mt-0" type="submit" name="release" value="seach">締めを解除する</button>
                        </div>
                    </div>
                </form>
            </main>
        </div>
        <!-- /#main -->
    </div>
    <!-- /.inner-->
</div>
<!-- /#contents -->
<?php

get_footer();