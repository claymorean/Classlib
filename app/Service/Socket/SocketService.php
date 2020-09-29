<?php
/**
 * Created by PhpStorm.
 * User: senuer
 * Date: 2019/4/26
 * Time: 14:35
 */

namespace App\Service\Socket;

/**
 * Class SocketService
 * socket相关操作
 * @package App\Service\Socket
 */
class SocketService {
    public function __construct() {
        $this->socket = @socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        if ($this->socket) {
            socket_set_option($this->socket, SOL_SOCKET, SO_RCVTIMEO, array("sec" => 1, "usec" => 0));
            socket_set_option($this->socket, SOL_SOCKET, SO_SNDTIMEO, array("sec" => 3, "usec" => 0));
            if (!@socket_connect($this->socket, env('SOCKET_IP'), env('SOCKET_PORT')))
                return false;
        }
        return false;
    }

    private function cal_checksum($buf) {
        $buflen = strlen($buf);
        $checksum = 0;
        for ($i = 0; $i < $buflen; $i++) {
            $checksum ^= ord($buf[ $i ]);
        }
        return $checksum;
    }

    private function make_struction($action, $params) {
        if (is_array($params)) {
            $param_uint_count = count($params);
            $param_size = 4 * $param_uint_count;
        } else {
            $param_uint_count = 0;
            $param_size = ($params && is_string($params)) ? strlen($params) : 0;
        }
        $msgid = 0x00008001;
        $sessionid = 0;
        $synid = time(NULL);
        $bodylen = 8 + $param_size;
        $encryption = 0;
        $buf = pack("L1L1L1L1C1L1L1", $msgid, $sessionid, $synid, $bodylen, $encryption, $action, $param_size);
        if ($param_uint_count > 0) {
            for ($i = 0; $i < $param_uint_count; $i++)
                $buf .= pack("L1", $params[ $i ]);
        } else if ($param_size > 0) {
            $buf .= $params;
        }
        $buf .= pack("C1", $this->cal_checksum($buf));
        return $buf;
    }

    private function check_struction($buf) {
        $buflen = strlen($buf);
        $checksum = 0;
        for ($i = 0; $i < $buflen; $i++) {
            $checksum ^= ord($buf[ $i ]);
        }
        if ($checksum == 0) {
            $arr = unpack("L", $buf);
            if ($arr[ 1 ] == 0x80000000) //通用应答
                return ord($buf[ 25 ]);//error_code
        }
        return NULL;
    }

    /**
     * JSON格式命令 https://gitlab.mplanet.cn/document/Gen-Docs/wikis/通信协议#json格式命令
     * @param $id 设备id
     *            开始直播
     *
     * @return string
     */
    public function jsonCommand($id, $json) {
        $action = 15;
        $params = pack("L1", $id).pack("L1", strlen($json)).$json;
        //params [设备ID,JSON字符串长度,JSON字符串]
        $buf = $this->make_struction($action, $params);

        if (@socket_send($this->socket, $buf, strlen($buf), 0)) {
            $ack = "";
            $recv_flag = 0;//MSG_WAITALL
            if (@socket_recv($this->socket, $ack, 27, $recv_flag)) {
                //    socket_read($socket, 1024); 读最大长度
                socket_close($this->socket);
                return $this->check_struction($ack);
                // $nowLive = Live::where('term_id', $id)
                //     ->where('onAir', 1)
                //     ->first();
                // if ($nowLive) {
                //     socket_close($this->socket);
                //     return $nowLive->pull_url;
                // }
                // return '';
            }
        }
        socket_close($this->socket);
    }

    /**
     * @param $id 设备id
     *            开始直播
     *
     * @return string
     */
    public function liveStart($id) {
        $action = 9;
        $params = [$id, 1];
        $buf = $this->make_struction($action, $params);

        if (@socket_send($this->socket, $buf, strlen($buf), 0)) {
            $ack = "";
            $recv_flag = 0;//MSG_WAITALL
            if (@socket_recv($this->socket, $ack, 27, $recv_flag)) {
//                            socket_read($socket, 1024); 读最大长度
//                        return $this->check_struction($ack);
                $nowLive = Live::where('term_id', $id)
                    ->where('onAir', 1)
                    ->first();
                if ($nowLive) {
                    socket_close($this->socket);
                    return $nowLive->pull_url;
                }
                return '';
            }
        }
        socket_close($this->socket);
    }

    public function liveContinue($id) {
        $action = 9;
        $params = [$id, 3];
        $buf = $this->make_struction($action, $params);

        if (@socket_send($this->socket, $buf, strlen($buf), 0)) {
            $ack = "";
            $recv_flag = 0;//MSG_WAITALL
            if (@socket_recv($this->socket, $ack, 27, $recv_flag)) {
//                            socket_read($socket, 1024); 读最大长度
                socket_close($this->socket);
                return $this->check_struction($ack);
            }
        }
        socket_close($this->socket);
    }

    /**
     * @param $id 设备id
     *            远程唤醒
     */
    public function remoteLive($id) {
        $action = 13;
        $params = [$id];
        $buf = $this->make_struction($action, $params);

        if (@socket_send($this->socket, $buf, strlen($buf), 0)) {
            $ack = "";
            $recv_flag = 0;//MSG_WAITALL
            if (@socket_recv($this->socket, $ack, 27, $recv_flag)) {
//                            socket_read($socket, 1024); 读最大长度
//                socket_close($this->socket);
//                        return $this->check_struction($ack);
                $live = $this->liveStart($id);
                while (!$live) {
                    $live = $this->liveStart($id);
                }
                return $live;
            }
        }
        socket_close($this->socket);
    }

    /**
     * 远程重启
     *
     * @param $id 设备id
     */
    public function remoteRestart($id) {
        $action = 8;
        $params = [$id];
        $buf = $this->make_struction($action, $params);

        if (@socket_send($this->socket, $buf, strlen($buf), 0)) {
            $ack = "";
            $recv_flag = 0;//MSG_WAITALL
            if (@socket_recv($this->socket, $ack, 27, $recv_flag)) {
//                            socket_read($socket, 1024); 读最大长度
                socket_close($this->socket);
//                        return $this->check_struction($ack);
                $nowLive = Live::where('term_id', $id)
                    ->where('onAir', 1)
                    ->first();
                return $nowLive ? $nowLive->pull_url : '';
            }
        }
        socket_close($this->socket);
    }
}