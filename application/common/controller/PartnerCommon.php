<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/11/2
 * Time: 上午11:08
 */
namespace app\common\controller;

use app\wechat\model\BillOrder;
use app\wechatpublic\model\Partner;
use think\Controller;
use think\Db;

class PartnerCommon extends Controller
{
    /**
     * openId获取合伙人信息
     * @param $wxid
     * @return array|false|mixed|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function wxidGetPartnerInfo($wxid)
    {
        $partnerModel = new Partner();

        $column = $partnerModel->column;

        $partnerInfo = $partnerModel
            ->where('wxid', $wxid)
            ->field($column)
            ->find();
        $partnerInfo = json_decode(json_encode($partnerInfo), true);

        if (!empty($partnerInfo)) {
            $review_desc = $partnerInfo['review_desc'];
            $partnerInfo['review_desc'] = htmlspecialchars_decode($review_desc);
        }

        return $partnerInfo;
    }

    /**
     * 注册新的合伙人
     * @param $phone
     * @param $name
     * @param $mp_openid
     * @param $wxid
     * @param $nickname
     * @param $avatar
     * @param $sex
     * @return array|bool
     * @throws \Exception
     */
    public function registerNewPartner($phone,$name,$mp_openid, $wxid, $nickname, $avatar, $sex)
    {
        $pid = generateReadableUUID("P");

        /*注册小程序二维码 On*/
        $wxQcCode = new WxQrCode();
        $res = $wxQcCode->create($pid);
        if (isset($res['result']) && !$res['result']) {
            return comReturn(false,config('message.create_qr_code_error'));
        }
        $qr_code = $res['data'];
        /*注册小程序二维码 Off*/

        if ($sex == '1') {
            $sex = "男";
        }elseif ($sex == '2') {
            $sex = "女";
        }else {
            $sex = "";
        }

        $params = [
            "pid"            => $pid,
            "phone"          => $phone,
            "name"           => $name,
            "password"       => jmPassword(config('default_password')),
            "qr_code"        => $qr_code,
            "wxid"           => $wxid,
            "mp_openid"      => $mp_openid,
            "nickname"       => $nickname,
            "avatar"         => $avatar,
            "sex"            => $sex,
            "register_way"   => "wap",
            "register_time"  => time(),
            "lastlogin_time" => time(),
            "status"         => config('partner.status')['wait_check']['key'],
            "remember_token" => jmToken(generateReadableUUID("Q") . time()),
            "token_lastime"  => time(),
            "created_at"     => time(),
            "updated_at"     => time()
        ];
        $partnerModel = new Partner();

        $res = $partnerModel
            ->insert($params);

        if ($res) {
            return $params;
        } else {
            return false;
        }
    }

    /**
     * 更新合伙人信息
     * @param $params
     * @param $pid
     * @return bool
     */
    public function updatePartnerInfo($params, $pid)
    {
        $partnerModel = new Partner();

        $res = $partnerModel
            ->where('pid', $pid)
            ->update($params);

        if ($res !== false) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * pid获取收益和
     * @param $pid
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function pidGetEarningsSum($pid)
    {
        $partnerModel = new Partner();

        $partnerInfo = $partnerModel
            ->where('pid', $pid)
            ->field('account_balance,account_freeze,account_cash')
            ->find();

        $partnerInfo = json_decode(json_encode($partnerInfo), true);

        $account_balance = $partnerInfo['account_balance'];//用户钱包可用余额
        $account_freeze = $partnerInfo['account_freeze'];//钱包冻结金额
        $account_cash = $partnerInfo['account_cash'];//已提现金额

        //累计收益和
        $earnings_sum = bcadd(bcadd($account_freeze, $account_cash, 4), $account_balance, 2);

        return $earnings_sum;
    }

    /**
     * pid获取收益列表
     * @param $pid
     * @param int $page_size
     * @param int $now_page
     * @return \think\Paginator
     * @throws \think\exception\DbException
     */
    public function pidGetEarningsList($pid, $page_size = 5, $now_page = 1)
    {
        if (empty($page_size)) $page_size = config('xcx_page_size');
        if (empty($now_page)) $now_page = 1;

        $config = [
            "page" => $now_page,
        ];

        $billOrderModel = new BillOrder();

        $res = $billOrderModel
            ->alias('bo')
            ->join('billOrderGoods bog', 'bog.oid = bo.oid')
            ->join('user u', 'u.uid = bo.uid')
            ->where('bo.pid', $pid)
            ->field('truncate(bo.p_commission,2) p_commission,truncate(bo.deal_amount,2) deal_amount,bo.created_at')
            ->field('bog.goods_name')
            ->field('u.name,u.nickname,u.avatar')
            ->paginate($page_size, false, $config);

        return $res;
    }

    /**
     * pid获取下线列表
     * @param $pid
     * @param int $page_size
     * @param int $now_page
     * @return \think\Paginator
     * @throws \think\exception\DbException
     */
    public function pidGetOfflineList($pid, $page_size = 20, $now_page = 1)
    {
        if (empty($page_size)) $page_size = config('page_size');
        if (empty($now_page)) $now_page = 1;

        $config = [
            "page" => $now_page,
        ];

        $res = Db::name('partner_user')
            ->alias('pu')
            ->join('user u', 'u.uid = pu.uid')
            ->where('pu.pid', $pid)
            ->order('pu.order_num DESC')
            ->field('pu.order_num')
            ->field('u.name,u.nickname,u.avatar')
            ->paginate($page_size, false, $config);

        return $res;
    }

    /**
     * pid获取提现明细
     * @param $pid
     * @param int $page_size
     * @param int $now_page
     * @return \think\Paginator
     * @throws \think\exception\DbException
     */
    public function pidGetWithdrawDetails($pid, $page_size = 20, $now_page = 1)
    {
        if (empty($page_size)) $page_size = config('page_size');
        if (empty($now_page)) $now_page = 1;

        $config = [
            "page" => $now_page,
        ];

        $action_type = config("account.partner_account_action_type")['end_withdraw']['key'];

        $res = Db::name('partner_account')
            ->where('pid',$pid)
            ->where('action_type',$action_type)
            ->where('cash','>',0)
            ->order('created_at DESC')
            ->field('account_id,truncate(cash,2) cash,action_desc,created_at')
            ->paginate($page_size, false, $config);

        return $res;
    }

    /**
     * pid获取提现账户列表
     * @param $pid
     * @return false|mixed|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function pidGetWithdrawalAccount($pid)
    {
        $wx     = config('account.withdrawal_type')['wx']['key'];
        $alipay = config('account.withdrawal_type')['alipay']['key'];
        $bank   = config('account.withdrawal_type')['bank']['key'];

        $wx_account = Db::name("partner_withdrawals_account")
            ->where('pid',$pid)
            ->where('type',$wx)
            ->find();
        $wx_account = json_decode(json_encode($wx_account),true);

        if (!empty($wx_account)) {
            $wx_account['account'] = strReplace($wx_account['account'],"3","2");
        }

        $alipay_account = Db::name("partner_withdrawals_account")
            ->where('pid',$pid)
            ->where('type',$alipay)
            ->find();
        $alipay_account = json_decode(json_encode($alipay_account),true);

        if (!empty($alipay_account)) {
            $alipay_account['account'] = strReplace($alipay_account['account'],"3","2");
        }

        $bank_account = Db::name("partner_withdrawals_account")
            ->where('pid',$pid)
            ->where('type',$bank)
            ->find();
        $bank_account = json_decode(json_encode($bank_account),true);

        if (!empty($bank_account)) {
            $bank_account['account'] = strReplace($bank_account['account'],"3","2");
        }

        $res['wx_account']     = $wx_account;
        $res['alipay_account'] = $alipay_account;
        $res['bank_account']   = $bank_account;

        return $res;
    }

    /**
     * 合伙人提现账户添加
     * @param $pid
     * @param $type
     * @param $account
     * @param $name
     * @param $bank
     * @return array
     */
    public function addWithdrawalAccount($pid,$type,$account,$name,$bank)
    {
        /*检测是否在数据库中同类型唯一 On*/
        $is_only = $this->checkAccountOnly($pid,$type);
        if (!$is_only){
            return comReturn(false,config('message.account_is_exist'));
        }
        /*检测是否在数据库中同类型唯一 Off*/

        $params = [
            "pid"     => $pid,
            "type"    => $type,
            "account" => $account,
            "name"    => $name,
            "bank"    => $bank
        ];

        $res = Db::name("partner_withdrawals_account")
            ->insert($params);

        if ($res !== false){
            return comReturn(true,config('return_message.success'));
        }else{
            return comReturn(false,config('return_message.fail'));
        }
    }

    /**
     * 编辑提现账户信息
     * @param $pid
     * @param $id
     * @param $type
     * @param $account
     * @param $name
     * @param $bank
     * @return array
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function editWithdrawalAccount($pid,$id,$type,$account,$name,$bank)
    {
        $wx     = config('account.withdrawal_type')['wx']['key'];
        $alipay = config('account.withdrawal_type')['alipay']['key'];

        if ($type == $wx || $type == $alipay){
            $params = [
                "account" => $account,
                "name"    => $name,
            ];
        }else{
            //检测银行卡格式
            if (!checkBankCard($account)) return comReturn(false,config('message.bank_num_error'));

            $params = [
                "account" => $account,
                "name"    => $name,
                "bank"    => $bank,
            ];
        }

        $res = Db::name("partner_withdrawals_account")
            ->where('pid',$pid)
            ->where('id',$id)
            ->update($params);

        if ($res !== false){
            return comReturn(true,config('return_message.success'));
        }else{
            return comReturn(false,config('return_message.fail'));
        }
    }

    /**
     * 根据账户id获取提现账户信息
     * @param $pid
     * @param $id
     * @return array|false|mixed|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getAccountInfo($pid,$id)
    {
        $res = Db::name("partner_withdrawals_account")
            ->where('pid',$pid)
            ->where('id',$id)
            ->find();
        $res = json_decode(json_encode($res),true);
        return $res;
    }

    /**
     * 提现申请提交
     * @param $pid
     * @param $account_id
     * @param $amount
     * @param $account_balance
     * @param $account_freeze
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function withdrawalPost($pid,$account_id,$amount,$account_balance,$account_freeze)
    {
       /*获取提现账户信息 On*/
        $account_info = $this->getAccountInfo($pid,$account_id);

        if (empty($account_info)){
            return false;
        }
        $type    = $account_info['type'];
        $account = $account_info['account'];
        $name    = $account_info['name'];
        $bank    = $account_info['bank'];
       /*获取提现账户信息 Off*/

        $charge       = 0;
        $apply_remark = "";
        /*写入提现申请订单 On*/
        $partner_caid = $this->insertBillPartnerWithdrawals("$pid","$type","$account","$bank","$name","$amount","$charge","$apply_remark");

        if ($partner_caid === false){
            return false;
        }
        /*写入提现申请订单 Off*/

        /*冻结提现合伙人余额 On*/
        $new_account_balance = bcsub($account_balance,$amount,4);
        $new_account_freeze  = bcadd($account_freeze,$amount,4);
        $partnerParams = [
            "account_balance" => $new_account_balance,
            "account_freeze"  => $new_account_freeze,
            "updated_at"      => time()
        ];
        $updatePartnerInfoReturn = $this->updatePartnerInfo($partnerParams,$pid);

        if (!$updatePartnerInfoReturn){
            return false;
        }
        /*冻结提现合伙人余额 Off*/

        /*冻结明细 On*/
        $partnerAccountParams = [
            "pid"           => $pid,
            "freeze"        => $amount,
            "last_freeze"   => $new_account_freeze,
            "change_type"   => config("account.partner_account_change_type")['sys']['key'],
            "action_user"   => config("account.partner_account_change_type")['sys']['name'],
            "action_type"   => config("account.partner_account_action_type")['start_withdraw']['key'],
            "oid"           => $partner_caid,
            "deal_amount"   => $amount,
            "action_desc"   => config("account.partner_account_action_type")['start_withdraw']['name'],
            "created_at"    => time(),
            "updated_at"    => time()
        ];

        $orderCommonObj = new OrderCommon();

        $insertPartnerAccountReturn = $orderCommonObj->insertPartnerAccount($partnerAccountParams);
        if (!$insertPartnerAccountReturn){
            return false;
        }
        /*冻结明细 Off*/

        return true;
    }


    /**
     * 插入新的提现申请
     * @param $pid
     * @param $type
     * @param $account
     * @param $bank
     * @param $name
     * @param $amount
     * @param $charge
     * @param $apply_remark
     * @return bool
     */
    public function insertBillPartnerWithdrawals($pid,$type,$account,$bank,$name,$amount,$charge,$apply_remark)
    {
        $partner_caid = generateReadableUUID("CP");
        $params = [
            "partner_caid" => $partner_caid,
            "pid"          => $pid,
            "receipt_type" => $type,
            "account_num"  => $account,
            "bank"         => $bank,
            "name"         => $name,
            "amount"       => $amount,
            "charge"       => $charge,
            "apply_time"   => time(),
            "apply_remark" => $apply_remark,
            "status"       => config('account.withdrawal_status')['wait_check']['key'],
            "created_at"   => time(),
            "updated_at"   => time(),
        ];

        $is_ok = Db::name('bill_partner_withdrawals')
            ->insert($params);

        if ($is_ok){
            return $partner_caid;
        }else{
            return false;
        }
    }

    /**
     * 检测合伙人账户中是否已存在该类型账户
     * @param $pid
     * @param $type
     * @return bool
     */
    public function checkAccountOnly($pid,$type)
    {
        $is_only = Db::name('partner_withdrawals_account')
            ->where('pid',$pid)
            ->where('type',$type)
            ->count();

        if ($is_only > 0){
            return false;
        }else{
            return true;
        }
    }

    /**
     * 获取合伙人正在提现订单信息
     * @param $pid
     * @return array|false|mixed|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function checkPartnerWithdrawalStatus($pid)
    {
        $res = Db::name('bill_partner_withdrawals')
            ->where('pid',$pid)
            ->where('is_finish',0)
            ->field('partner_caid,receipt_type,account_num,bank,name,truncate(amount,2) amount,truncate(charge,2) charge,apply_time,apply_remark,pay_no,status,is_finish,review_time,review_desc,created_at')
            ->find();

        $res = json_decode(json_encode($res),true);

        return $res;
    }

    /**
     * 更新提现单据信息
     * @param $partner_caid
     * @param $params
     * @return bool
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function updatePartnerWithdrawalInfo($partner_caid,$params)
    {
        $res =  Db::name('bill_partner_withdrawals')
            ->where('partner_caid',$partner_caid)
            ->update($params);

        if ($res !== false) {
            return true;
        }else{
            return false;
        }
    }
}