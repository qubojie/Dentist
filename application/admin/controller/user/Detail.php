<?php

/**
 * 客户详细信息
 * @Author: zhangtao
 * @Date:   2018-11-05 11:32:51
 * @Last Modified by:   admin
 * @Last Modified time: 2018-11-20 12:31:22
 */
namespace app\admin\controller\user;

use app\common\controller\SysAdminAuth;
use app\admin\model\SysAdminUser;
use app\wechat\model\User;
use think\Controller;
use think\Db;
use think\Exception;
use think\Request;
use think\Validate;
use think\Cache;
use app\wechat\model\BillOrder;
use app\wechat\model\BillOrderGoods;
use app\wechat\model\BillOrderReserve;

class Detail extends SysAdminAuth{
    /**
     * 客户详细信息
     * @param Request $request
     * @return array
     */
    public function index(Request $request){

        $uid = $request->param("uid", "");//客户id

        try{
            //规则验证
            $rule = [
                "uid|客户id" => "require",
            ];
            $check_data = [
                "uid" => $uid,
            ];

            $validate = new Validate($rule);
            if (!$validate->check($check_data)){
                return comReturn(false,$validate->getError());
            }

            $userModel = new User();

            $user_info = $userModel
                        ->where("uid", $uid)
                        ->field('uid,phone,wxid,mp_openid,is_attention_wx,name,nickname,avatar,sex,province,city,country,register_way,register_time,lastlogin_time,status,credit_point,point,pid,created_at,updated_at')
                        ->find();

            if ($user_info) {

                $orderModel = new BillOrder();

                //统计下了多少单
                $total_order = $orderModel
                               ->field("COUNT(1) AS count")
                               ->where("uid", $uid)
                               ->find();

                //统计花了多少钱
                $total_fee = $orderModel
                             ->field("SUM(deal_amount) AS count")
                             ->where("uid", $uid)
                             ->where("sale_status = 1 OR sale_status = 2")
                             ->find();

                //统计待付款单数
                $wait_pay_order = $orderModel
                                  ->field("COUNT(1) AS count")
                                  ->where("uid", $uid)
                                  ->where("sale_status", 0)
                                  ->find();

                //统计待使用单数
                $wait_use_order = $orderModel
                                  ->field("COUNT(1) AS count")
                                  ->where("uid", $uid)
                                  ->where("sale_status", 1)
                                  ->find();

                //统计完成了多少单
                $complete_order = $orderModel
                                  ->field("COUNT(1) AS count")
                                  ->where("uid", $uid)
                                  ->where("sale_status", 2)
                                  ->find();

                //统计取消了多少单
                $cancel_order = $orderModel
                                ->field("COUNT(1) AS count")
                                ->where("uid", $uid)
                                ->where("sale_status", 9)
                                ->find();

                $user_info['total_order']    = $total_order['count'];
                $user_info['total_fee']      = $total_fee['count'] ? $total_fee['count'] : 0;
                $user_info['wait_pay_order'] = $wait_pay_order['count'];
                $user_info['wait_use_order'] = $wait_use_order['count'];
                $user_info['complete_order'] = $complete_order['count'];
                $user_info['cancel_order']   = $cancel_order['count'];


                return comReturn(true,config("return_message.success"),$user_info);
            }else{
                return comReturn(false,config("return_message.fail"));
            }
        }catch (Exception $e){
            return comReturn(false, $e->getMessage(), '', 500);
        }


    }
}