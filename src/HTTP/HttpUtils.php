<?php
namespace Ejiayou\PHP\Utils\HTTP;
use Ejiayou\PHP\Utils\Log\LogUtils;

/**
 * 易加油HTTP请求工具类
 * Class HttpUtils
 * @package Ejiayou\PHP\Utils\HTTP
 */
class HttpUtils {

    /**
     * 发送数据get
     * @param $url
     * @return mixed
     */
    public static function curlGet($url) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $data = curl_exec($curl);
        curl_close($curl);
        return $data;
    }

    /**
     * 发送数据post
     * @param $url
     * @param $post_data
     * @return mixed
     */
    public static function curlPost($url, $post_data){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // stop verifying certificate
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true); // enable posting
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data); // post
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true); // if any redirection after upload
        $data = curl_exec($curl);
        curl_close($curl);

        return $data;
    }

    /**
     * 发送数据异步post
     * @param $url
     * @param $post_data
     * @return mixed
     */
    public static function curlPostAsync($url,$post_data){
        $curl = curl_init();
        curl_setopt($curl,CURLOPT_URL,$url);
        curl_setopt($curl,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($curl, CURLOPT_POST, true); // enable posting
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post_data)); // post
        curl_setopt($curl,CURLOPT_TIMEOUT,1);
        $data = curl_exec($curl);
        curl_close($curl);
        return $data;
    }

    /**
     * 获取请求url的状态
     * @param $url
     * @return mixed
     */
    public static function curlCode($url){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, 1);
        curl_setopt($curl, CURLOPT_NOBODY,true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
        curl_exec($curl);
        $httpCode = curl_getinfo($curl,CURLINFO_HTTP_CODE);
        curl_close($curl);
        return $httpCode;
    }

    /**
     * 上传文件
     * php 5.5以上 $post_date 需要new CURLFile($path)
     * php 5.5以下可用 @$path 传递方式
     * @param $url
     * @param $post_data
     * @return mixed
     */
    public static function uploadFile($url, $post_data){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: multipart/form-data'));
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // stop verifying certificate
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true); // enable posting
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data); // post images
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true); // if any redirection after upload
        $data = curl_exec($curl);
        curl_close($curl);

        return $data;
    }


    /**
     * 发送XML数据
     * @param $url
     * @param $xml_data
     * @return mixed|string
     */
    public static function curlPostXml($url, $xml_data){
        try{
            if (!extension_loaded("curl")) {
                trigger_error("对不起，请开启curl功能模块！", E_USER_ERROR);
            }
            //初始一个curl会话
            $curl = curl_init();
            //设置url
            curl_setopt($curl, CURLOPT_URL, $url);
            //设置发送方式：post
            curl_setopt($curl, CURLOPT_POST, true);
            //设置发送数据
            curl_setopt($curl, CURLOPT_POSTFIELDS, $xml_data);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            $repose = curl_exec($curl);

            $httpCode = curl_getinfo($curl,CURLINFO_HTTP_CODE);
            //关闭cURL资源，并且释放系统资源
            curl_close($curl);
            if ($httpCode != "200"){
                return "";
            }

            return $repose;
        } catch (\Exception $e){
            LogUtils::error($e);
        }
        return "";
    }
}
