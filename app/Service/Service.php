<?php
/**
 * Created by PhpStorm.
 * User: senuer
 * Date: 2019/4/24
 * Time: 15:55
 */

namespace App\Service;

use App\Http\Controllers\API\ApiCode;
use App\Service\Device\DeviceType;

class Service {
//    static $serviceError = [
//        0 => 'success',
//        1 => 'fail',
//        2 => '数据源错误',
//        221 => '未找到该SN号对应的设备',
//        222 => '该设备已被其他机构绑定',
//        223 => '该设备已存在',
//        511 => '该成员已绑定为机队负责人，请先解除绑定',
//        512 => '该成员已绑定设备，请先解除绑定',
//        999 => '其他错误'
//    ];
//
//    public function response($code = 0, $msg = '') {
//        return [
//            'code' => $code,
//            'message' => isset(self::$serviceError[ $code ]) ? self::$serviceError[ $code ] : $msg
//        ];
//    }

    protected $responseCode = 0;

    public function response($data = []) {
        return json_encode([
            'code' => $this->responseCode,
            'message' => ApiCode::codeMessage($this->responseCode),
            'data' => $data
        ], JSON_UNESCAPED_UNICODE);
    }

    public function notAliDevice($type_id) {
        $deviceType = DeviceType::find($type_id);
        if ($deviceType->product_table_name && strpos($deviceType->product_table_name, 'aliiot') !== false)
            return false;
        return true;
    }
}