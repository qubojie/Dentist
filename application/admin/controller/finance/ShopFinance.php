<?php

/**
 * 店铺资金
 * @Author: zhangtao
 * @Date:   2018-11-06 10:59:58
 * @Last Modified by:   admin
 * @Last Modified time: 2018-12-04 16:02:25
 */
namespace app\admin\controller\finance;

use app\common\controller\SysAdminAuth;
use app\admin\model\SysAdminUser;
use app\shopadmin\model\Shop;
use app\shopadmin\model\ShopAccount;
use app\shopadmin\model\BillShopWithdrawals;
use app\common\controller\AliPay;
use app\common\controller\OrderCommon;
use think\Controller;
use think\Db;
use think\Exception;
use think\Request;
use think\Validate;
use think\Cache;

class ShopFinance extends SysAdminAuth{
    /**
     * 店铺提现列表
     * @param Request $request
     * @return array
     */
    public function index(Request $request){

        $pagesize   = $request->param("pagesize",config('PAGESIZE'));//显示个数,不传时为10

        if (empty($pagesize)) $pagesize = config('PAGESIZE');

        $nowPage    = $request->param("nowPage","1");

        $status       = $request->param("status", 1);//1 待审核    2 提现完成    3 提现失败
        $day          = $request->param("day","");// 1一天内 2两天内 3；两天以上
        $receipt_type = $request->param("receipt_type","");//wx微信  alipay支付宝  card银行卡
        $before_date  = $request->param("before_date", "");//起始时间
        $after_date   = $request->param("after_date", "");//截止时间

        $orderBy['filter']['orderBy'] = $request->has('orderBy') ? $request->param('orderBy') : 'created_at';
        $orderBy['filter']['sort'] = $request->has('sort') ? $request->param('sort') : 'desc';

        $config = [
            "page" => $nowPage,
        ];

        $where = [];
        $where['status'] = $status ? $status : 1;

        if ($day == 1) {
            $time = strtotime("-1day");
            $where['apply_time'] = array('gt', $time);
        }else if($day == 2) {
            $time = strtotime("-2day");
            $where['apply_time'] = array('gt', $time);
        }else if($day == 3) {
            $time = strtotime("-2day");
            $where['apply_time'] = array('lt', $time);
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
                $where['apply_time'] = array($where1, $where2, 'and');
            }elseif(!empty($where1)){
                $where['apply_time'] = $where1;
            }elseif(!empty($where2)){
                $where['apply_time'] = $where2;
            }
        }

        if (!empty($receipt_type)){
            $where["receipt_type"] = $receipt_type;
        }

        try{
            $withdrawalsModel = new BillShopWithdrawals();

            //处理排序条件
            $field_array = array("name");
            if (in_array($orderBy['filter']['orderBy'], $field_array)) {
                $orderBy['filter']['orderBy'] = fieldOrderByEncode($orderBy['filter']['orderBy']);
            }

            $list = $withdrawalsModel
                    ->where($where)
                    // ->order('updated_at desc')
                    ->order($orderBy['filter']['orderBy'], $orderBy['filter']['sort'])
                    ->field('shop_caid,eid,sid,receipt_type,account_num,bank,name,amount,charge,apply_time,apply_remark,pay_no,status,is_finish,review_time,review_user,review_desc,pay_time,pay_user,pay_desc,created_at,updated_at')
                    ->paginate($pagesize,false,$config);

            if ($list) {
                //保留两位小数
                $decimal = $this->getSettingInfo("sys_show_number_decimal")['sys_show_number_decimal'];
                foreach ($list as $key => $value) {
                    $list[$key]['amount'] = sprintf("%.".$decimal."f", $value['amount']);
                }
                return comReturn(true,config("return_message.success"),$list);
            }else{
                return comReturn(false,config("return_message.fail"));
            }
        }catch (Exception $e){
            return comReturn(false, $e->getMessage(), '', 500);
        }
    }

    /**
     * 店铺提现审核
     * @param Request $request
     * @return array
     */
    public function shopWithdrawalsAudit(Request $request){
        $Token = Request::instance()->header("Token","");

        $shop_caid = $request->param("shop_caid", "");//提现申请id
        $status    = $request->param("status", "");//2审核通过 3审核不通过

        try {
            //规则验证
            $rule = [
                "shop_caid|提现申请id" => "require",
                "status|审核状态"      => "require",
            ];
            $check_data = [
                "shop_caid" => $shop_caid,
                "status"    => $status,
            ];

            $validate = new Validate($rule);
            if (!$validate->check($check_data)){
                return comReturn(false,$validate->getError());
            }

            $withdrawalsModel = new BillShopWithdrawals();
            //提现申请数据
            $withdrawals_data = $withdrawalsModel
                                ->where("shop_caid", $shop_caid)
                                ->field("sid,receipt_type,truncate(amount,2) amount,name,account_num")
                                ->find();

            $admin = $this->tokenGetAdminInfo($Token);

            $withdrawals['status']   = $status;
            $withdrawals['review_time'] = time();
            $withdrawals['review_user'] = $admin['user_name'];
            $withdrawals['updated_at']  = time();

            if ($status == 2) {
                if ($withdrawals_data['receipt_type'] == "bank") {
                    $pay_no   = $request->param("pay_no", "");//提现支付回单号
                    $pay_time = $request->param("pay_time", "");//付款时间

                    unset($rule);
                    unset($check_data);
                    //规则验证
                    $rule = [
                        "pay_no|支付回单号" => "require",
                        "pay_time|付款时间" => "require",
                    ];
                    $check_data = [
                        "pay_no"   => $pay_no,
                        "pay_time" => $pay_time,
                    ];

                    $validate = new Validate($rule);
                    if (!$validate->check($check_data)){
                        return comReturn(false,$validate->getError());
                    }

                }else if($withdrawals_data['receipt_type'] == "alipay"){
                    $alipay = new AliPay();
                    $res = $alipay->fundToAccount($shop_caid, $withdrawals_data['account_num'], $withdrawals_data['amount'], $withdrawals_data['name'], '店铺-'.$withdrawals_data['name'].'-提现', date("Y-m-d H:i:s", time()));
                    if ($res['status'] == 'success') {

                        $pay_no   = $res['pay_no'];
                        $pay_time = strtotime($res['pay_time']);

                    }else if($res['status'] == 'fail'){
                        return comReturn(false,$res['msg'], '', 500);
                    }else{
                        return comReturn(false,config("return_message.fail"), '', 500);
                    }
                }else{
                    return comReturn(false,config("account.not_support_withdrawal_type"), '', 500);
                }

                $withdrawals['pay_no']   = $pay_no;
                $withdrawals['pay_time'] = $pay_time;
            }

            // 获取修改之前的数据
            $keys = array_keys($withdrawals);
            $databefore = $this->updateBefore("bill_shop_withdrawals", "shop_caid", $shop_caid, $keys);

            Db::startTrans();
            $res = $withdrawalsModel
                   ->where("shop_caid", $shop_caid)
                   ->update($withdrawals);
            if ($res) {
                $orderCommon = new OrderCommon();
                $result = $orderCommon->updateShopAccount($withdrawals_data['sid'], $shop_caid, $status, $withdrawals_data['amount']);

                if ($result) {
                    Db::commit();

                    $Token = Request::instance()->header("Token","");
                    //记录日志
                    $logtext = $this->checkDifAfter($databefore,$withdrawals);
                    $logtext .= "(SHOP_CAID:".$shop_caid.")";
                    $logtext = $this->infoAddClass($logtext, 'text-edit');
                    $route = $this->request->routeInfo();
                    $route_tran = $this->routeTranslation($route, 'sys_menu');
                    $admin = $this->tokenGetAdminInfo($Token);//获取管理员信息
                    $this->addSysLog(time(), $admin['user_name'], $route_tran.$logtext, $request->ip());

                    return comReturn(true,config("return_message.success"));
                }else{
                    Db::rollback();
                    return comReturn(false,config("return_message.fail"), '', 500);
                }

            }else{
                Db::rollback();
                return comReturn(false,config("return_message.fail"), '', 500);
            }
        } catch (Exception $e) {
            return comReturn(false, $e->getMessage(), '', 500);
        }
    }

    /**
     * 店铺资金列表
     * @param Request $request
     * @return array
     */
    public function shopAccountList(Request $request){

        $pagesize   = $request->param("pagesize",config('PAGESIZE'));//显示个数,不传时为10

        if (empty($pagesize)) $pagesize = config('PAGESIZE');

        $nowPage    = $request->param("nowPage","1");

        $sid        = $request->param("sid", "");//店铺id
        $eid        = $request->param("eid", "");//医院id
        $shop_name  = $request->param("shop_name","");//店铺名称
        $shop_phone = $request->param("shop_phone","");//店铺电话

        $orderBy['filter']['orderBy'] = $request->has('orderBy') ? $request->param('orderBy') : 's.created_at';
        $orderBy['filter']['sort'] = $request->has('sort') ? $request->param('sort') : 'desc';

        $config = [
            "page" => $nowPage,
        ];

        $where = [];
        if (!empty($sid)) {
            $where['s.sid'] = $sid;
        }
        if (!empty($eid)) {
            $where['s.eid'] = $eid;
        }

        if (!empty($shop_name)){
            $where["s.shop_name"] = ["like","%$shop_name%"];
        }
        if (!empty($shop_phone)){
            $where["s.shop_phone"] = ["like","%$shop_phone%"];
        }

        try{
            $shopModel = new Shop();

            //为排序条件加上别名
            if ($orderBy['filter']['orderBy'] == 'shop_name')
            {
                $orderBy['filter']['orderBy'] = 's.shop_name';
            }
            else if($orderBy['filter']['orderBy'] == 'shop_phone')
            {
                $orderBy['filter']['orderBy'] = 's.shop_phone';
            }
            else if ($orderBy['filter']['orderBy'] == 'e_name')
            {
                $orderBy['filter']['orderBy'] = 'se.e_name';
            }
            else if($orderBy['filter']['orderBy'] == 'account_cash')
            {
                $orderBy['filter']['orderBy'] = 's.account_cash';
            }
            else if($orderBy['filter']['orderBy'] == 'account_freeze')
            {
                $orderBy['filter']['orderBy'] = 's.account_freeze';
            }
            else if($orderBy['filter']['orderBy'] == 'account_balance')
            {
                $orderBy['filter']['orderBy'] = 's.account_balance';
            }
            else if($orderBy['filter']['orderBy'] == 'updated_at')
            {
                $orderBy['filter']['orderBy'] = 's.updated_at';
            }

            //处理排序条件
            $field_array = array("s.shop_name", "se.e_name");
            if (in_array($orderBy['filter']['orderBy'], $field_array)) {
                $orderBy['filter']['orderBy'] = fieldOrderByEncode($orderBy['filter']['orderBy']);
            }

            $list = $shopModel
                    ->alias("s")
                    ->join("dts_shop_enterprise se","s.eid = se.eid","LEFT")
                    ->field('s.sid,s.eid,s.shop_phone,s.shop_name,s.account_balance,s.account_freeze,s.account_cash,s.created_at,s.updated_at')
                    ->field('se.e_name')
                    ->where($where)
                    // ->order('s.updated_at desc')
                    ->order($orderBy['filter']['orderBy'], $orderBy['filter']['sort'])
                    ->paginate($pagesize,false,$config);

            if ($list) {

                $decimal = $this->getSettingInfo("sys_show_number_decimal")['sys_show_number_decimal'];
                foreach ($list as $key => $value) {
                    $list[$key]['account_balance'] = sprintf("%.".$decimal."f", $value['account_balance']);
                    $list[$key]['account_freeze'] = sprintf("%.".$decimal."f", $value['account_freeze']);
                    $list[$key]['account_cash'] = sprintf("%.".$decimal."f", $value['account_cash']);
                }
                return comReturn(true,config("return_message.success"),$list);
            }else{
                return comReturn(false,config("return_message.fail"));
            }
        }catch (Exception $e){
            return comReturn(false, $e->getMessage(), '', 500);
        }

    }

    /**
     * 店铺资金调账
     * @param Request $request
     * @return array
     */
    public function shopAccountAdjust(Request $request){
        $sid    = $request->param("sid", "");//店铺id
        $change = $request->param("change", "");//资金变动
        $desc   = $request->param("desc", "");//操作原因

        try{
            //规则验证
            $rule = [
                "sid|店铺id"      => "require",
                "change|资金变动" => "require|number",
                "desc|操作原因"   => "require",
            ];
            $check_data = [
                "sid"    => $sid,
                "change" => $change,
                "desc"   => $desc,
            ];

            $validate = new Validate($rule);
            if (!$validate->check($check_data)){
                return comReturn(false,$validate->getError(), '', 500);
            }

            $shopModel = new Shop();

            Db::startTrans();

            $shop_info = $shopModel
                       ->field("shop_name,account_balance")
                       ->where("sid", $sid)
                       ->find();
            $last_balance = $shop_info['account_balance'] + $change;
            if ($last_balance < 0) {
                return comReturn(false,config('finance.insufficient_adjust'));
            }

            $shop['account_balance'] = $last_balance;
            $shop['updated_at']      = time();

            // 获取修改之前的数据
            $keys = array_keys($shop);
            $databefore = $this->updateBefore("shop", "sid", $sid, $keys);

            $is_ok = $shopModel
                     ->where("sid", $sid)
                     ->update($shop);

            if ($is_ok !== false){
                $accountModel = new ShopAccount();

                $account['sid']          = $sid;
                $account['balance']      = $change;
                $account['last_balance'] = $last_balance;
                $account['change_type']  = 2;
                $account['action_user']  = 'sys';
                $account['action_type']  = 900;
                $account['action_desc']  = $desc;

                $account_id = $accountModel->insertGetId_ex($account, true);

                if ($account_id > 0) {
                    Db::commit();

                    $Token = Request::instance()->header("Token","");
                    //记录日志
                    $logtext = $this->checkDifAfter($databefore,$shop);
                    $logtext .= "(SID:".$sid.")";
                    $logtext = $this->infoAddClass($logtext, 'text-edit');
                    $route = $this->request->routeInfo();
                    $route_tran = $this->routeTranslation($route, 'sys_menu');
                    $admin = $this->tokenGetAdminInfo($Token);//获取管理员信息
                    $this->addSysLog(time(), $admin['user_name'], $route_tran.$logtext, $request->ip());

                    // //获取当前登录管理员
                    // $admin = $this->tokenGetAdminInfo($Token);
                    // $action_user = $admin['user_name'];
                    // //添加至系统操作日志
                    // $this->addSysLog(time(),$action_user,"店铺调账 -> ".$shop_info['shop_name']."(".$sid.")",$request->ip());

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
     * 店铺钱包明细
     * @param Request $request
     * @return array
     */
    public function shopAccountDetail(Request $request){

        $pagesize   = $request->param("pagesize",config('PAGESIZE'));//显示个数,不传时为10

        if (empty($pagesize)) $pagesize = config('PAGESIZE');

        $nowPage    = $request->param("nowPage","1");

        $sid          = $request->param("sid", "");//店铺id
        $before_date  = $request->param("before_date", "");//起始时间
        $after_date   = $request->param("after_date", "");//截止时间

        if (empty($sid)) {
            return comReturn(false,config("finance.no_sid"));
        }

        $config = [
            "page" => $nowPage,
        ];

        $where = [];
        $where['sid'] = $sid;

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
            $where['created_at'] = array($where1, $where2, 'and');
        }elseif(!empty($where1)){
            $where['created_at'] = $where1;
        }elseif(!empty($where2)){
            $where['created_at'] = $where2;
        }

        try{
            $accountModel = new ShopAccount();

            $list = $accountModel
                    ->field('account_id,sid,balance,last_balance,freeze,last_freeze,cash,last_cash,action_user,action_desc,created_at,updated_at')
                    ->where($where)
                    ->order('updated_at desc')
                    ->paginate($pagesize,false,$config);

            if ($list) {
                $decimal = $this->getSettingInfo("sys_show_number_decimal")['sys_show_number_decimal'];
                foreach ($list as $key => $value) {
                    $list[$key]['balance']      = sprintf("%.".$decimal."f", $value['balance']);
                    $list[$key]['last_balance'] = sprintf("%.".$decimal."f", $value['last_balance']);
                    $list[$key]['freeze']       = sprintf("%.".$decimal."f", $value['freeze']);
                    $list[$key]['last_freeze']  = sprintf("%.".$decimal."f", $value['last_freeze']);
                    $list[$key]['cash']         = sprintf("%.".$decimal."f", $value['cash']);
                    $list[$key]['last_cash']    = sprintf("%.".$decimal."f", $value['last_cash']);
                }
                return comReturn(true,config("return_message.success"),$list);
            }else{
                return comReturn(false,config("return_message.fail"));
            }
        }catch (Exception $e){
            return comReturn(false, $e->getMessage(), '', 500);
        }

    }
}