<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta http-equiv="Content-Security-Policy"
 content="default-src 'self' http://html5shim.googlecode.com/; img-src 'self' data:; script-src 'unsafe-inline' <?php echo get_js_url('/'); ?> https://code.jquery.com/ https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/ https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/">

<meta name="autor" content="&#169;Dream Net Systems">
<meta name="description" content="">
<meta name="keywords" content="">

<!-- リセットCSS読み込み -->
<link rel="stylesheet" type="text/css" href="<?php

echo get_css_url('/');
?>reset.css">

<!-- bootstrap4読み込み(CSS) -->
<link rel="stylesheet" type="text/css" href="<?php

echo get_css_url('/');
?>bootstrap.min.css">
<!-- 通常CSS読み込み -->
<link rel="stylesheet" type="text/css" href="<?php

echo get_css_url('/');
?>style.css?<?php

echo date('Ymd-Hi');
?>">

<!-- jQuery読み込み -->
<script src="https://code.jquery.com/jquery-3.6.0.js" integrity="sha256-H+K7U5CnXl1h5ywQfKtSj8PCmoN9aaq30gDh27Xc0jk=" crossorigin="anonymous"></script>


<!-- bootstrap4読み込み(JS) -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js" integrity="sha384-OgVRvuATP1z7JjHLkuOU7Xw704+h835Lr+6QL9UvYjZE3Ipu6Tp75j7Bh/kR0JKI" crossorigin="anonymous"></script>

<!--[if lt IE 9]>
<script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
<script src="<?php

echo get_js_url('/');
?>css3-mediaqueries.js"></script>
<![endif]-->

<title>メルカリ出荷管理システム</title>
</head>