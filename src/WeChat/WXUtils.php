<?php

namespace Ejiayou\PHP\Utils\WeChat;

use Illuminate\Support\Facades\Redis;
use Ejiayou\PHP\Utils\Log\LogUtils;
use Ejiayou\PHP\Utils\HTTP\HttpUtils;
use Ejiayou\PHP\Utils\EjyUtils;
use stdClass;

/**
 * 微信公众号 工具类
 * @package Ejiayou\PHP\Utils\WeChat
 */
class WXUtils {
    private static $project = 'default';

    private static $app_id;
    private static $app_secret;
    private static $access_token_key;
    private static $jsapi_ticket_key;
    private static $access_token_server;
    private static $jsapi_ticket_server;

    const AUTHORIZE_URL = "https://open.weixin.qq.com/connect/oauth2/authorize"; // 用户同意授权，获取code
    const OAUTH2_TOKEN_URL = "https://api.weixin.qq.com/sns/oauth2/access_token"; // 通过code换取网页授权access_token
    const GET_USER_INFO_URL = "https://api.weixin.qq.com/cgi-bin/user/info"; // 通过OpenID来获取用户基本信息
    const BATCH_GET_USER_INFO_URL = "https://api.weixin.qq.com/cgi-bin/user/info/batchget"; // 通过OpenID来获取用户基本信息（批量）
    const SEND_CUSTOM_MESSAGE_URL = "https://api.weixin.qq.com/cgi-bin/message/custom/send"; // 发送客服消息
    const SEND_TEMPLATE_URL = "https://api.weixin.qq.com/cgi-bin/message/template/send"; // 发送模板消息
    const ADD_MATERIAL_URL = "https://api.weixin.qq.com/cgi-bin/material/add_material"; // 上传永久素材
    const ADD_NEWS_URL = "https://api.weixin.qq.com/cgi-bin/material/add_news"; // 上传图文素材
    const UPDATE_NEWS_URL = "https://api.weixin.qq.com/cgi-bin/material/update_news"; // 更新图文素材
    const GENERATE_SHORTURL_URL = "https://api.weixin.qq.com/cgi-bin/shorturl"; // 生成短链
    const CREATE_QRCODE_URL = "https://api.weixin.qq.com/cgi-bin/qrcode/create"; // 生成二维码
    const SHOW_QRCODE_URL = "https://mp.weixin.qq.com/cgi-bin/showqrcode"; // 展示二维码


    /**
     * 初始化
     * WXUtils constructor.
     * @param string $project
     */
    public function __construct($project='default') {
        self::$project = $project;
        $configs = config('ephputils.mp_weixin',[]);

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
        $configs = config('ephputils.mp_weixin',[]);

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
    public function __call($methods,$parameters){
        $project = 'default';
        $configs = config('ephputils.mp_weixin',[]);

        self::$app_id = isset($configs[$project]) && isset($configs[$project]['app_id']) ? $configs[$project]['app_id']:null;
        self::$app_secret = isset($configs[$project]) && isset($configs[$project]['app_secret']) ?$configs[$project]['app_secret']:null;
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
     */
    protected static function getOauth2Code($redirect_uri, $scope){
        return self::AUTHORIZE_URL
            ."?appid=".self::$app_id
            ."&redirect_uri=".urlencode($redirect_uri)
            ."&response_type=code"
            ."&scope=".$scope
            ."&state=STATE"
            ."#wechat_redirect";
    }


    /**
     * 通过code换取网页授权access_token
     * @param $code
     * @return mixed|stdClass
     */
    protected static function getOauthToken($code){

        $oauth2_token_url=self::OAUTH2_TOKEN_URL
            ."?appid=".self::$app_id
            ."&secret=".self::$app_secret
            ."&code=".$code
            ."&grant_type=authorization_code";

        $data = HttpUtils::curlGet($oauth2_token_url);

        LogUtils::debug("[WXUtils] get_oauth2_token() result:".$data);

        $result = json_decode($data);
        if(!$result){
            $result = new stdClass();
            $result->ret = 1;
            return $result;
        }

        if(isset($result->errcode)){
            $result->ret = 1;
        }else{
            $result->ret = 0;
            $r = self::getUserInfo($result->openid);
            if($r->result == 0 && isset($r->unionid)){
                $result->unionid = $r->unionid;
            }else{
                $result->unionid = "";
            }
        }
        return $result;
    }

    /**
     * 生成相关JS相关签名
     * @return string
     */
    private static function makeNonceStr(){
        $codeSet = '1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        for ($i = 0; $i<16; $i++) {
            $codes[$i] = $codeSet[mt_rand(0, strlen($codeSet)-1)];
        }
        return implode($codes);
    }

    /**
     * 获取accessToken
     * @param bool $needToken
     * @return string
     */
    private static function getAccessToken($needToken=True){
        $access_token = Redis::get(self::$access_token_key);
        LogUtils::debug('[WXUtils] getAccessToken() project:'.self::$project.', access_token_key:'.self::$access_token_key.', cache_value:'.$access_token);

        if($access_token != ''){
            return $access_token;
        }
        $get_token_url = self::$access_token_server;
        if($needToken){
            $get_token_url = self::addCustomToken($get_token_url);
        }
        $result = HttpUtils::curlGet($get_token_url);
        LogUtils::debug("[WXUtils] getAccessToken() curlGet(get_toekn_url) result:".$result);

        $data = json_decode($result);
        if(!$data){
            LogUtils::debug("[WXUtils] getAccessToken() json_decode 数据获取失败");
            return '';
        }

        if((isset($data->Result) && $data->Result == 0) || (isset($data->errcode) && $data->errcode == 0)){
            $access_token = isset($data->AccessToken) ? $data->AccessToken : (isset($data->access_token) ? $data->access_token: '');
        }
        return $access_token;
    }

    /**
     * 生成签名之前必须先了解一下jsapi_ticket，jsapi_ticket是公众号用于调用微信JS接口的临时票据。
     * 正常情况下，jsapi_ticket的有效期为7200秒，通过access_token来获取。
     * 由于获取jsapi_ticket的api调用次数非常有限，频繁刷新jsapi_ticket会导致api调用受限，
     * 影响自身业务，开发者必须在自己的服务全局缓存jsapi_ticket 。
     */
    private static function getTicket($needToken=True) {
        $ticket = Redis::get(self::$jsapi_ticket_key);
        LogUtils::debug('[WXUtils] getTicket() project:'.self::$project.', jsapi_ticket_key:'.self::$jsapi_ticket_key.', cache_value:'.$ticket);

        if($ticket != ''){
            return $ticket;
        }
        $get_ticket_url = self::$jsapi_ticket_server;
        if($needToken){
            $get_ticket_url = self::addCustomToken($get_ticket_url);
        }
        $result = HttpUtils::curlGet($get_ticket_url);
        LogUtils::debug("[WXUtils] getTicket() curlGet(get_ticket_url) result:".$result);

        $data = json_decode($result);
        if(!$data){
            LogUtils::debug("[WXUtils] getTicket() json_decode 数据获取失败");
            return '';
        }

        if((isset($data->Result) && $data->Result == 0) || (isset($data->errcode) && $data->errcode == 0)){
            $ticket = isset($data->Ticket) ? $data->Ticket : (isset($data->ticket) ? $data->ticket: '');
        }
        return $ticket;
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
     * 生成签名信息
     * @param $nonceStr
     * @param $timestamp
     * @param $jsapi_ticket
     * @param $url
     * @return string
     */
    private static function makeSignature($nonceStr, $timestamp, $jsapi_ticket, $url) {
        $tmpArr = array(
            'noncestr' => $nonceStr,
            'timestamp' => $timestamp,
            'jsapi_ticket' => $jsapi_ticket,
            'url' => $url
        );
        ksort($tmpArr, SORT_STRING);
        $string = http_build_query( $tmpArr );
        $string = urldecode( $string );

        return sha1( $string );
    }

    /**
     * 获取网页JS的相关参数
     * @param bool $is_https
     * @return stdClass
     */
    protected static function getWeixinParams($is_https=True) {

        $rtn = new stdClass();
        $nonceStr = self::makeNonceStr();
        $timestamp = time();

        $jsapi_ticket = self::getTicket();
        if(!$jsapi_ticket){
            $rtn->ret = 1;
            $rtn->msg = '获取ticket失败';
            return $rtn;
        }
        $http_scheme = $is_https ? 'https' : 'https';
        $refer_url = $http_scheme.'://'.$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"];
        LogUtils::debug("[WXUtils] getWeixinParams() refer_url:".$refer_url);

        $weixin_params = [
            'appId' => self::$app_id,
            'timestamp' => $timestamp,
            'nonceStr' => $nonceStr,
            'signature' => self::makeSignature($nonceStr, $timestamp, $jsapi_ticket, $refer_url)
        ];
        $rtn->ret = 0;
        $rtn->msg = 'success';
        $rtn->weixin_params = $weixin_params;
        return $rtn;
    }

    /**
     * 开发者可通过OpenID来获取用户基本信息。请使用https协议。
     * getone  true  $openid  单个
     * getone  false $openid  多个openid以','连接的字符串
     */
    protected static function getUserInfo($openid, $getone=True){

        $access_token = self::getAccessToken();
        $rtn = new stdClass();
        if(!$access_token){
            $rtn->result = 1;
            $rtn->msg = '获取access_token失败';
            return $rtn;
        }

        if($getone){
            // get方式  可获取单个openid的信息
            $url = self::GET_USER_INFO_URL
                ."?access_token={$access_token}"
                ."&openid={$openid}"
                ."&lang=zh_CN";
            $result = HttpUtils::curlGet($url);
            LogUtils::debug("[WXUtils] getUserInfo() curlGet(url) result::".$result);
        }else{
            // post方式  可获取多个openid的信息
            $url = self::BATCH_GET_USER_INFO_URL."?access_token={$access_token}";
            $open_arr = array_filter(explode(',',$openid));
            $arr = [];
            foreach ($open_arr as $k=>$v){
                $arr[$k]['openid'] = $v;
                $arr[$k]['lang'] = 'zh-CN';
            }
            $post_data = json_encode(array('user_list'=>$arr));

            $result = HttpUtils::curlPost($url,$post_data);
            LogUtils::debug("[WXUtils] getUserInfo() curlPost(url,post_data) result::".$result);
        }
        $res = json_decode($result);
        if(!$res){
            $rtn = new stdClass();
            $rtn->result = 2;
            $rtn->msg = '返回数据解析失败';
            return $rtn;
        }

        $res->result = 0;
        if(isset($res->errcode) && $res->errcode > 0){
            $res->result = $res->errcode;
        }
        return $res;
    }

    /**
     * 发送客服消息
     * @param $openid
     * @param $content
     * @param string $msgtype
     * @return mixed|stdClass
     */
    protected static function sendCustomMsg($openid,$content,$msgtype = "text"){
        $rtn = new stdClass();
        if($msgtype == "text"){
            //发送文本消息
            $content = array('content' => $content);
        }else if($msgtype == "image" || $msgtype == "voice" || $msgtype == "mpnews"){
            //发送图片、语音、图文
            $content = array('media_id' => $content);
        }else if ($msgtype == "news"){
            $content = array('articles' => $content);
        }else{
            $rtn->ret = 1;
            $rtn->msg = "消息类型不正确";
            return $rtn;
        }

        $access_token = self::getAccessToken();
        if(!$access_token){
            $rtn->ret = 2;
            $rtn->msg = "获取access_token失败";
            return $rtn;
        }

        $url = self::SEND_CUSTOM_MESSAGE_URL."?access_token=$access_token";

        $post_data_arr = array(
            'touser' => $openid,
            'msgtype' => $msgtype,
            $msgtype => $content
        );
        $post_data = json_encode($post_data_arr,JSON_UNESCAPED_UNICODE);
        $result = HttpUtils::curlPost($url,$post_data);
        LogUtils::debug("[WXUtils] sendCustomMessage() curlPost(url,post_data) msg_type:$msgtype result:".$result);
        $res = json_decode($result);
        if(!$res){
            $rtn->ret = 3;
            $rtn->msg = "返回数据解析失败";
            return $rtn;
        }

        $res->ret = 0;
        if(isset($res->errcode) && $res->errcode != 0){
            $res->ret = $res->errcode;
        }
        return $res;
    }

    /**
     * 发送微信模板消息
     * @param $template_id
     * @param $access_link
     * @param $openid
     * @param $content
     * @param int $url_type
     * @param string $miniprogram
     * @return mixed|stdClass
     */
    protected static function sendTemplateMsg($template_id,$access_link,$openid,$content,$url_type = 1,$miniprogram = "")
    {
        $rtn = new stdClass();
        $access_token = self::getAccessToken();
        if(!$access_token){
            $rtn->ret = 1;
            $rtn->msg = "获取access_token失败";
            return $rtn;
        }

        if($url_type == 1){
            $post_data_arr = array(
                'touser' => $openid,
                'template_id' => $template_id,
                'url' => $access_link,
                'data' => $content
            );
        }else{
            $post_data_arr = array(
                'touser' => $openid,
                'template_id' => $template_id,
                'url' => $access_link,
                'data' => $content,
                'miniprogram' => $miniprogram
            );
        }

        $post_data = json_encode($post_data_arr,JSON_UNESCAPED_UNICODE);

        $url = self::SEND_TEMPLATE_URL."?access_token=$access_token";
        $result = HttpUtils::curlPost($url,$post_data);

        LogUtils::debug("[WXUtils] sendTemplateMsg() curlPost(url,post_data) result:".$result);

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

    /**
     * 上传永久素材
     * @param $type 媒体文件类型
     * @param $file 上传文件对象
     * @return mixed|string
     */
    protected static function addMaterial($type,$file)
    {
        $rtn = new stdClass();
        $access_token = self::getAccessToken();
        if(!$access_token){
            $rtn->ret = 1;
            $rtn->msg = "获取access_token失败";
            return json_encode($rtn);
        }

        $url = self::ADD_MATERIAL_URL.'?access_token='.$access_token.'&type='.$type;
        $post_data = curl_file_create($file['file']['tmp_name'],'image/jpeg','default.jpg');

        return HttpUtils::curlPost($url,array('media'=>$post_data));
    }


    /**
     * 上传图文素材
     * @param $data
     * @return mixed
     */
    protected static function addNews($data){
        $rtn = new stdClass();
        $access_token = self::getAccessToken();
        if(!$access_token){
            $rtn->ret = 1;
            $rtn->msg = "获取access_token失败";
            return json_encode($rtn);
        }

        $post_data_arr = array(
            'articles' => $data
        );

        $post_data = json_encode($post_data_arr,JSON_UNESCAPED_UNICODE);
        $url = self::ADD_NEWS_URL."?access_token=".$access_token;

        return HttpUtils::curlPost($url,$post_data);
    }


    /**
     * 修改图文素材
     * @param $media_id
     * @param $data
     * @return mixed
     */
    protected static function updateNews($media_id,$data){
        $rtn = new stdClass();
        $access_token = self::getAccessToken();
        if(!$access_token){
            $rtn->ret = 1;
            $rtn->msg = "获取access_token失败";
            return json_encode($rtn);
        }

        $post_data_arr = array(
            'media_id' => $media_id,
            'articles' => $data
        );

        $post_data = json_encode($post_data_arr,JSON_UNESCAPED_UNICODE);
        $url = self::UPDATE_NEWS_URL."?access_token=" . $access_token;

        return HttpUtils::curlPost($url, $post_data);
    }

    /**
     * 生成短连接
     * @param $long_url
     * @return mixed|stdClass
     */
    protected static function makeShortUrl($long_url)
    {
        $rtn = new stdClass();
        $access_token = self::getAccessToken();
        if(!$access_token){
            $rtn->ret = 1;
            $rtn->msg = "获取access_token失败";
            return $rtn;
        }
        $post_data_arr = array(
            'action' => 'long2short',
            'long_url' => $long_url
        );
        $post_data = json_encode($post_data_arr,JSON_UNESCAPED_UNICODE);

        $url = self::GENERATE_SHORTURL_URL."?access_token=$access_token";
        $result = HttpUtils::curlPost($url,$post_data);
        LogUtils::debug("[WXUtils] makeShortUrl() curlPost(url,post_data) result:".$result);

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

    /**
     * 生成带参数二维码
     * @param $scene_str
     * @return string
     */
    protected static function makeQRCode($scene_str){
        $access_token = self::getAccessToken();
        if(!$access_token){
            LogUtils::error('[WXUtils] makeQRCode() err:获取access_token失败');
            return "";
        }

        $get_ticket_url = self::CREATE_QRCODE_URL.'?access_token='.$access_token;

        $post_data = '{"action_name": "QR_LIMIT_STR_SCENE", "action_info": {"scene": {"scene_str": "'.$scene_str.'"}}}';

        LogUtils::debug('[WXUtils] makeQRCode() post_data:'.$post_data);
        $result = HttpUtils::curlPost($get_ticket_url,$post_data);
        LogUtils::debug("[WXUtils] makeQRCode() curlPost(get_ticket_url,post_data) result:".$result);
        $res = json_decode($result);
        if(!$res || !isset($res->ticket) || !$res->ticket){
            LogUtils::error('[WXUtils] makeQRCode() err:返回数据解析失败');
            return "";
        }
        $code_url = self::SHOW_QRCODE_URL.'?ticket='.UrlEncode($res->ticket);
        return $code_url;
    }
}
