<?php

/**
 * 管理员登录
 * @Author: zhangtao
 * @Date:   2018-10-18 11:39:00
 * @Last Modified by:   admin
 * @Last Modified time: 2018-12-04 14:51:49
 */
namespace app\shopadmin\controller;

use app\shopadmin\model\ShopAdmin;
use think\Controller;
use think\Db;
use think\Exception;
use think\Request;
use think\Validate;
use think\Cache;
use app\common\controller\SendSms;

class Login extends Controller
{
    /**
     * 管理员用户密码登录
     *
     */
    public function login(Request $request)
    {
        $username = $request->param("username","");
        $password  = $request->param("password","");

        try{
            $rule = [
                "username|账号" => "require",
                "password|密码"  => "require"
            ];

            $request_res = [
                "username" => $username,
                "password"  => $password
            ];

            $validate = new Validate($rule);

            if (!$validate->check($request_res)){
                return comReturn(false,$validate->getError(), '', 500);
            }


            $ip = $request->ip();

            $password = jmPassword($password);//密码加密

            $shopAdminModel = new ShopAdmin();

            //当店主手机号与店长用户名重复，优先进店主端
            $is_exist =  $shopAdminModel
                ->alias("sa")
                ->join("dts_shop_enterprise se","sa.eid = se.eid", "LEFT")
                ->where("sa.phone",$username)
                ->where("sa.password",$password)
                ->where("sa.type",0)
                ->where("sa.status",0)
                ->field("sa.aid,sa.eid,sa.sid,sa.type,sa.status,sa.avatar,sa.username,sa.phone,sa.register_way,sa.register_ver,sa.lastlogin_way,sa.lastlogin_time,sa.remember_token,sa.created_at,sa.updated_at")
                ->field("se.e_name")
                ->find();
            if (!$is_exist) {
                $is_exist =  $shopAdminModel
                    ->alias("sa")
                    ->join("dts_shop_enterprise se","sa.eid = se.eid", "LEFT")
                    ->join("dts_shop s","s.sid = sa.sid", "LEFT")
                    ->where("sa.username",$username)
                    ->where("sa.password",$password)
                    ->where("sa.type",1)
                    ->where("sa.status",0)
                    ->field("sa.aid,sa.eid,sa.sid,sa.type,sa.status,sa.avatar,sa.username,sa.phone,sa.register_way,sa.register_ver,sa.lastlogin_way,sa.lastlogin_time,sa.remember_token,sa.created_at,sa.updated_at")
                    ->field("se.e_name")
                    ->field("s.shop_name,s.status shop_status")
                    ->find();
            }

            if ($is_exist){
                $aid = $is_exist['aid'];

                //变更token,并返回token

                $remember_token = jmToken($password);//生成Token

                //更新token
                $save_data = [
                    "remember_token" => $remember_token,
                    "lastlogin_ip"   => $ip,
                    "lastlogin_time" => time(),
                    "token_lastime"  => time()
                ];

                Db::startTrans();
                $is_ok = $shopAdminModel
                    ->where("aid",$aid)
                    ->update($save_data);
                if ($is_ok !== false){
                    Db::commit();
                    $is_exist['remember_token'] = $remember_token;
                    $is_exist['lastlogin_ip'] = $ip;

                    return comReturn(true,config('login.success'),$is_exist);
                }else{
                    Db::rollback();
                    return comReturn(false,config('login.fail'), '', 500);
                }
            }else{
                return comReturn(false,config("return_message.error_status_code")['login_fail']['value'], '', config("return_message.error_status_code")['login_fail']['key']);
            }
        }catch (Exception $e){
            return comReturn(false, $e->getMessage(), '', 500);
        }

    }


    /*
     * 刷新token
     * @return 新token
     * */
    public function refresh_token()
    {
        $Authorization = Request::instance()->header("Token","");

        if (empty($Authorization)){

            return comReturn(false,config("PARAM_NOT_EMPTY"));

        }

        try{
            $shopAdminModel = new ShopAdmin();

            $new_token = jmToken($Authorization);

            $update_date = [
                "remember_token" => $new_token,
                "token_lastime" => time()
            ];

            Db::startTrans();
            $is_exist = $shopAdminModel
                ->where("remember_token",$Authorization)
                ->update($update_date);
            if ($is_exist){
                Db::commit();
                return comReturn(true,config("OPERATE_SUCCESS"),$new_token);
            }else{
                Db::rollback();
                return comReturn(false,config("OPERATE_FAIL"));
            }
        }catch (Exception $e){
            return omReturn(false,$e->getMessage());
        }
    }

    public function sendSms(Request $request){
        $phone = $request->param("phone","");

        $res = SendSms::send($phone,'【牙闺蜜】您请求的手机验证码为 %$code%，如非本人操作，请及时反馈在线客服。');
        if (!$res){
            return comReturn(false,config("sms.send_fail"));
        }

        return comReturn(true,config("sms.send_success"));
    }

    public function resetPassword(Request $request){
        $phone    = $request->param("phone","");
        $code     = $request->param("code","");//验证码
        $password = $request->param("password", "");

        try{
            //规则验证
            $rule = [
                "phone|电话"                  => "require|regex:1[3-8]{1}[0-9]{9}",
                "code|验证码"                 => "require|number|length:4",
                "password|密码"               => "require",
            ];
            $check_data = [
                "phone"               => $phone,
                "code"                => $code,
                "password"            => $password,
            ];

            $validate = new Validate($rule);
            if (!$validate->check($check_data)){
                return comReturn(false,$validate->getError(), '', 500);
            }

            $shopAdminModel = new ShopAdmin();

            //判断是否有此用户
            $is_exist = $shopAdminModel
                        ->where("phone", $phone)
                        ->find();

            if (!$is_exist) {
                return comReturn(false,config("shop_admin.has_not_user"));
            }

            //判断是否为店主
            $is_exist = $shopAdminModel
                        ->where("phone", $phone)
                        ->where("type", 0)
                        ->find();

            if (!$is_exist) {
                return comReturn(false,config("shop_admin.is_not_super_admin"));
            }

            $send_code = Cache::get("sms_verify_code_".$phone);

            if ($send_code && $send_code == $code) {
                $shopadmin['password'] = jmPassword($password);

                $res = $shopAdminModel
                        ->where("phone", $phone)
                        ->where("type", 0)
                        ->update($shopadmin);

                if (!$res) {
                    return comReturn(false,config("return_message.fail"));
                }
                return comReturn(true,config("return_message.success"));

            }else{
                return comReturn(false,config("sms.verify_fail"));
            }
        }catch (Exception $e){
            return comReturn(false, $e->getMessage(), '', 500);
        }

    }
}