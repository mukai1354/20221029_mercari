<?php
require_once ('DBConfig.php');

class DB extends PDO
{

    public function __construct()
    {
        // 設定ファイルから定数を取得しフラグを返す。
        $pflg = dbConfig();

        // $pflg = ポートの指定があるか(ある：TRUE)
        // ポートの指定があるときだけ「;poret=%s」追加
        $dns = '%s:host=%s' . (($pflg) ? ';port=%s' : '') . ';dbname=%s';

        // ポートの指定があるときだけ「DB_PORT ,」追加
        if ($pflg) {
            $dns = sprintf($dns, DB_DRIVER, DB_HOST, DB_PORT, // ポート番号
            DB_NAME);
        } else {
            $dns = sprintf($dns, DB_DRIVER, DB_HOST, DB_NAME);
        }
        $dns .= ';charset=utf8';

        parent::__construct($dns, DB_USER, DB_PW);
    }

    public static function getDB()
    {
        $db = new DB();
        $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $db;
    }
}