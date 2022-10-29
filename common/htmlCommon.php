<?php
// define('HOME_URL', 'https://localhost/mercari');// （TODO：ローカル開発時はこちらを利用）
define('HOME_URL', 'https://dreamnetsys.xsrv.jp/mercari');
define('IMG_URL', get_home_url('/common/images'));
define('CSS_URL', get_home_url('/common/css'));
define('JS_URL', get_home_url('/common/js'));
define('LOGIN_URL', get_home_url('/login.php'));

function get_home_url($path = '')
{
    return HOME_URL . $path;
}

function get_img_url($path = '')
{
    return IMG_URL . $path;
}

function get_css_url($path = '')
{
    return CSS_URL . $path;
}

function get_js_url($path = '')
{
    return JS_URL . $path;
}

function get_login_url($path = '')
{
    return LOGIN_URL . $path;
}

function get_head()
{
    require_once ('head.php');
}

function get_header()
{
    require_once ('header.php');
}

function get_login_header()
{
    require_once ('login_header.php');
}

function get_footer()
{
    require_once ('footer.php');
}
