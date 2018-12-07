<?php

/**
 * @Author: zhangtao
 * @Date:   2018-11-09 10:32:20
 * @Last Modified by:   admin
 * @Last Modified time: 2018-11-27 15:36:43
 */
namespace app\admin\controller\statistics;

use app\common\controller\SysAdminAuth;
use app\wechat\model\User;
use app\shopadmin\model\Shop;
use app\wechat\model\BillOrder;
use app\shopadmin\model\ShopGoods;
use app\wechatpublic\model\Partner;
use app\wechatpublic\model\ShopEnterprise;
use think\Request;
use think\Exception;

class Index extends SysAdminAuth
{
    public $hour = ['00','01','02','03','04','05','06','07','08','09','10','11','12','13','14','15','16','17','18','19','20','21','22','23'];
    public $month = ['01','02','03','04','05','06','07','08','09','10','11','12'];

    /**
     * 数据统计
     * @param Request $request
     * @return array
     */
    public function index(Request $request)
    {
        $dateType = $request->param('dateType','today');//筛选类型 today:日;month:月;year:年
        if (empty($dateType)) {
            $dateType = 'today';
        }

        try{
            //客户总数
            $userNum = $this->userNumCount($dateType);
            //合伙人总数
            $partnerNum = $this->partnerNumCount($dateType, 1);
            //企业总数
            $enterpriseNum = $this->enterpriseNumCount($dateType, 1);
            //店铺总数
            $shopNum = $this->shopNumCount($dateType, 0);
            //商品总数
            $goodsNum = $this->goodsNumCount($dateType, 0);
            //全部订单
            $totalOrderNum = $this->orderNumCount($dateType);
            //待付款订单
            $waitPayOrderNum = $this->orderNumCount($dateType, 0);
            //待使用订单
            $waitUseOrderNum = $this->orderNumCount($dateType, 1);
            //已完成订单
            $usedOrderNum = $this->orderNumCount($dateType, 2);
            //已取消订单
            $cancelOrderNum = $this->orderNumCount($dateType, 9);
            //收入
            $moneyNum = $this->orderNumCount($dateType, 2);

            $res = [
                'userNum'         => $userNum,
                'partnerNum'      => $partnerNum,
                'enterpriseNum'   => $enterpriseNum,
                'shopNum'         => $shopNum,
                'goodsNum'        => $goodsNum,
                'totalOrderNum'   => $totalOrderNum['count'],
                'waitPayOrderNum' => $waitPayOrderNum['count'],
                'waitUseOrderNum' => $waitUseOrderNum['count'],
                'usedOrderNum'    => $usedOrderNum['count'],
                'cancelOrderNum'  => $cancelOrderNum['count'],
                'moneyNum'        => $moneyNum['amount'] ? $moneyNum['amount'] : 0,
            ];

            return comReturn(true,config("return_message.get_success"),$res);
        }catch (Exception $e){
            return comReturn(false, $e->getMessage(), '', 500);
        }

    }

    /**
     * 折线图数据统计
     * @param Request $request
     * @return array
     */
    public function chartData(Request $request)
    {
        $dateType = $request->param('dateType','today');//筛选类型 today:日;month:月;year:年
        if (empty($dateType)) {
            $dateType = 'today';
        }

        try{
            //订单曲线图
            $orderData = $this->chartList($dateType);
            //付款额曲线图
            // $amountData = $this->chartList($dateType, 1);

            // $orderData['amount_value'] = $amountData['amount_value'];

            $res = [
                'chartData' => $orderData,
            ];

            return comReturn(true,config("return_message.get_success"),$res);
        }catch (Exception $e){
            return comReturn(false, $e->getMessage(), '', 500);
        }

    }

    /**
     * 订单图表数据
     * @param string $dateType today:日;month:月;year:年 all全部
     * @param string $status 订单状态 all全部 0待付款 1待使用 2已完成 9已取消
     * @return int|string
     */
    public function chartList($dateType = "all")
    {
        //保留n位小数
        $decimal = $this->getSettingInfo("sys_show_number_decimal")['sys_show_number_decimal'];

        $orderModel = new BillOrder();

        $where = [];
        $where_date = "";
        // if ($status != "all") {
        //     $where['sale_status'] = $status;
        // }

        if ($dateType == 'today') {
            foreach ($this->hour as $key => $value) {
                $hour[] = date('Ymd').$value;
                $chartNumList['key'][] = $value.":00";
            }

            foreach ($hour as $key => $value) {
                $where_date = "DATE_FORMAT(FROM_UNIXTIME(created_at),'%Y%m%d%H') = ".$value;

                $temp = $orderModel
                       ->field("COUNT(1) AS count")
                       ->where($where_date)
                       ->find();
                $chartNumList['order_value'][]  = $temp['count'];

                $temp = $orderModel
                       ->field("SUM(deal_amount) AS amount")
                       ->where("sale_status = 1 OR sale_status = 2")
                       ->where($where_date)
                       ->find();
                $temp['amount'] = $temp['amount'] ? $temp['amount'] : 0;
                $chartNumList['amount_value'][] = sprintf("%.".$decimal."f", $temp['amount']);
            }
            $where_date = "DATE_FORMAT(FROM_UNIXTIME(created_at),'%Y%m%d') = ".date("Ymd");

        }else if($dateType == 'month'){

            $j = date("t");//获取本月天数
            $start_time = strtotime(date('Y-m-01'));//获取本月第一天时间戳
            $day = array();
            for($i=0;$i<$j;$i++){
                $day[] = date('Ymd',$start_time+$i*86400); //每隔一天赋值给数组
                $chartNumList['key'][] = date('m')."/".($i+1);
            }

            foreach ($day as $key => $value) {
                $where_date = "DATE_FORMAT(FROM_UNIXTIME(created_at),'%Y%m%d') = ".$value;

                $temp = $orderModel
                       ->field("COUNT(1) AS count")
                       ->where($where_date)
                       ->find();
                $chartNumList['order_value'][] = $temp['count'];

                $temp = $orderModel
                       ->field("SUM(deal_amount) AS amount")
                       ->where("sale_status = 1 OR sale_status = 2")
                       ->where($where_date)
                       ->find();
                $temp['amount'] = $temp['amount'] ? $temp['amount'] : 0;
                $chartNumList['amount_value'][] = sprintf("%.".$decimal."f", $temp['amount']);
            }
            $where_date = "DATE_FORMAT(FROM_UNIXTIME(created_at),'%Y%m') = ".date("Ym");

        }else if($dateType == 'year'){
            foreach ($this->month as $key => $value) {
                $month[] = date('Y').$value;
                $chartNumList['key'][] = (int)$value;
            }

            foreach ($month as $key => $value) {
                $where_date = "DATE_FORMAT(FROM_UNIXTIME(created_at),'%Y%m') = ".$value;

                $temp = $orderModel
                       ->field("COUNT(1) AS count")
                       ->where($where_date)
                       ->find();
                $chartNumList['order_value'][] = $temp['count'];

                $temp = $orderModel
                       ->field("SUM(deal_amount) AS amount")
                       ->where("sale_status = 1 OR sale_status = 2")
                       ->where($where_date)
                       ->find();
                $temp['amount'] = $temp['amount'] ? $temp['amount'] : 0;
                $chartNumList['amount_value'][] = sprintf("%.".$decimal."f", $temp['amount']);
            }
            $where_date = "DATE_FORMAT(FROM_UNIXTIME(created_at),'%Y') = ".date("Y");
        }

        //订单总数及总额
        $temp = $orderModel
                ->field("SUM(order_amount) AS amount,COUNT(1) AS count")
                // ->where("sale_status <> 9")
                ->where($where_date)
                ->find();
        $chartNumList['total_order_count'] = $temp['count'];
        $chartNumList['total_amount_count'] = $temp['amount'] ? $temp['amount'] : 0;
        $chartNumList['total_amount_count'] = sprintf("%.".$decimal."f", $chartNumList['total_amount_count']);

        //已支付订单总数及总额
        $temp = $orderModel
                ->field("SUM(deal_amount) AS amount,COUNT(1) AS count")
                ->where("sale_status = 1 OR sale_status = 2")
                ->where($where_date)
                ->find();
        $chartNumList['pay_order_count'] = $temp['count'];
        $chartNumList['pay_amount_count'] = $temp['amount'] ? $temp['amount'] : 0;
        $chartNumList['pay_amount_count'] = sprintf("%.".$decimal."f", $chartNumList['pay_amount_count']);

        return $chartNumList;

    }

    /**
     * 数据总数统计
     * @param Request $request
     * @return array
     */
    public function totalNum()
    {
        try{
            //客户总数
            $userNum = $this->userNumCount();
            //合伙人总数
            $partnerNum = $this->partnerNumCount("all", 1);
            //企业总数
            $enterpriseNum = $this->enterpriseNumCount("all", 1);
            //店铺总数
            $shopNum = $this->shopNumCount("all", 0);
            //商品总数
            $goodsNum = $this->goodsNumCount("all", 0);
            //全部订单
            $totalOrderNum = $this->orderNumCount();
            //待付款订单
            $waitPayOrderNum = $this->orderNumCount("all", 0);
            //待使用订单
            $waitUseOrderNum = $this->orderNumCount("all", 1);
            //已完成订单
            $usedOrderNum = $this->orderNumCount("all", 2);
            //已取消订单
            $cancelOrderNum = $this->orderNumCount("all", 9);
            //收入
            $moneyNum = $this->orderNumCount("all", 2);

            $res = [
                'userNum'         => $userNum,
                'partnerNum'      => $partnerNum,
                'enterpriseNum'   => $enterpriseNum,
                'shopNum'         => $shopNum,
                'goodsNum'        => $goodsNum,
                'totalOrderNum'   => $totalOrderNum['count'],
                'waitPayOrderNum' => $waitPayOrderNum['count'],
                'waitUseOrderNum' => $waitUseOrderNum['count'],
                'usedOrderNum'    => $usedOrderNum['count'],
                'cancelOrderNum'  => $cancelOrderNum['count'],
                'moneyNum'        => $moneyNum['amount'] ? $moneyNum['amount'] : 0,
            ];

            return comReturn(true,config("return_message.success"),$res);
        }catch (Exception $e){
            return comReturn(false, $e->getMessage(), '', 500);
        }


    }

    /**
     * 订单统计
     * @param string $dateType today:日;month:月;year:年 all全部
     * @param string $status 订单状态 all全部 0待付款 1待使用 2已完成 9已取消
     * @return int|string
     */
    public function orderNumCount($dateType = "all", $status = 'all')
    {
        $where = [];
        $where_date = "";
        if ($dateType == 'today') {
            $where_date .= "TO_DAYS(FROM_UNIXTIME(created_at)) = TO_DAYS(NOW())";
        }else if($dateType == 'month'){
            $where_date .= "DATE_FORMAT(FROM_UNIXTIME(created_at),'%Y%m') = DATE_FORMAT(NOW(),'%Y%m')";
        }else if($dateType == 'year'){
            $where_date .= "YEAR(FROM_UNIXTIME(created_at))=YEAR(NOW())";
        }
        if ($status != "all") {
            $where['sale_status'] = $status;
        }
        $orderModel = new BillOrder();

        $num = $orderModel
               ->field("SUM(deal_amount) AS amount,COUNT(1) AS count")
               ->where($where)
               ->where($where_date)
               ->find();
        return $num;
    }

    /**
     * 客户统计
     * @param string $dateType today:日;month:月;year:年 all全部
     * @param string $status 状态 0正常 1禁止登陆
     * @return int|string
     */
    public function userNumCount($dateType = "all", $status = 'all')
    {
        $where = [];
        $where_date = "";
        if ($dateType == 'today') {
            $where_date .= "TO_DAYS(FROM_UNIXTIME(register_time)) = TO_DAYS(NOW())";
        }else if($dateType == 'month'){
            $where_date .= "DATE_FORMAT(FROM_UNIXTIME(register_time),'%Y%m') = DATE_FORMAT(NOW(),'%Y%m')";
        }else if($dateType == 'year'){
            $where_date .= "YEAR(FROM_UNIXTIME(register_time))=YEAR(NOW())";
        }
        if ($status != "all") {
            $where['status'] = $status;
        }
        $userModel = new User();

        $num = $userModel
               ->where($where)
               ->where($where_date)
               ->count();
        return $num;
    }

    /**
     * 合伙人统计
     * @param string $dateType today:日;month:月;year:年 all全部
     * @param string $status 状态 all全部 已提交资料待审核 0 ，审核通过1，审核未通过 2   暂时停用 3   已注销9
     * @return int|string
     */
    public function partnerNumCount($dateType = "all", $status = 'all')
    {
        $where = [];
        $where_date = "";
        if ($dateType == 'today') {
            $where_date .= "TO_DAYS(FROM_UNIXTIME(created_at)) = TO_DAYS(NOW())";
        }else if($dateType == 'month'){
            $where_date .= "DATE_FORMAT(FROM_UNIXTIME(created_at),'%Y%m') = DATE_FORMAT(NOW(),'%Y%m')";
        }else if($dateType == 'year'){
            $where_date .= "YEAR(FROM_UNIXTIME(created_at))=YEAR(NOW())";
        }
        if ($status != "all") {
            $where['status'] = $status;
        }
        $partnerModel = new Partner();

        $num = $partnerModel
               ->where($where)
               ->where($where_date)
               ->count();
        return $num;
    }

    /**
     * 企业统计
     * @param string $dateType today:日;month:月;year:年 all全部
     * @param string $status 状态 all全部 已提交资料待审核 0 ，审核通过1，审核未通过 2   暂时停用 3   已注销9
     * @return int|string
     */
    public function enterpriseNumCount($dateType = "all", $status = 'all')
    {
        $where = [];
        $where_date = "";
        if ($dateType == 'today') {
            $where_date .= "TO_DAYS(FROM_UNIXTIME(created_at)) = TO_DAYS(NOW())";
        }else if($dateType == 'month'){
            $where_date .= "DATE_FORMAT(FROM_UNIXTIME(created_at),'%Y%m') = DATE_FORMAT(NOW(),'%Y%m')";
        }else if($dateType == 'year'){
            $where_date .= "YEAR(FROM_UNIXTIME(created_at))=YEAR(NOW())";
        }
        if ($status != "all") {
            $where['status'] = $status;
        }
        $enterpriseModel = new ShopEnterprise();

        $num = $enterpriseModel
               ->where($where)
               ->where($where_date)
               ->count();
        return $num;
    }
    /**
     * 店铺统计
     * @param string $dateType today:日;month:月;year:年 all全部
     * @param string $status 状态 all全部 0营业中   1闭店   9已注销
     * @return int|string
     */
    public function shopNumCount($dateType = "all", $status = 'all')
    {
        $where = [];
        $where_date = "";
        if ($dateType == 'today') {
            $where_date .= "TO_DAYS(FROM_UNIXTIME(created_at)) = TO_DAYS(NOW())";
        }else if($dateType == 'month'){
            $where_date .= "DATE_FORMAT(FROM_UNIXTIME(created_at),'%Y%m') = DATE_FORMAT(NOW(),'%Y%m')";
        }else if($dateType == 'year'){
            $where_date .= "YEAR(FROM_UNIXTIME(created_at))=YEAR(NOW())";
        }
        if ($status != "all") {
            $where['status'] = $status;
        }
        $shopModel = new Shop();

        $num = $shopModel
               ->where($where)
               ->where($where_date)
               ->count();
        return $num;
    }
    /**
     * 商品统计
     * @param string $dateType today:日;month:月;year:年 all全部
     * @param string $status 状态 all全部 在售0   下架1   禁用3
     * @return int|string
     */
    public function goodsNumCount($dateType = "all", $status = 'all')
    {
        $where = [];
        $where_date = "";
        if ($dateType == 'today') {
            $where_date .= "TO_DAYS(FROM_UNIXTIME(created_at)) = TO_DAYS(NOW())";
        }else if($dateType == 'month'){
            $where_date .= "DATE_FORMAT(FROM_UNIXTIME(created_at),'%Y%m') = DATE_FORMAT(NOW(),'%Y%m')";
        }else if($dateType == 'year'){
            $where_date .= "YEAR(FROM_UNIXTIME(created_at))=YEAR(NOW())";
        }
        if ($status != "all") {
            $where['status'] = $status;
        }
        $goodsModel = new ShopGoods();

        $num = $goodsModel
               ->where($where)
               ->where($where_date)
               ->count();
        return $num;
    }

}