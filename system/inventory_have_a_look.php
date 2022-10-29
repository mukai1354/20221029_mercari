<?php
require_once('../common/function.php');
require_once('../db/DB.php');

date_default_timezone_set('Asia/Tokyo');

$date = date("Y-m-d");
if (isset($_POST['last_day'])) {
    $date = $_POST['last_day'];
}
$search = null;

if (isset($_POST['last_day'])) {
    $last_day = $_POST['last_day'];
}

if (isset($_POST['select_owner'])) {
    $select_owner = $_POST['select_owner'];
}

if (isset($_POST['search'])) {
    $search = $_POST['search'];
    $target_date_key = getpstStrs("last_day");
    $years_months = date("Ym", strtotime("$target_date_key"));
    $first_day = date("Y-m-d", strtotime('first day of ' . "$target_date_key")); // "対象年月の初日(YYYY-MM-D1)"
    $last_day = date("Y-m-d", strtotime($last_day)); // "対象年月の指定日(YYYY-MM-DD)"
}

get_header();

// エラー情報リセット
$_SESSION["error_status"] = E_STATUS_DEFAULT;

$owner_id = '';
$owner_id = h($_SESSION['login_owner_id']);
$login_role = $_SESSION['login_role'];


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
$all_owners = null;
foreach ($result_staff as $varr) {
    $all_owners[] = $varr;
}

$select_all = '';

foreach ($result_staff as $varr) {
    $owner .= "\t\t\t\t\t<option value=\"" . $varr['owner_id'] . "\"";
    if (isset($select_owner)) {
        if ($select_owner == 0) {
            $select_all = 'selected';
        } elseif ($varr['owner_id'] == $select_owner) {
            $owner .= " selected";
        }
    } elseif ($varr['owner_id'] == $owner_id) {
        $owner .= " selected";
    }

    $owner .= ">" . $varr['owner'] . "</option>\n";
}

if (isset($search)) {
    // 商品マスタ取得
    // 以下商品所有者非選択時
    if ($select_owner == 0) {
        $contents2 = [];
        foreach ($all_owners as $owners) {
            $owners_id = $owners["owner_id"];

            try {
                // データベース接続
                $db = DB::getDB();

                // 最後のレコードからorder_noを取得
                $sql = "SELECT
                    m_staff.owner_id,
                    owner,
                    goods_id,
                    color,
                    color_size_id,
                    goods_name,
                    size,
                    picture,
                    user_id
                    FROM
                    m_staff
                    INNER JOIN
                    m_goods
                    using(owner_id)
                    LEFT JOIN
                    m_user
                    using(owner_id)
                    where owner_id= :owner_id
                    AND
                    m_goods.deleted_flag = 0
                    ORDER BY
                    m_staff.owner_id ASC,
                    goods_id DESC,
                    color_size_id DESC;";
                $mg_stmt = $db->prepare($sql);
                $mg_stmt->bindParam(':owner_id', $owners_id, PDO::PARAM_STR);
                $mg_stmt->execute();
                $mg_result = $mg_stmt->fetchAll(PDO::FETCH_ASSOC);

                $db = null;
                $mg_stmt = null;
            } catch (PDOException $e) {
                die('エラー：' . $e->getMessage());
            }

            // 実在庫数の取得
            // テーブル"month_stock"のvolumeには実在個数を入れる
            $stock_quantity = null;
            $result = null;
            try {
                // データベース接続
                $db = DB::getDB();
                $sql = "SELECT
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
                        m_goods.owner_id = :owner_id
                        AND
                        deleted_flag = 0
                        GROUP BY
                        m_goods.goods_id,
                        m_goods.color_size_id
                        ORDER BY
                        m_goods.goods_id DESC,
                        m_goods.color_size_id DESC;";
                $stmt = $db->prepare($sql);
                $stmt->bindParam(':first_day', $first_day, PDO::PARAM_STR);
                $stmt->bindParam(':last_day', $last_day, PDO::PARAM_STR);
                $stmt->bindParam(':owner_id', $owners_id, PDO::PARAM_STR);
                $stmt->execute();
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($result as $varr) {
                    $stock_quantity[] = get_stock_quantity($db, $varr, $years_months, $first_day, $last_day);
                }
                $db = null;
                $stmt = null;
            } catch (PDOException $e) {
                die('エラー：' . $e->getMessage());
            }
            // 出荷実績在庫取得
            try {
                // データベース接続
                $db = DB::getDB();

                $sql1 = "DROP TEMPORARY TABLE IF EXISTS volume_table;";
                $sh_stmt = $db->query($sql1);
                $sh_stmt->execute();

                $sql2 = "CREATE TEMPORARY TABLE IF NOT EXISTS volume_table(
                        owner_id varchar(4),
                        goods_id varchar(4),
                        color_size_id varchar(2),
                        volume int(4));";
                $sh_stmt = $db->query($sql2);
                $sh_stmt->execute();

                $sql3 = "INSERT INTO
                        volume_table
                        SELECT
                        m_goods.owner_id,
                        m_goods.goods_id,
                        m_goods.color_size_id,
                        IFNULL(sum(volume),0) AS volume
                        FROM
                        m_goods
                        LEFT JOIN
                        shipping_request
                        USING(owner_id,goods_id,color_size_id)
                        WHERE
                        owner_id = :owner_id
                        AND
                        completion_flag < 2
                        GROUP BY
                        owner_id,
                        goods_id,
                        color_size_id
                        ORDER BY
                        owner_id ASC,
                        goods_id DESC,
                        color_size_id DESC;";
                $sh_stmt = $db->prepare($sql3);
                //$sh_stmt->bindParam(':first_day', $first_day, PDO::PARAM_STR);
                //$sh_stmt->bindParam(':last_day', $last_day, PDO::PARAM_STR);
                $sh_stmt->bindParam(':owner_id', $owners_id, PDO::PARAM_STR);
                $sh_stmt->execute();

                $sql4 = "SELECT
                        m_goods.owner_id,
                        m_goods.goods_id,
                        m_goods.color_size_id,
                        IFNULL(volume, 0) AS volume
                        FROM
                        m_goods
                        LEFT JOIN
                        volume_table
                        USING(owner_id,goods_id,color_size_id)
                        WHERE
                        owner_id = :owner_id
                        AND
                        deleted_flag =0
                        ORDER BY
                        m_goods.owner_id ASC,
                        m_goods.goods_id DESC,
                        m_goods.color_size_id DESC;";
                $sh_stmt = $db->prepare($sql4);
                $sh_stmt->bindParam(':owner_id', $owners_id, PDO::PARAM_STR);
                $sh_stmt->execute();
                $sh_result = $sh_stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                die('エラー：' . $e->getMessage());
            }

            // 未出荷数の取得
            $not_shipped = null;

            foreach ($sh_result as $value) {
                $not_shipped[] = $value['volume'];
            }

            $contents = '';
            $counter_i = 0;
            $effective_stock = [];
            foreach ($mg_result as $varr) {
                if ($not_shipped[$counter_i] == null) {
                    $not_shipped[$counter_i] = 0;
                }

                $effective_stock[$counter_i] = $stock_quantity[$counter_i] - $not_shipped[$counter_i];
                $goods_id = $varr["goods_id"];
                $color = $varr["color"];
                $goods_name = $varr["goods_name"];
                $size = $varr["size"];
                $picture = "../common/images/" . $varr['owner_id'] . "/goods/" . $varr['picture'];
                $product_management_id = $varr['user_id'].'_'.$varr['goods_id'].'_'.$varr['color_size_id'];
                $contents .= <<< EOM
                <tr class="tr_even">
                    <td class="align-middle px-2" rowspan="2">$product_management_id</td>
                    <td class="align-middle">$goods_id</td>
                    <td class="align-middle">$color</td>
EOM;
                if(!empty($_POST["disp"])) {
                    $contents .= <<< EOM
                    <td class="border-none align-middle bg-white" rowspan="2"></td>
                    <td rowspan="2"><img id = "picture" name="picture_$counter_i" src="$picture" width="55px" height="55px"></td>
EOM;
}
                    $contents .= <<< EOM
                    <td class="border-none bg-white" rowspan="2"></td>
                    <td class="align-middle" rowspan="2">$effective_stock[$counter_i]</td>
                    <td class="align-middle" rowspan="2">$not_shipped[$counter_i]</td>
                    <td class="align-middle" rowspan="2">$stock_quantity[$counter_i]</td>
                </tr>
                <tr class="tr_even2">
                    <td class="align-middle">$goods_name</td>
                    <td class="align-middle">$size</td>
                </tr>
EOM;
                $counter_i ++;
            }
            $contents2[] = $contents;
        }
        // 以上商品所有者非選択時
    } else {
        // 以下商品所有者選択時

        try {
            // データベース接続
            $db = DB::getDB();

            // 最後のレコードからorder_noを取得
            $sql = "SELECT
                m_staff.owner_id,
                owner,
                goods_id,
                color,
                color_size_id,
                goods_name,
                size,
                picture,
                user_id
                FROM
                m_staff
                INNER JOIN
                m_goods
                using(owner_id)
                LEFT JOIN
                m_user
                using(owner_id)
                WHERE
                m_staff.owner_id = :owner_id
                AND
                m_goods.deleted_flag = 0
                ORDER BY
                goods_id DESC,
                color_size_id DESC;";

            $mg_stmt = $db->prepare($sql);
            $mg_stmt->bindParam(':owner_id', $select_owner, PDO::PARAM_STR);
            $mg_stmt->execute();
            $mg_result = $mg_stmt->fetchAll(PDO::FETCH_ASSOC);

            $db = null;
            $mg_stmt = null;
        } catch (PDOException $e) {
            die('エラー：' . $e->getMessage());
        }

        // 実在庫数の取得
        // テーブル"month_stock"のvolumeには実在個数を入れる
        try {
            // データベース接続
            $db = DB::getDB();
            $sql = "SELECT
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
                    m_goods.owner_id = :select_owner
                    AND
                    deleted_flag = 0
                    GROUP BY
                    m_goods.goods_id,
                    m_goods.color_size_id
                    ORDER BY
                    m_goods.goods_id DESC,
                    m_goods.color_size_id DESC;";

            $stmt = $db->prepare($sql);
            $stmt->bindParam(':first_day', $first_day, PDO::PARAM_STR);
            $stmt->bindParam(':last_day', $last_day, PDO::PARAM_STR);
            $stmt->bindParam(':select_owner', $select_owner, PDO::PARAM_STR);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($result as $varr) {
                $stock_quantity[] = get_stock_quantity($db, $varr, $years_months, $first_day, $last_day);
            }
            $db = null;
            $stmt = null;
        } catch (PDOException $e) {
            die('エラー：' . $e->getMessage());
        }

        // 出荷実績在庫取得
        try {
            // データベース接続
            $db = DB::getDB();
            $sql1 = "DROP TEMPORARY TABLE IF EXISTS volume_table;";
            $sh_stmt = $db->query($sql1);
            $sh_stmt->execute();

            $sql2 = "CREATE TEMPORARY TABLE IF NOT EXISTS volume_table(
                        owner_id varchar(4),
                        goods_id varchar(4),
                        color_size_id varchar(2),
                        volume int(4));";
            $sh_stmt = $db->query($sql2);
            $sh_stmt->execute();

            $sql3 = "INSERT INTO
                        volume_table
                        SELECT
                        m_goods.owner_id,
                        m_goods.goods_id,
                        m_goods.color_size_id,
                        IFNULL(sum(volume),0) AS volume
                        FROM
                        m_goods
                        LEFT JOIN
                        shipping_request
                        USING(owner_id,goods_id,color_size_id)
                        WHERE
                        deleted_flag = 0
                        AND
                        owner_id = :owner_id
                        AND
                        completion_flag < 2
                        GROUP BY
                        owner_id,
                        goods_id,
                        color_size_id
                        ORDER BY
                        goods_id DESC,
                        color_size_id DESC;";
            $sh_stmt = $db->prepare($sql3);
            //$sh_stmt->bindParam(':first_day', $first_day, PDO::PARAM_STR);
            //$sh_stmt->bindParam(':last_day', $last_day, PDO::PARAM_STR);
            $sh_stmt->bindParam(':owner_id', $select_owner, PDO::PARAM_STR);
            $sh_stmt->execute();

            $sql4 = "SELECT
                        m_goods.owner_id,
                        m_goods.goods_id,
                        m_goods.color_size_id,
                        IFNULL(volume, 0) AS volume
                        FROM
                        m_goods
                        LEFT JOIN
                        volume_table
                        USING(owner_id,goods_id,color_size_id)
                        WHERE
                        owner_id = :owner_id
                        AND
                        deleted_flag = 0
                        ORDER BY
                        m_goods.goods_id DESC,
                        m_goods.color_size_id DESC;";
            $sh_stmt = $db->prepare($sql4);
            $sh_stmt->bindParam(':owner_id', $select_owner, PDO::PARAM_STR);
            $sh_stmt->execute();
            $sh_result = $sh_stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die('エラー：' . $e->getMessage());
        }
        // 未出荷数の取得
        $not_shipped = [];

        foreach ($sh_result as $value) {
            $not_shipped[] = $value['volume'];
        }

        $contents = '';
        $counter_i = 0;
        $effective_stock = 0;
        foreach ($mg_result as $varr) {
            $effective_stock = $stock_quantity[$counter_i] - $not_shipped[$counter_i];
            $goods_id = $varr["goods_id"];
            $color = $varr["color"];
            $goods_name = $varr["goods_name"];
            $size = $varr["size"];
            $picture = "../common/images/" . $varr['owner_id'] . "/goods/" . $varr['picture'];
            $product_management_id = $varr['user_id'].'_'.$varr['goods_id'].'_'.$varr['color_size_id'];
            $contents .= <<< EOM
            <tr class="tr_even">
                            <td class="align-middle px-2 br-1" rowspan="2">$product_management_id</td>
                            <td class="align-middle px-2 border-left-0 br-1 bb-1">$goods_id</td>
                            <td class="align-middle px-2 border-left-0 bb-1">$color</td>
EOM;

            if(!empty($_POST["disp"])) {
                $contents .= <<< EOM
                    <td class="border-none align-middle bg-white" rowspan="2"></td>
                    <td rowspan="2"><img id = "picture" name="picture_$counter_i" src="$picture" width="55px" height="55px"></td>
EOM;
            }
            $contents .= <<< EOM
                            <td class="border-none align-middle bg-white" rowspan="2"></td>
                            <td class="align-middle br-1" rowspan="2">$effective_stock</td>
                            <td class="align-middle border-left-0 br-1" rowspan="2">$not_shipped[$counter_i]</td>
                            <td class="align-middle border-left-0" rowspan="2">$stock_quantity[$counter_i]</td>
                        </tr>
                        <tr class="tr_even2">
                            <td class="align-middle bt-0 border-left-0 border-top-0 br-1">$goods_name</td>
                            <td class="align-middle border-left-0 border-top-0">$size</td>
                        </tr>
EOM;
            $counter_i ++;
        }
    }
}
// 以上商品所有者選択時
?>
<div id="contents">
    <div class="inner">
        <div id="main">
            <main role="main">
                <h1 class="mt-3">在庫一覧</h1>
                <form method="post">
                    <div class="w-75 mx-auto mb-5 d-flex justify-content-around">
                        <label class="mr-3" for="date">年月日:&ensp;<input id="date" type="date" name="last_day" value="<?php echo $date; ?>"></label>
                        <?php if($login_role != 1): ?>
                        <label>商品所有者:&ensp;
                            <select name="select_owner">
                                <option value="0" <?php echo $select_all; ?>></option>
                                <?php echo $owner?>
                            </select>
                        </label>
                        <?php endif; ?>
                        <label>画像表示：&ensp;<input type="checkbox" name="disp" value="disp"></label>
                        <button class="mt-0" type="submit" name="search" value="search">表示</button>
                    </div>
                    <?php if($login_role == 1): ?>
                    <input type="hidden" name="select_owner" value="<?php echo $_SESSION['login_owner_id']; ?>">
                    <?php else: ?>

                <?php endif; ?>


                <?php

                if (isset($search)) :
                    ?>
                </form>
                <!-- 以下所有者非選択時 -->
                    <?php

                    if ($select_owner == 0) :
                        ?>
                    <?php

                        $i = 0;
                        ?>
                        <?php

                        foreach ($all_owners as $value) :
                            ?>
                        <div style="margin-bottom: 200px;">
                          <h3 class="mt-5 mb-3"><?php

                            echo $value['owner'];
                            ?></h3>
                          <table class="border-none mx-auto mb-5">
                          <thead class="shipment_record_table_title">
                              <tr>
                                  <th class="align-middle w-10rem" scope="col" rowspan="2">管理コード</th>
                                  <th class="w-30rem" scope="col">商品コード</th>
                                  <th class="w-10rem" scope="col">色</th>
                                  <?php if(!empty($_POST["disp"])): ?>
                                  <th class="border-none bg-white" width="30px" scope="col" rowspan="2"></th>
                                  <th class="border-none bg-white" scope="col"></th>
                                  <?php endif; ?>
                                  <th class="border-none bg-white" width="30px" scope="col" rowspan="2"></th>
                                  <th class="w-7rem px-2 align-middle" scope="col" rowspan="2">有効在庫数</th>
                                  <th class="w-6rem px-2 align-middle" scope="col" rowspan="2">未出荷数</th>
                                  <th class="w-6rem px-2 align-middle" scope="col" rowspan="2">実在庫数</th>
                              </tr>
                              <tr>
                                  <th class="" scope="col">商品名</th>
                                  <th class="" scope="col">サイズ</th>
                                  <?php if(!empty($_POST["disp"])): ?>
                                  <th class="" scope="col" rowspan="2">商品画像</th>
                                  <?php endif; ?>
                              </tr>
                          </thead>
                          <tbody>
                              <?php

                            echo $contents2[$i];
                            ?>
                          </tbody>
                      </table>
                    </div>
                    <?php

                            $i ++;
                            ?>
                        <?php
                        endforeach
                        ;
                        ?>
                    <!-- 以上所有者非選択時 -->
                    <!-- 以下所有者選択時 -->
                    <?php

                    else :
                        ?>
                    <table class="mt-5 border-none mx-auto">
                    <thead class="shipment_record_table_title">
                        <tr>
                            <th class="w-10rem align-middle border-bottom-0 br-1" scope="col" rowspan="2">管理コード</th>
                            <th class="w-30rem border-left-0 br-1 bb-1" scope="col">商品コード</th>
                            <th class="w-10rem border-left-0 bb-1" scope="col">色</th>
                            <?php if(!empty($_POST["disp"])): ?>
                            <th class="border-none bg-white" width="30px" scope="col" rowspan="2"></th>
                            <th class="border-none bg-white" scope="col"></th>
                            <?php endif;?>
                            <th class="border-none bg-white" width="30px" scope="col" rowspan="2"></th>
                            <th class="w-7rem px-2 align-middle br-1" scope="col" rowspan="2">有効在庫数</th>
                            <th class="w-6rem px-2 align-middle border-left-0  br-1" scope="col" rowspan="2">未出荷数</th>
                            <th class="w-6rem px-2 align-middle border-left-0" scope="col" rowspan="2">実在庫数</th>
                        </tr>
                        <tr>
                            <th class="border-top-0 border-left-0 br-1" scope="col">商品名</th>
                            <th class="border-top-0 border-left-0" scope="col">サイズ</th>
                            <?php if(!empty($_POST["disp"])): ?>
                            <th class="" scope="col" rowspan="2">商品画像</th>
                            <?php endif;?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php

                        echo $contents;
                        ?>
                    </tbody>
                </table>
                    <?php

                    endif;
                    ?>
                    <!-- 以上所有者選択時 -->
 <?php

endif;

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
