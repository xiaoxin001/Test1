<?php
namespace Ejiayou\PHP\Utils\HTTP;
use Ejiayou\PHP\Utils\Log\LogUtils;

/**
 * 易加油HTTP请求工具类
 * Class HttpUtils
 * @package Ejiayou\PHP\Utils\HTTP
 */
class HttpBuilder {

    private static $hb ;
    private static $url ;
    private static $method ;
    private static $params ;
    private static $timeout = 60;
    protected static $multipart ;
    protected static $dns_cache_timeout = 1800;

    public static function Get($data)
    {
        $data['method'] = "GET";
        return self::initRequest($data);
    }

    public static function Post($data)
    {
        $data['method'] = "POST";
        return self::initRequest($data);
    }

    public static function Put($data)
    {
        $data['method'] = "PUT";
        return self::initRequest($data);
    }

    public static function Delete($data)
    {
        $data['method'] = "DELETE";
        return self::initRequest($data);
    }

    public static function Upload($data)
    {
        $data['method'] = "POST";
        self::$multipart = true;
        return self::initRequest($data);
    }

    public static function Header($data)
    {
        $data['method'] = "HEADER";
        return self::initRequest($data);
    }


    public static function Trace($data)
    {
        $data['method'] = "TRACE";
        return self::initRequest($data);
    }

    /**
     * Http 请求
     * @param $data
     * @return array
     */
    private static function initRequest($data)
    {
        $response = [
            'code' => -1,
            'msg' => 'HttpClient Error:param url is wrong'
        ];
        // 初始cURL
        self::$hb = curl_init();
        if(!isset($data) || !isset($data['url'])){
            return $response;
        }
        $url = $data['url'];

        // 数据验证
        try {
            self::dataValication($data);
        } catch (\Exception $e) {
            return ['code'=>-1, 'msg'=>$e->getMessage()];
        }

        // cURL设置
        $timeout = isset($data['timeout'])?$data['timeout']:self::$timeout;
        $headers = self::setHeaders($data);
        curl_setopt(self::$hb, CURLOPT_TIMEOUT, $timeout); // 设置超时时间
        curl_setopt(self::$hb, CURLOPT_HEADER, true); // 将头文件的信息作为数据流输出
        curl_setopt(self::$hb, CURLINFO_HEADER_OUT, true); // 追踪句柄的请求字符串
        if (!empty($headers)) {curl_setopt(self::$hb, CURLOPT_HTTPHEADER, $headers);} // 设置HTTP头信息
        curl_setopt(self::$hb, CURLOPT_NOBODY, false); // 输出body部分
        curl_setopt(self::$hb, CURLOPT_RETURNTRANSFER, true); // 获取的信息以字符串返回
        curl_setopt(self::$hb, CURLOPT_CONNECTTIMEOUT, 0); // 在尝试连接时等待的秒数。设置为0，则无限等待
        curl_setopt(self::$hb, CURLOPT_CUSTOMREQUEST, self::$method); //设置请求方式
        curl_setopt(self::$hb, CURLOPT_DNS_CACHE_TIMEOUT, self::$dns_cache_timeout); //设置DNS缓存时间,减少时延
        self::setExecuteTime(0);   // 设置执行时间为没有限制

        // 设置主体(body)参数
        if (self::$method=="GET") {
            if(strpos(self::$url,'?')){
                self::$url .= http_build_query(self::$params);
            }else{
                self::$url .= '?' . http_build_query(self::$params);
            }
        }else{
            $params = (is_array(self::$params)||is_object(self::$params))?http_build_query(self::$params):self::$params;
            curl_setopt(self::$hb, CURLOPT_POSTFIELDS, self::$multipart?self::$params:$params);
        }

        // 设置URI:URL
        curl_setopt(self::$hb, CURLOPT_URL, self::$url);

        if (1 == strpos('$'.self::$url, "https://")) {
            curl_setopt(self::$hb, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt(self::$hb, CURLOPT_SSL_VERIFYHOST, false);
        }
        // 执行cURL
        $result = curl_exec(self::$hb);
        $t = microtime(true);

        // 结果判断
        if(!curl_errno(self::$hb)){ //if (curl_getinfo(self::$hb, CURLINFO_HTTP_CODE) == '200'){}
            list($response_header, $response_body) = explode("\r\n\r\n", $result, 2);
            LogUtils::httpInfo("[{$t}]Request Headers: ". json_encode($response_header));
            LogUtils::httpInfo("[{$t}]Request Body:".json_encode($response_body));
            $contentType = curl_getinfo(self::$hb, CURLINFO_CONTENT_TYPE);

            $info = curl_getinfo(self::$hb);
            LogUtils::httpInfo("[{$t}]Request TimeTaking[{$data['method']}]: {$info['total_time']} Seconds 发送请求到 {$info['url']}");
            $response = ['code'=>0, 'msg'=>'OK', 'contentType'=>$contentType, 'header'=>$response_header, 'data'=>$response_body, ];
        }else{
            LogUtils::httpError("[{$t}]Request cURL[{$data['method']}] ERROR: " . curl_error(self::$hb)) ;
            $response = ['data'=>(object)['code'=>-1, 'msg'=>"请求 {$url} 出错: curl error: ". curl_error(self::$hb)]];
        }

        // 关闭连接
        curl_close(self::$hb);

        // 返回数据
        return $response;
    }

    /**
     * 设置Header信息
     * @param $data
     * @return array
     */
    private static function setHeaders($data)
    {
        $headers = array();
        if (isset($data['headers'])) {
            foreach ($data['headers'] as $key=>$item) {
                $headers[] = "$key:$item";
            }
        }

        $headers[] = "Expect:"; // libcurl 会将大于1k的数据加上 Expect:100-continue, Client默认去掉
        return $headers;
    }

    /**
     * 执行时间设置
     * @param $second
     */
    protected static function setExecuteTime($second)
    {
        ini_set('max_execution_time',$second);// 秒,0 设置为执行时间没有限制
    }

    /**
     * 数据验证
     * @param $data
     * @throws \Exception
     */
    private static function dataValication($data)
    {
        // url验证
        if(!isset($data['url']) || empty($data['url'])){
            throw new \Exception("HttpClient Error: Uri不能为空", 4422);
        }else{
            self::$url = $data['url'];
        }

        // 参数验证
        if(!isset($data['params']) || empty($data['params'])){
            self::$params = [];
        }else{
            self::$params = $data['params'];
        }

        // 方法设置
        if(!isset($data['method']) || empty($data['method'])){
            self::$method = "POST";
        }else{
            self::$method = $data['method'];
        }
    }
}
