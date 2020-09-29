<?php

//加载环境配置
if (!function_exists('env')) {
    function env($key, $default = null) {
        $value = getenv($key);

        if ($value === false) {
            return value($default);
        }

        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'empty':
            case '(empty)':
                return '';
            case 'null':
            case '(null)':
                return;
        }

        return $value;
    }
}

if (!function_exists('dd')) {
    function dd($var) {
        echo '<pre>' . var_dump($var);
        exit;
    }
}

if (!function_exists('secToTime')) {
    //秒数 转 时:分:秒
    function secToTime($times) {
        $result = '00:00:00';
        if ($times > 0) {
            $hour = floor($times / 3600);
            $minute = floor(($times - 3600 * $hour) / 60);
            $second = floor((($times - 3600 * $hour) - 60 * $minute) % 60);
            $result = $hour . ':' . $minute . ':' . $second;
        }
        return $result;
    }
}

if (!function_exists('getformatMT')) {
    function getMicroTime() {
        if (PHP_VERSION > 5)
            return ceil(microtime(true) * 1000);
        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }
}


