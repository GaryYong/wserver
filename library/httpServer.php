<?php
/**
 * Created by PhpStorm.
 * User: gary.li<1031965173@qq.com>
 * Date: 2018/12/18 0018
 * Time: 11:06
 */

namespace lib;

abstract class httpServer
{
    protected $server = null;
    protected $host = '0.0.0.0';
    protected $port = 9502;
    final public function startServer(){
        $this->server = new \swoole_http_server($this->host,$this->port);
        $this->server->on('request',array($this,"onRequest"));
        echo "listen:{$this->host},port:{$this->port}\r\n";
        $this->server->start();
    }
    abstract public function onRequest($request,$response);
}