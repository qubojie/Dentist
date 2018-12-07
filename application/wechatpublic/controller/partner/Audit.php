<?php
/**
 * 审核
 * User: qubojie
 * Date: 2018/11/2
 * Time: 下午12:21
 */
namespace app\wechatpublic\controller\partner;

use app\common\controller\CommonAuth;
use app\common\controller\PartnerCommon;
use app\common\controller\SendSms;
use think\Exception;
use think\Validate;

class Audit extends CommonAuth
{
    /**
     * 绑定注册信息
     * @return array|string
     * @throws \Exception
     */
    public function bindData()
    {
        $name      = $this->request->param('name','');
        $phone     = $this->request->param('phone','');
        $code      = $this->request->param('code','');
        $openid    = $this->request->param('openid','');
        $unionid   = $this->request->param('unionid','');
        $nickname  = $this->request->param('nickname','');
        $sex       = $this->request->param('sex','');
        $headimgurl= $this->request->param('headimgurl','');

        $rule = [
            "name|姓名"    => "require",
            "phone|电话"   => "require|unique:partner",
            "code|验证码"  => "require",
            "openid"      => "require",
            "unionid"     => "require",
            "nickname"    => "require",
            "sex"         => "require",
            "headimgurl"  => "require",
        ];
        $check_data = [
            "name"       => $name,
            "phone"      => $phone,
            "code"       => $code,
            "openid"     => $openid,
            "unionid"    => $unionid,
            "nickname"   => $nickname,
            "sex"        => $sex,
            "headimgurl" => $headimgurl,
        ];

        $validate = new Validate($rule);
        if (!$validate->check($check_data)){
            return comReturn(false,$validate->getError());
        }

        /*验证验证码 On*/
        $is_pp = SendSms::checkCode($phone,$code);
        if (!$is_pp['result']) return $is_pp;
        /*验证验证码 Off*/

        $partnerCommonObj = new PartnerCommon();
        /*检测用户信息  On*/
        $registerRes = $partnerCommonObj->registerNewPartner("$phone","$name","$openid","$unionid","$nickname","$headimgurl","$sex");

        if ($registerRes === false) {
            return comReturn(false,config('return_message.fail'));
        }
        return comReturn(true,config('return_message.success'),$registerRes);
    }

    /**
     * 重新申请
     * @return array|string
     */
    public function editApply()
    {
        $pid   = $this->request->param('pid','');
        $name  = $this->request->param('name','');
        $phone = $this->request->param('phone','');
        $code  = $this->request->param('code','');

        $rule = [
            "name|姓名"    => "require",
            "phone|电话"   => "require|unique_me:partner,pid",
            "code|验证码"  => "require",
            "pid"         => "require"
        ];
        $check_data = [
            "name"       => $name,
            "phone"      => $phone,
            "code"       => $code,
            "pid"        => $pid
        ];

        $validate = new Validate($rule);
        if (!$validate->check($check_data)){
            return comReturn(false,$validate->getError());
        }

        try {
            /*验证验证码 On*/
            $is_pp = SendSms::checkCode($phone,$code);
            if (!$is_pp['result']) return $is_pp;
            /*验证验证码 Off*/

            $partnerCommonObj = new PartnerCommon();

            $params = [
                "phone"       => $phone,
                "name"        => $name,
                "status"      => config("partner.status")['wait_check']['key'],
                "review_time" => "",
                "review_desc" => "",
                "review_user" => "",
                "updated_at"  => time()
            ];

            $res = $partnerCommonObj->updatePartnerInfo($params,$pid);
            if ($res == false) {
                return comReturn(false,config("return_message.fail"));
            }
            return comReturn(true,config("return_message.success"));
        } catch (Exception $e) {
            return comReturn(false, $e->getMessage(), null, 500);
        }
    }
}