<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/11/5
 * Time: 上午11:45
 */
namespace app\common\controller;

use think\Controller;
use think\Env;

class WxQrCode extends Controller
{
    /**
     * 生成二维码
     * @param $value
     * @return array|bool|mixed|string
     * @throws \Exception
     */
    public function create($value)
    {
        if (empty($value)) {
            return comReturn(false,config('return_message.fail'));
        }

        $delimiter = config("qr_code.delimiter")['key'];

        $prefix    = config("qr_code.prefix")['0']['key'];

        $page      = config("qr_code.page")['key'];

        $width     = config("qr_code.width")['key'];
        $is_hyaline= config("qrcode.is_hyaline")['key'];

        $postParams = [
            "scene"      => $prefix.$delimiter.$value,
            "page"       => $page,
            "width"      => $width,
            "auto_color" => false,
            "is_hyaline" => $is_hyaline
        ];

        $postParams = json_encode($postParams);

        $ACCESS_TOKEN = $this->getAccessToken();

        $res = $this->requestPost($ACCESS_TOKEN,$postParams);

        //  设置文件路径和文件前缀名称
        $path = __DIR__."/../../../public/upload/xcx_qr_code/";

        is_dir($path) OR @mkdir($path,0777,true);

        $name = $value;

        file_put_contents($path.$name.'.png',$res);

        $src = __PUBLIC__."upload/xcx_qr_code/".$name.'.png';

        $prefix = 'partner_source';

        $qiNiuObj = new QiNiuUpload();

        $res = $qiNiuObj->serverUpload("$value","$prefix","$src");

        return $res;
    }

    public function getAccessToken()
    {
        $data   = json_decode(file_get_contents("xcx_access_token.json"));
        $appid  = Env::get("WECHAT_XCX_APPID");
        $secret = Env::get("WECHAT_XCX_APPSECRET");

        if ($data->expire_time < time()) {
            // 如果是企业号用以下URL获取access_token
            // $url = "https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid=$this->appId&corpsecret=$this->appSecret";

            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$appid."&secret=".$secret."";
            $res = json_decode($this->httpGet($url));
            $access_token = $res->access_token;
            if ($access_token) {
                $data->expire_time = time() + 7000;
                $data->access_token = $access_token;
                $fp = fopen("xcx_access_token.json", "w");
                fwrite($fp, json_encode($data));
                fclose($fp);
            }
        } else {
            $access_token = $data->access_token;
        }
        return $access_token;
    }

    /**
     * 模拟post接口请求,获取二维码
     *
     * @param $ACCESS_TOKEN
     * @param array $curlPost
     * @return bool|mixed
     */
    public function requestPost($ACCESS_TOKEN,$curlPost = array())
    {
        $postUrl = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=$ACCESS_TOKEN";

        if (empty($postUrl) || empty($curlPost)) {
            return false;
        }

        $ch = curl_init();//初始化curl
        curl_setopt($ch, CURLOPT_URL,$postUrl);//抓取指定网页
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
        $data = curl_exec($ch);//运行curl
        curl_close($ch);

        return $data;
    }

    private function httpGet($url) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 500);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_URL, $url);

        $res = curl_exec($curl);
        curl_close($curl);

        return $res;
    }
}