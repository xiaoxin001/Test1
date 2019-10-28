<?php

namespace Ejiayou\PHP\Utils;

use Ejiayou\PHP\Utils\HTTP\EjiayouUtils;
use Ejiayou\PHP\Utils\HTTP\HttpUtils;

/**
 * 短信发送工具类
 * Class SmsUtils
 * @package Ejiayou\PHP\Utils
 */
class SmsUtils {

    /**
     * 发送验证码短信
     * @param $mobile
     * @param $code
     * @param string $custom_sms_code_send_api
     * @return mixed|\stdClass
     */
    public static function sendSMS($mobile, $code, $client_ip = '', $custom_sms_code_send_api='') {
        $sms_code_send_api = $custom_sms_code_send_api != '' ? $custom_sms_code_send_api : config('ephputils.sms_code_send_api','');
        if($sms_code_send_api == ''){
            $result = new \stdClass();
            $result->ret = 1;
            $result->msg = '缺少API配置';
            return $result;
        }

        $params = array(
            'mobile' => $mobile,
            'code' => $code,
            'timestamp' => time(),
            'noncestr' => uniqid()
        );

        if(!$client_ip){
            $client_ip = EjyUtils::getRealIP();
        }

        $signature = EjyUtils::signatureMethod($params);

        $params['signature'] = $signature;
        $params['client_ip'] = $client_ip;

        $result = HttpUtils::curlGet($sms_code_send_api."?".http_build_query($params));

        return json_decode($result);
    }


    /**
     * 发送营销短信
     * @param $mobile
     * @param $msg
     * @param int $activity_sms_task_id
     * @param $custom_sms_sale_send_api
     * @return mixed|\stdClass
     */
    public static function sendNews($mobile, $msg, $client_ip = '', $activity_sms_task_id = 0, $custom_sms_sale_send_api='') {
        $sms_sale_send_api = $custom_sms_sale_send_api != '' ? $custom_sms_sale_send_api : config('ephputils.sms_sale_send_api','');
        if($sms_sale_send_api == ''){
            $result = new \stdClass();
            $result->ret = 1;
            $result->msg = '缺少API配置';
            return $result;
        }
        $params = array(
            'numbers' => $mobile,
            'msg' => $msg,
            'timestamp' => time(),
            'noncestr' => uniqid()
        );

        if(!$client_ip){
            $client_ip = EjyUtils::getRealIP();
        }

        $signature = EjyUtils::signatureMethod($params);

        $params['activity_sms_task_id'] = $activity_sms_task_id;
        $params['signature'] = $signature;
        $params['client_ip'] = $client_ip;

        $result = HttpUtils::curlPost($sms_sale_send_api,http_build_query($params));

        return json_decode($result);
    }
}
