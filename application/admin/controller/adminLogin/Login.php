<?php

/**
 * 管理员登录
 * @Author: zhangtao
 * @Date:   2018-10-18 11:39:00
 * @Last Modified by:   admin
 * @Last Modified time: 2018-11-29 16:04:36
 */
namespace app\admin\controller\adminLogin;

use app\admin\model\SysAdminUser;
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
        $user_name = $request->param("user_name","");
        $password  = $request->param("password","");

        try{
            $rule = [
                "user_name|账号" => "require",
                "password|密码"  => "require"
            ];

            $request_res = [
                "user_name" => $user_name,
                "password"  => $password
            ];

            $validate = new Validate($rule);

            if (!$validate->check($request_res)){
                return comReturn(false,$validate->getError());
            }

            $ip = $request->ip();

            $password = jmPassword($password);//密码加密

            $sysAdminModel = new SysAdminUser();

            $is_exist =  $sysAdminModel
                ->where("user_name",$user_name)
                ->where("password",$password)
                ->where("is_delete",0)
                ->field("id,user_name,avatar,phone,email,created_at,updated_at")
                ->find();

            if ($is_exist){
                $id = $is_exist['id'];

                //变更token,并返回token

                $remember_token = jmToken($password);//生成Token


                //更新token
                $save_data = [
                    "remember_token" => $remember_token,
                    "last_ip"        => $ip,
                    "updated_at"     => time()
                ];

                Db::startTrans();
                $is_ok = $sysAdminModel
                    ->where("id",$id)
                    ->update($save_data);
                if ($is_ok !== false){
                    Db::commit();
                    $is_exist['remember_token'] = $remember_token;
                    $is_exist['last_ip'] = $ip;

                    return comReturn(true,config('login.success'),$is_exist);
                }else{
                    Db::rollback();
                    return comReturn(false,config('login.fail'));
                }
            }else{
                return comReturn(false,config('login.fail_re'));
            }
        }catch (Exception $e){
            return comReturn(false,$e->getMessage(), '', 500);
        }

    }


    /*
     * 刷新token
     * @return 新token
     * */
    public function refresh_token()
    {
        $Authorization = Request::instance()->header("Token","");

        try{
            if (empty($Authorization)){

                return comReturn(false,config("PARAM_NOT_EMPTY"));

            }

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
            return comReturn(false,$e->getMessage(), '', 500);
        }
    }

}