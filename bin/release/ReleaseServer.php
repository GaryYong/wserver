<?php

/**
 * Created by PhpStorm.
 * User: gary.li<1031965173@qq.com>
 * Date: 2018/12/18 0018
 * Time: 13:58
 */
namespace bin\release;
use library\servers\Server;
use factory\LogHandle;
/**
 * $serv = new ReleaseServer();
 * $serv->run();
 * @todo
 * @author gary.li<1031965173@qq.com>
 * @date ${DATE}
 */
class ReleaseServer extends Server
{
    protected $runCfg = [
        'timeout'       => 1, //select and epoll_wait timeout.
        'poll_thread_num' => 1, //reactor thread num
        /**
         * 进程数，
         * 全异步非阻塞服务器 worker_num配置为CPU核数的1-4倍即可。
         * 同步阻塞服务器，worker_num配置为100或者更高，具体要看每次请求处理的耗时和操作系统负载状况
         */
        'worker_num' => 1,
        /**
         * 此参数表示worker进程在处理完n次请求后结束运行。manager会重新创建一个worker进程。此选项用来防止worker进程内存溢出。
         */
        'max_request' => 2000,
        /**
         * 此参数将决定最多同时有多少个待accept的连接，swoole本身accept效率是很高的，基本上不会出现大量排队情况。
         */
        'backlog' => 128,
        /**
         * 此参数用来设置Server最大允许维持多少个tcp连接。超过此数量后，新进入的连接将被拒绝
         * 此参数不要调整的过大，根据机器内存的实际情况来设置。底层会根据此数值一次性分配一块大内存来保存Connection信息
         */
        'max_conn' => 1024,
        /**
         * 1平均分配，2按FD取模固定分配，3抢占式分配，默认为取模(dispatch=2)
         * 抢占式分配，每次都是空闲的worker进程获得数据。很合适SOA/RPC类的内部服务框架
        当选择为dispatch=3抢占模式时，worker进程内发生onConnect/onReceive/onClose/onTimer会将worker进程标记为忙，不再接受新的请求。reactor会将新请求投递给其他状态为闲的worker进程
        如果希望每个连接的数据分配给固定的worker进程，dispatch_mode需要设置为2
         */
        'dispatch_mode' => 3,
        'open_length_check' => TRUE,
        'package_length_type' => 'L',
        'package_length_offset' => 3,
        'package_body_offset' => 7,
        'package_max_length' => 1024 * 64, //最大支持-512K数据
        //'daemonize'     => true,//守护进程化
        //'open_tcp_keepalive' => 1,
        //'log_file' => '/tmp/swoole.log', //swoole error log
    ];
    public function __construct($host,$port)
    {
        $this->host = $host;
        $this->port = $port;
    }
    function onReceive($serv, $fd, $from_id, $data){
        //LogHandle::serverLog()->error($data);
        echo "\r\n";
        //$msg_length = unpack("L", $data)[1];
        $heads = unpack("CcmdLen/SfileNameLen",substr($data,0,3));
        $cmd = substr($data,$this->runCfg['package_body_offset'],$heads['cmdLen']);
        $fileName = substr($data,$this->runCfg['package_body_offset']+$heads['cmdLen'],$heads['fileNameLen']);
        $body = substr($data,$this->runCfg['package_body_offset']+$heads['cmdLen']+$heads['fileNameLen']);
        switch ($cmd){
            case "release":
                $filePath = LOG_PATH.$fileName;
                $filePathInfo = pathinfo($filePath);
                if(!is_dir($filePathInfo['dirname'])){
                    try{
                        mkdir($filePathInfo['dirname'],0777,true);
                    }catch (\Exception $ex){
                        LogHandle::serverLog()->error($ex->getMessage());
                    }
                }
                file_put_contents($filePath,$body);
                var_dump($heads,$cmd,$fileName,$body);
                echo "\r\n";
                $serv->send($fd, "service:hello");
                $serv->close($fd);
                break;
            default:
                $this->adminOp($serv, $fd, $from_id,$cmd);
        }

    }
}