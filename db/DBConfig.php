<?php

function dbConfig()
{
    $file = 'setting/db_setting.ini';

    // ファイルのデータを連想配列化。できなければ例外を投げる。
    if (! $settings = parse_ini_file($file, TRUE)) {
        throw new Exception('設定ファイルが開けません。');
    }
    // ポート番号が設定ファイルに書かれているか。
    $pflg = FALSE;
    if (! empty($settings['database']['port'])) {
        $pflg = TRUE;
    }
    // 定数DB_DRIVERが定義済みの場合DNSのすべての定数を定義しない
    // ！【複数のDBを使うときはこの関数を変更してください】
    // $pflg = ポートの指定があるか(ある：TRUE)
    // ポートの指定があるときだけ定数「DB_PORT」の追加
    if (! defined('DB_DRIVER')) {
        define('DB_DRIVER', $settings['database']['driver']);
        define('DB_HOST', $settings['database']['host']);
        if ($pflg) {
            define('DB_PORT', $settings['database']['port']);
        }
        define('DB_NAME', $settings['database']['schema']);
        define('DB_USER', $settings['database']['username']);
        define('DB_PW', $settings['database']['password']);
    }
    return $pflg;
}