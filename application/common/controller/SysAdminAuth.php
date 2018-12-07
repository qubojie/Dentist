<?php

/**
 * 系统管理继承方法
 * @Author: zhangtao
 * @Date:   2018-10-18 11:15:59
 * @Last Modified by:   admin
 * @Last Modified time: 2018-12-05 17:37:45
 */
namespace app\common\controller;

use app\admin\model\SysAdminUser;
use app\admin\model\SysAdminLog;
use think\Controller;
use think\exception\HttpException;
use think\Log;
use think\Request;
use think\Db;

class SysAdminAuth extends Controller
{
    /**
     * 初始化方法,其余控制器继承此方法，进行判断登录(token)
     * @
     */
    public function _initialize()
    {
        parent::_initialize();
        $method = Request::instance()->method();

        if ( $method != "OPTIONS"){

            $Token = Request::instance()->header("Token","");

            if (!empty($Token)){

                $sysModel = new SysAdminUser();
                $is_exist = $sysModel
                    ->where("remember_token",$Token)
                    ->count();

                if ($is_exist){

                }else{
                    exit(json_encode(comReturn(false, config("return_message.error_status_code")['token_invalid']['value'], '', config("return_message.error_status_code")['token_invalid']['key']),JSON_UNESCAPED_UNICODE));
                    // throw new HttpException(403,'登陆失效');
                }

            }else{
                exit(json_encode(comReturn(false, config("return_message.error_status_code")['token_invalid']['value'], '', config("return_message.error_status_code")['token_invalid']['key']),JSON_UNESCAPED_UNICODE));
                // throw new HttpException(403,'登陆失效');
            }
        }
    }

    /**
     * 根据token获取管理员信息
     * @param $token
     * @return array|false|null|\PDOStatement|string|\think\Model
     */
    public function tokenGetAdminInfo($token)
    {
        $sysAdminModel = new SysAdminUser();

        $res = $sysAdminModel
                ->field('id,user_name,avatar,phone,last_ip,phone')
                ->where('remember_token',$token)
                ->where('is_delete', 0)
                ->find();

        if (!empty($res)){
            return $res;
        }else{
            return null;
        }
    }

    /**
     * 返回状态信息
     * @param $module
     * @return array|false
     */
    public function getStatus($module)
    {
        if ($module == "order") {
            $res = config("order.order_status");
        }else if($module == "enterprise"){
            $res = config("enterprise.status");
        }else if($module == "partner"){
            $res = config("partner.status");
            foreach ($res as $key => $value) {
                $res2[] = $value;
            }
            $res = $res2;
        }else if($module == "withdrawals"){
            $res = config("account.withdrawal_status");
            foreach ($res as $key => $value) {
                $res2[] = $value;
            }
            $res = $res2;
        }

        if (!empty($res)){
            return $res;
        }else{
            return null;
        }
    }

    /**
     * 获取第N天时间戳
     * @param n 第N天
     * @param time 时:分:秒
     * @param sign 1加法 2减法
     */
    public function getNTime($n, $time = "00:00:00", $sign = 2)
    {
        if ($sign == 1) {
            $res = strtotime(date('Y-m-d '.$time,time()+$n*24*60*60));
        }else{
            $res = strtotime(date('Y-m-d '.$time,time()-$n*24*60*60));
        }

        return $res;
    }


    /**
     * 返回日志信息
     * @param list 列表
     * @param time 时:分:秒
     * @param sign 1加法 2减法
     */
    public function getSysAdminLog($list, $field){
        $logModel = new SysAdminLog();
        foreach ($list as $key => $value) {
            $log_list = [];
            $log_list = $logModel
                        ->where('id', $value[$field])
                        ->field("log_id,id,action,reason,action_user,action_time")
                        ->order("action_time DESC")
                        ->select();
            foreach ($log_list as $k => $v) {
                $log_list[$k]['action_name'] = config("log.sys_admin_log_action")[$v['action']];

                $log_list[$k]['reason'] = htmlspecialchars_decode($v['reason']);
            }
            $list[$key]['admin_log'] = $log_list;
        }
        return $list;
    }


}