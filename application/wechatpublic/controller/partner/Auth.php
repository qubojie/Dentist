<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/11/2
 * Time: 上午10:46
 */
namespace app\wechatpublic\controller\partner;

use app\common\controller\CommonAuth;
use app\common\controller\PartnerCommon;
use app\common\controller\UserCommon;
use app\wechatpublic\controller\WechatAuth;
use think\Db;
use think\Exception;
use think\Validate;

class Auth extends CommonAuth
{
    /**
     * 授权登陆
     * @return array
     */
    public function authLogin()
    {
        $code    = $this->request->param("code","");//code
        $unionid = $this->request->param("unionid","");//code

        /*$rule = [
            "code" => "require",
        ];
        $check_data = [
            "code" => $code,
        ];

        $validate = new Validate($rule);
        if (!$validate->check($check_data)){
            return comReturn(false,$validate->getError());
        }*/

        try {
           if (!empty($code)) {
               /*根据code获取授权信息 On*/
               $wechatAuthObj = new WechatAuth();
               $access_token  = $wechatAuthObj->getUserAccessToken($code);
               if (!empty($access_token->errcode)){
                   return comReturn(false,$access_token->errmsg);
               }
               $userInfo      = $wechatAuthObj->getUserInfo($access_token);

               $openid        = $userInfo['openid'];
               $unionid       = $userInfo['unionid'];
               $nickname      = $userInfo['nickname'];
               $sex           = $userInfo['sex'];
               $avatar        = $userInfo['headimgurl'];
               /*根据code获取授权信息 Off*/
           }else{
               $userInfo = [];
           }

            $partnerCommonObj = new PartnerCommon();

            /*检测用户信息  On*/
            $partnerInfo = $partnerCommonObj->wxidGetPartnerInfo($unionid);
            if (empty($partnerInfo)){
                //注册用户
                //$registerRes = $partnerCommonObj->registerNewPartner("$openid","$unionid","$nickname","$avatar","$sex");
                return comReturn(true,config('register.new_partner'),$userInfo);

            }else{
                $remember_token = jmToken(time().$unionid);
                $partnerInfo['remember_token'] = $remember_token;
                $partnerInfo['token_lastime']  = time();

                $params = [
                    "remember_token" => $remember_token,
                    "token_lastime"  => time()
                ];
                $pid           = $partnerInfo['pid'];
                $userCommonObj = new UserCommon();
                $res           = $userCommonObj->updatePartnerInfo($params,$pid);
                if ($res == false){
                    return comReturn(false,config('return_message.fail'));
                }
                //老用户,返回注册信息
                return comReturn(true,config('return_message.success'),$partnerInfo);
            }
            /*检测用户信息  Off*/
        } catch (Exception $e) {
            return comReturn(false, $e->getMessage(), null, 500);
        }
    }
}