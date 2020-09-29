<?php
/**
 * Created by PhpStorm.
 * User: senuer
 * Date: 2017/11/1
 * Time: 14:42
 */

namespace App\Service\WX;

class WxService {
    use WxPub, WxMiniPro;

    public function __construct() {
        $this->appID = env('WX_ID');
        $this->appSecret = env('WX_SECRET');
        $this->token = env('WX_TOKEN');
        $this->access_token = $this->getAccessToken();
    }

    /**
     * @return string
     * 自动回复图文消息
     */
    public function autoReplyPT($mediaId, $toUser, $fromUser) {
        $media = $this->getSingleMedia($mediaId);
        if (!$media)
            return '';
        $newsTplHead = "<xml>
                <ToUserName><![CDATA[%s]]></ToUserName>
                <FromUserName><![CDATA[%s]]></FromUserName>
                <CreateTime>%s</CreateTime>
                <MsgType><![CDATA[news]]></MsgType>
                <ArticleCount>1</ArticleCount>
                <Articles>";
        $newsTplBody = "<item>
                <Title><![CDATA[%s]]></Title> 
                <Description><![CDATA[%s]]></Description>
                <PicUrl><![CDATA[%s]]></PicUrl>
                <Url><![CDATA[%s]]></Url>
                </item>";
        $newsTplFoot = "</Articles>
                </xml>";
        $header = sprintf($newsTplHead, $toUser, $fromUser, time());
        foreach ($media as $item) {
            $title = $item['title'];
            $desc = $item['content'];
            $picUrl = $item['show_cover_pic'];
            $url = $item['url'];
            $body = sprintf($newsTplBody, $title, $desc, $picUrl, $url);
        }
        $footer = sprintf($newsTplFoot);
        echo($header . $body . $footer);
        die();
    }
}