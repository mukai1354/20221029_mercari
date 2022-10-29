<?php
require_once ('common/function.php');

get_header();

// エラー情報リセット
$_SESSION["error_status"] = E_STATUS_DEFAULT;

?>
  <div id="contents">
    <div class="inner">
      <div id="main">
      <main role="main">
        <h1 class="page_title mt-4">在庫一覧</h1>
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