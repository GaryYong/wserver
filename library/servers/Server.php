<?php

/**
 * Created by PhpStorm.
 * User: gary.li<1031965173@qq.com>
 * Date: 2018/12/18 0018
 * Time: 14:00
 */
namespace library\servers;
use factory\LogHandle;
abstract class Server
{
    protected $host = '0.0.0.0';
    protected $port = 8082;
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
        //'daemonize'     => true,//守护进程化
        //'open_tcp_keepalive' => 1,
        //'log_file' => '/tmp/swoole.log', //swoole error log
    ];

    /*
    struct
    {
        char cmd;
        ushort fileName;
        uint32 length;
        char body[0];
    }
    */
    //
    protected $clients;
    protected $backends;
    protected $serv;

    public function run($host=false,$port=false,$mode=SWOOLE_PROCESS,$sock_type=SWOOLE_SOCK_TCP)
    {
        $this->host = $host ? $host : $this->host;
        $this->port = $port ? $port : $this->port;
        $serv = new \swoole_server($this->host, $this->port,$mode,$sock_type);
        $serv->set($this->runCfg);
        $serv->on('Start', array($this, 'onStart'));
        $serv->on('WorkerStart', array($this, 'onWorkerStart'));
        $serv->on('Connect', array($this, 'onConnect'));
        $serv->on('Receive', array($this, 'onReceive'));
        $serv->on('Close', array($this, 'onClose'));
        $serv->on('WorkerStop', array($this, 'onShutdown'));
        //swoole_server_addtimer($serv, 2);
        #swoole_server_addtimer($serv, 10);
        $serv->start();
    }
    protected function adminOp($serv, $fd, $from_id,$cmd){
        switch ($cmd){
            case "reload":
                $serv->send($fd,"reload ok");
                $serv->close($fd);
                $serv->reload();
                break;
            case "stop":
                $serv->send($fd,"stop ok");
                $serv->close($fd);
                $serv->stop();
                break;
            case "halt":
                $serv->send($fd,"halt ok");
                $serv->close($fd);
                $serv->shutdown();
                break;
            case "status":
                var_dump($serv->stats());
                $serv->send($fd,json_encode($serv->stats()));
                $serv->close($fd);
                break;
            default:
                $serv->close($fd);
        }
    }

    public function onWorkerStart($serv,$worker_id){
        LogHandle::serverLog()->warning("on Worker Start");
        LogHandle::serverLog()->warning("master_pid:{$serv->master_pid},manager_pid:{$serv->manager_pid}");
    }

    /**
     * Server启动在主进程的主线程回调此函数
     * @param $serv
     */
    public function onStart($serv){
        LogHandle::serverLog()->warning("on Start");
        LogHandle::serverLog()->warning("master_pid:{$serv->master_pid},manager_pid:{$serv->manager_pid}");
        //var_dump(get_included_files());
    }
    /**
     * 使当前Worker进程停止运行，并立即触发onWorkerStop回调函数。
     * 进程异常结束，如被强制kill、致命错误、core dump 时无法执行onWorkerStop回调函数
     * */
    public function onWorkerStop($server,$worker_id){
        LogHandle::serverLog()->warning("on Stop");
    }
    public function onConnect($server, $fd, $worker_id){
        LogHandle::serverLog()->warning("on Connect");
    }
    public function onClose($server, $fd, $worker_id){
        LogHandle::serverLog()->warning("on Close");
    }
    public function onShutdown($server){
        LogHandle::serverLog()->warning("on Sthudown");
    }

    abstract function onReceive($server, $fd, $worker_id, $data);
}