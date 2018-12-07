<?php

/**
 * 订单
 * @Author: zhangtao
 * @Date:   2018-10-23 11:26:35
 * @Last Modified by:   admin
 * @Last Modified time: 2018-11-21 16:06:16
 */

namespace app\shopadmin\controller;

use app\common\controller\ShopAdminAuth;
use app\services\controller\ImageUpload;
use app\wechat\model\BillOrder;
use app\wechat\model\BillOrderGoods;
use app\wechat\model\BillOrderReserve;
use think\Env;
use think\Log;
use think\Request;
use think\Validate;
use think\Db;

class Statistics extends ShopAdminAuth
{

    /**
     * 列表
     * @param Request $request
     * @return array
     */
    public function index(Request $request){

        $sid        = $request->param("sid","");
        if (empty($sid)) {
            return comReturn(false, config("return_message.error_status_code")['lack_param_necessary']['value'], '', config("return_message.error_status_code")['lack_param_necessary']['key']);
        }

        try{
            $orderModel = new BillOrder();
            $reserveModel = new BillOrderReserve();

            //全部数据统计
            $order_info = $orderModel
                           ->field("SUM(order_amount) AS total_amount,COUNT(1) AS total_pay_order,COUNT(distinct uid) AS total_pay_user")
                           ->where("sid", $sid)
                           ->where("sale_status = 1 OR sale_status = 2")
                           ->find()
                           ->toArray();
            $order_info['total_amount'] = empty($order_info['total_amount']) ? 0 : $order_info['total_amount'];

            //全部到店统计
            $order_info['total_arrive_user'] = $this->getTotalArriveUser($sid);


            //昨日数据统计
            $result = $this->getYesterdayData($sid);
            $order_info['yesterday_amount']    = $result['yesterday_amount'];
            $order_info['yseterday_pay_order'] = $result['yseterday_pay_order'];
            $order_info['yesterday_pay_user']  = $result['yesterday_pay_user'];

            //昨日到店统计
            $order_info['yesterday_total_arrive_user'] = $this->getYesterdayArriveUser($sid);

            //待付款统计
            $order_info['wait_pay_order'] = $this->getWaitPay($sid);


            //7天下单笔数
            $order_info['day_7_pay_order']    = $this->getTotalWeekPlaceOrder($sid);


            //过去7天下单统计
            $time1 = $this->getNTime(7);
            $time2 = $this->getNTime(7,"23:59:59");
            $order_info['before_7']['before_7_place'][] = $this->getPastPlaceOrder($time1, $time2, $sid);

            $time1 = $this->getNTime(6);
            $time2 = $this->getNTime(6, "23:59:59");
            $order_info['before_7']['before_7_place'][] = $this->getPastPlaceOrder($time1, $time2, $sid);

            $time1 = $this->getNTime(5);
            $time2 = $this->getNTime(5, "23:59:59");
            $order_info['before_7']['before_7_place'][] = $this->getPastPlaceOrder($time1, $time2, $sid);

            $time1 = $this->getNTime(4);
            $time2 = $this->getNTime(4, "23:59:59");
            $order_info['before_7']['before_7_place'][] = $this->getPastPlaceOrder($time1, $time2, $sid);

            $time1 = $this->getNTime(3);
            $time2 = $this->getNTime(3, "23:59:59");
            $order_info['before_7']['before_7_place'][] = $this->getPastPlaceOrder($time1, $time2, $sid);

            $time1 = $this->getNTime(2);
            $time2 = $this->getNTime(2, "23:59:59");
            $order_info['before_7']['before_7_place'][] = $this->getPastPlaceOrder($time1, $time2, $sid);

            $time1 = $this->getNTime(1);
            $time2 = $this->getNTime(1, "23:59:59");
            $order_info['before_7']['before_7_place'][] = $this->getPastPlaceOrder($time1, $time2, $sid);


            //过去7天付款统计
            $time1 = $this->getNTime(7);
            $time2 = $this->getNTime(7,"23:59:59");
            $order_info['before_7']['before_7_pay'][] = $this->getPastWeekPay($time1, $time2, $sid);

            $time1 = $this->getNTime(6);
            $time2 = $this->getNTime(6,"23:59:59");
            $order_info['before_7']['before_7_pay'][] = $this->getPastWeekPay($time1, $time2, $sid);

            $time1 = $this->getNTime(5);
            $time2 = $this->getNTime(5,"23:59:59");
            $order_info['before_7']['before_7_pay'][] = $this->getPastWeekPay($time1, $time2, $sid);

            $time1 = $this->getNTime(4);
            $time2 = $this->getNTime(4,"23:59:59");
            $order_info['before_7']['before_7_pay'][] = $this->getPastWeekPay($time1, $time2, $sid);

            $time1 = $this->getNTime(3);
            $time2 = $this->getNTime(3,"23:59:59");
            $order_info['before_7']['before_7_pay'][] = $this->getPastWeekPay($time1, $time2, $sid);

            $time1 = $this->getNTime(2);
            $time2 = $this->getNTime(2,"23:59:59");
            $order_info['before_7']['before_7_pay'][] = $this->getPastWeekPay($time1, $time2, $sid);

            $order_info['before_7']['before_7_pay'][] = $order_info['yseterday_pay_order'];


            //过去7天日期
            $order_info['before_7']['before_7_week'] = $this->getPastWeek();

            if ($order_info) {
                return comReturn(true,config("return_message.success"),$order_info);
            }else{
                return comReturn(false,config("return_message.fail"));
            }
        }catch (Exception $e){
            return comReturn(false, $e->getMessage(), '', 500);
        }

    }

    /**
     * 全部到店统计
     * @param sid
     * @return array
     */
    function getTotalArriveUser($sid){
        $reserveModel = new BillOrderReserve();
        $temp = $reserveModel
                ->alias('bor')
                ->join('dts_bill_order_goods bog','bog.oid = bor.oid AND bog.gid = bor.gid', 'LEFT')
                ->join('dts_bill_order bo','bo.oid = bog.oid','LEFT')
                ->field("COUNT(1) AS total_arrive_user")
                ->where("bo.sid", $sid)
                ->where("bor.status = 2 OR bor.status = 3")
                ->where("bo.sale_status = 1 OR bo.sale_status = 2")
                ->find();

        return $temp['total_arrive_user'];
    }

    /**
     * 昨日数据统计
     * @param sid
     * @return array
     */
    function getYesterdayData($sid){
        $time1 = $this->getNTime(1);
        $time2 = $this->getNTime(1, "23:59:59");

        $orderModel = new BillOrder();
        $temp = $orderModel
                ->field("SUM(order_amount) AS yesterday_amount,COUNT(1) AS yseterday_pay_order,COUNT(distinct uid) AS yesterday_pay_user")
                ->where("sid", $sid)
                ->where("sale_status = 1 OR sale_status = 2")
                ->where("pay_time >= ".$time1." AND pay_time <= ".$time2)
                ->find();

        $res['yesterday_amount']    = empty($temp['yesterday_amount']) ? 0 : $temp['yesterday_amount'];
        $res['yseterday_pay_order'] = $temp['yseterday_pay_order'];
        $res['yesterday_pay_user']  = $temp['yesterday_pay_user'];


        return $res;
    }

    /**
     * 昨日到店统计
     * @param sid
     * @return array
     */
    function getYesterdayArriveUser($sid){
        $time1 = $this->getNTime(1);
        $time2 = $this->getNTime(1, "23:59:59");

        $reserveModel = new BillOrderReserve();
        $temp = $reserveModel
                ->alias('bor')
                ->join('dts_bill_order_goods bog','bog.oid = bor.oid AND bog.gid = bor.gid', 'LEFT')
                ->join('dts_bill_order bo','bo.oid = bog.oid','LEFT')
                ->field("COUNT(1) AS yesterday_total_arrive_user")
                ->where("bo.sid", $sid)
                ->where("bor.status = 2 OR bor.status = 3")
                ->where("bo.sale_status = 1 OR bo.sale_status = 2")
                ->where("bor.reserve_arrive_time >= ".$time1." AND bor.reserve_arrive_time <= ".$time2)
                ->find();

        return $temp['yesterday_total_arrive_user'];
    }

    /**
     * 待付款统计
     * @param sid
     * @return array
     */
    function getWaitPay($sid){
        $orderModel = new BillOrder();
        $temp = $orderModel
                ->field("COUNT(1) AS wait_pay_order")
                ->where("sid", $sid)
                ->where("sale_status = 0")
                ->find();

        return $temp['wait_pay_order'];
    }

    /**
     * 7天下单笔数
     * @param sid
     * @return array
     */
    function getTotalWeekPlaceOrder($sid){
        $time1 = $this->getNTime(7);
        $time2 = $this->getNTime(1, "23:59:59");

        $orderModel = new BillOrder();
        $temp = $orderModel
                ->field("COUNT(1) AS 7_day_pay_order")
                ->where("sid", $sid)
                ->where("deal_time >= ".$time1." AND deal_time <= ".$time2)
                ->find();

        return $temp['7_day_pay_order'];
    }

    /**
     * 获取过去7天下单统计
     * @param time1
     * @param time2
     * @param sid
     * @return array
     */
    function getPastPlaceOrder($time1, $time2, $sid){
        $orderModel = new BillOrder();
        $temp = $orderModel
                ->field("COUNT(1) AS count_place_order")
                ->where("sid", $sid)
                ->where("deal_time >= ".$time1." AND deal_time <= ".$time2)
                ->find();

        return $temp['count_place_order'];
    }

    /**
     * 获取过去付款统计
     * @param time1
     * @param time2
     * @param sid
     * @return array
     */
    function getPastWeekPay($time1, $time2, $sid){
        $orderModel = new BillOrder();
        $temp = $orderModel
                ->field("COUNT(1) AS count_pay_order")
                ->where("sid", $sid)
                ->where("sale_status = 1 OR sale_status = 2")
                ->where("pay_time >= ".$time1." AND pay_time <= ".$time2)
                ->find();

        return $temp['count_pay_order'];
    }

    /**
     * 获取过去一周日期
     * @param day 过去第day天
     * @return array
     */
    function getPastWeek(){
        $past[] = date('Y-m-d',time()-7*24*60*60);
        $past[] = date('Y-m-d',time()-6*24*60*60);
        $past[] = date('Y-m-d',time()-5*24*60*60);
        $past[] = date('Y-m-d',time()-4*24*60*60);
        $past[] = date('Y-m-d',time()-3*24*60*60);
        $past[] = date('Y-m-d',time()-2*24*60*60);
        $past[] = date('Y-m-d',time()-1*24*60*60);

        return $past;
    }


}