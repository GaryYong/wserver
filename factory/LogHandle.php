<?php
namespace factory;
/**
 * Created by PhpStorm.
 * User: gary.li<1031965173@qq.com>
 * Date: 2018/12/19 0019
 * Time: 14:18
 */
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class LogHandle
{
    private static $logHandles = [];

    public static function init($logName="app",$channel=""){
        if(isset(self::$logHandles[$logName]) && self::$logHandles[$logName])return self::$logHandles[$logName];
        self::$logHandles[$logName] = new Logger($channel);
        $stream = new StreamHandler(LOG_PATH.$logName.".log", Logger::WARNING);
        self::$logHandles[$logName]->pushHandler($stream);
        return self::$logHandles[$logName];
    }

    public static function serverLog($channel=""){
        return self::init('server',$channel);
    }
}