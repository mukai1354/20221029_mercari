<?php
// 2021/10/12 issue289 出荷依頼確認　出荷予定日の変更 demachi
// 2021/08/04 issue236 二重送信防止のロジックが出荷依頼登録以外は実装されていない demachi
require_once ('htmlCommon.php');

define("TOKEN_LENGTH", 16); // 16*2=32byte
define("STRETCH_COUNT", 1);
define("SALT_LENGTH", 4); // 4*2=8byte
define("PASSWORD_LENGTH", 16); // 16*2=32byte

// エラーステータス
define("E_STATUS_DEFAULT", 0); // デフォルト(リセット用)
// define( "STATUS_", 1 );//
// define( "STATUS_", 2 );//
// define( "STATUS_", 3 );//
// define( "STATUS_", 4 );//
// define( "STATUS_", 5 );//

// 権限
define("ROLE_INVALID", 0); //
define("ROLE_SELLER", 1); //
define("ROLE_DNS_STAFF", 2); //
define("ROLE_LOGISTICS", 3); //
define("ROLE_ADMIN", 4); //
define("ROLE_TESHITA", 5);

define('LOG_DIRECTORY_PATH', __DIR__ . '/../log');

// ---------------------------------------------------issue289 start----------------------------------------------------
// 出荷完了ステータス
const COMPLETION_FLAG_INCOMPLETE = "0";
const COMPLETION_FLAG_CONFIRMED = "1";
const COMPLETION_FLAG_COMPLETE = "2";

// 発送方法
const SHIPPING_METHODS = [
    "1" => "ネコポス",
    "2" => "クリックポスト",
    "3" => "ゆうパケット",
    "4" => "定形外郵便",
    "5" => "宅急便コンパクト",
    "6" => "レターパックプラス",
    "7" => "宅急便",
    "8" => "その他",
];

// 発送方法と配送業者の対応
const RELATIONS_BETWEEN_SHIPPING_METHODS_AND_DELIVERY_COMPANIES = [
    "ネコポス" => "ヤマト運輸",
    "クリックポスト" => "郵便局",
    "ゆうパケット" => "郵便局",
    "定形外郵便" => "郵便局",
    "宅急便コンパクト" => "ヤマト運輸",
    "レターパックプラス" => "郵便局",
    "宅急便" => "ヤマト運輸",
    "その他" => "佐川急便",
];

/**
 * 排他制御用の例外
 */
class ExclusiveControlException extends Exception
{
    public function __construct()
    {
        parent::__construct('他のユーザが編集中のデータを更新しようとしています。');
    }
}
// ----------------------------------------------------issue289 end-----------------------------------------------------

date_default_timezone_set('Asia/Tokyo');

// CSRFトークン作成
function get_csrf_token()
{
    // 暗号学的的に安全なランダムなバイナリを生成し、それを16進数に変換することでASCII文字列に変換します
    $bytes = openssl_random_pseudo_bytes(TOKEN_LENGTH);
    return bin2hex($bytes);
}

// --------------------------------------------------issue236 start-------------------------------------------------------
/**
 * トークンが有効かを判定する。
 * トークンが有効の場合true、無効の場合falseを返す
 *
 * @param string $posted_token_named_key トークンを取得するための$_POSTのキー
 * @param string $session_token_named_key トークンを取得するための$_SESSIONのキー
 * @return boolean
 */
function is_token_valid(string $posted_token_named_key, string $session_token_named_key): bool {

    $posted_token = getpstStrs($posted_token_named_key);
    $session_token = isset($_SESSION[$session_token_named_key]) ? $_SESSION[$session_token_named_key] : "";
    unset($_SESSION[$session_token_named_key]);
    if ($posted_token != "" && $posted_token == $session_token) {
        return true;
    }

    return false;
}

// --------------------------------------------------issue236 end---------------------------------------------------------
// パスワードをストレッチング
function strechedPassword($password)
{
    $hash_pass = '';

    for ($i = 0; $i < STRETCH_COUNT; $i ++) {
        $hash_pass = password_hash($password, PASSWORD_DEFAULT);
    }

    return $hash_pass;
}

/*
 * //ソルトを作成
 * function get_salt() {
 * $bytes = openssl_random_pseudo_bytes( SALT_LENGTH );
 * return bin2hex( $bytes );
 * }
 *
 * //URL の一時パスワードを作成
 * function get_url_password() {
 * $bytes = openssl_random_pseudo_bytes( PASSWORD_LENGTH );
 * return hash( 'sha256', $bytes );
 * }
 */

// POSTの値のセッティング
function getpstStrs($str)
{
    if (isset($_POST[$str])) {
        // $_POST['name']が定義済みの場合は、値をエスケープ処理して $name に代入
        $pststr = h($_POST[$str]);
    } else {
        // $_POST['name']が未定義の場合は、$name に空文字を代入
        $pststr = '';
    }
    return $pststr;
}

// htmlspecialchars → h()
function h($str)
{
    if (is_array($str)) {
        return array_map('h', $str); // 配列も一気にh();
    } else {
        return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
    }
}

function redirect($url)
{
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: {$url}");
    exit();
}

function redirect_err()
{
    redirect(get_login_url());
}

function redirect_home()
{
    redirect(get_home_url('/'));
}

// 直近の月曜日を求める
function getMonday()
{
    // 今日の日付(タイムスタンプ)を取得
    $now = new DateTime();

    // 日曜日なら0、月曜なら1、火曜なら2...土曜なら6といった数値を返す
    $w = date('w', $now->getTimestamp());
    // +Nする値を調整（\$w=1なので\$wに+6して曜日数（=7）で割った余りが0になるように）
    $diff = ($w + 6) % 7;

    // 今日の日時から$diff日前に遡る
    $ymd = date('Y-m-j', strtotime("-{$diff} day", $now->getTimestamp()));
    return $ymd;
}

function replace_text_to_asterisk($text)
{
    $asterisk = '';
    for ($i = 1; $i <= strlen($text); $i ++) {
        $asterisk = $asterisk . '*';
    }
    return $asterisk;
}

function getUniqueArray($array, $column)
{
    $tmp = [];
    $uniqueArray = [];
    foreach ($array as $value) {
        if (! in_array($value[$column], $tmp)) {
            $tmp[] = $value[$column];
            $uniqueArray[] = $value;
        }
    }
    return $uniqueArray;
}

// ■◇■◇■◇■◇■◇■◇■◇■◇■◇■◇■◇■◇■◇■◇■◇■◇■◇■◇■◇■
//
// 実在庫数を求める関数 get_stock_quantity
//
// [引数]
// ・$db：DB接続に使用する変数
// ・$varr：テーブル"stock_results"の対象となる行
// ・$years_months：対象年月 YYYYMM、202101、202102等
// ・$first_day：対象となる最初の日 YYYY-MM-D1
// ・$last_day:対象となる最後の日 YYYY-MM-DD
//
// ■◇■◇■◇■◇■◇■◇■◇■◇■◇■◇■◇■◇■◇■◇■◇■◇■◇■◇■◇■
function get_stock_quantity($db, $varr, $years_months, $first_day, $last_day)
{
    $stock_quantity = 0; // 実在庫数を初期化

    $sql = "SELECT volume
            FROM
            month_stock
            WHERE
            owner_id=:owner_id
            AND
            goods_id=:goods_id
            AND
            color_size_id=:color_size_id
            AND
            years_months=:years_months";

    $stmt = $db->prepare($sql);
    $stmt->bindParam(':owner_id', $varr['owner_id'], PDO::PARAM_STR);
    $stmt->bindParam(':goods_id', $varr['goods_id'], PDO::PARAM_STR);
    $stmt->bindParam(':color_size_id', $varr['color_size_id'], PDO::PARAM_STR);
    $stmt->bindParam(':years_months', $years_months, PDO::PARAM_STR);
    $stmt->execute();
    $result2 = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($result2 as $varr2) {
        $stock_quantity += $varr2['volume']; // ①照会対象月の月初在庫
    }

    $stock_quantity += $varr['stock_volume']; // ②照会対象月内に存在する入荷実績数

    // ③照会対象月内に存在する出荷実績数を求める
    $sql = "SELECT volume
            FROM shipping_request
            WHERE shipping_results_day
            BETWEEN
            :first_day
            AND
            :last_day
            AND
            owner_id=:owner_id
            AND
            goods_id=:goods_id
            AND
            color_size_id=:color_size_id
            AND
            completion_flag='2'";

    $stmt = $db->prepare($sql);
    $stmt->bindParam(':first_day', $first_day, PDO::PARAM_STR);
    $stmt->bindParam(':last_day', $last_day, PDO::PARAM_STR);
    $stmt->bindParam(':owner_id', $varr['owner_id'], PDO::PARAM_STR);
    $stmt->bindParam(':goods_id', $varr['goods_id'], PDO::PARAM_STR);
    $stmt->bindParam(':color_size_id', $varr['color_size_id'], PDO::PARAM_STR);
    $stmt->execute();
    $result2 = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($result2 as $varr2) {
        $stock_quantity -= $varr2['volume']; // ③照会対象月内に存在する出荷実績数
    }

    // ④棚卸差異数量（正負あり）を求める
    $sql = "SELECT difference_volume
            FROM inventory_data
            WHERE inventory_date BETWEEN :first_day AND :last_day
            AND owner_id=:owner_id
            AND goods_id=:goods_id
            AND color_size_id=:color_size_id";

    $stmt = $db->prepare($sql);
    $stmt->bindParam(':first_day', $first_day, PDO::PARAM_STR);
    $stmt->bindParam(':last_day', $last_day, PDO::PARAM_STR);
    $stmt->bindParam(':owner_id', $varr['owner_id'], PDO::PARAM_STR);
    $stmt->bindParam(':goods_id', $varr['goods_id'], PDO::PARAM_STR);
    $stmt->bindParam(':color_size_id', $varr['color_size_id'], PDO::PARAM_STR);
    $stmt->execute();
    $result2 = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($result2 as $varr2) {
        $stock_quantity += $varr2['difference_volume']; // ④棚卸差異数量（正負あり）
    }

    return $stock_quantity;
}

/**
 * 渡された年月の締め処理が行われているか判定する
 *
 * $year_monthに「Ym」形式の文字列で年月を渡す(exp:'202101')
 * 渡された年月が締められている場合は「true」
 * それ以外の場合は「false」を返す
 *
 * @param [type] $db
 * @param string $year_month
 * @return boolean
 */
function is_monthly_closed($db, string $year_month):bool
{
    $row_count = 0;
    $deadline_status = '';

    try {
        $sql = " SELECT COUNT(*)";
        $sql .= " FROM m_deadline";
        $sql .= " WHERE years_months = :years_months";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':years_months', $year_month, PDO::PARAM_STR);
        $stmt->execute();
        $row_count = (int)$stmt->fetchColumn();

        if ($row_count === 1) {
            $sql = " SELECT deadline_status";
            $sql .= " FROM m_deadline";
            $sql .= " WHERE years_months = :years_months";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':years_months', $year_month, PDO::PARAM_STR);
            $stmt->execute();
            $deadline_status = $stmt->fetchColumn();
        }
    } catch (Exception $e) {
        die('エラー：' . $e->getMessage());
    }

    return $deadline_status === '1' ? true : false;
}

/**
 * debugDumpParamsの出力を変数へ取り込む関数
 */
function pdo_debugStrParams($s) {
  ob_start();
  $s->debugDumpParams();
  $r = ob_get_contents();
  ob_end_clean();
  return $r;
}

/**
 * 現在時刻を取得する
 * @return string 現在時刻
 */
function getTime() {
    $miTime = explode('.',microtime(true));
    $msec = str_pad(substr($miTime[1], 0, 3) , 3, "0");
    $time = date('Y-m-d H:i:s', $miTime[0]) . '.' .$msec;
    return $time;
}

/**
 * ログファイルへの出力用関数
 *
 * @param string $message
 * @return void
 */
function log_message(string $message) {
    $date = date('Ymd', time());
    $time = getTime();
    $logMessage = "[{$time}]" . rtrim($message) . "\n";
    error_log($logMessage, 3, LOG_DIRECTORY_PATH . "/log_file_{$date}.log");
}

// -----------------------------------------------issue289 start--------------------------------------------------------
/**
 * 新しい出荷依頼Noを採番する
 *
 * @param DB $db
 * @param string $owner_id
 * @param string $shipping_scheduled_day
 * @return int
 */
function get_new_shipping_request_no(DB $db, string $owner_id, string $shipping_scheduled_day):int
{
    $formatted_shipping_scheduled_day = str_replace('-', '', $shipping_scheduled_day);
    $condition_of_shipping_request_no = $formatted_shipping_scheduled_day . '%';

    // owner_id と shipping_request_no を条件に検索、その中の最大のshipping_request_noを取得
    $sql = <<< EOM
        SELECT
            MAX(shipping_request_no)
        FROM
            shipping_request
        WHERE
            owner_id = :owner_id
            AND
            shipping_request_no LIKE :condition_of_shipping_request_no
        ;
        EOM;
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':owner_id', $owner_id);
    $stmt->bindValue(':condition_of_shipping_request_no', $condition_of_shipping_request_no);
    $stmt->execute();

    $max_shipping_request_no = $stmt->fetchColumn();
    if (is_null($max_shipping_request_no)) { // 引数に渡された出荷予定日のshipping_request_noが存在しない場合
        return (int) ($formatted_shipping_scheduled_day . '001');
    } else {
        return (int) $max_shipping_request_no + 1;
    }
}

/**
 * 出荷依頼をキャンセルする
 * @param DB $db
 * @param string $owner_id
 * @param int $shipping_request_no
 * @param int $details_no
 */
function cancel_shipping_request(DB $db, string $owner_id, int $shipping_request_no, int $details_no):void
{
    $sql = <<< EOM
        UPDATE
            shipping_request
        SET
            volume = 0
        WHERE
            owner_id = :owner_id
            AND
            shipping_request_no = :shipping_request_no
            AND
            details_no = :details_no 
        ;
        EOM;
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':owner_id', $owner_id);
    $stmt->bindValue(':shipping_request_no', $shipping_request_no, PDO::PARAM_INT);
    $stmt->bindValue(':details_no', $details_no, PDO::PARAM_INT);
    $stmt->execute();
    log_message("[{$_SESSION['login_user_id']}]" . pdo_debugStrParams($stmt));
}

/**
 * shipping_requestテーブルの排他制御のための関数。
 * 対象の出荷依頼が変更されていればfalse,変更されていなければtrueを返す。
 *
 * @param DB $db
 * @param string $owner_id
 * @param int $shipping_request_no
 * @param int $details_no
 * @param string $updated_at_to_be_compared
 * @return bool
 */
function check_shipping_request_record_for_exclusive_control(
    DB $db,
    string $owner_id,
    int $shipping_request_no,
    int $details_no,
    string $updated_at_to_be_compared
):bool {
    // 引数で渡されたupdated_atとDBから再度取得したupdated_atを比較。
    $sql = <<< EOM
        SELECT
            updated_at
        FROM
            shipping_request
        WHERE
            owner_id = :owner_id
            AND
            shipping_request_no = :shipping_request_no
            AND
            details_no = :details_no
        ;
        EOM;
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':owner_id', $owner_id);
    $stmt->bindValue(':shipping_request_no', $shipping_request_no, PDO::PARAM_INT);
    $stmt->bindValue(':details_no', $details_no, PDO::PARAM_INT);
    $stmt->execute();
    $current_updated_at = $stmt->fetchColumn();
    if ($current_updated_at === false) { // 変更しようとしている出荷依頼が存在しない場合は他のユーザが出荷予定日を変更したとみなす。
        return false;
    }
    $current_updated_at = (string) $current_updated_at;
    if ($updated_at_to_be_compared !== $current_updated_at) {
        return false;
    }

    return true;
}

/**
 * 発送方法の一覧を格納している配列を取得
 *
 * @return string[]
 */
function get_all_shipping_methods():array
{
    return SHIPPING_METHODS;
}

/**
 * shipping_idに対応する発送方法を取得
 *
 * @param string $shipping_id
 * @return string
 */
function get_shipping_method_by_shipping_id(string $shipping_id):string
{
    return SHIPPING_METHODS[$shipping_id];
}

/**
 * 発送方法に対応する配送業者を取得
 *
 * @param string $shipping_method
 * @return string
 */
function get_delivery_company_by_shipping_method(string $shipping_method):string
{
    return RELATIONS_BETWEEN_SHIPPING_METHODS_AND_DELIVERY_COMPANIES[$shipping_method];
}

/**
 * 商品所有者選択用のドロップダウンメニューのオプションタグを取得
 *
 * @param DB $db
 * @param string $default_selected_owner_id
 * @return string
 */
function get_option_tags_for_owners(DB $db, string $default_selected_owner_id):string
{
    // 商品所有者一覧を取得
    $sql = <<< EOM
        SELECT
        owner_id,
        owner
        FROM
        m_staff
        ;
        EOM;
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $m_staff_result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 商品所有者のレコードが取得できない場合は空文字列を返す
    if ($m_staff_result === false) {
        return '';
    }

    // 引数で渡されたowner_idの商品所有者がデフォルトで選択されるようにオプションタグを生成
    $option_tags_for_owners = '';
    foreach ($m_staff_result as $staff) {
        if ($staff['owner_id'] == $default_selected_owner_id) {
            $option_tags_for_owners .= "\t\t\t\t\t<option value=\"" . h($staff['owner_id']) . "\" selected>" . h($staff['owner']) . "</option>\n";
        } else {
            $option_tags_for_owners .= "\t\t\t\t\t<option value=\"" . h($staff['owner_id']) . "\">" . h($staff['owner']) . "</option>\n";
        }
    }

    return $option_tags_for_owners;
}
// -----------------------------------------------issue289 end----------------------------------------------------------
