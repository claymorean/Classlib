<?php
/*
 * ����������������1��GBK�����ύ������urlencode�������ݣ�content����Ȼ����API����ʱ������encode=gbk

    2��UTF-8����Ľ�content ��urlencode����󣬴���encode=utf8��utf-8
    ʵ����http://m.5c.com.cn/api/send/index.php?username=XXX&password_md5=XXX&apikey=XXX&mobile=XXX&content=%E4%BD%A0%E5%A5%BD%E6%89%8D%E6%94%B6%E7%9B%8A%E9%9F%A6&encode=utf8
 * 
 * ��������ת�����⡣      UTF-8 ת GBK��$content = iconv("UTF-8","GBK//IGNORE",$content);GBK ת UTF-8��$content = iconv("GBK","UTF-8",$content);
 * 
 * username  �û���
 * password_md5   ����
 * mobile  �ֻ���
 * apikey  apikey��Կ
 * content  ��������
 * startTime  UNIXʱ�������дΪ���̷��ͣ�http://tool.chinaz.com/Tools/unixtime.aspx ��UNIXʱ�����վ��
 *
 * success:msgid  �ύ�ɹ���
 error:msgid  �ύʧ��
 error:Missing username  �û���Ϊ��
 error:Missing password  ����Ϊ��
 error:Missing apikey  APIKEYΪ��
 error:Missing recipient  �ֻ�����Ϊ��
 error:Missing message content  ��������Ϊ��
 error:Account is blocked  �ʺű�����
 error:Unrecognized encoding  ����δ��ʶ��
 error:APIKEY or password error  APIKEY���������
 error:Unauthorized IP address  δ��Ȩ IP ��ַ
 error:Account balance is insufficient  ����
 * */

$encode='UTF-8';  //ҳ�����Ͷ������ݱ���ΪGBK����Ҫ˵�������ύ���ź��յ����룬�뽫GBK��ΪUTF-8���ԡ��籾����ҳ��Ϊ�����ʽΪ��ASCII/GB2312/GBK��ô�ΪGBK���籾ҳ�����ΪUTF-8����Ҫ֧�ַ��壬�������ĵ�Unicode���뽫�˴�дΪ��UTF-8

$username='filter';  //�û���

$password_md5='1ADBB3178591FD5BB0C248518F39BF6D';  //32λMD5������ܣ������ִ�Сд

$apikey='36e74088db48842ce54ee65643b8667a';  //apikey��Կ�����¼ http://m.5c.com.cn ����ƽ̨-->�˺Ź���-->�ҵ���Ϣ �и���apikey��

$mobile='18610310068';  //�ֻ���,ֻ��һ�����룺13800000001����������룺13800000001,13800000002,...N ��ʹ�ð�Ƕ��ŷָ���

$content='���ã�������֤���ǣ�12345��������';  //Ҫ���͵Ķ������ݣ��ر�ע�⣺ǩ���������ã���ҳ��֤��Ӧ����Ҫ����ӡ�ͼ��ʶ���롿��

$content = iconv("GBK","UTF-8",$content);

$contentUrlEncode = urlencode($content);//ִ��URLencode����  ��$content = urldecode($content);����

$result = sendSMS($username,$password_md5,$apikey,$mobile,$contentUrlEncode,$encode);  //���з���

if(strpos($result,"success")>-1) {
	//�ύ�ɹ�
	//�߼�����
} else {
	//�ύʧ��
	//�߼�����
}
echo $result;  //���result���ݣ��鿴����ֵ���ɹ�Ϊsuccess������Ϊerror����������������������ʾ��

//���ͽӿ�
function sendSMS($username,$password_md5,$apikey,$mobile,$contentUrlEncode,$encode)
{
    //�������ӣ��û��������룬apikey���ֻ��ţ����ݣ�
    $url = "http://m.5c.com.cn/api/send/index.php?";  //�����ӳ�ʱ������������������֧�������������뽫���������еģ���m.5c.com.cn���޸�ΪIP����115.28.23.78��
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
    //print_r($data); //����
    return $result;
}
function curlSMS($url,$post_fields=array())
{
    $ch=curl_init();
    curl_setopt($ch,CURLOPT_URL,$url);//��PHPȡ�ص�URL��ַ��ֵ������Ϊ�ַ�����
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);//ʹ��curl_setopt��ȡҳ�����ݻ��ύ���ݣ���ʱ��ϣ�����ص�������Ϊ�����洢��������ֱ���������ʱ��ϣ�����ص�������Ϊ����
    curl_setopt($ch,CURLOPT_TIMEOUT,30);//30�볬ʱ����
    curl_setopt($ch,CURLOPT_HEADER,1);//���ļ�ͷ���ֱ�ӿɼ���
    curl_setopt($ch,CURLOPT_POST,1);//�������ѡ��Ϊһ�����ֵ�����post����ͨ��application/x-www-from-urlencoded���ͣ�������HTTP����á�
    curl_setopt($ch,CURLOPT_POSTFIELDS,$post_fields);//post�������������ݵ��ַ�����
    $data = curl_exec($ch);//ץȡURL���������ݸ������
    curl_close($ch);//�ͷ���Դ
    $res = explode("\r\n\r\n",$data);//explode������ɢ��Ϊ����
    return $res[2]; //Ȼ�������ﷵ�����顣
}

?>