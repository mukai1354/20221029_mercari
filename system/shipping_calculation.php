<?php
require_once('../common/function.php');
require_once('../db/DB.php');

get_header();

//今月の値を代入。形式は2021-06のように。
$select_month = date("Y-m");

//対象月選択時にその値で上書き
if(!empty($_POST["select_month"])){
    $select_month = h($_POST["select_month"]);
}

//各発送方法名の変数を宣言
$owners = "";
$nekoposu = "";
$click_post = "";
$click_post2 = "";
$yu_packet = "";
$yu_packet2 = "";
$teikeigai = "";
$takkyubin_compact = "";
$takkyubin_compact2 = "";
$letter_pack = "";
$letter_pack2 = "";
$takkyubin = "";
$other = "";

//選択月の初日と最終日を取得
$first_day = date('Y-m-d', strtotime('first day of ' . $select_month));
$last_day = date('Y-m-d', strtotime('last day of ' . $select_month));

if(!empty($_POST["submit"])) {
    try {
        $sql = <<< EOM
                    SELECT
                    m_staff.owner_id,
                    owner,
                    IFNULL(COUNT(shipping_id="1" or NULL),0) AS nekoposu,
                    IFNULL(COUNT(shipping_id="2" or NULL),0) AS click_post,
                    IFNULL(COUNT(shipping_id="3" or NULL),0) AS yu_packet,
                    IFNULL(COUNT(shipping_id="4" or NULL),0) AS teikeigai,
                    IFNULL(COUNT(shipping_id="5" or NULL),0) AS takkyubin_compact,
                    IFNULL(COUNT(shipping_id="6" or NULL),0) AS letter_pack,
                    IFNULL(COUNT(shipping_id="7" or NULL),0) AS takkyubin,
                    IFNULL(COUNT(shipping_id="8" or NULL),0) AS other
                    FROM
                    m_staff
                    LEFT JOIN
                    shipping_request
                    ON
                    m_staff.owner_id = shipping_request.owner_id
                    AND
                    shipping_results_day
                    BETWEEN
                    :first_day
                    AND
                    :last_day
                    GROUP BY
                    owner_id
                    ORDER BY
                    owner_id ASC;
EOM;

        // データベース接続
        $db = DB::getDB();
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':first_day', $first_day, PDO::PARAM_STR);
        $stmt->bindParam(':last_day', $last_day, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $db = null;
        $stmt = null;
    } catch (PDOException $e) {
        die('エラー：' . $e->getMessage());
    }
}
?>

<div id="contents">
    <div class="inner">
        <div id="main">
            <main role="main">
                <h1 class="mt-30px">送料計算</h1>
                <!-- 以下対象月選択 -->
                <form class="mb-5" action="shipping_calculation.php" method ="post">
                    <input type="month" name="select_month" value="<?php echo $select_month; ?>">
                    <input type="submit" name="submit" value="表示">
                </form>
                <!-- 以下対象月選択 -->

                <?php if(!empty($_POST["submit"])): ?>
                <!-- 以下送料計算表 -->
                <table class="border-0  mb-5">
                    <thead class="shipment_record_table_title"><!-- 以下セルの説明 -->
                        <tr>
                            <th class="px-2 w-10rem border-0 bg-white"></th>
                            <th class="w-10rem px-2 py-1" colspan="2">ネコポス</th>
                            <th class="w-10rem px-2 py-1" colspan="2">クリックポスト</th>
                            <th class="w-10rem px-2 py-1" colspan="2">ゆうパケット</th>
                            <th class="w-10rem px-2 py-1" colspan="2">定形外郵便</th>
                            <th class="w-15rem px-2 py-1" colspan="2">宅急便コンパクト</th>
                            <th class="w-15rem px-2 py-1" colspan="2">レターパックプラス</th>
                            <th class="w-10rem px-2 py-1" colspan="2">宅急便</th>
                            <th class="w-10rem px-2 py-1" colspan="2">その他</th>
                        </tr>
                        <tr>
                            <th class="px-2 py-1">商品所有者</th><!-- 商品所有者 -->
                            <th class="px-2 py-1">件数</th><!-- ネコポス -->
                            <th class="px-2 py-1">送料</th>
                            <th class="px-2 py-1">件数</th><!-- クリックポスト -->
                            <th class="px-2 py-1">送料</th>
                            <th class="px-2 py-1">件数</th><!-- ゆうパケット -->
                            <th class="px-2 py-1">送料</th>
                            <th class="px-2 py-1">件数</th><!-- 定形外郵便 -->
                            <th class="px-2 py-1">送料</th>
                            <th class="px-2 py-1">件数</th><!-- 宅急便コンパクト -->
                            <th class="px-2 py-1">送料</th>
                            <th class="px-2 py-1">件数</th><!-- レターパックプラス -->
                            <th class="px-2 py-1">送料</th>
                            <th class="px-2 py-1">件数</th><!-- 宅急便 -->
                            <th class="px-2 py-1">送料</th>
                            <th class="px-2 py-1">件数</th><!-- その他 -->
                            <th class="px-2 py-1">送料</th>
                        </tr>
                    </thead><!-- 以上セルの説明 -->
                    <tbody><!-- 以下表示欄 -->
                        <?php foreach($result as $result2): ?>

                        <?php
                            $owners = $result2["owner"];
                            $nekoposu = $result2["nekoposu"];
                            $click_post = $result2["click_post"];
                            $click_post2 = $click_post * 198;
                            $yu_packet = $result2["yu_packet"];
                            $yu_packet2 = $yu_packet * 65;
                            $teikeigai = $result2["teikeigai"];
                            $takkyubin_compact = $result2["takkyubin_compact"];
                            $takkyubin_compact2 = $takkyubin_compact * 70;
                            $letter_pack = $result2["letter_pack"];
                            $letter_pack2 = $letter_pack * 520;
                            $takkyubin = $result2["takkyubin"];
                            $other = $result2["other"];
                        ?>

                        <tr class="tr-even">
                            <td class="px-2 py-1"><?php echo $owners; ?></td><!-- 商品所有者 -->
                            <td class="px-2 py-1 text-right"><?php echo $nekoposu; ?></td><!-- ネコポス -->
                            <td class="px-2 py-1 text-right table-secondary"></td>
                            <td class="px-2 py-1 text-right"><?php echo $click_post; ?></td><!-- クリックポスト -->
                            <td class="px-2 py-1 text-right"><?php echo $click_post2. "&ensp;円"; ?></td>
                            <td class="px-2 py-1 text-right"><?php echo $yu_packet; ?></td><!-- ゆうパケット -->
                            <td class="px-2 py-1 text-right"><?php echo $yu_packet2. "&ensp;円"; ?></td>
                            <td class="px-2 py-1 text-right"><?php echo $teikeigai; ?></td><!-- 定形外郵便 -->
                            <td class="px-2 py-1 text-right table-secondary"></td>
                            <td class="px-2 py-1 text-right"><?php echo $takkyubin_compact; ?></td><!-- 宅急便コンパクト -->
                            <td class="px-2 py-1 text-right"><?php echo $takkyubin_compact2. "&ensp;円"; ?></td>
                            <td class="px-2 py-1 text-right"><?php echo $letter_pack; ?></td><!-- レターパックプラス -->
                            <td class="px-2 py-1 text-right"><?php echo $letter_pack2. "&ensp;円"; ?></td>
                            <td class="px-2 py-1 text-right"><?php echo $takkyubin; ?></td><!-- 宅急便 -->
                            <td class="px-2 py-1 text-right table-secondary"></td>
                            <td class="px-2 py-1 text-right"><?php echo $other; ?></td><!-- その他 -->
                            <td class="px-2 py-1 text-right table-secondary"></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody><!-- 以上表示欄 -->
                </table>
                <!-- 以上送料計算表 -->
                <?php endif; ?>
            </main>
        </div>
    </div>
</div>

<?php
get_footer();