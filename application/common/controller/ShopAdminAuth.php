<?php

/**
 * 判断Token继承方法
 * @Author: zhangtao
 * @Date:   2018-10-18 11:15:59
 * @Last Modified by:   admin
 * @Last Modified time: 2018-12-05 17:37:13
 */
namespace app\common\controller;

use app\shopadmin\model\ShopAdmin;
use app\shopadmin\model\Shop;
use app\shopadmin\model\ShopImage;
use think\Controller;
use think\exception\HttpException;
use think\Log;
use think\Request;
use think\Db;

class ShopAdminAuth extends Controller
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

                $shopAdminModel = new ShopAdmin();
                $is_exist = $shopAdminModel
                    ->where("remember_token",$Token)
                    ->field('token_lastime')
                    ->find();

                if (!empty($is_exist)){
                    $time = time();//当前时间
                    $token_lastime = $is_exist['token_lastime'];//上次刷新token时间

                    $over_time = $token_lastime + 86400000;   //过期时间 1天
                    if ($time > $over_time){
                        exit(json_encode(comReturn(false, config("return_message.error_status_code")['token_overdue']['value'], '', config("return_message.error_status_code")['token_overdue']['key']),JSON_UNESCAPED_UNICODE));
                    }
                }else{
                    exit(json_encode(comReturn(false, config("return_message.error_status_code")['token_invalid']['value'], '', config("return_message.error_status_code")['token_invalid']['key']),JSON_UNESCAPED_UNICODE));

                }

            }else{
                exit(json_encode(comReturn(false, config("return_message.error_status_code")['token_invalid']['value'], '', config("return_message.error_status_code")['token_invalid']['key']),JSON_UNESCAPED_UNICODE));

            }
        }
    }

    /**
     * 根据token获取管理员信息
     * @param $token
     * @return array|false|null|\PDOStatement|string|\think\Model
     */
    public function tokenGetAdmin($token)
    {
        $shopAdminModel = new ShopAdmin();

        $res = $shopAdminModel
                ->where('remember_token',$token)
                ->field('sid,eid,name,type,username,phone')
                ->find();

        if (!empty($res)){
            if ($res['type'] == 0) {
                $res['log_name'] = $res['phone'];
            }else if(empty($res['name'])){
                $res['log_name'] = $res['username'];
            }else{
                $res['log_name'] = $res['name'];
            }
            return $res;
        }else{
            return null;
        }
    }

    /**
     * 根据token获取首页信息(超级管理员专用)
     * @param $token
     * @return array|false|null|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function tokenGetList($token)
    {
        $shopAdminModel = new ShopAdmin();

        // $column = $shopAdminModel->column;

        $shopadmin = $shopAdminModel
            ->where('remember_token',$token)
            ->where('type', 0)
            ->field('eid')
            ->find();

        $res = $shopAdminModel
                ->alias('sa')
                ->join('dts_shop s','sa.sid = s.sid')
                ->field('sa.aid,sa.eid,sa.sid,sa.type')
                ->field('s.shop_name,s.account_balance')
                // ->where('s.status = 0')
                ->where('s.eid', $shopadmin['eid'])
                ->order("s.created_at")
                ->select();

        if (!empty($res)){

            $decimal = $this->getSettingInfo("sys_show_number_decimal")['sys_show_number_decimal'];

            //提取商铺图片
            $imageModel = new ShopImage();
            foreach ($res as $key => $value) {

                $shop_cover_image = '';
                $shop_image = '';

                $image_arr = $imageModel
                             ->field('image')
                             ->where('sid', $value['sid'])
                             ->order("sort")
                             ->select();
                $shop_image = '';
                foreach ($image_arr as $k => $v) {
                    $shop_image .= $v['image'];
                    if ($k+1 != count($image_arr)) {
                        $shop_image .= ',';
                    }
                    if ($k == 0) {
                        $shop_cover_image = $v['image'];
                    }
                }
                $res[$key]['shop_cover_image'] = $shop_cover_image;
                $res[$key]['shop_image']       = $shop_image;

                //格式化小数点
                $res[$key]['account_balance'] = sprintf("%.".$decimal."f", $value['account_balance']);

            }

            return $res;
        }else{
            return null;
        }
    }


    /**
     * 根据token判断是否有操作权限
     * @param $token,$sid
     * @return return|false
     */
    public function tokenJudgeAuth($token, $sid)
    {
        if (empty($sid)) {
            return false;
        }

        $shopAdminModel = new ShopAdmin();
        $shopModel = new Shop();

        $shopadmin = $shopAdminModel
            ->where('remember_token',$token)
            ->field('sid,eid,type')
            ->find();

        $res = false;
        if ($shopadmin['type'] == 0) {
            $shopList = $shopModel
                        ->field('sid')
                        ->where('eid', $shopadmin['eid'])
                        ->select();
            foreach ($shopList as $key => $value) {
                if ($value['sid'] == $sid) {
                    $res = true;
                }
            }

        }else{
            if ($sid == $shopadmin['sid']) {
                $res = true;
            }
        }

        if ($res){
            return true;
        }else{
            return false;
        }
    }


    /**
     * 判断是否有
     * @param shop_name
     */
    public function checkField($dbName, $field, $param)
    {
        $res = Db::name($dbName)
            ->where($field,$param)
            ->find();

        $res = json_decode(json_encode($res),true);
        return $res;
    }

    /**
     * 获取第N天时间戳
     * @param n 第N天
     * @param time 时:分:秒
     * @param sign 1加法 2减法
     * @return string
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

}