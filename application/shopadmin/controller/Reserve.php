<?php

/**
 * 订单
 * @Author: zhangtao
 * @Date:   2018-10-23 11:26:35
 * @Last Modified by:   admin
 * @Last Modified time: 2018-12-05 17:41:25
 */

namespace app\shopadmin\controller;

use app\common\controller\ShopAdminAuth;
use app\services\controller\ImageUpload;
use app\shopadmin\model\ShopGoods;
use app\shopadmin\model\ShopDoctor;
use app\shopadmin\model\GoodsImage;
use app\wechat\model\BillOrder;
use app\wechat\model\BillOrderGoods;
use app\wechat\model\BillOrderReserve;
use app\shopadmin\model\Partner;
use app\shopadmin\model\PartnerAccount;
use app\shopadmin\model\Shop;
use app\shopadmin\model\ShopAccount;
use app\shopadmin\model\ShopTag;
use think\Env;
use think\Log;
use think\Request;
use think\Validate;
use think\Db;

class Reserve extends ShopAdminAuth
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

        $pagesize   = $request->param("pagesize",config('PAGESIZE'));//显示个数,不传时为10

        if (empty($pagesize)) $pagesize = config('PAGESIZE');

        $nowPage        = $request->param("nowPage","1");

        $keyword        = $request->param("keyword","");

        $is_today       = $request->param("is_today","");//0今天 1 明天

        $is_confirm     = $request->param("is_confirm","");//0待确认 1已确认(包括到店、完成、取消)

        $before_date    = $request->param("before_date","");//起始时间
        $after_date     = $request->param("after_date","");//截止时间

        $reserve_status = $request->param("reserve_status","");//预约状态

        $orderBy['filter']['orderBy'] = $request->has('orderBy') ? $request->param('orderBy') : 'bor.created_at';
        $orderBy['filter']['sort'] = $request->has('sort') ? $request->param('sort') : 'desc';

        $config = [
            "page" => $nowPage,
        ];

        $where = [];

        if ($is_confirm != "" && $is_confirm == 0){//未确认 status=0
            $where['bor.status'] = 0;
        }else{//已确认 status=1|2|3|9
            $where["bor.status"] = array('in','1,2,3,9');

            if (!empty($keyword)){
                $where["bo.oid|bor.reserve_name|bor.reserve_phone|bor.reserve_doctor"] = ["like","%$keyword%"];
            }

            if (!empty($reserve_status)){
                $where["bor.status"] = $reserve_status;
            }

            if ($is_today != "" && $is_today == 0){
                $where1 = array('egt', strtotime(date('Y-m-d 00:00:00',time())));
                $where2 = array('elt', strtotime(date('Y-m-d 23:59:59',time())));
                $where['bor.reserve_time'] = array($where1, $where2, 'and');
            }else if($is_today == 1){
                $where1 = array('egt', strtotime(date('Y-m-d 00:00:00',time()+1*24*60*60)));
                $where2 = array('elt', strtotime(date('Y-m-d 23:59:59',time()+1*24*60*60)));
                $where['bor.reserve_time'] = array($where1, $where2, 'and');
            }else{
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
                    $where['bor.reserve_time'] = array($where1, $where2, 'and');
                }elseif(!empty($where1)){
                    $where['bor.reserve_time'] = $where1;
                }elseif(!empty($where2)){
                    $where['bor.reserve_time'] = $where2;
                }
            }
        }

        try{
            $reserveModel = new BillOrderReserve();

            //为排序条件加上别名
            if ($orderBy['filter']['orderBy'] == 'oid')
            {
                $orderBy['filter']['orderBy'] = 'bo.oid';
            }
            else if($orderBy['filter']['orderBy'] == 'reserve_time')
            {
                $orderBy['filter']['orderBy'] = 'bor.reserve_time';
            }
            else if ($orderBy['filter']['orderBy'] == 'reserve_phone')
            {
                $orderBy['filter']['orderBy'] = 'bor.reserve_phone';
            }
            else if($orderBy['filter']['orderBy'] == 'goods_name')
            {
                $orderBy['filter']['orderBy'] = 'bog.goods_name';
            }
            else if($orderBy['filter']['orderBy'] == 'show_doctor_name')
            {
                $orderBy['filter']['orderBy'] = 'bor.reserve_doctor';
            }
            else if($orderBy['filter']['orderBy'] == 'reserve_status')
            {
                $orderBy['filter']['orderBy'] = 'bor.status';
            }
            else if($orderBy['filter']['orderBy'] == 'reserve_arrive_time')
            {
                $orderBy['filter']['orderBy'] = 'bor.reserve_arrive_time';
            }
            else if($orderBy['filter']['orderBy'] == 'reserve_name')
            {
                $orderBy['filter']['orderBy'] = 'bor.reserve_name';
            }

            //处理排序条件
            $field_array = array("bor.reserve_name", "bog.goods_name", "bor.reserve_doctor");
            if (in_array($orderBy['filter']['orderBy'], $field_array)) {
                $orderBy['filter']['orderBy'] = fieldOrderByEncode($orderBy['filter']['orderBy']);
            }

            $reserve_list = $reserveModel
                            ->alias('bor')
                            ->join('dts_bill_order_goods bog','bog.oid = bor.oid AND bog.gid = bor.gid', 'LEFT')
                            ->join('dts_bill_order bo','bo.oid = bog.oid','LEFT')
                            ->field('bo.oid,bo.sid,bo.sale_status,bo.cancel_user bo_cancel_user,bo.cancel_time bo_cancel_time')
                            ->field('bog.gid,bog.cat_id,bog.goods_name,bog.goods_sketch,bog.goods_price,bog.verify_code,bog.verify_time')
                            ->field('bor.rid,bor.status reserve_status,bor.reserve_name,bor.reserve_phone,bor.reserve_doctor,bor.reserve_time,bor.reserve_arrive_time,bor.reserve_remark,bor.cancel_user bor_cancel_user,bor.cancel_desc bor_cancel_desc')
                            ->where($where)
                            ->where('bo.sid', $sid)
                            // ->order('bor.updated_at desc')
                            ->order($orderBy['filter']['orderBy'], $orderBy['filter']['sort'])
                            ->paginate($pagesize,false,$config);

            if ($reserve_list) {
                $doctorModel = new ShopDoctor();
                foreach ($reserve_list as $key => $value) {
                    $reserve_list[$key]['show_reserve_date'] = date('Y-m-d', $value['reserve_time']);
                    $reserve_list[$key]['show_reserve_time'] = date('H:i', $value['reserve_time']);
                    $doctor = $doctorModel
                              ->where('doc_id', $value['reserve_doctor'])
                              ->field("doctor_name")
                              ->find();
                    $reserve_list[$key]['show_doctor_name'] = $doctor['doctor_name'];
                }

                $interval = $this->getSettingInfo("sys_reserve_time")['sys_reserve_time'];
                $interval_array = explode(",", $interval);
                $time_select = getTimeInterval($interval_array[0], $interval_array[1]);

                //当前店铺下的医生列表
                $doctor_list = $doctorModel
                               ->where("sid", $sid)
                               ->where("is_delete", 0)
                               ->where("is_enable", 1)
                               ->field("doc_id,doctor_name")
                               ->select();

                //计算有几个待确认
                $to_be_confirm_count = $reserveModel
                                       ->alias('bor')
                                       ->join('dts_bill_order_goods bog','bog.oid = bor.oid AND bog.gid = bor.gid', 'LEFT')
                                       ->join('dts_bill_order bo','bo.oid = bog.oid','LEFT')
                                       ->where("bo.sid", $sid)
                                       ->where("bor.status", 0)
                                       ->count();


                //返回取消原因
                $tagModel = new ShopTag();
                $user_cancel_tag = $tagModel
                              ->field("tag")
                              ->where("tag_type = 200")
                              ->select();
                $shop_cancel_tag = $tagModel
                              ->field("tag")
                              ->where("tag_type = 201")
                              ->select();

                $res = [
                    'result'              => true,
                    'message'             => config("return_message.success"),
                    'data'                => $reserve_list,
                    'time_select'         => $time_select,
                    'doctor_list'         => $doctor_list,
                    'to_be_confirm_count' => $to_be_confirm_count,
                    'user_cancel_tag'     => $user_cancel_tag,
                    'shop_cancel_tag'     => $shop_cancel_tag
                ];

                return $res;

            }else{
                return comReturn(false,config("return_message.fail"), '', 500);
            }
        }catch (Exception $e){
            return comReturn(false, $e->getMessage(), '', 500);
        }


    }

    /**
     * 确认预约
     * @param Request $request
     * @return array
     */
    public function reserveConfirm(Request $request)
    {
        $Token = Request::instance()->header("Token","");

        $rid               = $request->param("rid","");//预约id
        $reserve_doctor    = $request->param("reserve_doctor","");//预约医生
        $show_reserve_date = $request->param("show_reserve_date","");//预约日期
        $show_reserve_time = $request->param("show_reserve_time","");//预约时间

        $reserve['rid']            = $rid;
        $reserve['reserve_doctor'] = $reserve_doctor;

        Log::info("预约参数  ---- ".var_export($reserve,true));

        try{
            //规则验证
            $rule = [
                "rid|预约id"                => "require",
                "reserve_doctor|预约医生"    => "require",
                "show_reserve_date|预约日期" => "require",
                "show_reserve_time|预约时间" => "require",
            ];
            $check_data = [
                "rid"               => $rid,
                "reserve_doctor"    => $reserve_doctor,
                "show_reserve_date" => $show_reserve_date,
                "show_reserve_time" => $show_reserve_time,
            ];

            $validate = new Validate($rule);
            if (!$validate->check($check_data)){
                return comReturn(false,$validate->getError(), '', 500);
            }

            $reserve['reserve_time'] = strtotime($show_reserve_date.$show_reserve_time);
            $reserve['status']       = 1;
            $reserve['updated_at']   = time();

            // 获取修改之前的数据
            $keys = array_keys($reserve);
            $databefore = $this->updateBefore("bill_order_reserve", "rid", $rid, $keys);

            $reserveModel = new BillOrderReserve();

            Db::startTrans();

            $is_ok = $reserveModel
                     ->where("rid", $rid)
                     ->update($reserve);
            if ($is_ok) {
                $reserve_info = $reserveModel
                                ->alias('bor')
                                ->join('dts_bill_order_goods bog','bog.oid = bor.oid AND bog.gid = bor.gid', 'LEFT')
                                ->join('dts_bill_order bo','bo.oid = bog.oid','LEFT')
                                ->field("bor.oid,bor.gid,bo.sid")
                                ->where("bor.rid",$rid)
                                ->find();
                //在订单商品表里插入rid
                $goodsModel = new BillOrderGoods();
                $goods['rid']        = $rid;
                $goods['updated_at'] = time();
                $res = $goodsModel
                       ->where("oid", $reserve_info['oid'])
                       ->where("gid", $reserve_info['gid'])
                       ->update($goods);

                if ($res) {
                    Db::commit();

                    //记录日志
                    $admin = $this->tokenGetAdmin($Token);//获取管理员信息

                    $this->addAdminLog($reserve_info['gid'], $reserve_info['oid'], 'reserve_confirm', $admin['log_name'], $reserve_info['sid']);

                    //记录日志
                    $logtext = "(RID:".$rid.")";
                    $logtext = $this->infoAddClass($logtext, 'text-edit');
                    $route = $this->request->routeInfo();
                    $route_tran = $this->routeTranslation($route, 'shop_menu');
                    $this->addSysLog(time(), $admin['log_name'], $route_tran.$logtext, $request->ip(), $reserve_info['sid']);

                    return comReturn(true,config('return_message.success'));
                }else{
                    Db::rollback();
                    return comReturn(false,config('return_message.fail'), '', 500);
                }
            }else{
                Db::rollback();
                return comReturn(false,config('return_message.fail'), '', 500);
            }
        }catch (Exception $e){
            return comReturn(false, $e->getMessage(), '', 500);
        }
    }

    /**
     * 用户到店
     * @param Request $request
     * @return array
     */
    public function userArrive(Request $request)
    {
        $Token = Request::instance()->header("Token","");

        $rid   = $request->param("rid","");//预约id

        $reserve['rid'] = $rid;

        Log::info("到店参数  ---- ".var_export($reserve,true));

        try{
            //规则验证
            $rule = [
                "rid|预约id" => "require",
            ];
            $check_data = [
                "rid" => $rid
            ];

            $validate = new Validate($rule);
            if (!$validate->check($check_data)){
                return comReturn(false,$validate->getError());
            }

            $reserve['status']              = 2;
            $reserve['reserve_arrive_time'] = time();
            $reserve['updated_at']          = time();

            // 获取修改之前的数据
            $keys = array_keys($reserve);
            $databefore = $this->updateBefore("bill_order_reserve", "rid", $rid, $keys);

            $reserveModel = new BillOrderReserve();

            Db::startTrans();
            $is_ok = $reserveModel
                     ->where("rid", $rid)
                     ->update($reserve);
            if ($is_ok) {
                Db::commit();

                //记录日志
                $admin = $this->tokenGetAdmin($Token);//获取管理员信息
                unset($reserve);
                $reserve = $reserveModel
                           ->alias('bor')
                           ->join('dts_bill_order_goods bog','bog.oid = bor.oid AND bog.gid = bor.gid', 'LEFT')
                           ->join('dts_bill_order bo','bo.oid = bog.oid','LEFT')
                           ->field("bor.gid,bor.oid,bo.sid")
                           ->where("bor.rid", $rid)
                           ->find();
                $this->addAdminLog($reserve['gid'], $reserve['oid'], 'user_arrive_shop', $admin['log_name'], $reserve['sid']);

                //记录日志
                $logtext = "(RID:".$rid.")";
                $logtext = $this->infoAddClass($logtext, 'text-edit');
                $route = $this->request->routeInfo();
                $route_tran = $this->routeTranslation($route, 'shop_menu');
                $this->addSysLog(time(), $admin['log_name'], $route_tran.$logtext, $request->ip(), $reserve['sid']);

                return comReturn(true,config('return_message.success'));
            }else{
                Db::rollback();
                return comReturn(false,config('return_message.fail'), '', 500);
            }
        }catch (Exception $e){
            return comReturn(false, $e->getMessage(), '', 500);
        }
    }


    /**
     * 消费完成
     * @param Request $request
     * @return array
     */
    public function consumptionComplete(Request $request)
    {
        $Token = Request::instance()->header("Token","");

        $rid   = $request->param("rid","");//预约id

        $reserve['rid'] = $rid;

        Log::info("到店参数  ---- ".var_export($reserve,true));

        try{
            //规则验证
            $rule = [
                "rid|预约id" => "require",
            ];
            $check_data = [
                "rid" => $rid
            ];

            $validate = new Validate($rule);
            if (!$validate->check($check_data)){
                return comReturn(false,$validate->getError(), '', 500);
            }

            $reserveModel = new BillOrderReserve();
            $temp = $reserveModel
                    ->where("rid", $rid)
                    ->field("status")
                    ->find();
            //判断预约状态
            if ($temp['status'] == 0 || $temp['status'] == 1 || $temp['status'] == 3) {
                return comReturn(false,config('order.reserve_complete_error')[$temp['status']], '', 500);
            }

            $reserve['status']     = 3;
            $reserve['updated_at'] = time();

            $order['sale_status'] = 2;
            $order['finish_time'] = time();
            $order['updated_at']  = time();

            $goods['status'] = 2;
            $goods['verify_time'] = time();
            $goods['updated_at']  = time();

            $orderModel = new BillOrder();
            $goodsModel = new BillOrderGoods();

            unset($temp);
            $temp = $goodsModel
                    ->where('rid', $rid)
                    ->field("cid,oid")
                    ->find();
            $oid = $temp['oid'];
            $cid = $temp['cid'];


            Db::startTrans();

            $is_ok1 = $reserveModel
                     ->where("rid", $rid)
                     ->update($reserve);

            $is_ok2 = $orderModel
                     ->where("oid", $oid)
                     ->update($order);

            $is_ok3 = $goodsModel
                     ->where("cid", $cid)
                     ->update($goods);


            //计算佣金
            $commission = $orderModel
                          ->where("oid", $oid)
                          ->field("pid,p_commission,sid,shop_gain,deal_shop_gain,commission,deal_commission,deal_amount")
                          ->find();

            $partnerModel = new Partner();
            $count = $partnerModel
                     ->where("pid", $commission['pid'])
                     ->count();


            $admin = $this->tokenGetAdmin($Token);//获取管理员信息
            //判断是否有合伙人
            if ($commission['pid'] && $count == 1 && $commission['p_commission'] > 0) {
                $partnerAccountModel = new PartnerAccount();

                unset($temp);
                $temp = $partnerModel
                        ->where("pid", $commission['pid'])
                        ->field("account_balance")
                        ->find();

                //合伙人账户变动
                $partner['account_balance'] = $temp['account_balance']+$commission['p_commission'];

                $is_ok4 = $partnerModel
                         ->where("pid", $commission['pid'])
                         ->update($partner);

                //合伙人资金明细
                $partner_account['pid']          = $commission['pid'];
                $partner_account['balance']      = "+".$commission['p_commission'];//账户可用余额变动
                $partner_account['last_balance'] = $temp['account_balance']+$commission['p_commission'];//变动后的钱包总余额
                $partner_account['change_type']  = 1;//变更类型
                $partner_account['action_user']  = $admin['log_name'];//操作用户名
                $partner_account['action_type']  = 500;//500 账户佣金收入（余额账户+）
                $partner_account['oid']          = $oid;
                $partner_account['deal_amount']  = $commission['deal_amount'];
                $partner_account['action_desc']  = config('account.partner_account_action_type')['account_commission']['name'];
                $is_ok5 = $partnerAccountModel
                         ->insertGetId_ex($partner_account, true);
            }else{
                $is_ok4 = true;
                $is_ok5 = true;
            }

            //判断店铺收入
            if ($commission['shop_gain'] > 0) {
                $shopModel = new Shop();
                $shopAccountModel = new ShopAccount();

                unset($temp);
                $temp = $shopModel
                        ->where("sid", $commission['sid'])
                        ->field("account_balance")
                        ->find();

                //店铺账户变动
                $shop['account_balance'] = $temp['account_balance']+$commission['deal_shop_gain'];

                $is_ok6 = $shopModel
                         ->where("sid", $commission['sid'])
                         ->update($shop);

                //店铺资金明细-订单完成
                $shop_account['sid']          = $commission['sid'];
                $shop_account['balance']      = "+".$commission['shop_gain'];//账户可用余额变动
                $shop_account['last_balance'] = $temp['account_balance']+$commission['shop_gain'];//变动后的钱包总余额
                $shop_account['change_type']  = 1;//变更类型
                $shop_account['action_user']  = $admin['log_name'];//操作用户名
                $shop_account['action_type']  = 101;//101 订单交易完成
                $shop_account['oid']          = $oid;
                $shop_account['deal_amount']  = $commission['deal_amount'];
                // $shop_account['p_commission'] = $commission['p_commission'];
                // $shop_account['commission']   = $commission['deal_commission'];
                $shop_account['action_desc']  = config('account.shop_account_action_type')['order_complete']['name'];
                $is_ok7 = $shopAccountModel
                         ->insertGetId_ex($shop_account, true);

                //店铺资金明细-扣除佣金
                unset($shop_account);
                $shop_account['sid']          = $commission['sid'];
                $shop_account['balance']      = "-".$commission['commission'];//账户可用余额变动
                $shop_account['last_balance'] = $shop['account_balance'];//变动后的钱包总余额
                $shop_account['change_type']  = 1;//变更类型
                $shop_account['action_user']  = $admin['log_name'];//操作用户名
                $shop_account['action_type']  = 700;//101 订单交易完成
                $shop_account['oid']          = $oid;
                $shop_account['deal_amount']  = $commission['deal_amount'];
                $shop_account['p_commission'] = $commission['p_commission'];
                $shop_account['commission']   = $commission['deal_commission'];
                $shop_account['action_desc']  = config('account.shop_account_action_type')['platform_deduct_commission']['name'];
                $is_ok7 = $shopAccountModel
                         ->insertGetId_ex($shop_account, true);

            }else{
                $is_ok6 = true;
                $is_ok7 = true;
            }

            if ($is_ok1 && $is_ok2 && $is_ok3 && $is_ok4 && $is_ok5 && $is_ok6 && $is_ok7) {
                Db::commit();

                //记录日志
                unset($reserve);
                $reserve = $reserveModel
                           ->alias('bor')
                           ->join('dts_bill_order_goods bog','bog.oid = bor.oid AND bog.gid = bor.gid', 'LEFT')
                           ->join('dts_bill_order bo','bo.oid = bog.oid','LEFT')
                           ->field("bor.gid,bor.oid,bo.sid")
                           ->where("bor.rid", $rid)
                           ->find();
                $this->addAdminLog($reserve['gid'], $reserve['oid'], 'consumption_complete', $admin['log_name'], $reserve['sid']);

                //记录日志
                $logtext = "(RID:".$rid.")";
                $logtext = $this->infoAddClass($logtext, 'text-edit');
                $route = $this->request->routeInfo();
                $route_tran = $this->routeTranslation($route, 'shop_menu');
                $this->addSysLog(time(), $admin['log_name'], $route_tran.$logtext, $request->ip(), $reserve['sid']);

                return comReturn(true,config('return_message.success'));
            }else{
                Db::rollback();
                return comReturn(false,config('return_message.fail'), '', 500);
            }
        }catch (Exception $e){
            return comReturn(false, $e->getMessage(), '', 500);
        }
    }


    /**
     * 预约取消
     * @param Request $request
     * @return array
     */
    public function reserveCancel(Request $request)
    {
        $Token = Request::instance()->header("Token","");

        $rid            = $request->param("rid", "");
        $cancel_type    = $request->param("cancel_type", "");//1用户取消 2店铺取消
        $cancel_desc    = $request->param("cancel_desc", "");

        $reserve['rid'] = $rid;
        $reserve['cancel_type'] = $cancel_type;
        $reserve['cancel_desc'] = $cancel_desc;

        Log::info("取消参数  ---- ".var_export($reserve,true));

        try{
            //规则验证
            $rule = [
                "rid|预约id"           => "require",
                "cancel_type|取消类型" => "require",
                "cancel_desc|取消原因" => "require",
            ];
            $check_data = [
                "rid"         => $rid,
                "cancel_type" => $cancel_type,
                "cancel_desc" => $cancel_desc,
            ];

            $validate = new Validate($rule);
            if (!$validate->check($check_data)){
                return comReturn(false,$validate->getError(), '', 500);
            }

            $reserveModel = new BillOrderReserve();
            unset($temp);
            $temp = $reserveModel
                    ->where("rid", $rid)
                    ->field("status")
                    ->find();
            //判断预约状态
            if ($temp['status'] == 3) {
                return comReturn(false,config('order.reserve_complete_error')[$temp['status']]);
            }

            $admin = $this->tokenGetAdmin($Token);//获取管理员信息

            $reserve['status']      = 9;
            $reserve['cancel_user'] = $admin['log_name'];
            $reserve['cancel_time'] = time();
            $reserve['updated_at']  = time();

            // 获取修改之前的数据
            $keys = array_keys($reserve);
            $databefore_reserve = $this->updateBefore("bill_order_reserve", "rid", $rid, $keys);

            Db::startTrans();

            $is_ok1 = $reserveModel
                     ->where("rid", $rid)
                     ->update($reserve);

            unset($temp);
            $temp = $reserveModel
                    ->alias('bor')
                    ->join('dts_bill_order_goods bog','bog.oid = bor.oid AND bog.gid = bor.gid', 'LEFT')
                    ->join('dts_bill_order bo','bo.oid = bog.oid','LEFT')
                    ->where("bor.rid", $rid)
                    ->field("bog.cid")
                    ->find();

            $goodsModel = new BillOrderGoods();
            $goods['rid'] = "";

            $is_ok2 = $goodsModel
                      ->where("rid", $rid)
                      ->update($goods);
            if ($is_ok1 && $is_ok2) {
                Db::commit();

                //记录日志
                unset($reserve);
                $reserve = $reserveModel
                           ->alias('bor')
                           ->join('dts_bill_order_goods bog','bog.oid = bor.oid AND bog.gid = bor.gid', 'LEFT')
                           ->join('dts_bill_order bo','bo.oid = bog.oid','LEFT')
                           ->field("bor.gid,bor.oid,bo.sid")
                           ->where("bor.rid", $rid)
                           ->find();
                $this->addAdminLog($reserve['gid'], $reserve['oid'], 'reserve_cancel', $admin['log_name'], $reserve['sid']);

                //记录日志
                $logtext = "(RID:".$rid.")";
                $logtext = $this->infoAddClass($logtext, 'text-edit');
                $route = $this->request->routeInfo();
                $route_tran = $this->routeTranslation($route, 'shop_menu');
                $this->addSysLog(time(), $admin['log_name'], $route_tran.$logtext, $request->ip(), $reserve['sid']);


                return comReturn(true,config('return_message.success'));
            }else{
                Db::rollback();
                return comReturn(false,config('return_message.fail'), '', 500);
            }
        }catch (Exception $e){
            return comReturn(false, $e->getMessage(), '', 500);
        }
    }

}