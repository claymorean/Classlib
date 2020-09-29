<?php

namespace App\Service\Socket;

use Swoole\Client;

/**
 * Class AsyncTask
 * 通过send方法 插入命令中的swoole队列
 * @package App\Service\Socket
 */
class AsyncTask {
    private $client;

    public function __construct() {
        $this->client = new Client(SWOOLE_SOCK_TCP);

//        $this->client->on('Connect', [$this, 'onConnect']);
//        $this->client->on('Receive', [$this, 'onReceive']);
//        $this->client->on('Close', [$this, 'onClose']);
//        $this->client->on('Error', [$this, 'onError']);
    }

    public function connect() {
        if (!$fp = $this->client->connect("127.0.0.1", env('SWTASK_PORT'), 1)) {
            echo "Error: {$fp->errMsg}[{$fp->errCode}]".PHP_EOL;
            return;
        }
    }

    /**
     * @param string $data json
     *                     [
     *                     'number'=>'任务编号',    getMicroTime(),
     *                     'type'=> 1,         1-入库
     *                     'operator'=>1,       任务开始人
     *                     'data'=>[]          数据源
     *                     ]
     */
    public function send(string $data) {
        $this->connect();
        $this->client->send($data."\r\n\r\n");
    }

//    public function onConnect($cli) {
//        fwrite(STDOUT, "输入Email:");
//        swoole_event_add(STDIN, function () {
//            fwrite(STDOUT, "输入Email:");
//            $msg = trim(fgets(STDIN));
//            $this->send($msg);
//        });
//    }
//
//    public function onReceive($cli, $data) {
//        echo PHP_EOL."Received: ".$data.PHP_EOL;
//    }
//
//    public function onClose($cli) {
//        echo "Client close connection".PHP_EOL;
//    }
//
//    public function onError() {
//
//    }
}