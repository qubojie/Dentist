<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/11/2
 * Time: 下午12:22
 */
namespace app\common\controller;

use app\wechatpublic\model\Partner;
use think\Request;

class PartnerAuth extends CommonAuth
{

    /**
     * 初始化方法,其余控制器继承此方法，进行判断登录
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    /*public function _initialize()
    {
        parent::_initialize();
        $method = Request::instance()->method();

        if ( $method != "OPTIONS"){
            $token = Request::instance()->header("Token","");
            $partnerModel = new Partner();
            $is_true = $partnerModel
                ->where('remember_token',$token)
                ->find();
            $is_true = json_decode(json_encode($is_true),true);
            if (empty($is_true)){
                abort('403','登陆失效');
            }
            $over_time = $is_true['token_lastime'] + (24 * 60 * 60);
            if ($over_time < time()) {
                abort(403,"登陆失效");
            }
        }
    }*/

    /**
     * 检测手机号码是否唯一
     * @param $pid
     * @param $phone
     * @return bool
     */
    public function checkPhone($pid,$phone)
    {
        $partnerModel = new Partner();

        $is_exist = $partnerModel
            ->where('pid','neq',$pid)
            ->where('phone',$phone)
            ->count();

        if ($is_exist > 0){
            //存在
            return true;
        }else{
            //不存在
            return false;
        }
    }

    /**
     * token获取合伙人信息
     * @param $token
     * @return array|false|mixed|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function tokenGeyPartnerInfo($token)
    {
        $partnerModel = new Partner();

        $column_info = $partnerModel->column_info;

        $res = $partnerModel
            ->where('remember_token',$token)
            ->field($column_info)
            ->find();

        $res = json_decode(json_encode($res),true);

        return $res;
    }

}