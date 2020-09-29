<?php
/**
 * Created by PhpStorm.
 * User: senuer
 * Date: 2019/10/9
 * Time: 9:41
 */

namespace App\Http\Controllers;


use App\Service\Authority\BackUserWx;
use App\Service\Device\DeviceService;
use App\Service\Other\EncryptService;
use App\Service\ShareDevice;
use App\Service\User\UserService;
use App\Service\WX\WxService;
use App\Service\User\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class WXController extends Controller {

    /**
     * menu
     * {
     * "button": [
     * {
     * "type": "miniprogram",
     * "name": "我的设备",
     * "appid": "wx3ea944f793831479",
     * "pagepath": "pages/index/index"
     * "url":"https://wx.njxjwl.cn/wx"
     * "https://open.weixin.qq.com/connect/oauth2/authorize?appid=wxfae4359fea7b9288&redirect_uri=http%3A%2F%2Fyqt-dev.mplanet.cn%2Fwx%2Fdevices&response_type=code&scope=snsapi_base#wechat_redirect"
     * }]
     * }
     */

    public function __construct(Request $request, WxService $wxService, UserService $userService, DeviceService $deviceService) {
        $this->request = $request;
        $this->data = trim(str_replace(array("\r\n", "\r", "\n", ' ', '='), "", $request->getContent()));
        $this->wxService = $wxService;
        $this->userService = $userService;
        $this->deviceService = $deviceService;
        $this->access_token = $this->wxService->getAccessToken();
    }

    /**
     * @return bool|string
     * 显示已绑定 或者绑定页面
     */
    public function bindPage() {
        return view('wx.bind');
    }

    /**
     * @param $phone
     * 绑定微信号和用户手机号
     *
     * @return string
     */
    public function bindUser($phone) {
        $user = User::where('phone', $phone)->first();
        if ($user) {
            $this->responseCode = 20002;
            return $this->response();
        }
        $wxUser = $this->wxService->getUserInfo($this->data->openid, $this->access_token);
        User::where('wx_openid', $this->data->openid)->update([
            'username' => $wxUser->nickname,
            'phone' => $phone,
            'wx_openid' => $this->data->openid,
            'wx_unionid' => $wxUser->unionid
        ]);
        return view('wx.devices');
    }

    public function index() {
//        echo $this->wxService->checkWeixin();die();
        $postObj = $this->wxService->reply();
        if ($postObj) {
            $toUser = $postObj->FromUserName;
            //        发送客服消息
            $fromUser = $postObj->ToUserName;
            $time = time();
            $msgType = 'text';
            $content = '';
            switch ($postObj->MsgType) {
                case "event":
                    if (strtolower($postObj->Event) == 'subscribe') {
                        $this->addPubUser($toUser, $this->access_token);
                        $content = '欢迎关注' . env('APP_SHOW_NAME') . '！';
                        if (env('WX_MENU') == 'jax')
                            $content = '您好，感谢关注居安宣消防科技。我们聚焦在消防安全领域，赋能消防产品民用属性，让消防安全成为品质生活必不可少的一环。居安宣，做您身边的安全管家。';
                    }
                    if (strtolower($postObj->Event) == 'unsubscribe')
                        User::where('wx_openid', $toUser)->update(['wx_openid' => '']);
                    if (strtolower($postObj->Event) == 'click') {
                        switch ($postObj->EventKey) {
                            case "WiiOmdZhoYWep48Fwmp7UgfB39bwX96lRfQHmDCGtps":
                                $this->wxService->autoReplyPT('WiiOmdZhoYWep48Fwmp7UgfB39bwX96lRfQHmDCGtps', $toUser, $fromUser);
                                break;
                            case "WiiOmdZhoYWep48Fwmp7Uo45GedZhk0qFDqY8PdWQvA":
                                $this->wxService->autoReplyPT('WiiOmdZhoYWep48Fwmp7Uo45GedZhk0qFDqY8PdWQvA', $toUser, $fromUser);
                                break;
                            default:
                                $content = '您好，目前暂无人工服务！';
                                if (env('WX_MENU') == 'jax')
                                    $content = "您好，为了保障您更好的产品体验，请在捆绑设备后添加【客服公众号:juanxuan119】体验：新品推荐、以旧换新、在线商城、优惠活动、线下互动等内容";
                        }
                    }
                    break;
                case "text":
                    switch (trim($postObj->Content)) {
                        case "1":
//                            $content = "11111111";
//                            break;
                        default:
                            $content = "您好，感谢关注" . env('APP_SHOW_NAME') . "！请问有什么需要帮助吗？";
                            if (env('WX_MENU') == 'jax')
                                $content = "您好，为了保障您更好的产品体验，请在捆绑设备后添加【客服公众号:juanxuan119】体验：新品推荐、以旧换新、在线商城、优惠活动、线下互动等内容";
                    }
                    break;
            }
            if ($msgType == 'text') {
                $template = "<xml><ToUserName><![CDATA[%s]]></ToUserName><FromUserName><![CDATA[%s]]></FromUserName><CreateTime>%s</CreateTime><MsgType><![CDATA[%s]]></MsgType><Content><![CDATA[%s]]></Content></xml>";
                $info = sprintf($template, $toUser, $fromUser, $time, $msgType, $content);
                echo $info;
            }
        }
    }

    private function addPubUser($toUser, $accessToken) {
        $wxUser = $this->wxService->getUserInfo($toUser, $accessToken);
        $nowTime = date('Y-m-d H:i:s');
        if ($wxUser) {
            if (isset($wxUser->unionid)) {
                //必有unionid 否则视为未完成
                $user = User::where('wx_unionid', $wxUser->unionid)->first();
                if ($user)
                    $user->update(['wx_openid' => $toUser, 'follow_at' => $nowTime]);
                else
                    $this->userService->create(['wx_openid' => $toUser, 'wx_unionid' => $wxUser->unionid, 'follow_at' => $nowTime]);
                BackUserWx::updateOrCreate(['wx_unionid' => $wxUser->unionid], [
                    'wx_nickname' => $wxUser->nickname,
                    'wx_openid' => $toUser
                ]);
            }
//            else {
//                $this->userService->create(['wx_openid' => $toUser, 'follow_at' => $nowTime]);
//            }
        } else {
            $accessToken = $this->wxService->getAccessToken(1);
            $this->addPubUser($toUser, $accessToken);
            return false;
        }
    }

    public function ticket() {
        return json_encode([
            'appId' => env('WX_ID'),
            'signature' => $this->wxService->getJsSignature()
        ]);
    }

    public function devices() {
        if (isset($this->request->code) && ($openID = $this->wxService->getOpenID($this->request->code)))
            if (($user = User::where('wx_openid', $openID)->first()) && $user->phone) {
                $user->count = count($user->allDevices);
                return view('wx.devices')->with(['user' => $user]);
            } else {
                return view('wx.phone')->with(['user' => $user]);
            }
        return view('wx.devices');
    }

    public function warningType($waringType = 1) {
        if ($waringType == 1)
            return '请升级微信以使用小程序';
    }

    public function setDevice($deviceId, $setting = '') {
        if ($setting == 'set')
            return view('wx.set_devices');
        if ($setting == 'location')
            return view('wx.equipment_set');
        if ($setting == 'log')
            return view('wx.system_warning');
        if ($setting == 'history')
            return view('wx.history');
        if ($setting == 'address')
            return view('wx.address_baidu');
        if ($setting == 'check')
            return view('wx.equipment_check');
        if ($setting == 'share_equipment') {
            return view('wx.share_equipment')->with(['url' => $this->deviceService->shareUrl($deviceId)]);
        }
        if ($setting == 'share_manage') {
            return view('wx.share_manage');
        }
        if ($setting == 'history_data') {
            return view('wx.history_data');
        }
    }

    public function owner() {
        return view('wx.owner');
    }

    public function message() {
        return view('wx.message');
    }

    public function bind_device() {
        return view('wx.bind');
    }

    public function change_phone() {
        return view('wx.phone');
    }

    public function warning_message() {
        return view('wx.warning_message');
    }

    public function message_detail() {
        return view('wx.message_details');
    }

    public function share_device() {
        return view('wx.share_device');
    }

    public function invalid_link() {
        return view('wx.Invalid_link');
    }

    public function history_test() {
        return view('wx.history_test');
    }

    public function userPrivacy_xj() {
        return view('wx.userPrivacy_xj');
    }

    public function userPrivacy_ier() {
        return view('wx.userPrivacy_ier');
    }

    public function userPrivacy_jax() {
        return view('wx.userPrivacy_jax');
    }

    public function reportDetail() {
        return view('wx.reportDetail');
    }

    public function promptIndex() {
        return view('wx.report_prompt');
    }

    public function shareDevice($deviceId, $signature) {
        //判断有没有获取code 没有就通过微信跳转
        if (isset($this->request->code) && $this->request->code) {
            //判断链接是否失效
            $url = url('wx/share/' . $deviceId . '/' . $signature);
            $ever = Cache::store('redis')->get($url);
            $signature = EncryptService::decrypt($signature);
            if ($ever && ($signature['timestamp'] > time())) {
                //小程序的接受分享和公众号的接受分享
                if (isset($this->request->type) && $this->request->type == 'microPro') {
                    $openID = $this->wxService->getMicroProOpenID($this->request->code);
                    if ($openID && $openID->openid) {
                        $user = User::where('wx_pro_openid', $openID->openid)->first();
                        if ($this->deviceService->share($deviceId, $user->id)) {
                            Cache::store('redis')->forget($url);
                            return view('wx.share_success');
                        }
                    }
                    return view('wx.share_fail');
                } else {
                    $openID = $this->wxService->getOpenID($this->request->code);
                    if ($openID) {
                        $wxUser = $this->wxService->getUserInfo($openID, $this->access_token);
                        if ($wxUser->subscribe == 0)
                            return view('wx.share_device');
                        else {
                            $user = User::where('wx_openid', $wxUser->openid)->first();
                            if ($user->phone) {
                                $withData = ['user' => $user];
                                if ($this->deviceService->share($deviceId, $user->id)) {
                                    Cache::store('redis')->forget($url);
                                } else {
                                    $withData['errors'] = ['找不到该设备'];
                                }
                                return view('wx.devices')->with($withData);
                            } else
                                return view('wx.phone')->with(['user' => $user]);
                            return view('wx.devices');
                        }
                    }
                }
            } else {
                return view('wx.Invalid_link');
            }
        } else {
            return redirect()->to($this->wxService->getCode(url('/wx/share/' . $deviceId . '/' . $signature)));
        }
    }

}