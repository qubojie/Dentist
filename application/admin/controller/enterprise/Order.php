<?php

/**
 * 订单
 * @Author: zhangtao
 * @Date:   2018-11-05 16:53:03
 * @Last Modified by:   admin
 * @Last Modified time: 2018-12-04 15:57:49
 */


namespace app\admin\controller\Enterprise;

use app\common\controller\SysAdminAuth;
use app\wechatpublic\model\ShopEnterprise;
use app\wechat\model\BillOrder;
use app\wechat\model\BillOrderGoods;
use app\wechat\model\BillOrderReserve;
use app\shopadmin\model\ShopAdminLog;
use app\wechat\model\User;
use app\shopadmin\model\ShopDoctor;
use think\Controller;
use think\Db;
use think\Exception;
use think\Request;
use think\Validate;
use think\Cache;
use app\common\controller\SendSms;

class Order extends SysAdminAuth{

    /**
     * 订单列表
     * @param Request $request
     * @return array
     */
    public function index(Request $request){


        $pagesize   = $request->param("pagesize",config('PAGESIZE'));//显示个数,不传时为10

        if (empty($pagesize)) $pagesize = config('PAGESIZE');

        $nowPage        = $request->param("nowPage","1");

        $keyword        = $request->param("keyword","");

        $status         = $request->param("status","1");//订单状态 1待付款 2付款待使用 3已使用 4已退款 5已取消

        $before_date    = $request->param("before_date",strtotime('-1month'));//起始时间
        $after_date     = $request->param("after_date",time());//截止时间
        $sid            = $request->param("sid","");

        $orderBy['filter']['orderBy'] = $request->has('orderBy') ? $request->param('orderBy') : 'bo.created_at';
        $orderBy['filter']['sort'] = $request->has('sort') ? $request->param('sort') : 'desc';

        $config = [
            "page" => $nowPage,
        ];

        $where = [];
        if ($status == 2) {
            $where["bo.sale_status"] = 1;
        }else if($status == 3){
            $where["bo.sale_status"] = 2;
        }else if($status == 4){
            $where["bo.sale_status"] = 2;
            $where["bo.is_refund"]   = 1;
        }else if($status == 5){
            $where["bo.sale_status"] = 2;
            $where["bo.is_refund"]   = 0;
        }else{
            $where["bo.sale_status"] = 0;
        }

        if (!empty($sid)){
            $where["bo.sid"] = $sid;
        }

        if (!empty($keyword)){
            $where["bo.oid|bog.goods_name"] = ["like","%$keyword%"];
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
            $where['bo.created_at'] = array($where1, $where2, 'and');
        }elseif(!empty($where1)){
            $where['bo.created_at'] = $where1;
        }elseif(!empty($where2)){
            $where['bo.created_at'] = $where2;
        }

        try{
            $orderModel = new BillOrder();

            //为排序条件加上别名
            if ($orderBy['filter']['orderBy'] == 'oid')
            {
                $orderBy['filter']['orderBy'] = 'bo.oid';
            }
            else if($orderBy['filter']['orderBy'] == 'reserve_name')
            {
                $orderBy['filter']['orderBy'] = 'bor.reserve_name';
            }
            else if ($orderBy['filter']['orderBy'] == 'reserve_phone')
            {
                $orderBy['filter']['orderBy'] = 'bor.reserve_phone';
            }
            else if($orderBy['filter']['orderBy'] == 'goods_name')
            {
                $orderBy['filter']['orderBy'] = 'bog.goods_name';
            }
            else if($orderBy['filter']['orderBy'] == 'deal_amount')
            {
                $orderBy['filter']['orderBy'] = 'bo.deal_amount';
            }
            else if($orderBy['filter']['orderBy'] == 'pay_time')
            {
                $orderBy['filter']['orderBy'] = 'bo.pay_time';
            }
            else if($orderBy['filter']['orderBy'] == 'deal_time')
            {
                $orderBy['filter']['orderBy'] = 'bor.deal_time';
            }
            else if($orderBy['filter']['orderBy'] == 'payable_amount')
            {
                $orderBy['filter']['orderBy'] = 'bo.payable_amount';
            }
            else if($orderBy['filter']['orderBy'] == 'cancel_time')
            {
                $orderBy['filter']['orderBy'] = 'bo.cancel_time';
            }
            else if($orderBy['filter']['orderBy'] == 'finish_time')
            {
                $orderBy['filter']['orderBy'] = 'bo.finish_time';
            }
            else if($orderBy['filter']['orderBy'] == 'user_name')
            {
                $orderBy['filter']['orderBy'] = 'u.name';
            }

            //处理排序条件
            $field_array = array("u.name", "bog.goods_name");
            if (in_array($orderBy['filter']['orderBy'], $field_array)) {
                $orderBy['filter']['orderBy'] = fieldOrderByEncode($orderBy['filter']['orderBy']);
            }
// var_dump($orderBy);
            $order_list = $orderModel
                          ->alias('bo')
                          ->join('dts_bill_order_goods bog','bo.oid = bog.oid', 'LEFT')
                          ->join('dts_bill_order_reserve bor','bog.rid = bor.rid','LEFT')
                          ->join('dts_user u','u.uid = bo.uid','LEFT')
                          ->field('bo.oid,bo.uid,bo.sid,bo.sale_status,bo.deal_time,bo.pay_type,bo.pay_time,bo.pay_no,bo.finish_time,bo.cancel_user bo_cancel_user,bo.cancel_time bo_cancel_time,bo.auto_cancel,bo.is_refund,bo.refund_amount,bo.cancel_reason,bo.order_amount,bo.payable_amount,bo.deal_amount,bo.pid,bo.p_commission,bo.shop_gain,bo.deal_shop_gain,bo.commission,bo.deal_way,bo.cus_remark,bo.created_at,bo.updated_at')
                          ->field('bog.gid,bog.cat_id,bog.goods_name,bog.goods_sketch,bog.goods_original_price,bog.goods_price,bog.verify_time')
                          ->field('bor.rid,bor.status reserve_status,bor.reserve_name,bor.reserve_phone,bor.reserve_doctor,bor.reserve_time,bor.reserve_arrive_time,bor.reserve_remark,bor.cancel_user bor_cancel_user,bor.cancel_desc bor_cancel_desc')
                          ->field('u.name user_name,u.phone user_phone,u.nickname user_nickname')
                          ->where($where)
                          ->order($orderBy['filter']['orderBy'], $orderBy['filter']['sort'])
                          ->paginate($pagesize,false,$config);

            if ($order_list) {
                //保留n位小数
                $decimal = $this->getSettingInfo("sys_show_number_decimal")['sys_show_number_decimal'];

                $doctorModel = new ShopDoctor();
                $userModel   = new User();
                $logModel    = new ShopAdminLog();
                foreach ($order_list as $key => $value) {
                    if (!empty($value['rid'])) {
                        $doctor = $doctorModel
                                  ->where('doc_id', $value['reserve_doctor'])
                                  ->field("doctor_name")
                                  ->find();
                        $order_list[$key]['show_doctor_name'] = $doctor['doctor_name'];
                    }
                    // $user = $userModel
                    //           ->where('uid', $value['uid'])
                    //           ->field("phone,name,nickname")
                    //           ->find();
                    // $order_list[$key]['user_name']     = $user['name'];
                    // $order_list[$key]['user_phone']    = $user['phone'];
                    // $order_list[$key]['user_nickname'] = $user['nickname'];

                    $log = $logModel
                           ->field("action,reason,action_user,action_time")
                           ->where('oid', $value['oid'])
                           ->select();
                    foreach ($log as $k => $v) {
                        $log[$k]['action_name'] = config("log.action_type")[$v['action']];
                    }

                    $order_list[$key]['action_log'] = $log;

                    //保留n位小数
                    $order_list[$key]['commission']           = sprintf("%.".$decimal."f", $value['commission']);
                    $order_list[$key]['deal_amount']          = sprintf("%.".$decimal."f", $value['deal_amount']);
                    $order_list[$key]['deal_shop_gain']       = sprintf("%.".$decimal."f", $value['deal_shop_gain']);
                    $order_list[$key]['goods_original_price'] = sprintf("%.".$decimal."f", $value['goods_original_price']);
                    $order_list[$key]['goods_price']          = sprintf("%.".$decimal."f", $value['goods_price']);
                    $order_list[$key]['order_amount']         = sprintf("%.".$decimal."f", $value['order_amount']);
                    $order_list[$key]['p_commission']         = sprintf("%.".$decimal."f", $value['p_commission']);
                    $order_list[$key]['payable_amount']       = sprintf("%.".$decimal."f", $value['payable_amount']);
                    $order_list[$key]['refund_amount']        = sprintf("%.".$decimal."f", $value['refund_amount']);
                    $order_list[$key]['shop_gain']            = sprintf("%.".$decimal."f", $value['shop_gain']);
                }


                return comReturn(true,config("return_message.success"),$order_list);

            }else{
                return comReturn(false,config("return_message.fail"), '', 500);
            }
        }catch (Exception $e){
            return comReturn(false, $e->getMessage(), '', 500);
        }

    }

    /**
     * 订单状态列表
     * @return array
     */
    public function orderStatus(){
        $status_list = $this->getStatus("order");
        return comReturn(true,config("return_message.success"), $status_list);
    }
}