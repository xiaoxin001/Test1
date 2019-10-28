<?php

namespace Ejiayou\PHP\Utils\WxApp;

use Ejiayou\PHP\Utils\Encrypt\AES\MiniApps\WXBizDataCrypt;
use Illuminate\Support\Facades\Redis;
use Ejiayou\PHP\Utils\Log\LogUtils;
use Ejiayou\PHP\Utils\HTTP\HttpUtils;
use Ejiayou\PHP\Utils\EjyUtils;
use stdClass;

/**
 * 小程序 工具类
 * @package Ejiayou\PHP\Utils\WxApp
 */
class WxAppUtils {
    private static $project = 'default';
    private static $app_id;
    private static $app_secret;
    private static $access_token_key;
    private static $jsapi_ticket_key;
    private static $access_token_server;
    private static $jsapi_ticket_server;

    const SEND_TEMPLATE_URL = "https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send"; // 发送模板消息

    /**
     * 初始化
     * WxAppUtils constructor.
     * @param string $project
     */
    public function __construct($project='default') {
        self::$project = $project;
        $configs = config('ephputils.app_weixin',[]);

        self::$app_id = isset($configs[$project]) && isset($configs[$project]['app_id']) ? $configs[$project]['app_id']:null;
        self::$app_secret = isset($configs[$project]) && isset($configs[$project]['app_secret']) ?$configs[$project]['app_secret']:null;
        self::$access_token_key = isset($configs[$project]) && isset($configs[$project]['access_token_key']) ?$configs[$project]['access_token_key']:null;
        self::$jsapi_ticket_key = isset($configs[$project]) && isset($configs[$project]['jsapi_ticket_key']) ?$configs[$project]['jsapi_ticket_key']:null;
        self::$access_token_server = isset($configs[$project]) && isset($configs[$project]['access_token_server']) ?$configs[$project]['access_token_server']:null;
        self::$jsapi_ticket_server = isset($configs[$project]) && isset($configs[$project]['jsapi_ticket_server']) ?$configs[$project]['jsapi_ticket_server']:null;
    }


    /**
     * 静态方法初始化
     * @param $methods
     * @param $parameters
     * @return mixed
     */
    public static function __callStatic($methods,$parameters) {
        $project = 'default';
        $configs = config('ephputils.app_weixin',[]);

        self::$app_id = isset($configs[$project]) && isset($configs[$project]['app_id']) ? $configs[$project]['app_id']:null;
        self::$app_secret = isset($configs[$project]) && isset($configs[$project]['app_secret']) ?$configs[$project]['app_secret']:null;
        self::$access_token_key = isset($configs[$project]) && isset($configs[$project]['access_token_key']) ?$configs[$project]['access_token_key']:null;
        self::$jsapi_ticket_key = isset($configs[$project]) && isset($configs[$project]['jsapi_ticket_key']) ?$configs[$project]['jsapi_ticket_key']:null;
        self::$access_token_server = isset($configs[$project]) && isset($configs[$project]['access_token_server']) ?$configs[$project]['access_token_server']:null;
        self::$jsapi_ticket_server = isset($configs[$project]) && isset($configs[$project]['jsapi_ticket_server']) ?$configs[$project]['jsapi_ticket_server']:null;
        return (new static(self::$project))->$methods(...$parameters);
    }


    /**
     * 动态调用
     * @param $methods
     * @param $parameters
     * @return mixed
     */
    public function __call($methods,$parameters) {
        $project = 'default';
        $configs = config('ephputils.app_weixin',[]);

        self::$app_id = isset($configs[$project]) && isset($configs[$project]['app_id']) ? $configs[$project]['app_id']:null;
        self::$app_secret = isset($configs[$project]) && isset($configs[$project]['app_secret']) ?$configs[$project]['app_secret']:null;
        self::$access_token_key = isset($configs[$project]) && isset($configs[$project]['access_token_key']) ?$configs[$project]['access_token_key']:null;
        self::$jsapi_ticket_key = isset($configs[$project]) && isset($configs[$project]['jsapi_ticket_key']) ?$configs[$project]['jsapi_ticket_key']:null;
        self::$access_token_server = isset($configs[$project]) && isset($configs[$project]['access_token_server']) ?$configs[$project]['access_token_server']:null;
        self::$jsapi_ticket_server = isset($configs[$project]) && isset($configs[$project]['jsapi_ticket_server']) ?$configs[$project]['jsapi_ticket_server']:null;
        return (new static(self::$project))->$methods(...$parameters);
    }


    /**
     * 获取access_token
     * @param bool $needToken
     * @return string
     */
    private static function getAccessToken($needToken=True){
        $access_token = Redis::get(self::$access_token_key);
        LogUtils::debug('[WxAppUtils] getAccessToken() project:'.self::$project.' access_token_key'.self::$access_token_key.' cache_value'.$access_token);

        if($access_token != ''){
            return $access_token;
        }
        $get_token_url = self::$access_token_server;
        if($needToken){
            $get_token_url = self::addCustomToken($get_token_url);
        }
        $result = HttpUtils::curlGet($get_token_url);
        LogUtils::debug("[WxAppUtils] getAccessToken() curlGet(get_toekn_url) result:".$result);

        $data = json_decode($result);
        if(!$data){
            LogUtils::debug("[WxAppUtils] getAccessToken() json_decode 数据获取失败");
            return '';
        }

        if((isset($data->Result) && $data->Result == 0) || (isset($data->errcode) && $data->errcode == 0)){
            $access_token = isset($data->AccessToken) ? $data->AccessToken : (isset($data->access_token) ? $data->access_token: '');
        }
        return $access_token;
    }

    /**
     * 获取session_key
     * @param $code
     * @return string
     */
    protected static function getSessionKey($code){
        if (!$code){
            return '';
        }

        //获取session_key
        $url = "https://api.weixin.qq.com/sns/jscode2session?"
            . "appid=" . self::$app_id . "&"
            . "secret=" . self::$app_secret. "&"
            . "js_code=" . $code . "&grant_type=authorization_code";

        $result = json_decode(HttpUtils::curlGet($url));

        LogUtils::debug('WxAppUtils|getSessionKey返回结果:'.json_encode($result,JSON_UNESCAPED_UNICODE));


        if (!$result) {
            LogUtils::error('WxAppUtils|getSessionKey:程序异常');
            return '';
        }

        if (isset($result->errcode)) {
            LogUtils::error("WxAppUtils|getSessionKey:获取session_key失败；返回结果::".json_encode($result));
            return '';
        }

        //result=['openid'=>'',session_key='','unionid'=>'']
        return $result->session_key;
    }


    /**
     * 通过code获取session_key,openid等信息
     * @param $code
     * @return string
     */
    protected static function getBaseInfo($code){

        if (!$code){
            return [];
        }

        //获取session_key
        $url = "https://api.weixin.qq.com/sns/jscode2session?"
            . "appid=" . self::$app_id . "&"
            . "secret=" . self::$app_secret. "&"
            . "js_code=" . $code . "&grant_type=authorization_code";

        $result = json_decode(HttpUtils::curlGet($url));

        LogUtils::debug('WxAppUtils|getBaseInfo返回结果:'.json_encode($result,JSON_UNESCAPED_UNICODE));


        if (!$result) {
            LogUtils::error('WxAppUtils|getBaseInfo:程序异常');
            return [];
        }

        if (isset($result->errcode)) {
            LogUtils::debug("WxAppUtils|getBaseInfo:获取用户基本信息失败；返回结果::".json_encode($result));
            return [];
        }

        //result=['openid'=>'',session_key='','unionid'=>'']
        return $result;
    }


    /**
     * 授权获取用户信息
     * 根据code获取openid、session_key
     * @param code,iv,encrypted_data
     * 成功返回数据 ret:0, msg:"success",data:user(openid，unionid,user_id,mobile)
     **/
    protected static function getUserInfo($session_key,$iv,$encrypt_data)
    {

        //小程序数据解密，获取用户信息
        $de_result = self::decryptUserInfo($session_key,$encrypt_data,$iv);
        if($de_result['err_code'] != 0){
            if (in_array($de_result['err_code'],[-41001])){
                LogUtils::debug('WxAppUtils|getUserInfo:用户信息解密失败!错误信息:'.json_encode($de_result));
            }else{
                LogUtils::error('WxAppUtils|getUserInfo:用户信息解密失败!错误信息:'.json_encode($de_result));
            }
            return [];
        }

        return $de_result['data'];
    }

    /**
     * 获取手机号授权认证
     * @param Request $request
     * @return string
     */
    protected static function getUserPhone($session_key,$iv,$encrypt_data){

        LogUtils::debug('WxAppUtils|getUserPhone参数【iv:'.$iv.';encrypt_data:'.$encrypt_data.'】');

        //参数是否正确
        if (!$encrypt_data || !$iv ) {
            LogUtils::error('WxAppUtils|getUserPhone参数【iv:'.$iv.';encrypt_data:'.$encrypt_data.'】');
            return '';
        }

        //小程序数据解密，获取用户信息
        $de_result = self::decryptUserInfo($session_key,$encrypt_data,$iv);

        if($de_result['err_code'] != 0){
            if (in_array($de_result['err_code'],[-41001])){
                LogUtils::debug('WxAppUtils|getUserPhone:'.json_encode($de_result));
            }else{
                LogUtils::error('WxAppUtils|getUserPhone:'.json_encode($de_result));
            }
            return '';
        }

        return $de_result['data']->purePhoneNumber;
    }


    /**
     * 获取分享用户群GID
     * @param $session_key
     * @param $iv
     * @param $encrypt_data
     * @return string
     */
    protected static function getShareInfo($session_key,$iv,$encrypt_data){
        LogUtils::debug('WxAppUtils|getShareInfo参数【iv:'.$iv.';encrypt_data:'.$encrypt_data.'】');

        //参数是否正确
        if (!$encrypt_data || !$iv ) {
            LogUtils::error('WxAppUtils|getShareInfo参数【iv:'.$iv.';encrypt_data:'.$encrypt_data.'】');
            return '';
        }

        //小程序数据解密，获取用户信息
        $de_result = self::decryptUserInfo($session_key,$encrypt_data,$iv);

        if($de_result['err_code'] != 0){
            if (in_array($de_result['err_code'],[-41001])){
                LogUtils::debug('WxAppUtils|getShareInfo:'.json_encode($de_result));
            }else{
                LogUtils::error('WxAppUtils|getShareInfo:'.json_encode($de_result));
            }

            return '';
        }

        return $de_result['data'];
    }

    /**
     * 用户信息解密
     * @param $appid
     * @param $sessionKey
     * @param $encrypt_data
     * @param $iv
     * @return mixed
     */
    private static function decryptUserInfo($sessionKey,$encrypt_data,$iv){

        $pc = new WXBizDataCrypt(self::$app_id,$sessionKey);

        $errCode = $pc->decryptData($encrypt_data, $iv, $decrypt_data);

        $data['err_code']   = $errCode;
        $data['data']       = json_decode($decrypt_data);

        return $data;
    }

    /**
     * 自定义添加检验参数
     * @param $url
     * @return string
     */
    private static function addCustomToken($url){
        $t = time();
        $key = EjyUtils::encrypt(self::$app_id.'#'.self::$app_secret.'#'.$t);
        $token = md5($key.'#'.$t);
        $url = strpos($url,'?') !== false ? $url.'&key='.$key.'&token='.$token : $url.'?key='.$key.'&token='.$token ;
        return $url;
    }

    /**
     * 发送模板消息
     * @param $openid
     * @param $template_id
     * @param $form_id
     * @param $data
     * @param string $page
     * @return mixed|stdClass
     */
    protected static function sendTplMsg($openid, $template_id, $form_id, $data, $page = "" ,$emphasis_keyword="")
    {
        if($page){
            $post_data = array(
                "touser" => $openid,
                "template_id" => $template_id,
                "page" => $page,
                "form_id" => $form_id,
                "data" => $data
            );
        }else{
            $post_data = array(
                "touser" => $openid,
                "template_id" => $template_id,
                "form_id" => $form_id,
                "data" => $data
            );
        }

        if ($emphasis_keyword){
            $post_data['emphasis_keyword'] = $emphasis_keyword;
        }

        $access_token = self::getAccessToken();
        $rtn = new stdClass();
        if(!$access_token){
            $rtn->result = 1;
            $rtn->msg = '获取access_token失败';
            return $rtn;
        }
        $url = self::SEND_TEMPLATE_URL."?access_token=$access_token";

        $result = HttpUtils::curlPost($url,json_encode($post_data,JSON_UNESCAPED_UNICODE));
        LogUtils::debug("[WxAppUtils] sendTplMsg() curlPost(url,post_data) result:".$result);

        $res = json_decode($result);
        if(!$res){
            $rtn->ret = 2;
            $rtn->msg = "返回数据解析失败";
            return $rtn;
        }

        $res->ret = 0;
        $res->msg = 'success';
        if(isset($res->errcode) && $res->errcode != 0){
            $res->ret = $res->errcode;
            $res->msg = $res->errmsg;
        }
        return $res;
    }
}
