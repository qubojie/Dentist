<?php
/**
 * 微信授权
 * User: qubojie
 * Date: 2018/10/16
 * Time: 下午6:06
 */
namespace app\wechatpublic\controller;

use app\common\controller\CommonAuth;
use think\Controller;
use think\Env;

class WechatAuth extends CommonAuth
{
    /**
     * 获取授权信息
     */
    public function getInfo()
    {
        header("Access-Control-Allow-Origin:*");
        $url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=' . Env::get("WECHAT_PUBLIC_APPID") . '&redirect_uri=' . Env::get("WECHAT_PUBLIC_OAUTH_URL") . '&response_type=code&scope=snsapi_userinfo&state=state#wechat_redirect';
        header('location:' . $url);
        exit;
    }

    /**
     * code获取信息
     * @return string
     */
    public function login()
    {
        $code         = $_GET['code'];
        $access_token = $this->getUserAccessToken($code);
        $userInfo     = $this->getUserInfo($access_token);

        return comReturn(true,config('return_message.success'),$userInfo);
    }

    /**
     * code获取accessToken
     * @param $code
     * @return mixed
     */
    private function getUserAccessToken($code)
    {
        $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=".Env::get('WECHAT_PUBLIC_APPID')."&secret=".Env::get('WECHAT_PUBLIC_APPSECRET')."&code=$code&grant_type=authorization_code";

        $res = file_get_contents($url);
        return json_decode($res);
    }

    /**
     * 获取用户信息
     * @param $accessToken
     * @return mixed
     */
    private function getUserInfo($accessToken)
    {
        $url = "https://api.weixin.qq.com/sns/userinfo?access_token=$accessToken->access_token&openid=$accessToken->openid&lang=zh_CN";
        $UserInfo = file_get_contents($url);
        return json_decode($UserInfo, true);
    }


    /**
     * 此AccessToken   与 getUserAccessToken不一样
     * 获得AccessToken
     * @return mixed
     */
    private function getAccessToken()
    {
        // 获取缓存
        $access = cache('access_token');
        // 缓存不存在-重新创建
        if (empty($access)) {
            // 获取 access token
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".Env::get('WECHAT_PUBLIC_APPID')."&secret=".Env::get('WECHAT_PUBLIC_APPSECRET')."";
            $accessToken = file_get_contents($url);

            $accessToken = json_decode($accessToken);
            // 保存至缓存
            $access = $accessToken->access_token;
            cache('access_token', $access, 7000);
        }
        return $access;
    }

    /**
     * 获取JS证明
     * @param $accessToken
     * @return mixed
     */
    private function _getJsapiTicket($accessToken)
    {

        // 获取缓存
        $ticket = cache('jsapi_ticket');
        // 缓存不存在-重新创建
        if (empty($ticket)) {
            // 获取js_ticket
            $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=" . $accessToken . "&type=jsapi";
            $jsTicket = file_get_contents($url);
            $jsTicket = json_decode($jsTicket);
            // 保存至缓存
            $ticket = $jsTicket->ticket;
            cache('jsapi_ticket', $ticket, 7000);
        }
        return $ticket;
    }

    /**
     * 获取JS-SDK调用权限
     */
    public function shareAPi(Request $request)
    {
        header("Access-Control-Allow-Origin:*");
        // 获取accesstoken
        $accessToken = $this->getAccessToken();
        // 获取jsapi_ticket
        $jsapiTicket = $this->_getJsapiTicket($accessToken);

        // -------- 生成签名 --------
        $wxConf = [
            'jsapi_ticket' => $jsapiTicket,
            'noncestr'     => md5(time() . '!@#$%^&*()_+'),
            'timestamp'    => time(),
            'url'          => $request->post('url'),  //这个就是你要自定义分享页面的Url啦
        ];
        $string1 = sprintf('jsapi_ticket=%s&noncestr=%s×tamp=%s&url=%s', $wxConf['jsapi_ticket'], $wxConf['noncestr'], $wxConf['timestamp'], $wxConf['url']);
        // 计算签名
        $wxConf['signature'] = sha1($string1);
        $wxConf['appid'] = $this->appid;
        return json($wxConf);
    }
}