<?php
/**
 * Created by PhpStorm.
 * User: senuer
 * Date: 2017/8/14
 * Time: 16:37
 */

namespace App\Service\Socket;

/**
 * Class Curl
 * 封装curl方法
 * @package App\Service\Socket
 */
class Curl {
    static public function encodeFormat($array) {
//        return json_encode($array, JSON_UNESCAPED_UNICODE);
        foreach ($array as $key => $value) {
            if (!is_array($value)) {
//                $array[$key]=preg_replace_callback('/\\\\u([0-9a-f]{4})/i',
//                    create_function(
//                        '$matches',
//                        'return mb_convert_encoding(pack("H*", $matches[1]), "UTF-8", "UCS-2BE");'
//                    ),
//                    $value);
                $array[ $key ] = urlencode($value);
            } else {
                $array[ $key ] = self::encodeFormat($value);
            }
        }
        return $array;
    }

    /**
     * @param $options
     *      格式 [
     *      'host'=>'',
     *      'path'=>'',
     *      'query'=>[],
     *      'header'=>[]
     *      ]
     *
     * @return Exception|\Exception|mixed
     */
    static public function getMethod($options) {
        $host = $options[ 'host' ];
        $path = isset($options[ 'path' ]) ? $options[ 'path' ] : '';
        $method = "GET";
        $query = isset($options[ 'query' ]) ? $options[ 'query' ] : [];
        $header = isset($options[ 'header' ]) ? $options[ 'header' ] : [];
//        [   //            //headers内容
//            //            //请求体类型，请根据实际请求体内容设置。
//            //            "Content-Type: application/x-www-form-urlencoded; charset=UTF-8",
//            //            //请求响应体类型，部分 API 可以根据指定的响应类型来返回对应数据格式，建议手动指定此请求头，如果不设置，部分 HTTP 客户端会设置默认值 */*，导致签名错误。
//            //            "Accept: application/json",
//            //            //是否开启 Debug 模式，大小写不敏感，不设置默认关闭，一般 API 调试阶段可以打开此设置。
//            //            "X-Ca-Request-Mode: debug",
//            //            // API版本号，目前所有 API 仅支持版本号『1』，可以不设置此请求头，默认版本号为『1』。
//            //            "X-Ca-Version: 1",
//            //            //参与签名的自定义请求头，服务端将根据此配置读取请求头进行签名，此处设置不包含 Content-Type、Accept、Content-MD5、Date 请求头，这些请求头已经包含在了基础的签名结构中，详情参照请求签名说明文档。
//            //            "X-Ca-Signature-Headers: X-Ca-Request-Mode,X-Ca-Version,X-Ca-Stage,X-Ca-Key,X-Ca-Timestamp",
//            //            //请求 API的Stage，目前支持 TEST、PRE、RELEASE 三个 Stage，大小写不敏感，API 提供者可以选择发布到哪个 Stage，只有发布到指定 Stage 后 API 才可以调用，否则会提示 API 找不到或 Invalid Url。
//            //            "X-Ca-Stage: RELEASE",
//            //            //请求的 AppKey，请到 API 网关控制台生成，只有获得 API 授权后才可以调用，通过云市场等渠道购买的 API 默认已经给APP授过权，阿里云所有云产品共用一套 AppKey 体系，删除 ApppKey 请谨慎，避免影响到其他已经开通服务的云产品。
//            //            "X-Ca-Key: 60022326",
//            //            //请求的时间戳，值为当前时间的毫秒数，也就是从1970年1月1日起至今的时间转换为毫秒，时间戳有效时间为15分钟。
//            //            "X-Ca-Timestamp: ".time(),
//            //            //请求唯一标识，15分钟内 AppKey+API+Nonce 不能重复，与时间戳结合使用才能起到防重放作用。
//            //            "X-Ca-Nonce:b931bc77-645a-4299-b24b-f3669be577ac",
//            //            //请求签名。
//            //            "X-Ca-Signature: FJleSrCYPGCU7dMlLTG+UD3Bc5Elh3TV3CWHtSKh1Ys=",
//            //            //自定义请求头，此处仅作为示例，实际请求中根据 API定义可以设置多个自定义请求头。
//            //            "CustomHeader: CustomHeaderValue",
//        ];
        $url = $host.$path;
        if (!empty($query)) {
            $url .= "?";
            if (is_array($query)) {
                $flag = 1;
                foreach ($query as $key => $value) {
                    $url .= $flag == 1 ? $key.'='.$value : '&'.$key.'='.$value;
                    $flag += 1;
                }
            } else {
                $url .= $query;
            }
        }

//        dd($url);

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        // 返回 response_header, 该选项非常重要,如果不为 true, 只会获得响应的正文
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_FAILONERROR, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        if (0 === strpos($host, "https://")) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            if (isset($options[ 'cert' ])) {
//                curl_setopt($curl, CURLOPT_VERBOSE, 1);
//                curl_setopt($curl, CURLOPT_CERTINFO, true);
//                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
//                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
//                curl_setopt($curl, CURLOPT_CAINFO, '/home/vagrant/Code/mew/public/cacert.pem');
//                curl_setopt($curl, CURLOPT_CAPATH, '/home/vagrant/Code/mew/public');
                curl_setopt($curl, CURLOPT_SSLCERTTYPE, 'PEM');
                curl_setopt($curl, CURLOPT_SSLCERT, $options[ 'cert' ][ 'path' ]);
                curl_setopt($curl, CURLOPT_SSLCERTPASSWD, $options[ 'cert' ][ 'secret' ]);
                curl_setopt($curl, CURLOPT_SSLKEYTYPE, 'PEM');
                curl_setopt($curl, CURLOPT_SSLKEY, $options[ 'cert' ][ 'key' ]);
                curl_setopt($curl, CURLOPT_SSLKEYPASSWD, $options[ 'cert' ][ 'secret' ]);
            }
//            curl_setopt($curl, CURLOPT_CAINFO,  $options[ 'cert' ][ 'path' ]);
        }
        $result = curl_exec($curl);
        $temp = json_decode(trim($result), true);
        if ($temp)
            $result = $temp;
        try {
            curl_close($curl);
//            return urldecode(json_encode(self::encodeFormat($result))); 是字符串
            return $result;
        } catch (Exception $e) {
            return $e;
        }
    }

    /**
     * @param $options
     *        格式 [
     *        'host' => url('/'),
     *        'path' => '',
     *        'query'=>[],
     *        'header' => []
     *        'cert'=>['path'=>'','key'=>'']
     *        ];
     *
     * @return Exception|\Exception|mixed
     */
    static public function postMethod($options) {
        $host = $options[ 'host' ];
        $path = isset($options[ 'path' ]) ? $options[ 'path' ] : '';
        $method = "POST";
        $url = $host.$path;
        $post_data = isset($options[ 'query' ]) ? $options[ 'query' ] : [];
        $header = isset($options[ 'header' ]) ? $options[ 'header' ] : [];

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        // post数据
        curl_setopt($curl, CURLOPT_POST, 1);
        // post的变量
        //只能传递一维数组作为传递的参数 将多维数组进行http_build_query()进行处理
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
        // 返回 response_header, 该选项非常重要,如果不为 true, 只会获得响应的正文
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_FAILONERROR, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        if (0 === strpos($host, "https://")) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            if (isset($options[ 'cert' ])) {
//                curl_setopt($curl, CURLOPT_VERBOSE, 1);
//                curl_setopt($curl, CURLOPT_CERTINFO, true);
//                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
//                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
//                curl_setopt($curl, CURLOPT_CAINFO, '/home/vagrant/Code/mew/public/cacert.pem');
//                curl_setopt($curl, CURLOPT_CAPATH, '/home/vagrant/Code/mew/public');
                curl_setopt($curl, CURLOPT_SSLCERTTYPE, 'PEM');
                curl_setopt($curl, CURLOPT_SSLCERT, $options[ 'cert' ][ 'path' ]);
                curl_setopt($curl, CURLOPT_SSLCERTPASSWD, $options[ 'cert' ][ 'secret' ]);
                curl_setopt($curl, CURLOPT_SSLKEYTYPE, 'PEM');
                curl_setopt($curl, CURLOPT_SSLKEY, $options[ 'cert' ][ 'key' ]);
                curl_setopt($curl, CURLOPT_SSLKEYPASSWD, $options[ 'cert' ][ 'secret' ]);
            }
//            curl_setopt($curl, CURLOPT_CAINFO,  $options[ 'cert' ][ 'path' ]);
        }
//        dd($options);
//        dd(curl_getinfo($curl));
        $result = curl_exec($curl);
        if (($error = curl_error($curl))) {
            dd($error);
        }
        $result = json_decode(trim($result), true);
        try {
            curl_close($curl);
            return $result;
        } catch (Exception $e) {
            return $e;
        }
    }

    static public function deleteMethod($options) {
        $host = $options[ 'host' ];
        $path = isset($options[ 'path' ]) ? $options[ 'path' ] : '';
        $url = $host.$path;
        $header = isset($options[ 'header' ]) ? $options[ 'header' ] : [];

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');

        //设置头
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header); //设置请求头

        if (0 === strpos($host, "https://")) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            if (isset($options[ 'cert' ])) {
//                curl_setopt($curl, CURLOPT_VERBOSE, 1);
//                curl_setopt($curl, CURLOPT_CERTINFO, true);
//                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
//                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
//                curl_setopt($curl, CURLOPT_CAINFO, '/home/vagrant/Code/mew/public/cacert.pem');
//                curl_setopt($curl, CURLOPT_CAPATH, '/home/vagrant/Code/mew/public');
                curl_setopt($curl, CURLOPT_SSLCERTTYPE, 'PEM');
                curl_setopt($curl, CURLOPT_SSLCERT, $options[ 'cert' ][ 'path' ]);
                curl_setopt($curl, CURLOPT_SSLCERTPASSWD, $options[ 'cert' ][ 'secret' ]);
                curl_setopt($curl, CURLOPT_SSLKEYTYPE, 'PEM');
                curl_setopt($curl, CURLOPT_SSLKEY, $options[ 'cert' ][ 'key' ]);
                curl_setopt($curl, CURLOPT_SSLKEYPASSWD, $options[ 'cert' ][ 'secret' ]);
            }
//            curl_setopt($curl, CURLOPT_CAINFO,  $options[ 'cert' ][ 'path' ]);
        }
        $result = curl_exec($curl);
        if (($error = curl_error($curl))) {
            dd($error);
        }
        $result = json_decode(trim($result), true);
        try {
            curl_close($curl);
            return $result;
        } catch (Exception $e) {
            return $e;
        }
    }

    public function httpGet($url) {
//        $curl = curl_init();
//        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
//        curl_setopt($curl, CURLOPT_URL, $url);
//        curl_setopt($curl, CURLOPT_HTTPHEADER, []);
//        // 返回 response_header, 该选项非常重要,如果不为 true, 只会获得响应的正文
//        curl_setopt($curl, CURLOPT_HEADER, false);
//        curl_setopt($curl, CURLOPT_FAILONERROR, false);
//        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
//        if (1 == strpos("$".$url, "https://")) {
//            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
//            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
//        }
//        $result = curl_exec($curl);
//        $result= json_decode(trim($result), true);

        //1.初始化
        $curl = curl_init();
        //配置curl
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        //执行curl
//        dd($curl);
        $res = curl_exec($curl);
        //关闭curl
        curl_close($curl);
//        dd($result);
        return $res;
    }


    /**
     * @param       $url
     * @param null  $data
     * @param array $cert ['path'=>'','key'=>'']
     *
     * @return Exception|\Exception|mixed
     */
    public function httpPost($url, $data = null) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl, CURLOPT_URL, $url);
        // post数据
        curl_setopt($curl, CURLOPT_POST, 1);
        // post的变量
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        // 返回 response_header, 该选项非常重要,如果不为 true, 只会获得响应的正文
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_FAILONERROR, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        if (1 == strpos("$".$url, "https://")) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }
        $result = curl_exec($curl);
        $result = json_decode(trim($result), true);
        try {
            curl_close($curl);
            return $result;
        } catch (Exception $e) {
            return $e;
        }
    }

//    public function httpPostWithCA($url, $data, $cert, $key, $headers = 0)
//    {
//        $ip = rand(0, 255) . '.' . rand(0, 255) . '.' . rand(0, 255) . '.' . rand(0, 255);
//        $UserAgent = "Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.0; SLCC1; .NET CLR 2.0.50727; .NET CLR 3.0.04506; .NET CLR 3.5.21022; .NET CLR 1.0.3705; .NET CLR 1.1.4322)";
//        if (!$headers) {
//            $headers = ['X-FORWARDED-FOR:' . $ip . ", 'CLIENT-IP:' . $ip . "];
//        }
//        $curl = curl_init();
//        curl_setopt($curl, CURLOPT_URL, $url);
//        curl_setopt($curl, CURLOPT_TIMEOUT, 300);
//        curl_setopt($curl, CURLOPT_POST, 1);
//        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
//        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
//        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
//        curl_setopt($curl, CURLOPT_USERAGENT, $UserAgent);
//        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
//        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
//        curl_setopt($curl, CURLOPT_SSLCERTTYPE, 'PEM');
//        curl_setopt($curl, CURLOPT_SSLCERT, $cert);
//        curl_setopt($curl, CURLOPT_SSLKEYTYPE, 'PEM');
//        curl_setopt($curl, CURLOPT_SSLKEY, $key);
//        $output = curl_exec($curl);
//        curl_close($curl);
//        return $output;
//    }
}