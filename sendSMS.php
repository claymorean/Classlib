<?php
/*
 * 乱码问题解决方案，1、GBK编码提交的首先urlencode短信内容（content），然后在API请求时，带入encode=gbk

    2、UTF-8编码的将content 做urlencode编码后，带入encode=utf8或utf-8
    实例：http://m.5c.com.cn/api/send/index.php?username=XXX&password_md5=XXX&apikey=XXX&mobile=XXX&content=%E4%BD%A0%E5%A5%BD%E6%89%8D%E6%94%B6%E7%9B%8A%E9%9F%A6&encode=utf8
 * 
 * 关于内容转码问题。      UTF-8 转 GBK：$content = iconv("UTF-8","GBK//IGNORE",$content);GBK 转 UTF-8：$content = iconv("GBK","UTF-8",$content);
 * 
 * username  用户名
 * password_md5   密码
 * mobile  手机号
 * apikey  apikey秘钥
 * content  短信内容
 * startTime  UNIX时间戳，不写为立刻发送，http://tool.chinaz.com/Tools/unixtime.aspx （UNIX时间戳网站）
 *
 * success:msgid  提交成功。
 error:msgid  提交失败
 error:Missing username  用户名为空
 error:Missing password  密码为空
 error:Missing apikey  APIKEY为空
 error:Missing recipient  手机号码为空
 error:Missing message content  短信内容为空
 error:Account is blocked  帐号被禁用
 error:Unrecognized encoding  编码未能识别
 error:APIKEY or password error  APIKEY或密码错误
 error:Unauthorized IP address  未授权 IP 地址
 error:Account balance is insufficient  余额不足
 * */

$encode='UTF-8';  //页面编码和短信内容编码为GBK。重要说明：如提交短信后收到乱码，请将GBK改为UTF-8测试。如本程序页面为编码格式为：ASCII/GB2312/GBK则该处为GBK。如本页面编码为UTF-8或需要支持繁体，阿拉伯文等Unicode，请将此处写为：UTF-8

$username='filter';  //用户名

$password_md5='1ADBB3178591FD5BB0C248518F39BF6D';  //32位MD5密码加密，不区分大小写

$apikey='36e74088db48842ce54ee65643b8667a';  //apikey秘钥（请登录 http://m.5c.com.cn 短信平台-->账号管理-->我的信息 中复制apikey）

$mobile='18610310068';  //手机号,只发一个号码：13800000001。发多个号码：13800000001,13800000002,...N 。使用半角逗号分隔。

$content='您好，您的验证码是：12345【美联】';  //要发送的短信内容，特别注意：签名必须设置，网页验证码应用需要加添加【图形识别码】。

$content = iconv("GBK","UTF-8",$content);

$contentUrlEncode = urlencode($content);//执行URLencode编码  ，$content = urldecode($content);解码

$result = sendSMS($username,$password_md5,$apikey,$mobile,$contentUrlEncode,$encode);  //进行发送

if(strpos($result,"success")>-1) {
	//提交成功
	//逻辑代码
} else {
	//提交失败
	//逻辑代码
}
echo $result;  //输出result内容，查看返回值，成功为success，错误为error，（错误内容在上面有显示）

//发送接口
function sendSMS($username,$password_md5,$apikey,$mobile,$contentUrlEncode,$encode)
{
    //发送链接（用户名，密码，apikey，手机号，内容）
    $url = "http://m.5c.com.cn/api/send/index.php?";  //如连接超时，可能是您服务器不支持域名解析，请将下面连接中的：【m.5c.com.cn】修改为IP：【115.28.23.78】
    $data=array
    (
        'username'=>$username,
        'password_md5'=>$password_md5,
        'apikey'=>$apikey,
        'mobile'=>$mobile,
        'content'=>$contentUrlEncode,
        'encode'=>$encode,
    );
    $result = curlSMS($url,$data);
    //print_r($data); //测试
    return $result;
}
function curlSMS($url,$post_fields=array())
{
    $ch=curl_init();
    curl_setopt($ch,CURLOPT_URL,$url);//用PHP取回的URL地址（值将被作为字符串）
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);//使用curl_setopt获取页面内容或提交数据，有时候希望返回的内容作为变量存储，而不是直接输出，这时候希望返回的内容作为变量
    curl_setopt($ch,CURLOPT_TIMEOUT,30);//30秒超时限制
    curl_setopt($ch,CURLOPT_HEADER,1);//将文件头输出直接可见。
    curl_setopt($ch,CURLOPT_POST,1);//设置这个选项为一个零非值，这个post是普通的application/x-www-from-urlencoded类型，多数被HTTP表调用。
    curl_setopt($ch,CURLOPT_POSTFIELDS,$post_fields);//post操作的所有数据的字符串。
    $data = curl_exec($ch);//抓取URL并把他传递给浏览器
    curl_close($ch);//释放资源
    $res = explode("\r\n\r\n",$data);//explode把他打散成为数组
    return $res[2]; //然后在这里返回数组。
}

?>