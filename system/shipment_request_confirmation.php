<?php
// 2021/10/15 issue289 出荷依頼確認　出荷予定日の変更 demachi
// 2021/08/04 issue236 二重送信防止のロジックが出荷依頼登録以外は実装されていない demachi
// -------------------------------------------------------issue289 start------------------------------------------------
require_once('../common/function.php');
require_once('../db/DB.php');

get_header();

// --------------------------------------------------issue236 start-------------------------------------------------------
if (isset($_POST['confirm'])) {
    // トークンの確認
    $is_token_valid = is_token_valid('token', 'token_in_shipping_request_confirmation');
    if (!$is_token_valid) {
        die('ダブりでの送信を検知したので１回分のみ出荷確認しました。');
    }
}

$token = get_csrf_token(); // トークンの発行
$_SESSION["token_in_shipping_request_confirmation"] = $token; //トークンをセッション変数にセット

// 今日の日付を初期値に設定
$today = date("Y-m-d");
$selected_date = $today;
if (!empty($_POST["date_select"])) {
    $selected_date = $_POST["date_select"];
}

// ユーザの権限を取得
$login_role = $_SESSION['login_role'];

// ログインしたユーザのowner_idを初期値に設定
$select_owner_id = '';
if (!empty($_SESSION['login_owner_id'])) {
    $select_owner_id = $_SESSION['login_owner_id'];
}
if (!empty($_POST['select_owner_id'])) {
    $select_owner_id = $_POST['select_owner_id'];
}

// 商品所有者選択用のドロップダウンメニューのオプションタグを取得
$option_tags_for_owners = '';
try {
    // データベース接続
    $db = DB::getDB();
    $option_tags_for_owners = get_option_tags_for_owners($db, $select_owner_id);
    $db = null;
} catch (PDOException $e) {
    die('エラー：' . $e->getMessage());
}

$array_fetched_shipping_requests = [];
// "表示"ボタンが押された際実施
if (isset($_POST['display'])) {
    // "shipping_request"から選択された日付より前のshipping_scheduled_dayに
    // 該当するすべての値を取得する
    try {
        // データベース接続
        $db = DB::getDB();

        $sql = <<< EOM
            SELECT
                sh.owner_id,
                sh.shipping_request_no,
                sh.details_no,
                sh.goods_id,
                sh.record_day,
                sh.shipping_scheduled_day,
                sh.volume,
                sh.shipping_id,
                sh.zip_code,
                sh.address,
                sh.destination_name,
                sh.other_name,
                sh.shipment_source,
                sh.shipment_source_name,
                sh.convenience_store_qr_code,
                sh.other_qr_code,
                sh.updated_at,
                sh.remarks,
                sh.seq,
                mg.goods_name,
                mg.color,
                mg.size,
                mg.picture
            FROM
                shipping_request AS sh
                LEFT OUTER JOIN
                m_goods AS mg
                ON
                sh.owner_id = mg.owner_id
                AND
                sh.goods_id = mg.goods_id
                AND
                sh.color_size_id = mg.color_size_id
            WHERE
                sh.shipping_scheduled_day <= :shipping_scheduled_day
                AND
                sh.owner_id = :owner_id
                AND
                sh.completion_flag = :completion_flag
                AND
                sh.volume > 0
            ORDER BY
                sh.shipping_request_no,
                sh.details_no
            ;
            EOM;

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':shipping_scheduled_day', $selected_date);
        $stmt->bindValue(':owner_id', $select_owner_id);
        $stmt->bindValue(':completion_flag', COMPLETION_FLAG_INCOMPLETE);
        $stmt->execute();
        $array_fetched_shipping_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $db = null;
        $stmt = null;
    } catch (PDOException $e) {
        die('エラー：' . $e->getMessage());
    }
}

// "確定"ボタンを押下した際に実行
if (isset($_POST["confirm"])) {
    $count_posts = count($_POST["shipping_request_no"]);
    $ii = 0;
    $array_posted_shipping_requests = [];
    while ($ii < $count_posts) {
        $posted_shipping_request = [];
        $posted_shipping_request = [
            "owner_id" =>                                   $select_owner_id,
            "shipping_request_no" =>                        $_POST["shipping_request_no"][$ii],
            "details_no" =>                                 $_POST["details_no"][$ii],
            "updated_at" =>                                 $_POST["updated_at"][$ii],
        ];
        $array_posted_shipping_requests[] = $posted_shipping_request;
        $ii++;
    }

    try {
        $db = DB::getDB();
        $db->beginTransaction();

        foreach ($array_posted_shipping_requests as $posted_shipping_request) {
            $posted_updated_at = $posted_shipping_request["updated_at"];

            $up_owner_id = $posted_shipping_request["owner_id"];
            $up_shipping_request_no = (int)$posted_shipping_request["shipping_request_no"];
            $up_details_no = (int)$posted_shipping_request["details_no"];

            // 他のユーザと同時に更新しようとしている場合は例外をスローする。
            if (!check_shipping_request_record_for_exclusive_control(
                $db,
                $up_owner_id,
                $up_shipping_request_no,
                $up_details_no,
                $posted_updated_at
            )) {
                throw new ExclusiveControlException();
            }

            $sql = <<< EOM
                UPDATE
                    shipping_request
                SET
                    completion_flag = :completion_flag
                WHERE
                    owner_id = :owner_id
                    AND
                    shipping_request_no = :shipping_request_no
                    AND
                    details_no = :details_no
                ;
                EOM;
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':completion_flag', COMPLETION_FLAG_CONFIRMED);
            $stmt->bindValue(':owner_id', $up_owner_id);
            $stmt->bindValue(':shipping_request_no', $up_shipping_request_no, PDO::PARAM_INT);
            $stmt->bindValue(':details_no', $up_details_no, PDO::PARAM_INT);
            $stmt->execute();
            log_message("[{$_SESSION['login_user_id']}]" . pdo_debugStrParams($stmt));
        }

        $db->commit();
        $db = null;
        $stmt = null;
    } catch (PDOException | ExclusiveControlException $e) {
        log_message("[{$_SESSION['login_user_id']}]" . $e->getMessage());
        if (isset($stmt)) {
            log_message("[{$_SESSION['login_user_id']}]" . pdo_debugStrParams($stmt));
        }
        $db->rollBack();
        die('エラー：' . $e->getMessage());
    }

    echo '<p class="text-center mt-5">更新が完了しました。</p>';
}
?>

<!-- 以下SP -->
<main role="main">
    <div class="row mb-5 d-lg-none search_wrapper">
        <div class="col-12 mb-5">
            <h1 class="page_title">出荷依頼確認</h1>
            <form method="post" action="shipment_request_confirmation.php">
                <?php if ($login_role == ROLE_SELLER || $login_role == ROLE_TESHITA) : ?>
                <input type="hidden" name="select_owner_id" value="<?php echo h($_SESSION['login_owner_id']); ?>">
                <?php else : ?>
                <div class="d-flex mb-3">
                    <p class="d-inline-block select_owner_text ml-auto">商品所有者：</p>
                    <select id="sp_select_owner_id" class="mr-auto select_owner_id1" name="select_owner_id">
                        <?php echo $option_tags_for_owners; ?>
                    </select>
                </div>
                <?php endif; ?>
                <div class="d-flex mb-3">
                    <p class="d-inline-block select_owner_text ml-auto">対象日：</p>
                    <input class="mr-auto" type="date" name="date_select" value="<?php echo h($selected_date); ?>">
                </div>
                <button id="display" class="button mb-5" type="submit" name="display">表示</button>
            </form>
        <?php if (isset($_POST['display'])) : ?>
            <?php if (!empty($array_fetched_shipping_requests)) : ?>
            <form method="post" action="shipment_request_confirmation.php">
                <?php if ($login_role == ROLE_SELLER || $login_role == ROLE_TESHITA) : ?>
                <input type="hidden" name="select_owner_id" value="<?php echo h($_SESSION['login_owner_id']); ?>">
                <?php else : ?>
                <input class="select_owner_id2" type="hidden" name="select_owner_id" value="<?php echo h($select_owner_id); ?>">
                <?php endif; ?>
                <?php foreach ($array_fetched_shipping_requests as $fetched_shipping_request) : ?>
                    <?php
                    $no = $fetched_shipping_request["seq"];
                    $owner_id = $fetched_shipping_request["owner_id"];
                    $shipping_request_no = $fetched_shipping_request["shipping_request_no"];
                    $details_no = $fetched_shipping_request["details_no"];
                    $goods_id = $fetched_shipping_request["goods_id"];
                    $record_day = $fetched_shipping_request["record_day"];
                    $shipping_scheduled_day = $fetched_shipping_request["shipping_scheduled_day"];
                    $volume = $fetched_shipping_request["volume"];
                    $shipping_id = $fetched_shipping_request["shipping_id"];
                    $zip_code = $fetched_shipping_request["zip_code"];
                    $address = $fetched_shipping_request["address"];
                    $destination_name = $fetched_shipping_request["destination_name"];
                    $other_name = $fetched_shipping_request["other_name"];
                    $shipment_source = $fetched_shipping_request["shipment_source"];
                    $shipment_source_name = $fetched_shipping_request["shipment_source_name"];
                    $convenience_store_qr_code = $fetched_shipping_request["convenience_store_qr_code"];
                    $other_qr_code = $fetched_shipping_request["other_qr_code"];
                    $updated_at = $fetched_shipping_request["updated_at"];
                    $remarks = $fetched_shipping_request["remarks"];
                    $goods_name = $fetched_shipping_request["goods_name"];
                    $color = $fetched_shipping_request["color"];
                    $size = $fetched_shipping_request["size"];
                    $picture = $fetched_shipping_request["picture"];
                    if ($shipping_scheduled_day == $today) {
                        $alert = "当日出荷です　";
                    } elseif ($shipping_scheduled_day < $today) {
                        $alert = "出荷日を過ぎています　";
                    } else {
                        $alert = "";
                    }
                    ?>
                <input type="hidden" name="shipping_request_no[]" value="<?php echo h($shipping_request_no); ?>">
                <input type="hidden" name="details_no[]" value="<?php echo h($details_no); ?>">
                <input type="hidden" name="updated_at[]" value="<?php echo h($updated_at); ?>">
                <table class="table mb-5 sp_table_even">
                    <caption class="caption h4 font-weight-bold">No.<?php echo h($no); ?></caption>
                    <tbody class="target1">
                        <tr class="sp_column_name">
                            <th colspan="2">商品名</th>
                        </tr>
                        <tr>
                            <td colspan="2"><?php echo h($goods_name); ?></td>
                        </tr>
                        <tr class="sp_column_name">
                            <th class="table_cell_half">色</th>
                            <th class="table_cell_half">サイズ</th>
                        </tr>
                        <tr>
                            <td class="table_cell_half"><?php echo h($color); ?></td>
                            <td class="table_cell_half"><?php echo h($size); ?></td>
                        </tr>
                        <tr class="sp_column_name">
                            <th colspan="2">商品画像</th>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <div class="goods_img_wrapper">
                                    <img class="goods_img" src="<?php echo h(HOME_URL); ?>/common/images/<?php echo h($owner_id); ?>/goods/<?php echo h($picture); ?>">
                                </div>
                            </td>
                        </tr>
                        <tr class="sp_column_name">
                            <th colspan="2">出荷数量</th>
                        </tr>
                        <tr>
                            <td colspan="2"><?php echo h($volume); ?></td><!-- 数量 -->
                        </tr>
                        <tr class="sp_column_name">
                            <th colspan="2">発送方法</th>
                        </tr>
                        <tr>
                            <td colspan="2"><?php echo h(get_shipping_method_by_shipping_id($shipping_id)); ?></td>
                        </tr>
                        <tr class="sp_column_name">
                            <th colspan="2">コンビニ出荷用バーコード</th>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <div class="goods_img_wrapper">
                                    <img class="goods_img" src="<?php echo h(HOME_URL); ?>/common/images/<?php echo h($owner_id); ?>/qr/<?php echo h($convenience_store_qr_code); ?>">
                                </div>
                            </td>
                        </tr>
                        <tr class="sp_column_name">
                            <th colspan="2">ヤマト営業所（郵便局）QRコード</th>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <div class="goods_img_wrapper">
                                    <img class="goods_img" src="<?php echo h(HOME_URL); ?>/common/images/<?php echo h($owner_id); ?>/qr/<?php echo h($other_qr_code); ?>">
                                </div>
                            </td>
                        </tr>
                        <tr class="sp_column_name">
                            <th colspan="2">郵便番号</th>
                        </tr>
                        <tr>
                            <td colspan="2"><?php echo h($zip_code); ?></td>
                        </tr>
                        <tr class="sp_column_name">
                            <th colspan="2">住所</th>
                        </tr>
                        <tr>
                            <td colspan="2"><?php echo h($address); ?></td>
                        </tr>
                        <tr class="sp_column_name">
                            <th colspan="2">送付先氏名</th>
                        </tr>
                        <tr>
                            <td colspan="2"><?php echo h($destination_name); ?></td>
                        </tr>
                        <tr class="sp_column_name">
                            <th colspan="2">その他氏名</th>
                        </tr>
                        <tr>
                            <td colspan="2"><?php echo h($other_name); ?></td>
                        </tr>
                        <tr class="sp_column_name">
                            <th colspan="2">発送元住所</th>
                        </tr>
                        <tr>
                            <td colspan="2"><?php echo h($shipment_source); ?></td>
                        </tr>
                        <tr class="sp_column_name">
                            <th colspan="2">発送元氏名</th>
                        </tr>
                        <tr>
                            <td colspan="2"><?php echo h($shipment_source_name); ?></td>
                        </tr>
                        <tr class="sp_column_name">
                            <th colspan="2">出荷予定日</th>
                        </tr>
                        <tr>
                            <td colspan="2"><?php echo h($shipping_scheduled_day); ?></td>
                        </tr>
                        <tr class="sp_column_name">
                            <th colspan="2">出荷依頼No.</th>
                        </tr>
                        <tr>
                            <td colspan="2"><?php echo h($shipping_request_no); ?></td>
                        </tr>
                        <tr class="sp_column_name">
                            <th colspan="2">備考欄</th>
                        </tr>
                        <tr>
                            <td colspan="2"><?php echo h($remarks); ?></td>
                        </tr>
                        <tr class="sp_column_name">
                            <th colspan="2">登録時間</th>
                        </tr>
                        <tr>
                            <td colspan="2"><?php echo h($record_day); ?></td>
                        </tr>
                        <tr class="sp_column_name">
                            <th colspan="2">アラート</th>
                        </tr>
                        <tr>
                            <td colspan="2"><?php echo h($alert); ?></td>
                        </tr>
                    </tbody>
                </table>
                <?php endforeach; ?>
                <?php if ($login_role == ROLE_DNS_STAFF || $login_role == ROLE_ADMIN) : ?>
                <input type="hidden" name="token" value="<?php echo h($token);?>">
                <button id="confirm_sp" class="button confirm" type="submit" name="confirm">確定</button>
                <?php endif; ?>
            </form>
            <?php else : ?>
            <p class="text-center mt-5">指定された対象日までの出荷依頼は見つかりませんでした。</p>
            <?php endif; ?>
        <?php endif; ?>
        </div>
    </div>
    <!-- 以上SP -->
    <!-- 以下PC -->
    <div class="d-none d-lg-block border-0 search_wrapper">
        <h1 class="mt-5">出荷依頼確認</h1>
        <form method="post" action="shipment_request_confirmation.php">
            <?php if ($login_role == ROLE_SELLER || $login_role == ROLE_TESHITA) : ?>
            <input type="hidden" name="select_owner_id" value="<?php echo h($_SESSION['login_owner_id']); ?>">
            <?php else : ?>
            <select id="pc_select_owner_id" class="select_owner_id1" name="select_owner_id">
                <?php echo $option_tags_for_owners; ?>
            </select>
            <?php endif; ?>
            <input type="date" name="date_select" value="<?php echo h($selected_date); ?>">
            <button id="display" type="submit" name="display">表示</button>
        </form>
    <?php if (isset($_POST["display"])) : ?>
        <?php if (!empty($array_fetched_shipping_requests)) : ?>
        <form method="post" action="shipment_request_confirmation.php">
            <?php if ($login_role == ROLE_SELLER || $login_role == ROLE_TESHITA) : ?>
            <input type="hidden" name="select_owner_id" value="<?php echo h($_SESSION['login_owner_id']); ?>">
            <?php else : ?>
            <input class="select_owner_id2" type="hidden" name="select_owner_id" value="<?php echo h($select_owner_id); ?>">
            <?php endif; ?>
            <table class="table_scroll border-0">
                <caption></caption>
                <thead class="shipment_record_table_title">
                    <tr>
                        <th class="align-middle px-2 w-5rem" rowspan="2" scope="col">No</th>
                        <th class="align-middle px-2 w-30rem" scope="col">商品コード</th>
                        <th class="align-middle px-2 w-10rem" scope="col">色</th>
                        <th class="align-middle px-2 w-20rem" scope="col" rowspan="2">商品画像</th>
                        <th class="align-middle px-2 w-15rem" scope="col">出荷数量</th>
                        <th class="align-middle px-2 w-15rem" scope="col" rowspan="2">コンビニ出荷用バーコード</th>
                        <th class="align-middle px-2 w-15rem" scope="col" rowspan="2">ヤマト営業所（郵便局）QRコード</th>
                        <th class="align-middle px-2 w-10rem" scope="col">郵便番号</th>
                        <th class="align-middle px-2 w-15rem" scope="col">送付先氏名</th>
                        <th class="align-middle px-2 w-15rem" scope="col" rowspan="2">その他氏名</th>
                        <th class="align-middle px-2 w-30rem" scope="col">発送元住所</th>
                        <th class="align-middle px-2 w-15rem" scope="col" rowspan="2">出荷予定日</th>
                        <th class="align-middle px-2 w-15rem" scope="col" rowspan="2">出荷依頼No.</th>
                        <th class="align-middle px-2 w-20rem" scope="col" rowspan="2">備考欄</th>
                        <th class="align-middle px-2 w-20rem" scope="col" rowspan="2">登録時間</th>
                        <th class="align-middle px-2 w-15rem" scope="col" rowspan="2">アラート</th>
                    </tr>
                    <tr>
                        <th class="align-middle px-2" scope="col">商品名</th>
                        <th class="align-middle px-2" scope="col">サイズ</th>
                        <th class="align-middle px-2" scope="col">発送方法</th>
                        <th class="align-middle px-2" scope="col" colspan="2">住所</th>
                        <th class="align-middle px-2" scope="col">発送元氏名</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($array_fetched_shipping_requests as $fetched_shipping_request) : ?>
                        <?php
                        $no = $fetched_shipping_request["seq"];
                        $owner_id = $fetched_shipping_request["owner_id"];
                        $shipping_request_no = $fetched_shipping_request["shipping_request_no"];
                        $details_no = $fetched_shipping_request["details_no"];
                        $goods_id = $fetched_shipping_request["goods_id"];
                        $record_day = $fetched_shipping_request["record_day"];
                        $shipping_scheduled_day = $fetched_shipping_request["shipping_scheduled_day"];
                        $volume = $fetched_shipping_request["volume"];
                        $shipping_id = $fetched_shipping_request["shipping_id"];
                        $zip_code = $fetched_shipping_request["zip_code"];
                        $address = $fetched_shipping_request["address"];
                        $destination_name = $fetched_shipping_request["destination_name"];
                        $other_name = $fetched_shipping_request["other_name"];
                        $shipment_source = $fetched_shipping_request["shipment_source"];
                        $shipment_source_name = $fetched_shipping_request["shipment_source_name"];
                        $convenience_store_qr_code = $fetched_shipping_request["convenience_store_qr_code"];
                        $other_qr_code = $fetched_shipping_request["other_qr_code"];
                        $updated_at = $fetched_shipping_request["updated_at"];
                        $remarks = $fetched_shipping_request["remarks"];
                        $goods_name = $fetched_shipping_request["goods_name"];
                        $color = $fetched_shipping_request["color"];
                        $size = $fetched_shipping_request["size"];
                        $picture = $fetched_shipping_request["picture"];
                        if ($shipping_scheduled_day == $today) {
                            $alert = "当日出荷です　";
                        } elseif ($shipping_scheduled_day < $today) {
                            $alert = "出荷日を過ぎています　";
                        } else {
                            $alert = "";
                        }
                        ?>
                    <input type="hidden" name="shipping_request_no[]" value="<?php echo h($shipping_request_no); ?>">
                    <input type="hidden" name="details_no[]" value="<?php echo h($details_no); ?>">
                    <input type="hidden" name="updated_at[]" value="<?php echo h($updated_at); ?>">
                    <tr class="tr_even target1">
                        <td class="px-2 align-middle" rowspan="2"><?php echo h($no); ?></td><!-- 通し番号 -->
                        <td class="px-2 align-middle"><?php echo h($goods_id); ?></td><!-- 商品コード -->
                        <td class="px-2 align-middle"><?php echo h($color); ?></td><!-- 色 -->
                        <td class="w-20rem" rowspan="2"><img class="w-20rem" src="<?php echo h(HOME_URL); ?>/common/images/<?php echo h($owner_id); ?>/goods/<?php echo h($picture); ?>"></td><!-- 商品画像 -->
                        <td class="px-2 align-middle"><?php echo h($volume); ?></td><!-- 数量 -->
                        <td class="px-2" rowspan="2"><img src="<?php echo h(HOME_URL); ?>/common/images/<?php echo h($owner_id); ?>/qr/<?php echo h($convenience_store_qr_code); ?>"></td><!-- コンビニ出荷用バーコード -->
                        <td class="px-2" rowspan="2"><img src="<?php echo h(HOME_URL); ?>/common/images/<?php echo h($owner_id); ?>/qr/<?php echo h($other_qr_code); ?>"></td><!-- ヤマト営業所（郵便局）QRコード -->
                        <td class="px-2 align-middle"><?php echo h($zip_code); ?></td><!-- 郵便番号 -->
                        <td class="px-2 align-middle"><?php echo h($destination_name);?></td><!-- 送付先指名 -->
                        <td class="px-2 align-middle" rowspan="2"><?php echo h($other_name); ?></td><!-- その他氏名 -->
                        <td class="px-2 align-middle"><?php echo h($shipment_source); ?></td><!-- 発送元住所 -->
                        <td class="px-2 align-middle" rowspan="2"><?php echo h($shipping_scheduled_day); ?></td><!-- 出荷予定日 -->
                        <td class="px-2 align-middle" rowspan="2"><?php echo h($shipping_request_no); ?></td><!-- 出荷依頼No. -->
                        <td class="px-2 align-middle" rowspan="2"><?php echo h($remarks); ?></td><!-- 備考 -->
                        <td class="px-2 align-middle" rowspan="2"><?php echo h($record_day); ?></td><!-- 登録時間 -->
                        <td class="px-2 align-middle" rowspan="2"><?php echo h($alert); ?></td><!-- アラート -->
                    </tr>
                    <tr class="tr_even2">
                        <td class="px-2 align-middle"><?php echo h($goods_name); ?></td><!-- 商品名 -->
                        <td class="px-2 align-middle"><?php echo h($size); ?></td><!-- サイズ -->
                        <td class="px-2 align-middle"><?php echo h(get_shipping_method_by_shipping_id($shipping_id)); ?></td><!-- 発送方法 -->
                        <td class="px-2 align-middle" colspan="2"><?php echo h($address); ?></td><!-- 住所 -->
                        <td class="px-2 align-middle"><?php echo h($shipment_source_name); ?></td><!-- 発送元氏名 -->
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php if ($login_role == ROLE_DNS_STAFF || $login_role == ROLE_ADMIN) : ?>
            <input type="hidden" name="token" value="<?php echo h($token);?>">
            <button id="confirm" class="confirm" type="submit" name="confirm">確定</button>
            <?php endif; ?>
        </form>
        <?php else : ?>
        <p class="text-center mt-5">指定された対象日までの出荷依頼は見つかりませんでした。</p>
        <?php endif; ?>
    <?php endif; ?>
    </div>
    <!-- 以上PC -->
</main>

<script>

    //レスポンシブ用に、同じnameのinputが複数存在しているため、画面サイズごとに有効無効を切り替える
    let $dLgNone = $(".d-lg-none"); //SP版の要素
    let $dLgBlock = $(".d-lg-block"); //PC版の要素

    //最初の読み込み
    let windowWidth1 = $(window).width();
    if(windowWidth1 < 992) {
        $dLgNone.find("input").prop("disabled", false);//入力&値の送信禁止
        $dLgNone.find("textarea").prop("disabled", false);
        $dLgNone.find("select").prop("disabled", false);
        $dLgNone.find("button").prop("disabled", false);
        $dLgBlock.find("input").prop("disabled", true);//入力&値の送信許可
        $dLgBlock.find("textarea").prop("disabled", true);
        $dLgBlock.find("select").prop("disabled", true);
        $dLgBlock.find("button").prop("disabled", true);
    } else {
        $dLgNone.find("input").prop("disabled", true);
        $dLgNone.find("textarea").prop("disabled", true);
        $dLgNone.find("select").prop("disabled", true);
        $dLgNone.find("button").prop("disabled", true);
        $dLgBlock.find("input").prop("disabled", false);
        $dLgBlock.find("textarea").prop("disabled", false);
        $dLgBlock.find("select").prop("disabled", false);
        $dLgBlock.find("button").prop("disabled", false);
    }

    //ブラウザの幅変化時に、その値を取得
    $(window).on("resize", function() {
        let windowWidth2 = $(window).width();//ブラウザの横幅（スクロールバー除外）
        if(windowWidth2 < 992) {
            $dLgNone.find("input").prop("disabled", false);//入力&値の送信禁止
            $dLgNone.find("textarea").prop("disabled", false);
            $dLgNone.find("select").prop("disabled", false);
            $dLgNone.find("button").prop("disabled", false);
            $dLgBlock.find("input").prop("disabled", true);//入力&値の送信許可
            $dLgBlock.find("textarea").prop("disabled", true);
            $dLgBlock.find("select").prop("disabled", true);
            $dLgBlock.find("button").prop("disabled", true);
        } else {
            $dLgNone.find("input").prop("disabled", true);
            $dLgNone.find("textarea").prop("disabled", true);
            $dLgNone.find("select").prop("disabled", true);
            $dLgNone.find("button").prop("disabled", true);
            $dLgBlock.find("input").prop("disabled", false);
            $dLgBlock.find("textarea").prop("disabled", false);
            $dLgBlock.find("select").prop("disabled", false);
            $dLgBlock.find("button").prop("disabled", false);
        }
    });

    //確定ボタン押下時
    $(document).on("click", ".confirm", function(){
        let loginRole = JSON.parse(<?php echo json_encode($login_role, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>);
        const ROLE_DNS_STAFF = JSON.parse(<?php echo json_encode(ROLE_DNS_STAFF, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>);
        const ROLE_ADMIN = JSON.parse(<?php echo json_encode(ROLE_ADMIN, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>);
        if(loginRole === ROLE_DNS_STAFF || loginRole === ROLE_ADMIN) {
            let selectOwnerId1 = $(this).closest(".search_wrapper").find(".select_owner_id1").val();
            let selectOwnerId2 = $(this).closest(".search_wrapper").find(".select_owner_id2").val();
            //両者を比較し、値が異なる場合に警告＆画面遷移中止
            if(selectOwnerId1 !== selectOwnerId2) {
                window.alert("現在選択中の商品所有者と、表示中の商品の所有者が異なっています。\n\n商品所有者を変更する場合は以下の作業を行ってください。\n\n①プルダウンリストで商品所有者を選択\n②表示ボタンをクリックまたはタップ");
                return false;
            }
        }
    });
</script>
<?php

get_footer();
// -------------------------------------------------issue289 end--------------------------------------------------------