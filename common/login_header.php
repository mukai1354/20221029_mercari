<?php
// 2021/08/16 issue255 submitボタンをダブルクリックした際に、ログイン画面に飛ばされる demachi
require_once ('htmlCommon.php');
// セッション開始
session_start();
// --------------------------------------------------issue255 start-------------------------------------------------------
// session_regenerate_id(true);
// --------------------------------------------------issue255 end---------------------------------------------------------

header('Content-type: text/html; charset=utf-8');

?>
<?php

get_head()?>
<body>
<div id="container-fluid">
  <div class="row">
    <div class="header col-12">
    <header>
      <h1 class="site_title mt-5">メルカリ出荷管理システム</h1>
    </header>
    </div>
    <!-- /.header .col-12 -->
  </div>
  <!-- /.row -->