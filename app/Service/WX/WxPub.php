<?php
/**
 * Created by PhpStorm.
 * User: senuer
 * Date: 2017/11/1
 * Time: 14:42
 */

namespace App\Service\WX;

use App\Service\Socket\Curl;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

Trait WxPub {
    protected $appID;
    protected $appSecret;
    protected $token;
    protected $curl;
    protected $redis;

    public function __construct(Curl $curl) {
        $this->appID = env('WX_ID');
        $this->appSecret = env('WX_SECRET');
        $this->token = env('WX_TOKEN');
        $this->curl = $curl;
        $this->access_token = $this->getAccessToken();
    }

    //公众号
    public function checkWeixin() {
        //微信会发送4个参数到我们的服务器后台 签名 时间戳 随机字符串 随机数
        $signature = isset($_GET[ "signature" ]) ? $_GET[ "signature" ] : "";
        $timestamp = isset($_GET[ "timestamp" ]) ? $_GET[ "timestamp" ] : "";
        $nonce = isset($_GET[ "nonce" ]) ? $_GET[ "nonce" ] : "";
//        $echostr = $_GET[ "echostr" ];

        // 1）将token、timestamp、nonce三个参数进行字典序排序
        $tmpArr = array($nonce, $this->token, $timestamp);
        sort($tmpArr, SORT_STRING);

        // 2）将三个参数字符串拼接成一个字符串进行sha1加密
        $str = implode($tmpArr);
        $sign = sha1($str);

        // 3）开发者获得加密后的字符串可与signature对比，标识该请求来源于微信
        if (isset($_GET[ "echostr" ]) && $sign == $signature) {
            echo $_GET[ "echostr" ];
            exit;
        } else {
            $this->reply();
        }
    }

    public function getAccessToken($new = 0) {
        if (!$new && ($token = Cache::store('redis')->get(env('WX_TOKEN_CACHE_NAME'))))
            return $token;
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$this->appID."&secret=".$this->appSecret;
        //json字符串
//        $json = $this->curl->httpGet($url);
        $json = file_get_contents($url);
        //解析json
        $obj = json_decode($json);
        if ($obj && !isset($obj->errcode)) {

            Cache::store('redis')->put(env('WX_TOKEN_CACHE_NAME'), $obj->access_token, 118);

            return $obj->access_token;
        }
        return false;
    }

    public function getCode($url) {
//        https://open.weixin.qq.com/connect/oauth2/authorize?appid=wx6080e8740f99724d&redirect_uri=http%3a%2f%2fwx.mplanet.cn%2fbind&response_type=code&scope=snsapi_base#wechat_redirect
        $scope = 'snsapi_base';
        $url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$this->appID.'&redirect_uri='.urlencode($url).'&response_type=code&scope='.$scope.'#wechat_redirect';
        return $url;
    }

    public function getOpenID($code) {
        $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid='.$this->appID.'&secret='.$this->appSecret.'&code='.$code.'&grant_type=authorization_code';
//        $openID=$this->curl->httpGet($url);
        $openID = file_get_contents($url);
        $openID = json_decode($openID);
        if ($openID && isset($openID->openid)) {
            // $openid = Cache::get('openid');
            // if ($openid != $openID->openid) {
            // Cache::put('yqt_wx_openid', $openID->openid, 1440);
//                setcookie('yqt_wx_openid', $openID->openid, time() + 86400, '/');
            // }
            // return Cache::get('openid');
            return $openID->openid;
        }
        return false;
    }

    public function getUserInfo($openid, $access_token) {
        $url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$access_token.'&openid='.$openid.'&lang=zh_CN';
        $wxUser = file_get_contents($url);
//        $wxUser = $this->curl->httpGet($url);
//        {
//            "subscribe": 1,
//            "openid": "o6_bmjrPTlm6_2sgVt7hMZOPfL2M",
//            "nickname": "Band",
//            "sex": 1,
//            "language": "zh_CN",
//            "city": "广州",
//            "province": "广东",
//            "country": "中国",
//            "headimgurl":"http://thirdwx.qlogo.cn/mmopen/g3MonUZtNHkdmzicIlibx6iaFqAc56vxLSUfpb6n5WKSYVY0ChQKkiaJSgQ1dZuTOgvLLrhJbERQQ4eMsv84eavHiaiceqxibJxCfHe/0",
//            "subscribe_time": 1382694957,
//            "unionid": " o6_bmasdasdsad6_2sgVt7hMZOPfL"  只有在用户将公众号绑定到微信开放平台帐号后，才会出现该字段。
//            "remark": "",
//            "groupid": 0,
//            "tagid_list":[128,2]
//        }
        return json_decode($wxUser);
    }

    public function setMenu($json, $access_token) {
//        $json='{
//    "button": [
//        {
//            "name": "每日策略",
//            "sub_button": [
//            {
//                "type": "click",
//                "name": "市场点评",
//                "key": "market"
//            },
//            {
//                "type": "view",
//                "name": "游资跟踪",
//                "url": "https://open.weixin.qq.com/connect/oauth2/authorize?appid='.env('APPID').'&redirect_uri='.urlencode('http://www.tongzejiaoyu.com/capital').'&response_type=code&scope=snsapi_bas
//e&state=123#wechat_redirect"
//            },
//            {
//                "type": "view",
//                "name": "行业研报",
//                "url": "https://open.weixin.qq.com/connect/oauth2/authorize?appid='.env('APPID').'&redirect_uri='.urlencode('http://www.tongzejiaoyu.com/report').'&response_type=code&scope=snsapi_bas
//e&state=123#wechat_redirect"
//            }
//            ]
//        },
//    ]
//}';
        $url = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token=".$access_token;
        $result = $this->curl->httpPost($url, $json);
        if ($result) {
            dd($result);
        } else {
            dd($result.$json);
        }

        return $this->wxResponse($result);
    }

////    添加客服接口
//    public function addkf($json, $access_token){
//        $url = "https://api.weixin.qq.com/customservice/kfaccount/add?access_token=".$access_token;
//        $result = $this->curl->httpPost($url, $json);
//        dd($result.$json);
//        return $this->wxResponse($result);
//    }
//
//    //    发送客服接口
//    public function xxkf($json, $access_token){
//        $url = "https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=".$access_token;
//        $result = $this->curl->httpPost($url, $json);
//        dd($result);
////        return $this->wxResponse($result);
//    }

    public function reply() {

//        //1.获取到微信推送过来post数据（xml格式）
        $postArr = isset($GLOBALS[ 'HTTP_RAW_POST_DATA' ]) ? $GLOBALS[ 'HTTP_RAW_POST_DATA' ] : file_get_contents("php://input");
//        //2.处理消息类型，并设置回复类型和内容
//        /*<xml>
//        <ToUserName><![CDATA[toUser]]></ToUserName>
//        <FromUserName><![CDATA[FromUser]]></FromUserName>
//        <CreateTime>123456789</CreateTime>
//        <MsgType><![CDATA[event]]></MsgType>
//        <Event><![CDATA[subscribe]]></Event>
//        </xml>*/
////        $postObj = simplexml_load_string($postArr);
//        //解析post来的XML为一个对象$postObj
        if (!empty($postArr)) {
            $postObj = simplexml_load_string($postArr);
////        dd($postObj);
//        //$postObj->ToUserName = '';
//        //$postObj->FromUserName = '';
//        //$postObj->CreateTime = '';
//        //$postObj->MsgType = '';
//        //$postObj->Event = '';
//        // gh_e79a177814ed
//        //判断该数据包是否是订阅的事件推送
            return $postObj;
//            $toUser = $postObj->FromUserName;
//            //        发送客服消息
//            $fromUser = $postObj->ToUserName;
//            $time = time();
//            $msgType = 'text';
//            switch ($postObj->MsgType) {
//                case "event":
//                    if (strtolower($postObj->Event) == 'subscribe') {
//                        if (!User::where('wx_openid', $toUser)->first())
//                            $this->user->create(['wx_openid' => $toUser]);
//                        $content = '欢迎关注玄甲物联！';
//                    }
//                    if (strtolower($postObj->Event) == 'click') {
//                        switch ($postObj->EventKey) {
//                            case "kf":
//                                $content = "您好，产品客服热线：4008876119，接待时间为工作日早上8:30-下午5:30。您有任何建议及疑问，欢迎来电，希望能解决您的问题！";
//                                break;
//                            default:
//                                $content = "您好！";
//                        }
//                    }
//                    break;
//                case "text":
//                    switch (trim($postObj->Content)) {
//                        case "1":
//                            $content = "11111111";
//                            break;
//                        default:
//                            $content = "您好，感谢关注“玄甲物联”！请问有什么需要帮助吗？";
//                    }
//                    break;
//            }
//            $template = "<xml><ToUserName><![CDATA[%s]]></ToUserName><FromUserName><![CDATA[%s]]></FromUserName><CreateTime>%s</CreateTime><MsgType><![CDATA[%s]]></MsgType><Content><![CDATA[%s]]></Content></xml>";
//            $info = sprintf($template, $toUser, $fromUser, $time, $msgType, $content);
//            echo $info;
        } else {
            echo "";
            exit;
        }
    }

    /**
     * {
     * "template_list": [{
     * "template_id": "iPk5sOIt5X_flOVKn5GrTFpncEYTojx6ddbt8WYoV5s",
     * "title": "领取奖金提醒",
     * "primary_industry": "IT科技",
     * "deputy_industry": "互联网|电子商务",
     * "content": "{ {result.DATA} }\n\n领奖金额:{ {withdrawMoney.DATA} }\n领奖  时间:{ {withdrawTime.DATA} }\n银行信息:{
     * {cardInfo.DATA} }\n到账时间:  { {arrivedTime.DATA} }\n{ {remark.DATA} }",
     * "example": "您已提交领奖申请\n\n领奖金额：xxxx元\n领奖时间：2013-10-10
     * 12:22:22\n银行信息：xx银行(尾号xxxx)\n到账时间：预计xxxxxxx\n\n预计将于xxxx到达您的银行卡"
     * }]
     * }
     */
    public function getTemplate($title, $access_token) {
        $url = 'https://api.weixin.qq.com/cgi-bin/template/get_all_private_template?access_token='.$access_token;
        $templates = $this->curl->httpGet($url);
        $templates = json_decode($templates);
        foreach ($templates->template_list as $template) {
            if ($template->title == $title) {
                return $template->template_id;
            }
        }
        return false;
    }

    public function pushTemplate($json, $access_token) {
//        {
//            "touser":"OPENID",
//           "template_id":"ngqIpbwh8bUfcSsECmogfXcV14J0tQlEpBO27izEYtY",
//           "url":"http://weixin.qq.com/download",
//           "miniprogram":{
//            "appid":"xiaochengxuappid12345",
//             "pagepath":"index?foo=bar"
//           },
//           "data":{
//            "first": {
//                "value":"恭喜你购买成功！",
//                       "color":"#173177"
//                   },
//                   "keynote1":{
//                "value":"巧克力",
//                       "color":"#173177"
//                   },
//                   "keynote2": {
//                "value":"39.8元",
//                       "color":"#173177"
//                   },
//                   "keynote3": {
//                "value":"2014年9月22日",
//                       "color":"#173177"
//                   },
//                   "remark":{
//                "value":"欢迎再次购买！",
//                       "color":"#173177"
//                   }
//           }
//       }
        $url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token='.$access_token;
//        dd($url);
        return $this->curl->httpPost($url, $json);
//        return '{
//            "errcode":0,
//           "errmsg":"ok",
//           "msgid":200228332
//       }';
    }

    //JS-SDK
    public function getJsSignature() {
        $url = isset($_GET[ "url" ]) ? ('url='.$_GET[ "url" ]) : "";
        $timestamp = isset($_GET[ "timestamp" ]) ? ('timestamp='.$_GET[ "timestamp" ]) : "";
        $nonce = isset($_GET[ "noncestr" ]) ? ('noncestr='.$_GET[ "noncestr" ]) : "";
        $jsapi_ticket = ('jsapi_ticket='.$this->getTicket());

        // 1）noncestr、timestamp、noncestr、jsapi_ticket参数进行字典序排序
        $tmpArr = array($nonce, $jsapi_ticket, $timestamp, $url);
        sort($tmpArr, SORT_STRING);

        // 2）将三个参数字符串拼接成一个字符串进行sha1加密
        $str = implode('&', $tmpArr);
        return sha1($str);

        // 3）开发者获得加密后的字符串可与signature对比，标识该请求来源于微信
//        if (isset($_GET[ "echostr" ]) && $sign == $signature) {
//            echo $_GET[ "echostr" ];
//            exit;
//        } else {
//            $this->reply();
//        }
    }

    public function getTicket() {
        if (($ticket = Cache::store('redis')->get(env('WX_TICKET_CACHE_NAME'))))
            return $ticket;
        $url = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token='.$this->accessToken.'&type=jsapi';
        $result = json_decode(file_get_contents($url));
        if ($result->errcode == 0) {
            Cache::store('redis')->put(env('WX_TICKET_CACHE_NAME'), $result->ticket, 100);
            return $result->ticket;
        }
        $this->accessToken = $this->getAccessToken(1);
        return $this->getTicket();
    }
}