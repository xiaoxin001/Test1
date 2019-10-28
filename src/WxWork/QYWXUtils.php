<?php

namespace Ejiayou\PHP\Utils\WxWork;

use Illuminate\Support\Facades\Redis;
use Ejiayou\PHP\Utils\Log\LogUtils;
use Ejiayou\PHP\Utils\HTTP\HttpUtils;
use Ejiayou\PHP\Utils\EjyUtils;
use stdClass;

/**
 * 企业微信 工具类
 * @package Ejiayou\PHP\Utils\WxWork
 */
class QYWXUtils {
    private static $project = 'default';
    private static $corp_id;
    private static $agent_id;
    private static $crop_secret;
    private static $access_token_key;
    private static $jsapi_ticket_key;
    private static $access_token_server;
    private static $jsapi_ticket_server;

    const AUTHORIZE_URL = "https://open.weixin.qq.com/connect/oauth2/authorize"; // 用户同意授权，获取code
    const GET_USER_INFO_URL = "https://qyapi.weixin.qq.com/cgi-bin/user/getuserinfo"; // 通过OpenID来获取用户基本信息
    const GET_USER_URL = "https://qyapi.weixin.qq.com/cgi-bin/user/get"; // 企业号根据USERID获取用户信息
    const GET_DEPARTMENT_LIST_URL = "https://qyapi.weixin.qq.com/cgi-bin/department/list"; // 获取部门列表
    const GET_MEMBER_LIST_URL = "https://qyapi.weixin.qq.com/cgi-bin/user/list"; // 获取成员列表
    const AUTH_SUCCESS_URL = "https://qyapi.weixin.qq.com/cgi-bin/user/authsucc"; // 认证判断
    const SEND_MESSAGE_URL = "https://qyapi.weixin.qq.com/cgi-bin/message/send"; // 发送信息

    /**
     * 初始化
     * QYWXUtils constructor.
     * @param string $project
     */
    public function __construct($project='default') {
        self::$project = $project;
        $configs = config('ephputils.work_weixin',[]);

        self::$corp_id = isset($configs[$project]) && isset($configs[$project]['corp_id']) ? $configs[$project]['corp_id']:null;
        self::$agent_id = isset($configs[$project]) && isset($configs[$project]['agent_id']) ? $configs[$project]['agent_id']:null;
        self::$crop_secret = isset($configs[$project]) && isset($configs[$project]['crop_secret']) ?$configs[$project]['crop_secret']:null;
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
        $configs = config('ephputils.work_weixin',[]);

        self::$corp_id = isset($configs[$project]) && isset($configs[$project]['corp_id']) ? $configs[$project]['corp_id']:null;
        self::$agent_id = isset($configs[$project]) && isset($configs[$project]['agent_id']) ? $configs[$project]['agent_id']:null;
        self::$crop_secret = isset($configs[$project]) && isset($configs[$project]['crop_secret']) ?$configs[$project]['crop_secret']:null;
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
        $configs = config('ephputils.work_weixin',[]);

        self::$corp_id = isset($configs[$project]) && isset($configs[$project]['corp_id']) ? $configs[$project]['corp_id']:null;
        self::$agent_id = isset($configs[$project]) && isset($configs[$project]['agent_id']) ? $configs[$project]['agent_id']:null;
        self::$crop_secret = isset($configs[$project]) && isset($configs[$project]['crop_secret']) ?$configs[$project]['crop_secret']:null;
        self::$access_token_key = isset($configs[$project]) && isset($configs[$project]['access_token_key']) ?$configs[$project]['access_token_key']:null;
        self::$jsapi_ticket_key = isset($configs[$project]) && isset($configs[$project]['jsapi_ticket_key']) ?$configs[$project]['jsapi_ticket_key']:null;
        self::$access_token_server = isset($configs[$project]) && isset($configs[$project]['access_token_server']) ?$configs[$project]['access_token_server']:null;
        self::$jsapi_ticket_server = isset($configs[$project]) && isset($configs[$project]['jsapi_ticket_server']) ?$configs[$project]['jsapi_ticket_server']:null;

        return (new static(self::$project))->$methods(...$parameters);
    }


    /**
     * 用户同意授权，获取code
     * @param $scope
     * 应用授权作用域，snsapi_base
     * （不弹出授权页面，直接跳转，只能获取用户openid），
     * snsapi_userinfo （弹出授权页面，可通过openid拿到昵称、
     * 性别、所在地。并且，即使在未关注的情况下，只要用户授权，
     * 也能获取其信息）
     * @param $redirect_uri
     * @param $scope
     * @return string
     */
    protected static function getOauth2Code($redirect_uri, $scope){
        return self::AUTHORIZE_URL
            ."?appid=".self::$corp_id
            ."&redirect_uri=".urlencode($redirect_uri)
            ."&response_type=code"
            ."&scope=".$scope
            ."&state=STATE"
            ."#wechat_redirect";
    }

    /**
     * 企业号获取AccessToken
     * @param bool $needToken
     * @return string
     */
    private static function getAccessToken($needToken=True){

        $access_token = Redis::get(self::$access_token_key);
        LogUtils::debug('[QYWXUtils] getAccessToken() project:'.self::$project.' access_token_key:'.self::$access_token_key.' ;cache_value:'.$access_token);

        if($access_token != ''){
            return $access_token;
        }
        $get_token_url = self::$access_token_server;
        if($needToken){
            $get_token_url = self::addCustomToken($get_token_url);
        }

        $result = HttpUtils::curlGet($get_token_url);
        LogUtils::debug("[QYWXUtils] getAccessToken() curlGet(get_toekn_url) result:".$result);

        $data = json_decode($result);
        if(!$data){
            LogUtils::debug("[QYWXUtils] getAccessToken() json_decode 数据获取失败");
            return '';
        }

        if((isset($data->errcode) && $data->errcode == 0)){
            $access_token = isset($data->access_token) ? $data->access_token: '';
        }
        return $access_token;
    }

    /**
     * 自定义添加检验参数
     * @param $url
     * @return string
     */
    private static function addCustomToken($url){
        $t = time();
        $key = EjyUtils::encrypt(self::$corp_id.'#'.self::$crop_secret.'#'.$t);
        $token = md5($key.'#'.$t);
        $url = strpos($url,'?') !== false ? $url.'&key='.$key.'&token='.$token : $url.'?key='.$key.'&token='.$token ;
        return $url;
    }

    /**
     * 企业号根据code获取身份信息。请使用https协议。
     * @param $code
     * @return stdClass
     */
    protected static function getUserInfo($code){
        $rtn = new stdClass();
        $access_token = self::getAccessToken();
        if(!$access_token){
            $rtn->ret = 1;
            $rtn->msg = "获取access_token失败";
            return $rtn;
        }
        $url = self::GET_USER_INFO_URL."?access_token={$access_token}&code={$code}";

        $result = HttpUtils::curlGet($url);
        LogUtils::debug("[QYWXUtils] getUserInfo() curlGet(url) result::".$result);

        $res = json_decode($result);

        if(!$result || !$res){
            LogUtils::error("[QYWXUtils] getUserInfo() 企业号获取身份信息失败:数据解析失败");
            $rtn->ret = 2;
            $rtn->msg = "获取身份信息失败";
            return $rtn;
        }

        if(isset($res->errcode) && $res->errcode > 0){
            LogUtils::error("[QYWXUtils] getUserInfo() 企业号获取身份信息失败 errcode:".$res->errcode." errmsg:".$res->errmsg);
            $rtn->ret = $res->errcode;
            $rtn->msg = $res->errmsg;
            return $rtn;
        }

        if(!isset($res->UserId)){
            $rtn->ret = 3;
            $rtn->msg = "暂无权限使用";
            return $rtn;
        }

        $rtn->ret = 0;
        $rtn->qy_user_id = $res->UserId;
        $rtn->device_id = $res->DeviceId;
        $rtn->msg = "ok";
        return $rtn;
    }

    /**
     * 企业号根据USERID获取用户信息。请使用https协议。
     * @param $corp_user_id
     * @return stdClass
     */
    protected static function getUser($corp_user_id){
        $rtn = new stdClass();
        $access_token = self::getAccessToken();
        if(!$access_token){
            $rtn->ret = 1;
            $rtn->msg = "获取access_token失败";
            return $rtn;
        }
        $url = self::GET_USER_URL."?access_token={$access_token}&userid={$corp_user_id}";
        $result = HttpUtils::curlGet($url);
        LogUtils::debug("[QYWXUtils] getUser() curlGet(url) result::".$result);

        $res = json_decode($result);
        if(!$result || !$res){
            LogUtils::error("[QYWXUtils] getUser() 企业号获取身份信息失败:数据解析失败");
            $rtn->ret = 2;
            $rtn->msg = "获取身份信息失败";
            return $rtn;
        }

        if(isset($res->errcode) && $res->errcode > 0){
            LogUtils::error("[QYWXUtils] getUser() 企业号获取用户信息失败 errcode".$res->errcode." errmsg:".$res->errmsg);
            $rtn->ret = $res->errcode;
            $rtn->msg = $res->errmsg;
            return $rtn;
        }
        if($res->status == 1){
            $rtn->ret = 0;
            $rtn->msg = "ok";
            $rtn->user = $result;
        }
        elseif($result->status == 2){
            $rtn->ret = 3;
            $rtn->msg = "该账号已禁用";
        }
        elseif($result->status == 4){
            $rtn->ret = 0;
            $rtn->msg = "该账号还未关注";
            $rtn->user = $result;
        }else{
            $rtn->ret = 3;
            $rtn->msg = "该用户状态异常";
            return $rtn;
        }
        return $rtn;
    }

    /**
     * 发送消息接口
     * @param $qy_user_id
     * @param $type
     * @param $content_params
     * @return stdClass
     */
    protected static function sendWXMessage($qy_user_id, $type,$content_params){
        $rtn = new stdClass();
        $access_token = self::getAccessToken();
        if(!$access_token){
            $rtn->ret = 1;
            $rtn->msg = "获取access_token失败";
            return $rtn;
        }
        $api_url = self::SEND_MESSAGE_URL."?access_token={$access_token}";
        $wx_message_arr = [
            'touser'=>$qy_user_id,
            'msgtype'=>$type,
            'agentid'=>self::$agent_id
        ];
        switch ($type){
            case "text":
                if(!isset($content_params['content']) || $content_params['content'] == ''){
                    $rtn->ret = 1;
                    $rtn->msg = "发送text消息缺少content参数";
                    return $rtn;
                }
                $wx_message_arr['text']['content'] = $content_params['content'];
                break;
            case "news":
                if(!isset($content_params['title']) || $content_params['title'] == ''){
                    $rtn->ret = 1;
                    $rtn->msg = "发送news消息缺少title参数";
                    return $rtn;
                }
                if(!isset($content_params['description']) || $content_params['description'] == ''){
                    $rtn->ret = 1;
                    $rtn->msg = "发送news消息缺少description参数";
                    return $rtn;
                }
                if(!isset($content_params['url']) || $content_params['url'] == ''){
                    $rtn->ret = 1;
                    $rtn->msg = "发送news消息缺少url参数";
                    return $rtn;
                }
                if(!isset($content_params['picurl']) || $content_params['picurl'] == ''){
                    $rtn->ret = 1;
                    $rtn->msg = "发送news消息缺少picurl参数";
                    return $rtn;
                }
                $wx_message_arr['news']['articles']=[
                    0=>[
                        'title'=>$content_params['title'],
                        'description'=>$content_params['description'],
                        'url'=>$content_params['url'],
                        'picurl'=>$content_params['picurl']
                    ]
                ];
                break;
            default:
                $rtn->ret = 3;
                $rtn->msg = "暂不支持该类型消息的发送";
                return $rtn;

        }
        $api_post_params = json_encode($wx_message_arr);
        $result = HttpUtils::curlPost($api_url,$api_post_params);
        LogUtils::debug("[QYWXUtils] sendWXMessage() curlPost(api_url,api_post_params) result::".$result);
        $res = json_decode($result);
        if(!$res){
            LogUtils::error("[QYWXUtils] sendWXMessage() 发送信息接口返回数据解析失败");
            $rtn->ret = 4;
            $rtn->msg = "发送信息失败";
            return $rtn;
        }
        if(isset($res->errcode) && $res->errcode > 0){
            LogUtils::error("[QYWXUtils] sendWXMessage() 发送信息失败 errcode:{$res->errcode}  errmsg:{$res->errmsg}");
            $rtn->ret = $res->errcode;
            $rtn->msg = "发送信息失败：".$res->errmsg;
            return $rtn;
        }
        $rtn->ret = 0;
        $rtn->msg = "发送成功";
        return $rtn;
    }

    /**
     * 获取部门列表接口
     * @param string $department_id
     * @return array|stdClass
     */
    protected static function getDepartmentList($department_id = '')
    {
        $access_token = self::getAccessToken();
        if(!$access_token){
            LogUtils::error('[QYWXUtils] getDepartmentList() 获取access_token失败');
            return [];
        }

        $url = self::GET_DEPARTMENT_LIST_URL."?access_token={$access_token}&id=$department_id";
        $result = HttpUtils::curlGet($url);
        LogUtils::debug('[QYWXUtils] getDepartmentList() result:'. $result);
        $res = json_decode($result);
        if(!$result || !$res){
            LogUtils::error("[QYWXUtils] getDepartmentList() 获取部门列表失败:数据解析失败");
            return [];
        }
        $rtn = new stdClass();
        $rtn->ret = $res->errcode;
        $rtn->msg = $res->errmsg;
        $rtn->data = $res->department;
        return $rtn;
    }

    /**
     * 获取成员列表接口
     * @param $department_id
     * @param int $fetch_child
     * @return array|stdClass
     */
    protected static function getMemberList($department_id, $fetch_child = 1)
    {
        $access_token = self::getAccessToken();
        if(!$access_token){
            LogUtils::error('[QYWXUtils] getMemberList() 获取access_token失败');
            return [];
        }

        $url = self::GET_MEMBER_LIST_URL
            ."?access_token={$access_token}"
            ."&department_id={$department_id}"
            ."&fetch_child={$fetch_child}&status=0";
        $result = HttpUtils::curlGet($url);
        LogUtils::debug('[QYWXUtils] getMemberList() result:'. $result);
        $res = json_decode($result);
        if(!$result || !$res){
            LogUtils::error("[QYWXUtils] getMemberList() 获取成员列表失败:数据解析失败");
            return [];
        }
        $rtn = new stdClass();
        $rtn->ret = $res->errcode;
        $rtn->msg = $res->errmsg;
        $rtn->data = $res->userlist;
        return $rtn;
    }

    /**
     * 根据userid获取用户信息
     * @param $user_id
     * @return array|mixed
     */
    protected static function getUserInfoByUserId($user_id)
    {
        $access_token = self::getAccessToken();
        if(!$access_token){
            LogUtils::error('[QYWXUtils] getUserInfoByUserId() 获取access_token失败');
            return [];
        }

        $url = self::GET_USER_INFO_URL."?access_token={$access_token}&userid={$user_id}";
        $result = HttpUtils::curlGet($url);
        LogUtils::debug('[QYWXUtils] getMemberList() result:'. $result);
        $res = json_decode($result);
        if(!$result || !$res){
            LogUtils::error("[QYWXUtils] getMemberList() 获取成员列表失败:数据解析失败");
            return [];
        }
        return $res;
    }

    /**
     * 企业号二次验证
     * @param $corp_user_id
     * @return stdClass
     */
    protected static function AuthSuccess($corp_user_id){

        $rtn = new stdClass();
        $access_token = self::getAccessToken();
        if(!$access_token){
            $rtn->ret = 1;
            $rtn->msg = "获取access_token失败";
            return $rtn;
        }
        $url = self::AUTH_SUCCESS_URL."?access_token={$access_token}&userid={$corp_user_id}";
        $result = HttpUtils::curlGet($url);
        LogUtils::debug('[QYWXUtils] AuthSuccess() result:'. $result);

        $res = json_decode($result);
        if(!$result || !$res){
            LogUtils::error("[QYWXUtils] AuthSuccess() 企业号二次验证失败:数据解析失败");
            $rtn->ret = 1;
            $rtn->msg = "验证失败";
            return $rtn;
        }

        if(isset($res->errcode) && $res->errcode > 0){
            LogUtils::error("[QYWXUtils] AuthSuccess() 企业号二次验证失败 errcode".$res->errcode." errmsg:".$res->errmsg);
            $rtn->ret = $res->errcode;
            $rtn->msg = $res->errmsg;
            return $rtn;
        }
        $rtn->ret = 0;
        $rtn->msg = "ok";
        return $rtn;
    }
}
