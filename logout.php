<?php
require_once ("common/function.php");

session_start();

header("Content-type: text/html; charset=utf-8");

// セッション破棄
$_SESSION = array();
session_destroy();

// リダイレクト
redirect_err();