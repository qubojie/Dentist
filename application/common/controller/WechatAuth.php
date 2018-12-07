<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/10/24
 * Time: 下午3:10
 */
namespace app\common\controller;

use app\wechat\model\User;
use think\Request;

class WechatAuth extends CommonAuth
{
    /**
     * 初始化方法,其余控制器继承此方法，进行判断登录
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function _initialize()
    {
        parent::_initialize();
        $method = Request::instance()->method();

        if ( $method != "OPTIONS"){
            $token = Request::instance()->header("Token","");

            $userModel = new User();

            $is_true = $userModel
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
    }


    /**
     * 根据token获取登陆用户信息
     * @param $token
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function tokenGetUserInfo($token)
    {
        $userModel = new User();

        $res = $userModel
            ->where('remember_token',$token)
            ->field('uid,phone,wxid,mp_openid,nickname,name,avatar')
            ->find();

        return $res;
    }
}