<?php

/**
 * 客户列表
 * @Author: zhangtao
 * @Date:   2018-11-05 11:32:51
 * @Last Modified by:   admin
 * @Last Modified time: 2018-12-04 15:43:35
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

class Lists extends SysAdminAuth{
    /**
     * 客户列表
     * @param Request $request
     * @return array
     */
    public function index(Request $request){

        $pagesize   = $request->param("pagesize",config('PAGESIZE'));//显示个数,不传时为10

        if (empty($pagesize)) $pagesize = config('PAGESIZE');

        $nowPage = $request->param("nowPage","1");

        $keyword = $request->param("keyword","");//关键字 昵称真实姓名手机号

        $before_date = $request->param("before_date","");//起始时间
        $after_date  = $request->param("after_date","");//截止时间

        $orderBy['filter']['orderBy'] = $request->has('orderBy') ? $request->param('orderBy') : 'created_at';
        $orderBy['filter']['sort'] = $request->has('sort') ? $request->param('sort') : 'desc';

        $config = [
            "page" => $nowPage,
        ];

        $where = [];
        if (!empty($keyword)){
            $where["nickname|name|phone"] = ["like","%$keyword%"];
        }
        $where1 = "";
        $where2 = "";
        if ($before_date != "") {
            $where1 = array('egt', $before_date);
        }
        if ($after_date != "") {
            $after_date = strtotime(date("Y-m-d 23:59:59", $after_date));
            $where2 = array('elt', $after_date);
        }
        if (!empty($where1) && !empty($where2)) {
            $where['register_time'] = array($where1, $where2, 'and');
        }elseif(!empty($where1)){
            $where['register_time'] = $where1;
        }elseif(!empty($where2)){
            $where['register_time'] = $where2;
        }

        try{
            $userModel = new User();

            $user_list = $userModel
                         ->where($where)
                         ->order($orderBy['filter']['orderBy'], $orderBy['filter']['sort'])
                         ->field('uid,phone,wxid,mp_openid,is_attention_wx,name,nickname,avatar,sex,province,city,country,register_way,register_time,lastlogin_time,status,credit_point,point,pid,created_at,updated_at')
                         ->paginate($pagesize,false,$config);

            if ($user_list) {

                $orderModel = new BillOrder();

                foreach ($user_list as $key => $value) {
                    //统计下了多少单
                    $total_order = $orderModel
                                   ->field("COUNT(1) AS count")
                                   ->where("uid", $value['uid'])
                                   ->find();

                    //统计花了多少钱
                    $total_fee = $orderModel
                                 ->field("SUM(deal_amount) AS count")
                                 ->where("uid", $value['uid'])
                                 ->where("sale_status = 1 OR sale_status = 2")
                                 ->find();

                    //统计待付款单数
                    $wait_pay_order = $orderModel
                                            ->field("COUNT(1) AS count")
                                            ->where("uid", $value['uid'])
                                            ->where("sale_status", 0)
                                            ->find();

                    //统计待使用单数
                    $wait_use_order = $orderModel
                                            ->field("COUNT(1) AS count")
                                            ->where("uid", $value['uid'])
                                            ->where("sale_status", 1)
                                            ->find();

                    //统计完成了多少单
                    $complete_order = $orderModel
                                            ->field("COUNT(1) AS count")
                                            ->where("uid", $value['uid'])
                                            ->where("sale_status", 2)
                                            ->find();

                    //统计取消了多少单
                    $cancel_order = $orderModel
                                    ->field("COUNT(1) AS count")
                                    ->where("uid", $value['uid'])
                                    ->where("sale_status", 9)
                                    ->find();

                    $user_list[$key]['total_order']    = $total_order['count'];
                    $user_list[$key]['total_fee']      = $total_fee['count'] ? $total_fee['count'] : 0;
                    $user_list[$key]['wait_pay_order'] = $wait_pay_order['count'];
                    $user_list[$key]['wait_use_order'] = $wait_use_order['count'];
                    $user_list[$key]['complete_order'] = $complete_order['count'];
                    $user_list[$key]['cancel_order']   = $cancel_order['count'];

                }
                return comReturn(true,config("return_message.success"),$user_list);
            }else{
                return comReturn(false,config("return_message.fail"));
            }
        }catch (Exception $e){
            return comReturn(false, $e->getMessage(), '', 500);
        }


    }
}