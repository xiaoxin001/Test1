<?php

namespace Ejiayou\PHP\Utils\Log;

use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;

/**
 * 易加油日志工具类
 * Class LogUtils
 * @package Ejiayou\PHP\Utils\Log
 */
class LogUtils
{
    // 所有的LOG都要求在这里注册
    protected static $levels_action = [
        'test'     => 'debug',
        'debug'     => 'debug',
        'info'      => 'info',
        'notice'    => 'notice',
        'warning'   => 'warning',
        'error'     => 'error',
        'critical'  => 'critical',
        'alert'     => 'alert',
        'emergency' => 'emergency',
        'queueError' => 'error',
        'queueInfo' => 'info',
        'cronError' => 'error',
        'cronInfo' => 'info',
        'operateLog' => 'notice',
        'httpError' => 'error',
        'httpInfo' => 'info',
    ];

    //魔术方法
    public static function __callStatic($name,$arguments)
    {
        $handler= new RotatingFileHandler(storage_path().'/logs/'.$name .'.log', env('LOG_MAXFILES',30));
        $log= new Logger(env('APP_ENV'),[$handler]);//每个错误类型一个文件
        $log_action = self::$levels_action[$name];
        $log_msg = array_shift($arguments);
        $log->log($log_action,$log_msg,$arguments);
    }
}
