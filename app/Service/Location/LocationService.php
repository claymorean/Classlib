<?php
/**
 * Created by PhpStorm.
 * User: senuer
 * Date: 2019/3/28
 * Time: 16:36
 */

namespace App\Service\Location;

use App\Service\Socket\Curl;

/**
 * Class LocationService
 * 三坐标系互转 并提供反地理位置编码接口
 * @package App\Service\Location
 */
class LocationService {
    public function __construct() {
        $this->PI = 3.14159265358979324;
    }

    public function getIP() {
        if (getenv('HTTP_CLIENT_IP')) {
            $ip = getenv('HTTP_CLIENT_IP');
        } elseif (getenv('HTTP_X_FORWARDED_FOR')) {
            $ip = getenv('HTTP_X_FORWARDED_FOR');
        } elseif (getenv('HTTP_X_FORWARDED')) {
            $ip = getenv('HTTP_X_FORWARDED');
        } elseif (getenv('HTTP_FORWARDED_FOR')) {
            $ip = getenv('HTTP_FORWARDED_FOR');
        } elseif (getenv('HTTP_FORWARDED')) {
            $ip = getenv('HTTP_FORWARDED');
        } else {
            $ip = isset($_SERVER[ 'REMOTE_ADDR' ]) ? $_SERVER[ 'REMOTE_ADDR' ] : '';
        }
        return $ip;
    }

    //十进制ip转通用ip
    public function int2ip($n) {
        $iphex = dechex($n);//将10进制数字转换成16进制
        $len = strlen($iphex);//得到16进制字符串的长度
        if (strlen($iphex) < 8) {
            $iphex = '0'.$iphex;//如果长度小于8，在最前面加0
            $len = strlen($iphex); //重新得到16进制字符串的长度
        }
        //这是因为ipton函数得到的16进制字符串，如果第一位为0，在转换成数字后，是不会显示的
        //所以，如果长度小于8，肯定要把第一位的0加上去
        //为什么一定是第一位的0呢，因为在ipton函数中，后面各段加的'0'都在中间，转换成数字后，不会消失
        for ($i = 0, $j = 0; $j < $len; $i = $i + 1, $j = $j + 2) {//循环截取16进制字符串，每次截取2个长度
            $ippart = substr($iphex, $j, 2);//得到每段IP所对应的16进制数
            $fipart = substr($ippart, 0, 1);//截取16进制数的第一位
            if ($fipart == '0') {//如果第一位为0，说明原数只有1位
                $ippart = substr($ippart, 1, 1);//将0截取掉
            }
            $ip[] = hexdec($ippart);//将每段16进制数转换成对应的10进制数，即IP各段的值
        }
        $ip = array_reverse($ip);

        return implode('.', $ip);//连接各段，返回原IP值
    }

    //通过sina接口从ip获取所在省市区
    public function getIpCity($ip = '') {
        if (empty($ip)) {
            $ip = $this->ip;
        }
        $res = @file_get_contents('http://int.dpool.sina.com.cn/iplookup/iplookup.php?format=js&ip='.$ip);
        if (empty($res)) {
            return false;
        }
        $jsonMatches = array();
        preg_match('#\{.+?\}#', $res, $jsonMatches);
        if (!isset($jsonMatches[ 0 ])) {
            return false;
        }
        $json = json_decode($jsonMatches[ 0 ], true);
        if (isset($json[ 'ret' ]) && $json[ 'ret' ] == 1) {
            $json[ 'ip' ] = $ip;
            unset($json[ 'ret' ]);
            return $json;
        }
        return false;
    }

    //从经纬度获取省市区
    public function getLatCity($lat, $lon) {
//        原api接口
//        $host = "http://maps.google.cn/maps/api/geocode/json";
//        $querys = "latlng=".($lat / 1000000).','.($lon / 1000000).'&language=CN';
        $host = "http://api.map.baidu.com/geocoder";
//        $method = "GET";
        $query = "location=".($lat / 1000000).','.($lon / 1000000).'&output=json';
//        array:2 [
//              "status" => "OK"
//              "result" => array:5 [▼
//                  "location" => array:2 [▶]
//                  "formatted_address" => "江苏省南京市玄武区北京东路41号7号楼"
//                  "business" => "兰园,珠江路,玄武湖"
//                  "addressComponent" => array:7 [▼
//                      "city" => "南京市"
//                      "direction" => "附近"
//                      "distance" => "38"
//                      "district" => "玄武区"
//                      "province" => "江苏省"
//                      "street" => "北京东路"
//                      "street_number" => "41号7号楼"
//                  ]
//                  "cityCode" => 315
//             ]
//        ]
        $headers = [];
        $option = [
            'host' => $host,
            'path' => '',
            'query' => $query,
            'header' => $headers
        ];
        $result = Curl::getMethod($option);
//        $url = $host."?".$query;
//        $curl = curl_init();
//        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
//        curl_setopt($curl, CURLOPT_URL, $url);
//        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
//        // 返回 response_header, 该选项非常重要,如果不为 true, 只会获得响应的正文
//        curl_setopt($curl, CURLOPT_HEADER, false);
//        curl_setopt($curl, CURLOPT_FAILONERROR, false);
//        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
//        if (1 == strpos("$".$host, "https://")) {
//            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
//            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
//        }
//        $result = json_decode(trim(curl_exec($curl)), true);
        return $result;
    }

//WGS-84坐标系：
//是国际标准坐标系，GPS坐标（Google Earth使用、或者GPS模块）。
//GCJ-02坐标系：
//火星坐标系 腾讯地图和高德地图
//BD-09坐标系：
//百度坐标系
    public function Gcj2Wgs($gcjLat, $gcjLon) {
        if ($this->outOfChina($gcjLat, $gcjLon))
            return array('lat' => $gcjLat, 'lon' => $gcjLon);

        $d = $this->delta($gcjLat, $gcjLon);
        return array('lat' => $gcjLat - $d[ 'lat' ], 'lon' => $gcjLon - $d[ 'lon' ]);
    }

    //WGS-84 to GCJ-02
    public function wgs2gcj($wgsLat, $wgsLon) {
        if ($this->outOfChina($wgsLat, $wgsLon))
            return array('lat' => $wgsLat, 'lon' => $wgsLon);

        $d = $this->delta($wgsLat, $wgsLon);
        return array('lat' => $wgsLat + $d[ 'lat' ], 'lon' => $wgsLon + $d[ 'lon' ]);
    }

    //GCJ-02 to BD-09

    /**
     * @param $gcjLat
     * @param $gcjLon
     *
     * @return mixed ['lat'=>'','lon'=>'']
     */
    public function gcj2bd($gcjLat, $gcjLon) {
        $x_pi = 3.14159265358979324 * 3000.0 / 180.0;
        $x = $gcjLon;
        $y = $gcjLat;
        $z = sqrt($x * $x + $y * $y) - 0.00002 * sin($y * $x_pi);
        $theta = atan2($y, $x) - 0.000003 * cos($x * $x_pi);
        $bd_lon = $z * cos($theta) + 0.0065;
        $bd_lat = $z * sin($theta) + 0.006;
        // 保留小数点后六位
        $data[ 'lon' ] = round($bd_lon, 6);
        $data[ 'lat' ] = round($bd_lat, 6);
        return $data;
    }

    public function wgs2bd($wgsLat, $wgsLon) {
        if ($this->outOfChina($wgsLat, $wgsLon))
            return array('lat' => $wgsLat, 'lon' => $wgsLon);
        $d = $this->delta($wgsLat, $wgsLon);
        $gcjLat = $wgsLat + $d[ 'lat' ];
        $gcjLon = $wgsLon + $d[ 'lon' ];
        return $this->gcj2bd($gcjLat, $gcjLon);
    }

    private function outOfChina($lat, $lon) {
        if ($lon < 72.004 || $lon > 137.8347)
            return TRUE;
        if ($lat < 0.8293 || $lat > 55.8271)
            return TRUE;
        return FALSE;
    }

    private function delta($lat, $lon) {
        // Krasovsky 1940
        //
        // a = 6378245.0, 1/f = 298.3
        // b = a * (1 - f)
        // ee = (a^2 - b^2) / a^2;
        $a = 6378245.0;//  a: 卫星椭球坐标投影到平面地图坐标系的投影因子。
        $ee = 0.00669342162296594323;//  ee: 椭球的偏心率。
        $dLat = $this->transformLat($lon - 105.0, $lat - 35.0);
        $dLon = $this->transformLon($lon - 105.0, $lat - 35.0);
        $radLat = $lat / 180.0 * $this->PI;
        $magic = sin($radLat);
        $magic = 1 - $ee * $magic * $magic;
        $sqrtMagic = sqrt($magic);
        $dLat = ($dLat * 180.0) / (($a * (1 - $ee)) / ($magic * $sqrtMagic) * $this->PI);
        $dLon = ($dLon * 180.0) / ($a / $sqrtMagic * cos($radLat) * $this->PI);
        return array('lat' => $dLat, 'lon' => $dLon);
    }

    private function transformLat($x, $y) {
        $ret = -100.0 + 2.0 * $x + 3.0 * $y + 0.2 * $y * $y + 0.1 * $x * $y + 0.2 * sqrt(abs($x));
        $ret += (20.0 * sin(6.0 * $x * $this->PI) + 20.0 * sin(2.0 * $x * $this->PI)) * 2.0 / 3.0;
        $ret += (20.0 * sin($y * $this->PI) + 40.0 * sin($y / 3.0 * $this->PI)) * 2.0 / 3.0;
        $ret += (160.0 * sin($y / 12.0 * $this->PI) + 320 * sin($y * $this->PI / 30.0)) * 2.0 / 3.0;
        return $ret;
    }

    private function transformLon($x, $y) {
        $ret = 300.0 + $x + 2.0 * $y + 0.1 * $x * $x + 0.1 * $x * $y + 0.1 * sqrt(abs($x));
        $ret += (20.0 * sin(6.0 * $x * $this->PI) + 20.0 * sin(2.0 * $x * $this->PI)) * 2.0 / 3.0;
        $ret += (20.0 * sin($x * $this->PI) + 40.0 * sin($x / 3.0 * $this->PI)) * 2.0 / 3.0;
        $ret += (150.0 * sin($x / 12.0 * $this->PI) + 300.0 * sin($x / 30.0 * $this->PI)) * 2.0 / 3.0;
        return $ret;
    }
}