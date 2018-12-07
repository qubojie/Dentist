<?php

/**
 * 订单
 * @Author: zhangtao
 * @Date:   2018-10-23 11:26:35
 * @Last Modified by:   admin
 * @Last Modified time: 2018-11-29 15:06:05
 */

namespace app\shopadmin\controller;

use app\common\controller\ShopAdminAuth;
use app\shopadmin\model\Shop;
use app\shopadmin\model\ShopImage;
use app\shopadmin\model\ShopAccount;
use app\shopadmin\model\ShopWithdrawalsAccount;
use app\shopadmin\model\BillShopWithdrawals;
use app\wechat\model\BillOrder;
use think\Env;
use think\Log;
use think\Request;
use think\Validate;
use think\Db;
use think\Cache;

class Asset extends ShopAdminAuth
{

    /**
     * 店铺资产信息
     * @param Request $request
     * @return array
     */
    public function index(Request $request){
        $Token = Request::instance()->header("Token","");
        $sid   = $request->param("sid", "");

        try{
            $shopModel = new Shop();
            $shop = $shopModel
                    ->alias("s")
                    ->join("dts_shop_admin sa",'s.sid = sa.sid','LEFT')
                    ->field("s.sid,s.eid,s.shop_name,s.shop_phone,s.account_balance,s.account_freeze,s.account_cash,sa.name,sa.username,sa.phone")
                    ->where("s.sid", $sid)
                    ->find();

            if ($shop) {
                //格式化金额
                $decimal = $this->getSettingInfo("sys_show_number_decimal")['sys_show_number_decimal'];
                $shop['account_balance'] = sprintf("%.".$decimal."f", $shop['account_balance']);
                $shop['account_freeze']  = sprintf("%.".$decimal."f", $shop['account_freeze']);
                $shop['account_cash']    = sprintf("%.".$decimal."f", $shop['account_cash']);

                $time1 = $this->getNTime(7);
                $time2 = $this->getNTime(1, "23:59:59");
                unset($temp);
                $orderModel = new BillOrder();
                //7天收入
                $temp = $orderModel
                        ->field("SUM(deal_shop_gain) AS day_7_gain")
                        ->where("sid", $sid)
                        ->where("pay_time >= ".$time1." AND pay_time <= ".$time2)
                        ->find();
                $shop['day_7_gain'] = empty($temp['day_7_gain']) ? 0 : $temp['day_7_gain'];
                $shop['day_7_gain'] = sprintf("%.".$decimal."f", $shop['day_7_gain']);

                //待结算金额
                unset($temp);
                $temp = $orderModel
                        ->field("SUM(shop_gain) AS wait_settle_gain")
                        ->where("sid", $sid)
                        ->where("sale_status = 0 OR sale_status = 1")
                        ->find();

                $shop['wait_settle_gain'] = empty($temp['wait_settle_gain']) ? 0 : $temp['wait_settle_gain'];
                $shop['wait_settle_gain'] = sprintf("%.".$decimal."f", $shop['wait_settle_gain']);

                //所有企业下的提现账户
                $accountModel = new ShopWithdrawalsAccount();
                $withdrawals_account = $accountModel
                                       ->field("id,type,account,name,bank")
                                       ->where("eid", $shop['eid'])
                                       ->select();
                foreach ($withdrawals_account as $key => $value) {
                    if ($value['type'] == "bank") {
                        $withdrawals_account[$key]['account_info'] = $value['bank'];
                    }else{
                        $withdrawals_account[$key]['account_info'] = '支付宝';
                    }
                    $withdrawals_account[$key]['account_info'] .= " - ".$value['name']." - 尾号".mb_substr($value['account'],-4);
                }

                $shop['withdrawals_account'] = $withdrawals_account;

                $imageModel = new ShopImage();
                $shop_image = $imageModel
                              ->field("image")
                              ->where("sid", $sid)
                              ->order("sort")
                              ->find();

                $shop['shop_image'] = $shop_image['image'];


                return comReturn(true,config("return_message.success"),$shop);
            }else{
                return comReturn(false,config("return_message.fail"));
            }
        }catch (Exception $e){
            return comReturn(false, $e->getMessage(), '', 500);
        }

    }


    /**
     * 店铺交易记录
     * @param Request $request
     * @return array
     */
    public function dealRecord(Request $request){
        $Token = Request::instance()->header("Token","");
        $sid   = $request->param("sid", "");
        $type  = $request->param("type", 0);//0七天收支明细 1近30天待结算记录

        try{
            //保留n位小数
            $decimal = $this->getSettingInfo("sys_show_number_decimal")['sys_show_number_decimal'];
            if ($type == 1) {
                $time1 = $this->getNTime(30);
                $time2 = $this->getNTime(1, "23:59:59");

                $orderModel = new BillOrder();
                $list = $orderModel
                        ->alias("bo")
                        ->join("dts_bill_order_goods bog","bo.oid = bog.oid","LEFT")
                        ->field("bo.sale_status,bo.deal_time,bo.pay_type,bo.deal_amount,bo.shop_gain,bo.deal_shop_gain,bog.goods_name,bog.goods_price")
                        ->where("bo.sid", $sid)
                        ->where("bo.deal_time >= ".$time1." AND bo.deal_time <= ".$time2)
                        ->where("bo.sale_status = 0 OR bo.sale_status = 1")
                        ->order("bo.created_at DESC")
                        ->select();

                foreach ($list as $key => $value) {
                    $list[$key]['deal_amount']    = sprintf("%.".$decimal."f", $value['deal_amount']);
                    $list[$key]['shop_gain']      = sprintf("%.".$decimal."f", $value['shop_gain']);
                    $list[$key]['deal_shop_gain'] = sprintf("%.".$decimal."f", $value['deal_shop_gain']);
                    $list[$key]['goods_price']    = sprintf("%.".$decimal."f", $value['goods_price']);
                }
            }else{

                $time1 = $this->getNTime(7);
                $time2 = $this->getNTime(1, "23:59:59");
                $shopAccountModel = new ShopAccount();
                $list = $shopAccountModel
                        ->field("account_id,balance,last_balance,freeze,last_freeze,cash,last_cash,action_user,action_desc,created_at")
                        ->where("sid", $sid)
                        ->where("created_at >= ".$time1." AND created_at <= ".$time2)
                        ->order("created_at DESC")
                        ->select();

                foreach ($list as $key => $value) {
                    $list[$key]['balance']      = sprintf("%.".$decimal."f", $value['balance']);
                    $list[$key]['last_balance'] = sprintf("%.".$decimal."f", $value['last_balance']);
                    $list[$key]['freeze']       = sprintf("%.".$decimal."f", $value['freeze']);
                    $list[$key]['last_freeze']  = sprintf("%.".$decimal."f", $value['last_freeze']);
                    $list[$key]['cash']         = sprintf("%.".$decimal."f", $value['cash']);
                    $list[$key]['last_cash']    = sprintf("%.".$decimal."f", $value['last_cash']);
                }

            }
            return comReturn(true,config("return_message.success"),$list);
        }catch (Exception $e){
            return comReturn(false, $e->getMessage(), '', 500);
        }

    }



    /**
     * 添加提现账户
     * @param Request $request
     * @return array
     */
    public function addWithdrawAccount(Request $request){
        $Token = Request::instance()->header("Token","");

        $type    = $request->param("type","");//提现类型
        $account = $request->param("account","");//账户号
        $name    = $request->param("name","");//收款人或单位名称
        $code    = $request->param("code","");//验证码
        $phone   = $request->param("phone","");//手机号
        $sid     = $request->param("sid","");

        $withdrawals_account['type']    = $type;
        $withdrawals_account['account'] = $account;
        $withdrawals_account['name']    = $name;

        try{
            //规则验证
            $rule = [
                "type|提现类型"  => "require",
                "account|账户号" => "require",
                "name|收款人"    => "require",
                "code|验证码"    => "require|number|length:4",
                "phone|手机号"   => "require|number",
                "sid|sid"        => "require",
            ];
            $check_data = [
                "type"    => $type,
                "account" => $account,
                "name"    => $name,
                "code"    => $code,
                "phone"   => $phone,
                "sid"     => $sid,
            ];

            if ($type == "bank") {
                $bank = $request->param("bank","");//开户行
                $withdrawals_account['bank']    = $bank;
                $rule['bank|开户行'] = "require";
                $check_data['bank'] = $bank;
            }

            Log::info("提现账户参数  ---- ".var_export($withdrawals_account,true));

            $validate = new Validate($rule);
            if (!$validate->check($check_data)){
                return comReturn(false,$validate->getError(), '', 500);
            }

            $send_code = Cache::get("sms_verify_code_".$phone);

            //验证验证码
            if (!$send_code || $send_code != $code) {
                return comReturn(false,config("sms.verify_fail"), '', 500);
            }

            $admin = $this->tokenGetAdmin($Token);

            $withdrawals_account['eid'] = $admin['eid'];

            $accountModel = new ShopWithdrawalsAccount();
            $id = $accountModel
                   ->insertGetId($withdrawals_account);

            if ($id) {

                //记录日志
                $logtext = "(ID:".$id.")";
                $logtext = $this->infoAddClass($logtext, 'text-add');
                $route = $this->request->routeInfo();
                $route_tran = $this->routeTranslation($route, 'shop_menu');
                $admin = $this->tokenGetAdmin($Token);//获取管理员信息
                $this->addSysLog(time(), $admin['log_name'], $route_tran.$logtext, $request->ip(), $sid);

                $res['id'] = $id;
                if ($type == "bank") {
                    $res['account_info'] = $withdrawals_account['bank'];
                }else{
                    $res['account_info'] = '支付宝';
                }
                $res['account_info'] .= " - ".$withdrawals_account['name']." - 尾号".substr($withdrawals_account['account'],-4);

                return comReturn(true,config("return_message.success"),$res);
            }else{
                return comReturn(false,config("return_message.fail"), '', 500);
            }
        }catch (Exception $e){
            return comReturn(false, $e->getMessage(), '', 500);
        }

    }

    /**
     * 删除提现账户
     * @param Request $request
     * @return array
     */
    public function delWithdrawAccount(Request $request){
        $Token = Request::instance()->header("Token","");

        $id  = $request->param("id","");//账户id
        $sid = $request->param("sid","");

        try{
            //规则验证
            $rule = [
                "id|账户id"   => "require",
                "sid|店铺id"   => "require",
            ];
            $check_data = [
                "id"  => $id,
                "sid" => $sid,
            ];

            $validate = new Validate($rule);
            if (!$validate->check($check_data)){
                return comReturn(false,$validate->getError(), '', 500);
            }

            $accountModel = new ShopWithdrawalsAccount();
            $res = $accountModel
                  ->where("id", $id)
                  ->delete();

            if ($res) {
                //记录日志
                $logtext = "(ID:".$id.")";
                $logtext = $this->infoAddClass($logtext, 'text-del');
                $route = $this->request->routeInfo();
                $route_tran = $this->routeTranslation($route, 'shop_menu');
                $admin = $this->tokenGetAdmin($Token);//获取管理员信息
                $this->addSysLog(time(), $admin['log_name'], $route_tran.$logtext, $request->ip(), $sid);

                return comReturn(true,config("return_message.success"));
            }else{
                return comReturn(false,config("return_message.fail"), '', 500);
            }
        }catch (Exception $e){
            return comReturn(false, $e->getMessage(), '', 500);
        }


    }

    /**
     * 申请提现
     * @param Request $request
     * @return array
     */
    public function withdrawCash(Request $request){
        $Token = Request::instance()->header("Token","");

        $sid        = $request->param("sid", "");
        $account_id = $request->param("account_id", "");//账号id
        $amount     = $request->param("amount", "");//提现金额

        $withdrawals['sid']       = $sid;
        $withdrawals['amount']    = $amount;

        try{
            //规则验证
            $rule = [
                "sid|店铺id"        => "require",
                "account_id|账户id" => "require",
                "amount|提现金额"   => "require",
            ];
            $check_data = [
                "sid"        => $sid,
                "account_id" => $account_id,
                "amount"     => $amount,
            ];

            Log::info("提现申请参数  ---- ".var_export($withdrawals,true));

            $validate = new Validate($rule);
            if (!$validate->check($check_data)){
                return comReturn(false,$validate->getError(), '', 500);
            }

            $min_withdrawals = $this->getSettingInfo('shop_cash_lower_limit')['shop_cash_lower_limit'];
            if ($amount < $min_withdrawals) {
                return comReturn(false,config("account.shop_account_action_type")['gt_withdraw']['name'].$min_withdrawals);
            }

            $shopModel = new Shop();
            $shop_info = $shopModel
                         ->field("account_balance")
                         ->where("sid", $sid)
                         ->find();

            if ($amount > $shop_info['account_balance']) {
                return comReturn(false,config("account.shop_account_action_type")['insufficient_withdraw']['name']);
            }


            Db::startTrans();
            $shop['account_balance'] = array('exp', 'account_balance-'.$amount);
            $shop['account_freeze']  = array('exp', 'account_freeze+'.$amount);

            $is_ok = $shopModel
                    ->where('sid',$sid)
                    ->update($shop);
            if ($is_ok) {
                $accountModel = new ShopWithdrawalsAccount();
                $withdrawals_account = $accountModel
                                       ->field("type,account,name,bank")
                                       ->where("id", $account_id)
                                       ->find();

                //收款类型
                if ($withdrawals_account['type'] == 'bank') {
                    $withdrawals['receipt_type'] = 'bank';
                    $withdrawals['bank']         = $withdrawals_account['bank'];
                }else{
                    $withdrawals['receipt_type'] = 'alipay';
                }

                $admin = $this->tokenGetAdmin($Token);

                $withdrawals['shop_caid']   = generateReadableUUID("SC");
                $withdrawals['eid']         = $admin['eid'];
                $withdrawals['name']        = $withdrawals_account['name'];
                $withdrawals['account_num'] = $withdrawals_account['account'];
                $withdrawals['apply_time']  = time();
                $withdrawals['status']      = 1;

                $withdrawalsModel = new BillShopWithDrawals();

                $res = $withdrawalsModel
                       ->insert_ex($withdrawals, true);

                if ($res) {
                    Db::commit();

                    //记录日志
                    $logtext = "(shop_caid:".$withdrawals['shop_caid'].")";
                    $logtext = $this->infoAddClass($logtext, 'text-add');
                    $route = $this->request->routeInfo();
                    $route_tran = $this->routeTranslation($route, 'shop_menu');
                    $admin = $this->tokenGetAdmin($Token);//获取管理员信息
                    $this->addSysLog(time(), $admin['log_name'], $route_tran.$logtext, $request->ip(), $sid);

                    return comReturn(true,config('return_message.success'));
                }else{
                    Db::rollback();
                    return comReturn(false,config('return_message.fail'));
                }
            }else{
                Db::rollback();
                return comReturn(false,config('return_message.fail'));
            }
        }catch (Exception $e){
            return comReturn(false, $e->getMessage(), '', 500);
        }


        // if ($res) {


        //     //处理资金账目变化及明细
        //     $shop['account_balance'] = array('exp','account_balance-'.$amount);
        //     $shop['account_freeze']  = array('exp','account_freeze+'.$amount);
        //     $shopModel->where("sid", $sid)->update($shop);


        //     return comReturn(true,config("return_message.success"));
        // }else{
        //     return comReturn(false,config("return_message.fail"));
        // }

    }


}