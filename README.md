## License

The project is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT).
### 使用
  加解密默认使用的key为`1234567890A`，若需要调整，请复制config下的ephputils.php到项目的config下，修改对应的配置即可

### 示例
```
<?php

namespace App\Http\Controllers;
use Ejiayou\PHP\Utils\EjyUtils;
use Ejiayou\PHP\Utils\HTTP\HttpUtils;
use Ejiayou\PHP\Utils\Log\LogUtils;
use Ejiayou\PHP\Utils\String\UUID;
use Ejiayou\PHP\Utils\DateUtils;
use Ejiayou\PHP\Utils\EjyFileUtils;
use Ejiayou\PHP\Utils\PickSqlUtils;
use Ejiayou\PHP\Utils\WeChat\WXUtils;

class LaravelTestController extends Controller
{

    public function index(){
//        dd(EjyUtils::encrypt("ejiayou"));
//        dd(EjyUtils::decrypt("51D75290E5A0AFE2F2F6223739E7A88CDDF47789927222AFEA0A06CD9DBE6F1C"));
//        dd(EjyUtils::clientOsType());
//        dd(EjyUtils::createChannelNo(8));
//        dd(EjyUtils::getRealIP());
//        dd(EjyUtils::makeSmsCode());
//        dd(EjyUtils::isHTTPS());
//        dd(EjyUtils::createChannelNo());

//        dd(HttpUtils::curlGet("https://www.baidu.com"));

//        LogUtils::info("这是在测试phputils");

//        dd(UUID::create());

//        dd(DateUtils::dateInfo('SX','1992-09-22'));
//        dd(DateUtils::dateInfo('GZ','1992-09-22'));
//        dd(DateUtils::dateInfo('XZ','1992-09-22'));

//        dd(EjyFileUtils::dataIsMobile([1,2,3,4,5,'asd']));
//        dd(EjyFileUtils::getFileData(['name'=>'test.xlsx','tmp_name'=>'C:\Users\Administrator\Desktop\test.xlsx']));

//        dd(PickSqlUtils::pickTime("select * from `test` where ctime >= '2018-01-08 12:00:00' "));

        $test_wx = new WXUtils('test');
//        $at = $test_wx->getAccessToken();
//        dd($at);
//        $wp = $test_wx->getWeixinParams();
//        dd($wp);
        dd($test_wx->getUserInfo('oTrMfxOyljdh84HCOXiNOfqUfJmI'));
        
//        $data = [
//            'headers'=>["Content-Type:application"=>"application/json", "X-HTTP-Method-Override"=>"GET", "Request_id"=>"TEST"],
//            'url'=>'https://www.baidu.com',
//            'timeout'=>3
//        ];
//        $http_data = HttpBuilder::Get($data);
//        dd($http_data);
        
//        $data = [
//            'headers'=>["Content-Type:application"=>"application/json"],
//            'url'=>'https://www.baidu.com',
//            'timeout'=>3,
//            'params'=> [
//                'user'=>"username:test"
//            ]
//        ];
//        $http_data = HttpBuilder::Get($data);
//        dd($http_data);

    }
}
```
### 建议
- 微信这块我们没有特殊的处理，目前的调用方式暂时不变，后面如果有必要的话可参考使用 overture （安正超）的 easywechat
