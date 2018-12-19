<?php
/**
 * Created by PhpStorm.
 * User: gary.li<1031965173@qq.com>
 * Date: 2018/12/18 0018
 * Time: 14:48
 */


define("ROOT_PATH",__DIR__);
$dataFile = ROOT_PATH.DIRECTORY_SEPARATOR."test_data.zip";

/**
 * head
 * 2个字节标识文件名
 *
 *


if(count($argv) < 2){
    echo "\r\n";
    echo "NOTICE:php client.php reload|stop|halt|release";
    echo "\r\n";
    exit;
}
$cmd = $argv[1];
 */

function send($data){
    $cmd = 'release';
    foreach ($data as $v){
        $client = new swoole_client(SWOOLE_SOCK_TCP);
        if (!$client->connect('127.0.0.1', 8082, -1))
        {
            exit("connect failed. Error: {$client->errCode}\n");
        }
        $cfgName = "ok/{$v['config_name']}.php";
        $time = time();
        $body = $v['config'];
        $client->send(pack("C",strlen($cmd)));
        $client->send(pack("S",strlen($cfgName)));
        $client->send(pack("L",strlen($cmd.$cfgName.$body)));
        $client->send($cmd.$cfgName.$body);
        //$client->recv();
        $client->close();
    }
}

if(!empty($_POST['config_name'])){
    $configName = $_POST['config_name'];
    $config = $_POST['config'];

}
/*
for ($i=1;$i<1000;$i++){
    $client->send("hello-{$i}\r\n");
}
/*
$handle = fopen($dataFile,'r');
while (!feof($handle)) {
    $contents = fread($handle, 500);
    $client->send($contents);
}
fclose($handle);
*/
?>
<html>
<head>
    <meta charset="UTF-8">
    <title>发布</title>
</head>
<body>

<form method="post">
    文件名：<input type="text" name="config_name">
    配置内容：<textarea rows="6" cols="60" name="config"></textarea>
    <input type="submit" name="btn" value="提交">
</form>
</body>
</html>
