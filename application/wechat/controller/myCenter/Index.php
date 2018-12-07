<?php
/**
 *我的.
 * User: qubojie
 * Date: 2018/10/22
 * Time: 下午3:35
 */
namespace app\wechat\controller\myCenter;

use app\common\controller\SendSms;
use app\common\controller\UserCommon;
use app\common\controller\WechatAuth;
use think\Validate;

class Index extends WechatAuth
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
        $token    = $this->request->header('Token','');
        $userInfo = $this->tokenGetUserInfo($token);
        $uid      = $userInfo->uid;

        /*获取是否有待使用或待处理的订单标记 On*/
        $userCommonObj = new UserCommon();
        $haveDealOrderRes = $userCommonObj->getDealOrderNum($uid);
        /*获取是否有待使用或待处理的订单标记 Off*/

        $userInfo->order_num = $haveDealOrderRes;

        return comReturn(true,config('return_message.success'),$userInfo);

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
        $name  = $this->request->param('name','');

        $rule = [
            "phone|手机号码" => "require|unique_me:user|regex:1[0-9]{1}[0-9]{9}",
            "name|名称"     => "require",
            "code|验证码"    => "require",
        ];
        $check_data = [
            "name"   => $name,
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
        $userInfo = $this->tokenGetUserInfo($token);
        if (empty($userInfo)){
            return comReturn(false,config('return_message.abnormal_action'));
        }
        $uid = $userInfo->uid;
        /*获取登陆用户信息 Off*/

        /*判断手机号码是否已绑定 On*/
        $isOnly = $this->checkPhoneIsOnly($phone,$uid);
        if (!$isOnly){
            return comReturn(false,config('return_message.phone_exist'));
        }
        /*判断手机号码是否已绑定 Off*/

        /*更新用户信息 On*/
        $params = [
            "phone"      => $phone,
            "name"       => $name,
            "password"   => jmPassword(config('default_password')),
            "updated_at" => time()
        ];
        $userCommonObj = new UserCommon();
        $updateUserInfoRes = $userCommonObj->updateUserInfo($params,$uid);
        if (!$updateUserInfoRes){
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
        $userInfo = $this->tokenGetUserInfo($token);
        if (empty($userInfo)){
            return comReturn(false,config('return_message.abnormal_action'));
        }
        $uid = $userInfo->uid;
        /*获取登陆用户信息 Off*/

        /*更新用户信息 On*/
        $params = [
            "phone"      => "",
            "password"   => "",
            "updated_at" => time()
        ];
        $userCommonObj = new UserCommon();
        $updateUserInfoRes = $userCommonObj->updateUserInfo($params,$uid);
        if (!$updateUserInfoRes){
            return comReturn(false,config('return_message.abnormal_action'));
        }
        /*更新用户信息 Off*/

        return comReturn(true,config('return_message.success'));

    }
}