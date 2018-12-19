<?php
/**
 * Created by PhpStorm.
 * User: gary.li<1031965173@qq.com>
 * Date: 2018/12/18 0018
 * Time: 10:44
 */
define('ROOT_PATH',__DIR__.DIRECTORY_SEPARATOR);
define('LOG_PATH',ROOT_PATH.'logs'.DIRECTORY_SEPARATOR);
require_once ROOT_PATH."vendor".DIRECTORY_SEPARATOR."autoload.php";
$release = new \bin\release\ReleaseServer('0.0.0.0',8082);
$release->run();
