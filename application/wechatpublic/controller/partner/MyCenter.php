<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/11/2
 * Time: 下午3:57
 */
namespace app\wechatpublic\controller\partner;

use app\common\controller\PartnerAuth;
use app\common\controller\PartnerCommon;
use app\common\controller\SendSms;
use think\Validate;

class MyCenter extends PartnerAuth
{
    /**
     * 我的信息
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function myInfo()
    {
        $token  =$this->request->header('Token','');
        $partnerInfo = $this->tokenGeyPartnerInfo($token);

        return comReturn(true,config('return_message.success'),$partnerInfo);
    }

    /**
     * 绑定手机号码
     * @return array|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function bindPhone()
    {
        $token = $this->request->header('Token','');
        $phone = $this->request->param('phone','');
        $code  = $this->request->param('code','');

        $rule = [
            "phone|手机号码" => "require|regex:1[0-9]{1}[0-9]{9}",
            "code|验证码"    => "require",
        ];
        $check_data = [
            "phone"  => $phone,
            "code"   => $code,
        ];
        $validate = new Validate($rule);
        if (!$validate->check($check_data)){
            return comReturn(false,$validate->getError());
        }

        /*验证验证码 On*/
        $is_pp = SendSms::checkCode($phone,$code);
        if (!$is_pp['result']) return $is_pp;
        /*验证验证码 Off*/

        /*获取登陆用户信息 On*/
        $partnerInfo = $this->tokenGeyPartnerInfo($token);
        if (empty($partnerInfo)){
            return comReturn(false,config('return_message.abnormal_action'));
        }
        $pid = $partnerInfo['pid'];
        /*获取登陆用户信息 Off*/

        /*判断手机号码是否已绑定 On*/
        $isOnly = $this->checkPhone("$pid","$phone");
        if (!$isOnly){
            return comReturn(false,config('return_message.phone_exist'));
        }
        /*判断手机号码是否已绑定 Off*/

        /*更新用户信息 On*/
        $params = [
            "phone"      => $phone,
            "password"   => jmPassword(config('default_password')),
            "updated_at" => time()
        ];
        $partnerCommonObj = new PartnerCommon();
        $updatePartnerInfoRes = $partnerCommonObj->updatePartnerInfo($params,$pid);
        if (!$updatePartnerInfoRes){
            return comReturn(false,config('return_message.abnormal_action'));
        }
        /*更新用户信息 Off*/

        return comReturn(true,config('return_message.success'));
    }

    /**
     * 手机号码解除绑定
     * @return array|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function unbindPhone()
    {
        $token = $this->request->header('Token','');
        $phone = $this->request->param('phone','');
        $code  = $this->request->param('code','');

        $rule = [
            "phone|手机号码" => "require|regex:1[0-9]{1}[0-9]{9}",
            "code|验证码"    => "require",
        ];
        $check_data = [
            "phone"  => $phone,
            "code"   => $code,
        ];
        $validate = new Validate($rule);
        if (!$validate->check($check_data)){
            return comReturn(false,$validate->getError());
        }

        /*验证验证码 On*/
        $is_pp = SendSms::checkCode($phone,$code);
        if (!$is_pp['result']) return $is_pp;
        /*验证验证码 Off*/

        /*获取登陆用户信息 On*/
        $partnerInfo = $this->tokenGeyPartnerInfo($token);
        if (empty($partnerInfo)){
            return comReturn(false,config('return_message.abnormal_action'));
        }
        $pid = $partnerInfo['pid'];
        /*获取登陆用户信息 Off*/

        /*更新用户信息 On*/
        $params = [
            "phone"      => "",
            "password"   => "",
            "updated_at" => time()
        ];
        $partnerCommonObj = new PartnerCommon();
        $updatePartnerInfoRes = $partnerCommonObj->updatePartnerInfo($params,$pid);
        if (!$updatePartnerInfoRes){
            return comReturn(false,config('return_message.abnormal_action'));
        }
        /*更新用户信息 Off*/

        return comReturn(true,config('return_message.success'));

    }
}