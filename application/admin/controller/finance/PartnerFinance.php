<?php

/**
 * 合伙人资金
 * @Author: zhangtao
 * @Date:   2018-11-06 10:59:27
 * @Last Modified by:   admin
 * @Last Modified time: 2018-12-04 16:04:14
 */
namespace app\admin\controller\finance;

use app\common\controller\SysAdminAuth;
use app\admin\model\SysAdminUser;
use app\wechatpublic\model\Partner;
use app\admin\model\BillPartnerWithdrawals;
use app\shopadmin\model\PartnerAccount;
use app\common\controller\AliPay;
use app\common\controller\OrderCommon;
use think\Controller;
use think\Db;
use think\Exception;
use think\Request;
use think\Validate;
use think\Cache;

class PartnerFinance extends SysAdminAuth{
    // public function ceshi(){

    //     $aa = new AliPay();

    //     $res = $aa->fundToAccount('3142321423412', 'ypnhmv8520@sandbox.com', 1000, '沙箱环境', '合伙人提现', '测试备注');
    //     // $res = $aa->checkFundToAccount();
    //     var_dump($res);
    // }
    /**
     * 合伙人提现列表
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
            $withdrawalsModel = new BillPartnerWithdrawals();

            //处理排序条件
            $field_array = array("name");
            if (in_array($orderBy['filter']['orderBy'], $field_array)) {
                $orderBy['filter']['orderBy'] = fieldOrderByEncode($orderBy['filter']['orderBy']);
            }

            $list = $withdrawalsModel
                    ->where($where)
                    // ->order('updated_at desc')
                    ->order($orderBy['filter']['orderBy'], $orderBy['filter']['sort'])
                    ->field('partner_caid,pid,receipt_type,account_num,bank,name,amount,charge,apply_time,apply_remark,pay_no,status,is_finish,review_time,review_user,review_desc,pay_time,pay_user,pay_desc,created_at,updated_at')
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
     * 合伙人提现审核
     * @param Request $request
     * @return array
     */
    public function partnerWithdrawalsAudit(Request $request){
        $Token = Request::instance()->header("Token","");

        $partner_caid = $request->param("partner_caid", "");//提现申请id
        $status       = $request->param("status", "");//2审核通过 3审核不通过

        try {
            //规则验证
            $rule = [
                "partner_caid|提现申请id" => "require",
                "status|审核状态"         => "require",
            ];
            $check_data = [
                "partner_caid" => $partner_caid,
                "status"       => $status,
            ];

            $validate = new Validate($rule);
            if (!$validate->check($check_data)){
                return comReturn(false,$validate->getError());
            }

            $withdrawalsModel = new BillPartnerWithdrawals();
            //提现申请数据
            $withdrawals_data = $withdrawalsModel
                                ->where("partner_caid", $partner_caid)
                                ->field("pid,receipt_type,truncate(amount,2) amount,name,account_num")
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
                    $res = $alipay->fundToAccount($partner_caid, $withdrawals_data['account_num'], $withdrawals_data['amount'], $withdrawals_data['name'], '合伙人-'.$withdrawals_data['name'].'-提现', date("Y-m-d H:i:s", time()));
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
            $databefore = $this->updateBefore("bill_partner_withdrawals", "partner_caid", $partner_caid, $keys);

            Db::startTrans();

            $res = $withdrawalsModel
                   ->where("partner_caid", $partner_caid)
                   ->update($withdrawals);
            if ($res) {
                $orderCommon = new OrderCommon();
                $result = $orderCommon->updatePartnerAccount($withdrawals_data['pid'], $partner_caid, $status, $withdrawals_data['amount']);

                if ($result) {
                    Db::commit();

                    $Token = Request::instance()->header("Token","");
                    //记录日志
                    $logtext = $this->checkDifAfter($databefore,$withdrawals);
                    $logtext .= "(PARTNER_CAID:".$partner_caid.")";
                    $logtext = $this->infoAddClass($logtext, 'text-edit');
                    $route = $this->request->routeInfo();
                    $route_tran = $this->routeTranslation($route, 'sys_menu');
                    $admin = $this->tokenGetAdminInfo($Token);//获取管理员信息
                    $this->addSysLog(time(), $admin['user_name'], $route_tran.$logtext, $request->ip());

                    return comReturn(true,config("return_message.success"));
                }else{
                    Db::rollback();
                    return comReturn(false,config("return_message.fail"));
                }

            }else{
                Db::rollback();
                return comReturn(false,config("return_message.fail"));
            }
        } catch (Exception $e) {
            return comReturn(false, $e->getMessage(), '', 500);
        }
    }

    /**
     * 合伙人资金列表
     * @param Request $request
     * @return array
     */
    public function partnerAccountList(Request $request){

        $pagesize   = $request->param("pagesize",config('PAGESIZE'));//显示个数,不传时为10

        if (empty($pagesize)) $pagesize = config('PAGESIZE');

        $nowPage    = $request->param("nowPage","1");

        $pid      = $request->param("pid", "");//合伙人id
        $phone    = $request->param("phone","");//手机号码
        $name     = $request->param("name","");//真实姓名
        $nickname = $request->param("nickname", "");//昵称

        $orderBy['filter']['orderBy'] = $request->has('orderBy') ? $request->param('orderBy') : 'created_at';
        $orderBy['filter']['sort'] = $request->has('sort') ? $request->param('sort') : 'desc';

        $config = [
            "page" => $nowPage,
        ];

        $where = [];
        if (!empty($pid)) {
            $where['pid'] = $pid;
        }

        if (!empty($phone)){
            $where["phone"] = ["like","%$phone%"];
        }
        if (!empty($name)){
            $where["name"] = ["like","%$name%"];
        }
        if (!empty($nickname)){
            $where["nickname"] = ["like","%$nickname%"];
        }

        try{
            $partnerModel = new Partner();

            //处理排序条件
            $field_array = array("name", "nickname");
            if (in_array($orderBy['filter']['orderBy'], $field_array)) {
                $orderBy['filter']['orderBy'] = fieldOrderByEncode($orderBy['filter']['orderBy']);
            }

            $list = $partnerModel
                    ->field('pid,phone,name,nickname,account_balance,account_freeze,account_cash,register_time,lastlogin_time,created_at,updated_at')
                    ->where($where)
                    // ->order('updated_at desc')
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
     * 合伙人资金调账
     * @param Request $request
     * @return array
     */
    public function partnerAccountAdjust(Request $request){
        $pid    = $request->param("pid", "");//合伙人id
        $change = $request->param("change", "");//资金变动
        $desc   = $request->param("desc", "");//操作原因

        try{
            //规则验证
            $rule = [
                "pid|合伙人id"    => "require",
                "change|资金变动" => "require|number",
                "desc|操作原因"   => "require",
            ];
            $check_data = [
                "pid"    => $pid,
                "change" => $change,
                "desc"   => $desc,
            ];

            $validate = new Validate($rule);
            if (!$validate->check($check_data)){
                return comReturn(false,$validate->getError(), '', 500);
            }

            $partnerModel = new Partner();

            Db::startTrans();

            $partner_info = $partnerModel
                       ->field("name,account_balance")
                       ->where("pid", $pid)
                       ->find();
            $last_balance = $partner_info['account_balance'] + $change;
            if ($last_balance < 0) {
                return comReturn(false,config('finance.insufficient_adjust'), '', 500);
            }

            $partner['account_balance'] = $last_balance;
            $partner['updated_at']      = time();

            // 获取修改之前的数据
            $keys = array_keys($partner);
            $databefore = $this->updateBefore("partner", "pid", $pid, $keys);

            $is_ok = $partnerModel
                     ->where("pid", $pid)
                     ->update($partner);

            if ($is_ok !== false){
                $accountModel = new PartnerAccount();

                $account['pid']          = $pid;
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
                    $logtext = $this->checkDifAfter($databefore,$partner);
                    $logtext .= "(PID:".$pid.")";
                    $logtext = $this->infoAddClass($logtext, 'text-edit');
                    $route = $this->request->routeInfo();
                    $route_tran = $this->routeTranslation($route, 'sys_menu');
                    $admin = $this->tokenGetAdminInfo($Token);//获取管理员信息
                    $this->addSysLog(time(), $admin['user_name'], $route_tran.$logtext, $request->ip());

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
     * 合伙人钱包明细
     * @param Request $request
     * @return array
     */
    public function partnerAccountDetail(Request $request){

        $pagesize   = $request->param("pagesize",config('PAGESIZE'));//显示个数,不传时为10

        if (empty($pagesize)) $pagesize = config('PAGESIZE');

        $nowPage    = $request->param("nowPage","1");

        $pid          = $request->param("pid", "");//合伙人id
        $before_date  = $request->param("before_date", "");//起始时间
        $after_date   = $request->param("after_date", "");//截止时间

        if (empty($pid)) {
            return comReturn(false,config("finance.no_pid"));
        }

        $config = [
            "page" => $nowPage,
        ];

        $where = [];
        $where['pid'] = $pid;

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

            $accountModel = new PartnerAccount();

            $list = $accountModel
                    ->field('account_id,pid,balance,last_balance,freeze,last_freeze,cash,last_cash,action_user,action_desc,created_at,updated_at')
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
    /**
     * 状态列表
     * @return array
     */
    public function partnerWithdrawalsStatus(){
        $status_list = $this->getStatus("withdrawals");
        return comReturn(true,config("return_message.success"), $status_list);
    }

}