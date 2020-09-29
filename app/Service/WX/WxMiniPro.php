<?php
/**
 * Created by PhpStorm.
 * User: senuer
 * Date: 2017/11/1
 * Time: 14:42
 */

namespace App\Service\WX;

use Illuminate\Support\Facades\Cache;

Trait WxMiniPro {
    /**
     * 构造函数
     *
     * @param $sessionKey string 用户在小程序登录后获取的会话密钥
     * @param $appid      string 小程序的appid
     */
    public function WxMiniPro() {
        $this->appId = env('WX_PRO_ID');
        $this->appSecrect = env('WX_PRO_SECRET');
        $this->sessionKeyName = env('WX_SK_CACHE_NAME');
    }

    public function getMicroProOpenID($code) {
        $this->WxMiniPro();
        $url = 'https://api.weixin.qq.com/sns/jscode2session?appid='.$this->appId.'&secret='.$this->appSecrect.'&js_code='.$code.'&grant_type=authorization_code';
        $openID = file_get_contents($url);
        $openID = json_decode($openID);
//openid	    string	用户唯一标识
//session_key	string	会话密钥
//unionid	    string	用户在开放平台的唯一标识符
        if ($openID) {
            Cache::store('redis')->put($this->sessionKeyName, $openID->session_key, 118);
            return $openID;
        }
        return false;
    }

    /**
     * 检验数据的真实性，并且获取解密后的明文.
     *
     * @param $sessionKey    string code拿openid一起带过来
     * @param $encryptedData string 加密的用户数据
     * @param $iv            string 与用户数据一同返回的初始向量
     * @param $data          string 解密后的原文
     *
     * @return int 成功0，失败返回对应的错误码
     */
    public function miniProDecrypt($encryptedData, $iv, &$data) {
        $this->WxMiniPro();
        $sessionKey = Cache::store('redis')->get($this->sessionKeyName);
        if (!$sessionKey)
            return ErrorCode::$IllegalSkError;
        if (strlen($sessionKey) != 24) {
            return ErrorCode::$IllegalAesKey;
        }
        $aesKey = base64_decode($sessionKey);

        if (strlen($iv) != 24) {
            return ErrorCode::$IllegalIv;
        }
        $aesIV = base64_decode($iv);

        $aesCipher = base64_decode($encryptedData);

        $result = openssl_decrypt($aesCipher, "AES-128-CBC", $aesKey, 1, $aesIV);

        $dataObj = json_decode($result);
        if ($dataObj == NULL) {
            return ErrorCode::$IllegalBuffer;
        }
        if ($dataObj->watermark->appid != $this->appId) {
            return ErrorCode::$IllegalBuffer;
        }
        $data = $dataObj;
//        {
//            "openId": "OPENID",
//            "nickName": "NICKNAME",
//            "gender": GENDER,
//            "city": "CITY",
//            "province": "PROVINCE",
//            "country": "COUNTRY",
//            "avatarUrl": "AVATARURL",
//            "unionId": "UNIONID",
//            "watermark":
//            {
//                "appid":"APPID",
//                "timestamp":TIMESTAMP
//            }
//        }
        return ErrorCode::$OK;
    }
}