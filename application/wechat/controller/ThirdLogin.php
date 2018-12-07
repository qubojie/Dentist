<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/10/29
 * Time: 下午6:29
 */
namespace app\wechat\controller;

use app\common\controller\CommonAuth;
use app\common\controller\UserCommon;
use app\wechat\model\User;
use think\Env;
use think\Exception;
use think\Log;

class ThirdLogin extends CommonAuth
{
    /**
     * 微信三方授权登陆
     * @return array
     */
    public function wechatLogin()
    {
        $code       = $this->request->param('code','');

        $nickname   = $this->request->param('nickname','');

        $headimgurl = $this->request->param('headimgurl','');

        $pid        = $this->request->param('pid','');//推荐人id

        try {
            $userInfo = $this->getOpenId($code);

            if (isset($userInfo['errcode'])){
                return comReturn(false,$userInfo['errmsg']);
            }

            $openid  = $userInfo['openid'];
//            $unionid = $userInfo['unionid'];

            $userModel = new User();

            $is_exist = $userModel
                ->where('mp_openid',$openid)
                ->find();

            $is_exist = json_decode(json_encode($is_exist));

            $token = jmToken(generateReadableUUID("QBJ"));

            if (!empty($is_exist)){
                //查询当前用户是否已经有完成订单,如果没有,则可进行推荐人绑定
                $uid = $is_exist->uid;

                $params = [
                    'nickname'       => $nickname,
                    'avatar'         => $headimgurl,
                    'lastlogin_time' => time(),
                    'updated_at'     => time(),
                    'remember_token' => $token,
                    "token_lastime"  => time()
                ];

                $userCommonObj = new UserCommon();
                if (!empty($pid)) {
                    //查询是否已有推荐人
                    $have_partner = $userCommonObj->checkUserHavePartner($uid);
                    if (!$have_partner){
                        //如果没有推荐人
                        //查询是否有已消费订单
                        $is_exist = $userCommonObj->checkUserHaveOrderInfo($uid);
                        if (!$is_exist){
                            //如果没有消费订单,则绑定推荐人信息
                            $params['pid'] = $pid;
                        }
                    }
                }

                $res = $userModel
                    ->where('mp_openid',$openid)
                    ->update($params);
            }else{
                //新增
                $params = [
                    "uid"            => generateReadableUUID("U"),
                    "wxid"           => "",
                    "mp_openid"      => $openid,
                    "nickname"       => $nickname,
                    "avatar"         => $headimgurl,
                    "register_way"   => 'wxapp',
                    "register_time"  => time(),
                    "lastlogin_time" => time(),
                    "pid"            => $pid,
                    "remember_token" => $token,
                    "token_lastime"  => time(),
                    "created_at"     => time(),
                    "updated_at"     => time(),
                ];

                $res = $userModel
                    ->insert($params);
            }

            $userInfo = $userModel
                ->where('mp_openid',$openid)
                ->find();

            if ($res !== false){
                return comReturn(true,config('return_message.success'),$userInfo);
            }else{
                return comReturn(false,config('return_message.fail'));
            }
        } catch (Exception $e) {
            return comReturn(false, $e->getMessage(), null, 500);
        }
    }


    public function getJmInfo()
    {
        $encryptedData = $this->request->param("encryptedData","");
        $iv            = $this->request->param("iv","");
        $code         = $this->request->param("code","");

        try {
            $userCommonObj = new UserCommon();
            $userInfo = $this->getOpenId($code);

            if (isset($userInfo['errcode'])){
                return comReturn(false,$userInfo['errmsg']);
            }

            Log::info("获取的的密文".var_export($userInfo,true));

            $openid     = $userInfo['openid'];
            $sessionKey = $userInfo['session_key'];

            $res = $userCommonObj->getUserWxUniqueId("$sessionKey","$encryptedData","$iv");

            return comReturn(true,config("success"),$res);
        } catch (Exception $e) {
            return comReturn(false, $e->getMessage(), null, 500);
        }
    }


    public function getOpenId($code)
    {
        $Appid  = Env::get("WECHAT_XCX_APPID");
        $Secret = Env::get("WECHAT_XCX_APPSECRET");
        $url    = 'https://api.weixin.qq.com/sns/jscode2session?appid='.$Appid.'&secret='.$Secret.'&js_code=' . $code . '&grant_type=authorization_code';
        $info   = $this->vget($url);
        $info   = json_decode($info,true);//对json数据解码
        return $info;
    }

    public function vget($url)
    {
        $info=curl_init();
        curl_setopt($info,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($info,CURLOPT_HEADER,0);
        curl_setopt($info,CURLOPT_NOBODY,0);
        curl_setopt($info,CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($info,CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($info,CURLOPT_URL,$url);
        $output= curl_exec($info);
        curl_close($info);
        return $output;
    }
}