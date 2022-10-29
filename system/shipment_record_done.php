<?php
require_once ('../common/function.php');
require_once ('../db/DB.php');

get_header();

$array_of_posted_owner_id = getpstStrs('owner_id');
$array_of_posted_shipping_request_no = getpstStrs('shipping_request_no');
$array_of_posted_details_no = getpstStrs('details_no');
$array_of_posted_search_no = getpstStrs('search_no');
$array_of_posted_search_no2 = getpstStrs('search_no2');
$array_of_posted_delivery_company = getpstStrs('delivery_company');
$array_of_posted_shipping_results_day = getpstStrs('shipping_results_day');
$array_of_posted_packing_material = getpstStrs('packing_material');
$array_of_posted_delivery_plan = getpstStrs('delivery_plan');
$array_of_posted_postage = getpstStrs('postage');

try{
    $db = DB::getDB();
    $stmt = null;
    $sql = '';

    for($i = 0; $i < count($array_of_posted_owner_id); $i++){
       $shipping_results_day = new DateTime($array_of_posted_shipping_results_day[$i]);
       if(is_monthly_closed($db, $shipping_results_day->format('Ym'))){
           echo '該当年月での月締め処理実行済みのため ' . 'owner_id:' . $array_of_posted_owner_id[$i] . ' shipping_request_no:' . $array_of_posted_shipping_request_no[$i] . ' details_no:' . $array_of_posted_details_no[$i] . ' のレコードの出荷実績入力を完了することができませんでした。' . '<br>';
           continue;
       }
$sql = <<< EOM
UPDATE
shipping_request
SET
search_no = :search_no,
search_no2 = :search_no2,
delivery_company = :delivery_company,
shipping_results_day = :shipping_results_day,
packing_material = :packing_material,
delivery_plan = :delivery_plan,
postage = :postage,
EOM;

//        $sql = " UPDATE shipping_request";
//        $sql .= " SET search_no = :search_no";
//        $sql .= " , delivery_company = :delivery_company";
//        $sql .= " , shipping_results_day = :shipping_results_day";
//        $sql .= " , packing_material = :packing_material";
//        $sql .= " , delivery_plan = :delivery_plan";
//        $sql .= " , postage = :postage";
       if(!empty($_POST["save"])) {
           $sql .= "\r\ncompletion_flag = '1'";
       } else {
            $sql .= "\r\ncompletion_flag = '2'";
       }
       $sql .= "\r\nWHERE\r\nowner_id = :owner_id";
       $sql .= "\r\nAND\r\nshipping_request_no = :shipping_request_no";
       $sql .= "\r\nAND\r\ndetails_no = :details_no";
       $stmt = $db->prepare($sql);
       if($array_of_posted_search_no[$i] === ''){
           $stmt->bindValue(':search_no', null, PDO::PARAM_NULL);
       }else{
           $stmt->bindParam(':search_no', $array_of_posted_search_no[$i], PDO::PARAM_STR);
       }

       if($array_of_posted_search_no2[$i] === ''){
           $stmt->bindValue(':search_no2', null, PDO::PARAM_NULL);
       }else{
           $stmt->bindParam(':search_no2', $array_of_posted_search_no2[$i], PDO::PARAM_STR);
       }

       if($array_of_posted_delivery_company[$i] === ''){
           $stmt->bindValue(':delivery_company', null, PDO::PARAM_NULL);
       }else{
           $stmt->bindParam(':delivery_company', $array_of_posted_delivery_company[$i], PDO::PARAM_STR);
       }
       if($array_of_posted_shipping_results_day[$i] === ''){
           $stmt->bindValue(':shipping_results_day', null, PDO::PARAM_NULL);
       }else{
           $stmt->bindParam(':shipping_results_day', $array_of_posted_shipping_results_day[$i], PDO::PARAM_STR);
       }
       if($array_of_posted_packing_material[$i] === ''){
           $stmt->bindValue(':packing_material', null, PDO::PARAM_NULL);
       }else{
           $stmt->bindParam(':packing_material', $array_of_posted_packing_material[$i], PDO::PARAM_STR);
       }
       if($array_of_posted_delivery_plan[$i] === ''){
           $stmt->bindValue(':delivery_plan', null, PDO::PARAM_NULL);
       }else{
           $stmt->bindParam(':delivery_plan', $array_of_posted_delivery_plan[$i], PDO::PARAM_STR);
       }
       if($array_of_posted_postage[$i] === ''){
           $stmt->bindValue(':postage', null, PDO::PARAM_NULL);
       }else{
           $postage = (int)$array_of_posted_postage[$i];
           $stmt->bindParam(':postage', $postage, PDO::PARAM_INT);
       }
       $stmt->bindParam(':owner_id', $array_of_posted_owner_id[$i], PDO::PARAM_STR);
       $shipping_request_no = (int)$array_of_posted_shipping_request_no[$i];
       $stmt->bindParam(':shipping_request_no', $shipping_request_no, PDO::PARAM_INT);
       $details_no = (int)$array_of_posted_details_no[$i];
       $stmt->bindParam(':details_no', $details_no, PDO::PARAM_INT);
       $stmt->execute();
       log_message("[{$_SESSION['login_user_id']}]" . pdo_debugStrParams($stmt));
       echo 'owner_id:' . $array_of_posted_owner_id[$i] . ' shipping_request_no:' . $array_of_posted_shipping_request_no[$i] . ' details_no:' . $array_of_posted_details_no[$i] . ' のレコードの出荷実績入力を完了しました。' . '<br>';
    }
    $db = null;
    $stmt = null;
    $sql = '';
}catch(Exception $e){
    log_message("[{$_SESSION['login_user_id']}]" . $e->getMessage());
    log_message("[{$_SESSION['login_user_id']}]" . pdo_debugStrParams($stmt));
    die('エラー：' . $e->getMessage());
}
?>

<?php

get_footer();