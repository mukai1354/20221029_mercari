<?php
// 2021/12/07 issue296 「役割」が「システム管理者」のアカウントでログインした場合、未実装の「出荷依頼登録（同梱）」がメニューに表示される。 demachi
// 2021/10/15 issue289 出荷依頼確認　出荷予定日の変更 demachi
// 2021/08/16 issue255 submitボタンをダブルクリックした際に、ログイン画面に飛ばされる demachi
require_once('function.php');

// セッション開始
session_start();
// --------------------------------------------------issue255 start-------------------------------------------------------
// session_regenerate_id(true);
// --------------------------------------------------issue255 end---------------------------------------------------------

header('Content-type: text/html; charset=utf-8');

//強制ブラウズはリダイレクト
if (!isset($_SESSION['login_user_id'])) {
    redirect_err();
}

$nav_li = '';
if (isset($_SESSION['login_role'])) {
    switch ($_SESSION['login_role']) {
        case ROLE_SELLER:
            $nav_li = "<li class=\"nav-item\"><a class=\"nav-link\" href=\"" . get_home_url('/master/m_goods.php') . "\">商品情報登録</a></li>\n" .
                "<li class=\"nav-item\"><a class=\"nav-link\" href=\"" . get_home_url('/master/m_goods_update.php') . "\">商品情報更新</a></li>\n" .
                "<li class=\"nav-item\"><a class=\"nav-link\" href=\"" . get_home_url('/system/scheduled_arrival_registration.php') . "\">入荷予定登録</a></li>\n" .
                "<li class=\"nav-item\"><a class=\"nav-link\" href=\"" . get_home_url('/system/stock_results_inquiry.php') . "\">入荷実績照会</a></li>\n" .
                "<li class=\"nav-item\"><a class=\"nav-link\" href=\"" . get_home_url('/system/shipment_request_registration.php') . "\">出荷依頼登録</a></li>\n" .
                //"<li class=\"nav-item\"><a class=\"nav-link\" href=\"" . get_home_url('/system/combined_shipping.php') . "\">出荷依頼登録（同梱）</a></li>\n" .
                // -----------------------------------------------------------issue289 start--------------------------------------------------------------------------
                "<li class=\"nav-item\"><a class=\"nav-link\" href=\"" . get_home_url('/system/shipment_request_modification.php') . "\">出荷依頼変更</a></li>\n" .
                // -----------------------------------------------------------issue289 end----------------------------------------------------------------------------
                "<li class=\"nav-item\"><a class=\"nav-link\" href=\"" . get_home_url('/system/shipment_request_confirmation.php') . "\">出荷依頼確認</a></li>\n" .
                "<li class=\"nav-item\"><a class=\"nav-link\" href=\"" . get_home_url('/system/shipping_schedule.php') . "\">出荷予定一覧確認</a></li>\n" .
                "<li class=\"nav-item\"><a class=\"nav-link\" href=\"" . get_home_url('/system/shipment_inquiry.php') . "\">出荷実績照会</a></li>\n" .
                "<li class=\"nav-item\"><a class=\"nav-link\" href=\"" . get_home_url('/system/inventory_have_a_look.php') . "\">在庫一覧</a></li>\n" .
                "<li class=\"nav-item\"><a class=\"nav-link\" href=\"" . get_home_url('/system/inventory_changes.php') . "\">在庫推移</a></li>\n";
            break;
        case ROLE_DNS_STAFF:
            $nav_li = "<li class=\"nav-item\"><a class=\"nav-link\" href=\"" . get_home_url('/master/m_goods.php') . "\">商品情報登録</a></li>\n" .
                "<li class=\"nav-item\"><a class=\"nav-link\" href=\"" . get_home_url('/system/stock_results_inquiry.php') . "\">入荷実績照会</a></li>\n" .
                // -----------------------------------------------------------issue289 start--------------------------------------------------------------------------
                "<li class=\"nav-item\"><a class=\"nav-link\" href=\"" . get_home_url('/system/shipment_request_modification.php') . "\">出荷依頼変更</a></li>\n" .
                // -----------------------------------------------------------issue289 end----------------------------------------------------------------------------
                "<li class=\"nav-item\"><a class=\"nav-link\" href=\"" . get_home_url('/system/shipment_request_confirmation.php') . "\">出荷依頼確認</a></li>\n" .
                    "<li class=\"nav-item\"><a class=\"nav-link\" href=\"" . get_home_url('/system/shipping_schedule.php') . "\">出荷予定一覧確認</a></li>\n" .
                "<li class=\"nav-item\"><a class=\"nav-link\" href=\"" . get_home_url('/system/shipping_prepare2.php') . "\">出荷準備</a></li>\n" .
                "<li class=\"nav-item\"><a class=\"nav-link\" href=\"" . get_home_url('/system/shipment_inquiry.php') . "\">出荷実績照会</a></li>\n" .
                "<li class=\"nav-item\"><a class=\"nav-link\" href=\"" . get_home_url('/system/inventory_have_a_look.php') . "\">在庫一覧</a></li>\n" .
                "<li class=\"nav-item\"><a class=\"nav-link\" href=\"" . get_home_url('/system/inventory_changes.php') . "\">在庫推移</a></li>\n" .
                "<li class=\"nav-item\"><a class=\"nav-link\" href=\"" . get_home_url('/system/monthly_closing.php') . "\">月締め処理</a></li>\n" .
                "<li class=\"nav-item dropdown\">\n" . "<a class=\"nav-link dropdown-toggle\" href=\"#\" id=\"navbarDropdownMenuLink\" role=\"button\" data-toggle=\"dropdown\" aria-haspopup=\"true\" aria-expanded=\"false\">各情報登録</a>\n" . "\t<ul class=\"dropdown-menu\" aria-labelledby=\"navbarDropdownMenuLink\">\n" . "\t\t<li><a class=\"dropdown-item\" href=\"" . get_home_url('/master/m_staff.php') . "\">商品所有者情報登録</a></li>\n" . "\t\t<li><a class=\"dropdown-item\" href=\"" . get_home_url('/master/m_staff_update.php') . "\">商品所有者情報更新</a></li>\n" . "\t\t<li><a class=\"dropdown-item\" href=\"" . get_home_url('/master/m_staff_undelete.php') . "\">商品所有者情報復活</a></li>\n" . "\t\t<li><a class=\"dropdown-item\" href=\"" . get_home_url('/master/m_user.php') . "\">ユーザー情報登録</a></li>\n" . "\t\t<li><a class=\"dropdown-item\" href=\"" . get_home_url('/master/m_user_update.php') . "\">ユーザー情報更新</a></li>\n" . "\t\t<li><a class=\"dropdown-item\" href=\"" . get_home_url('/master/m_user_undelete.php') . "\">ユーザー情報復活</a></li>\n" . "\t\t<li><a class=\"dropdown-item\" href=\"" . get_home_url('/master/m_goods.php') . "\">商品情報登録</a></li>\n" . "\t\t<li><a class=\"dropdown-item\" href=\"" . get_home_url('/master/m_goods_update.php') . "\">商品情報更新</a></li>\n" . "\t\t<li><a class=\"dropdown-item\" href=\"" . get_home_url('/master/m_goods_undelete.php') . "\">商品情報復活</a></li>\n" . "\t</ul>\n" . "</li>\n";
            break;
        case ROLE_LOGISTICS:
            $nav_li = "<li class=\"nav-item\"><a class=\"nav-link\" href=\"" . get_home_url('/system/registrer_in_stock.php') . "\">入荷登録</a></li>\n" .
                "<li class=\"nav-item\"><a class=\"nav-link\" href=\"" . get_home_url('/system/stock_results_inquiry.php') . "\">入荷実績照会</a></li>\n" .
                    "<li class=\"nav-item\"><a class=\"nav-link\" href=\"" . get_home_url('/system/shipment_request_confirmation.php') . "\">出荷依頼確認</a></li>\n" .
                "<li class=\"nav-item\"><a class=\"nav-link\" href=\"" . get_home_url('/system/shipping_schedule.php') . "\">出荷予定一覧確認</a></li>\n" .
                "<li class=\"nav-item\"><a class=\"nav-link\" href=\"" . get_home_url('/system/shipping_prepare2.php') . "\">出荷準備</a></li>\n" .
                "<li class=\"nav-item\"><a class=\"nav-link\" href=\"" . get_home_url('/system/shipment_record.php') . "\">出荷実績入力</a></li>\n" .
                "<li class=\"nav-item\"><a class=\"nav-link\" href=\"" . get_home_url('/system/shipment_inquiry.php') . "\">出荷実績照会</a></li>\n" .
                "<li class=\"nav-item\"><a class=\"nav-link\" href=\"" . get_home_url('/system/inventory.php') . "\">棚卸入力</a></li>\n" .
                "<li class=\"nav-item\"><a class=\"nav-link\" href=\"" . get_home_url('/system/inventory_have_a_look.php') . "\">在庫一覧</a></li>\n" .
                "<li class=\"nav-item\"><a class=\"nav-link\" href=\"" . get_home_url('/system/inventory_changes.php') . "\">在庫推移</a></li>\n";
            break;
        case ROLE_ADMIN:
            $nav_li = "<li class=\"nav-item\"><a class=\"nav-link\" href=\"" . get_home_url('/master/m_goods.php') . "\">商品情報登録</a></li>\n" .
                "<li class=\"nav-item\"><a class=\"nav-link\" href=\"" . get_home_url('/system/scheduled_arrival_registration.php') . "\">入荷予定登録</a></li>\n" .
                "<li class=\"nav-item\"><a class=\"nav-link\" href=\"" . get_home_url('/system/registrer_in_stock.php') . "\">入荷登録</a></li>\n" .
                "<li class=\"nav-item\"><a class=\"nav-link\" href=\"" . get_home_url('/system/stock_results_inquiry.php') . "\">入荷実績照会</a></li>\n" .
                "<li class=\"nav-item\"><a class=\"nav-link\" href=\"" . get_home_url('/system/shipment_request_registration.php') . "\">出荷依頼登録</a></li>\n" .
                // -----------------------------------------------------------issue296 start--------------------------------------------------------------------------
                // "<li class=\"nav-item\"><a class=\"nav-link\" href=\"" . get_home_url('/system/combined_shipping.php') . "\">出荷依頼登録（同梱）</a></li>\n" .
                // -----------------------------------------------------------issue296 end----------------------------------------------------------------------------
                // -----------------------------------------------------------issue289 start--------------------------------------------------------------------------
                "<li class=\"nav-item\"><a class=\"nav-link\" href=\"" . get_home_url('/system/shipment_request_modification.php') . "\">出荷依頼変更</a></li>\n" .
                // -----------------------------------------------------------issue289 end----------------------------------------------------------------------------
                "<li class=\"nav-item\"><a class=\"nav-link\" href=\"" . get_home_url('/system/shipment_request_confirmation.php') . "\">出荷依頼確認</a></li>\n" .
                "<li class=\"nav-item\"><a class=\"nav-link\" href=\"" . get_home_url('/system/shipping_schedule.php') . "\">出荷予定一覧確認</a></li>\n" .
                "<li class=\"nav-item\"><a class=\"nav-link\" href=\"" . get_home_url('/system/shipping_prepare2.php') . "\">出荷準備</a></li>\n" .
                "<li class=\"nav-item\"><a class=\"nav-link\" href=\"" . get_home_url('/system/shipment_record.php') . "\">出荷実績入力</a></li>\n" .
                "<li class=\"nav-item\"><a class=\"nav-link\" href=\"" . get_home_url('/system/shipment_inquiry.php') . "\">出荷実績照会</a></li>\n" .
                    "<li class=\"nav-item\"><a class=\"nav-link\" href=\"" . get_home_url('/system/dns_check.php') . "\">DNS専用確認ページ</a></li>\n" .
                "<li class=\"nav-item\"><a class=\"nav-link\" href=\"" . get_home_url('/system/inventory.php') . "\">棚卸入力</a></li>\n" .
                "<li class=\"nav-item\"><a class=\"nav-link\" href=\"" . get_home_url('/system/inventory_have_a_look.php') . "\">在庫一覧</a></li>\n" .
                "<li class=\"nav-item\"><a class=\"nav-link\" href=\"" . get_home_url('/system/inventory_changes.php') . "\">在庫推移</a></li>\n" .
                "<li class=\"nav-item\"><a class=\"nav-link\" href=\"" . get_home_url('/system/monthly_closing.php') . "\">月締め処理</a></li>\n" .
                "<li class=\"nav-item dropdown\">\n" . "<a class=\"nav-link dropdown-toggle\" href=\"#\" id=\"navbarDropdownMenuLink\" role=\"button\" data-toggle=\"dropdown\" aria-haspopup=\"true\" aria-expanded=\"false\">各種計算</a>\n" . "\t<ul class=\"dropdown-menu\" aria-labelledby=\"navbarDropdownMenuLink\">\n" . "\t\t<li><a class=\"dropdown-item\" href=\"" . get_home_url('/system/shipping_calculation.php') . "\">送料計算</a></li>\n" . "\t\t<li><a class=\"dropdown-item\" href=\"" . get_home_url('/system/storage_fee.php') . "\">保管料計算</a></li>\n"."\t</ul>\n" . "</li>\n".
                "<li class=\"nav-item dropdown\">\n" . "<a class=\"nav-link dropdown-toggle\" href=\"#\" id=\"navbarDropdownMenuLink\" role=\"button\" data-toggle=\"dropdown\" aria-haspopup=\"true\" aria-expanded=\"false\">各情報登録</a>\n" . "\t<ul class=\"dropdown-menu\" aria-labelledby=\"navbarDropdownMenuLink\">\n" . "\t\t<li><a class=\"dropdown-item\" href=\"" . get_home_url('/master/m_staff.php') . "\">商品所有者情報登録</a></li>\n" . "\t\t<li><a class=\"dropdown-item\" href=\"" . get_home_url('/master/m_staff_update.php') . "\">商品所有者情報更新</a></li>\n" . "\t\t<li><a class=\"dropdown-item\" href=\"" . get_home_url('/master/m_staff_undelete.php') . "\">商品所有者情報復活</a></li>\n" . "\t\t<li><a class=\"dropdown-item\" href=\"" . get_home_url('/master/m_user.php') . "\">ユーザー情報登録</a></li>\n" . "\t\t<li><a class=\"dropdown-item\" href=\"" . get_home_url('/master/m_user_update.php') . "\">ユーザー情報更新</a></li>\n" . "\t\t<li><a class=\"dropdown-item\" href=\"" . get_home_url('/master/m_user_undelete.php') . "\">ユーザー情報復活</a></li>\n" . "\t\t<li><a class=\"dropdown-item\" href=\"" . get_home_url('/master/m_goods.php') . "\">商品情報登録</a></li>\n" . "\t\t<li><a class=\"dropdown-item\" href=\"" . get_home_url('/master/m_goods_update.php') . "\">商品情報更新</a></li>\n" . "\t\t<li><a class=\"dropdown-item\" href=\"" . get_home_url('/master/m_goods_undelete.php') . "\">商品情報復活</a></li>\n" . "\t</ul>\n" . "</li>\n";
            break;
        case ROLE_TESHITA:
            $nav_li = "<li class=\"nav-item\"><a class=\"nav-link\" href=\"" . get_home_url('/system/shipment_request_registration.php') . "\">出荷依頼登録</a></li>\n" .
                //"<li class=\"nav-item\"><a class=\"nav-link\" href=\"" . get_home_url('/system/combined_shipping.php') . "\">出荷依頼登録（同梱）</a></li>\n" .
                // -----------------------------------------------------------issue289 start--------------------------------------------------------------------------
                "<li class=\"nav-item\"><a class=\"nav-link\" href=\"" . get_home_url('/system/shipment_request_modification.php') . "\">出荷依頼変更</a></li>\n" .
                // -----------------------------------------------------------issue289 end----------------------------------------------------------------------------
                "<li class=\"nav-item\"><a class=\"nav-link\" href=\"" . get_home_url('/system/shipment_request_confirmation.php') . "\">出荷依頼確認</a></li>\n" .
                    "<li class=\"nav-item\"><a class=\"nav-link\" href=\"" . get_home_url('/system/shipping_schedule.php') . "\">出荷予定一覧確認</a></li>\n" .
                "<li class=\"nav-item\"><a class=\"nav-link\" href=\"" . get_home_url('/system/shipment_inquiry.php') . "\">出荷実績照会</a></li>\n" .
                "<li class=\"nav-item\"><a class=\"nav-link\" href=\"" . get_home_url('/system/shipping_calculation.php') . "\">送料計算</a></li>\n" .
                "<li class=\"nav-item\"><a class=\"nav-link\" href=\"" . get_home_url('/system/inventory_have_a_look.php') . "\">在庫一覧</a></li>\n" .
                "<li class=\"nav-item\"><a class=\"nav-link\" href=\"" . get_home_url('/system/inventory_changes.php') . "\">在庫推移</a></li>\n" ;
            break;
        case ROLE_INVALID:
        default:
    }
}

get_head()?>

<body>
<div class="container-fluid">
  <div class="row">
    <div class="header col-12">
    <header id="header">
      <h1 class="site_title mt-4">メルカリ出荷管理システム</h1>
      <p class="login_user"><?php

if (isset($_SESSION['login_user_name'])) {
    echo $_SESSION['login_user_name'];
}
?>
        <a href="<?php

    echo get_home_url('/')?>logout.php">ログアウト</a></p>
    </div>
      <nav id="nav" class="w-100 navbar navbar-expand-lg navbar-light bg-light">
        <!--a class="navbar-brand" href="#">Navbar</a-->
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
          <ul class="navbar-nav mr-auto">
            <?php

    echo $nav_li;
    ?>
          </ul>
        </div>
      </nav>
    </header>
  </div>
  <!-- /.row -->