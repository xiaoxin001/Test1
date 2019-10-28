<?php

namespace Ejiayou\PHP\Utils;

/**
 * 易加油自用工具类
 * Class EjyUtils
 * @package Ejiayou\PHP\Utils
 */
class EjyUtils {

    private static $key = '1234567890A';

    /**
     * 可加密一个或多个半角逗号分隔的字符串
     * @param $content
     * @return string
     */
    public static function encryptContent($content)
    {
        $content = explode(",",$content);
        foreach($content as &$c){
            $c = trim($c);
            if($c != null && $c != ""){
                $c = strtoupper(self::encrypt($c));
            }
        }

        return implode(",",$content);
    }

    /**
     * 可解密一个或多个半角逗号分隔的字符串
     * @param $content
     * @return string
     */
    public static function decryptContent($content)
    {
        $content = explode(",",$content);
        foreach($content as &$c){
            $c = trim($c);
            if($c != null && $c != ""){
                $c = self::decrypt($c);
            }
        }

        return implode(",",$content);
    }


    /**
     * 可加密单个字符串
     * @param $str
     * @param string $custom_key
     * @return string
     */
    public static function encrypt($str, $custom_key='') {
        $crypt_key = $custom_key != '' ? $custom_key : config('ephputils.crypt_key',self::$key);
        try{
            $key = substr(openssl_digest(openssl_digest($crypt_key, 'sha1', true), 'sha1', true), 0, 16);
            $iv = openssl_random_pseudo_bytes(0);
            $encrypted = openssl_encrypt($str, 'AES-128-ECB',$key,OPENSSL_RAW_DATA,$iv);
            return bin2hex($encrypted);
        }catch(\Exception $e){
            return $str;
        }
    }


    /**
     * 可解密单个字符串
     * @param $str
     * @param string $custom_key
     * @return string
     */
    public static function decrypt($str, $custom_key='') {
        $crypt_key = $custom_key != '' ? $custom_key : config('ephputils.crypt_key',self::$key);
        try{
            $key = substr(openssl_digest(openssl_digest($crypt_key, 'sha1', true), 'sha1', true), 0, 16);
            $decoded = hex2bin($str);
            $iv = openssl_random_pseudo_bytes(0);
            return openssl_decrypt($decoded, 'AES-128-ECB',$key,OPENSSL_RAW_DATA,$iv);
        }catch(\Exception $e){
            return $str;
        }
    }

    /**
     * 验证码生成（随机数字串生成）
     * @param int $length
     * @return string
     */
    public static function makeSmsCode($length = 6){
        $codeSet = '1234567890';
        $codes = array();
        for ($i = 0; $i<$length; $i++) {
            $codes[$i] = $codeSet[mt_rand(0, strlen($codeSet)-1)];
        }

        return implode($codes);
    }

    /**
     * 获取访问IP
     * @return mixed
     */
    public static function getRealIP()
    {
        if (isset($_SERVER)) {
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                foreach ($arr as $ip) {
                    $ip = trim($ip);
                    if ($ip != 'unknown') {
                        $realip = $ip;
                        break;
                    }
                }
            } else if (isset($_SERVER['HTTP_CLIENT_IP'])) {
                $realip = $_SERVER['HTTP_CLIENT_IP'];
            } else if (isset($_SERVER['REMOTE_ADDR'])) {
                $realip = $_SERVER['REMOTE_ADDR'];
            } else {
                $realip = '0.0.0.0';
            }
        } else if (getenv('HTTP_X_FORWARDED_FOR')) {
            $realip = getenv('HTTP_X_FORWARDED_FOR');
        } else if (getenv('HTTP_CLIENT_IP')) {
            $realip = getenv('HTTP_CLIENT_IP');
        } else {
            $realip = getenv('REMOTE_ADDR');
        }

        preg_match('/[\\d\\.]{7,15}/', $realip, $onlineip);
        $realip = (!empty($onlineip[0]) ? $onlineip[0] : '0.0.0.0');
        return $realip;
    }

    /**
     * 判断是否是https
     * @return bool
     */
    public static function isHTTPS(){
        $is_https_1 = false;
        if (array_key_exists('HTTPS', $_SERVER)){
            $is_https_1 = $_SERVER['HTTPS'] === 'on' || $_SERVER['HTTPS'] === 1 || $_SERVER['HTTPS'] === 443;
        }
        $is_https_2 = false;
        if (array_key_exists('HTTP_X_FORWARDED_PROTO', $_SERVER)){
            $is_https_2 = $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https';
        }
        return $is_https_1 || $is_https_2;
    }

    /**
     * 对象转数组
     * @param $object
     * @return array
     */
    public static function objectToArray($object){
        $result = array();
        $object = is_object($object) ? get_object_vars($object) : $object;
        foreach ($object as $key => $val) {
            $val = (is_object($val) || is_array($val)) ? self::objectToArray($val) : $val;
            $result[$key] = $val;
        }

        return $result;
    }


    /**
     * 生成 n 位渠道号(生成随机字符串)
     * @param int $length
     * @return string
     */
    public static function createChannelNo($length=8,$key_pre='') {
        $data_src = '1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $len = strlen($data_src);

        $key = $key_pre;
        for($i=0; $i<($length-strlen($key_pre)); $i++) {
            $key .= $data_src[mt_rand(0, $len-1)];    //生成php随机数
        }

        return $key;
    }

    /**
     * 签名重要接口
     * @param $params
     * @return string
     */
    public static function signatureMethod($params)
    {
        if(!array_key_exists('timestamp', $params) || !array_key_exists('noncestr', $params)) {
            return '';
        }

        $params['author'] = config('ephputils.signature_author',self::$key); //加盐. 固定字符

        // 按字典排序
        ksort($params);
        $query_str = '';
        foreach($params as $key => $value) {
            // 注意, 键和值都需要自定义编码
            $query_str .= $key.'='.$value.'&';
        }

        $signature = substr($query_str, 0, strlen($query_str)-1);
        // md5 加密
        $signature = md5($signature);
        // 转大写, 加入签名键值对
        return strtoupper($signature);
    }

    /**
     * 去除字符串中所有空格（包括半角和全角）
     * @param $string
     * @return mixed
     */
    public static function blankSpaceRemove($string){
        $search = array(" ","　","\n","\r","\t");
        $replace = array("","","","","");

        return str_replace($search, $replace, $string);
    }


    /**
     * 验证是否是手机号码
     *
     * @param string $phone 待验证的号码
     * @return boolean 如果验证失败返回false,验证成功返回true
     */
    public static function isTelNumber($phone) {
        $rule = '/^1\d{10}$/';
        return strlen($phone) == 11 && preg_match ($rule, $phone);
    }

    /**
     * 验证是否是车牌号
     *
     * @param string $car_number 待验证的车牌号
     * @return boolean 如果验证失败返回false,验证成功返回true
     */
    public static function isCarNumber($car_number) {
        //全部车牌 /^[京津沪渝冀豫云辽黑湘皖鲁新苏浙赣鄂桂甘晋蒙陕吉闽贵粤青藏川宁琼使领A-Z]{1}[A-Z]{1}[A-Z0-9]{4}[A-Z0-9挂学警港澳]{1}$/u
        //普通车牌 /^[\x{4e00}-\x{9fa5}]{1}[A-Z]{1}[A-Z0-9]{5}$/u
        $rule = '/^[京津沪渝冀豫云辽黑湘皖鲁新苏浙赣鄂桂甘晋蒙陕吉闽贵粤青藏川宁琼使领A-Z]{1}[A-Z]{1}[A-Z0-9]{4}[A-Z0-9挂学警港澳]{1}$/u';
        return preg_match($rule,$car_number);
    }

    /**
     * 验证是否是https合法url
     *
     * @url $url 待验证链接
     * @return boolean 如果验证失败返回false,验证成功返回true
     */
    public static function isHttpsURL($url){
        $pattern = '/^(https):\/\//';
        return preg_match($pattern, $url);
    }

    /**
     * 将xml字符串转换为对象，如果字符串不是xml格式则返回false
     * @param $str
     * @return bool|\SimpleXMLElement
     */
    public static function xmlParser($str){
        $xml_parser = xml_parser_create();
        if(!xml_parse($xml_parser,$str,true)){
            xml_parser_free($xml_parser);
            return false;
        }
        return simplexml_load_string($str, 'SimpleXMLElement', LIBXML_NOCDATA);
    }

    /**
     * 获取浏览器平台  IOS=1 安卓=2 微信=3 AliApp=5 其他浏览器=4
     * @return int
     */
    public static function clientOsType(){
        if(isset($_SERVER['HTTP_USER_AGENT'])){
            $user_agent = $_SERVER['HTTP_USER_AGENT'];
            $user_agent = strtolower($user_agent);
            if (strpos($user_agent, 'micromessenger')){
                return 3;
            }
            if (strpos($user_agent, 'alipayclient')){
                return 5;
            }
            if(isset($_SERVER["HTTP_OSTYPE"])){
                $os_type = $_SERVER["HTTP_OSTYPE"];
                if( $os_type == 'iOSejy'){
                    return 1;
                }
                if( $os_type == 'android'){
                    return 2;
                }
            }
            return 4;
        }

        return 0;
    }
}
