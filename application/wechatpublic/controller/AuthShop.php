<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/10/16
 * Time: 下午2:18
 */
namespace app\wechatpublic\controller;

use app\common\controller\CommonAuth;
use app\common\controller\SendSms;
use think\Db;
use think\Request;
use think\Validate;

class AuthShop extends CommonAuth
{
    /**
     * 发送验证码
     * @param Request $request
     * @return string
     */
    public function sendCode(Request $request)
    {
        $phone = $request->param("phone","");


        return SendSms::sendCode($phone);
    }

    /**
     * 验证验证码
     * @param Request $request
     * @return string
     */
    public function checkCode(Request $request)
    {
        $phone = $request->param("phone","");
        $code  = $request->param("code","");

        return SendSms::checkCode($phone,$code);
    }
}