<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/11/2
 * Time: 下午4:02
 */
namespace app\wechatpublic\controller\partner;

use app\common\controller\OrderCommon;
use app\common\controller\PartnerAuth;
use app\common\controller\PartnerCommon;
use app\wechatpublic\model\Partner;
use think\Db;
use think\Exception;
use think\Validate;

class Wallet extends PartnerAuth
{
    /**
     * 钱包预览
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function walletPreview()
    {
        $token = $this->request->header('Token', '');

        $partnerModel = new Partner();

        $res = $partnerModel
            ->where('remember_token', $token)
            ->field('truncate(account_balance,2) account_balance,truncate(account_freeze,2) account_freeze,truncate(account_cash,2) account_cash')
            ->find();

        return comReturn(true, config('return_message.success'), $res);
    }

    /**
     * 提现明细
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function withdrawalDetail()
    {
        $now_page    = $this->request->param('now_page','');
        $page_size   = $this->request->param('page_size','');
        $token       = $this->request->header('Token','');

        $partnerInfo = $this->tokenGeyPartnerInfo($token);
        $pid = $partnerInfo['pid'];

        $partnerCommonObj = new PartnerCommon();

        $res = $partnerCommonObj->pidGetWithdrawDetails($pid,$page_size, $now_page);

        return comReturn(true,config('return_message.success'),$res);
    }

    /**
     * 提现到账账户列表
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function withdrawalAccount()
    {
        $token            = $this->request->header('Token','');
        $partnerInfo      = $this->tokenGeyPartnerInfo($token);
        $pid              = $partnerInfo['pid'];
        $partnerCommonObj = new PartnerCommon();
        $res = $partnerCommonObj->pidGetWithdrawalAccount($pid);
        return comReturn(true,config('return_message.success'),$res);
    }

    /**
     * 添加提现账户
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function withdrawalAccountAdd()
    {
        $type    = $this->request->param('type','');
        $account = $this->request->param('account','');
        $name    = $this->request->param('name','');
        $bank    = $this->request->param('bank','');

        $rule = [
            "type|账户类型" => "require",
            "name|姓名"    => "require",
            "account|账号" => "require",
        ];
        $check_data = [
            "type"    => $type,
            "name"    => $name,
            "account" => $account
        ];

        $validate = new Validate($rule);
        if (!$validate->check($check_data)){
            return comReturn(false,$validate->getError());
        }

        if ($type == config('account.withdrawal_type')['bank']['key']) {
            //如果提现账户类型是银行,则开户行不能为空
            if (empty($bank)) {
                return comReturn(false,config('message.bank_no_empty'));
            }
        }

        $token       = $this->request->header('Token','');
        $partnerInfo = $this->tokenGeyPartnerInfo($token);
        $pid         = $partnerInfo['pid'];

        $partnerCommonObj = new PartnerCommon();
        $res = $partnerCommonObj->addWithdrawalAccount("$pid","$type","$account","$name","$bank");
        return $res;
    }

    /**
     * 提现到账账户编辑
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function withdrawalAccountEdit()
    {
        $id      = $this->request->param('id','');
        $type    = $this->request->param('type','');
        $name    = $this->request->param('name','');
        $bank    = $this->request->param('bank','');
        $account = $this->request->param('account','');

        $rule = [
            "id|账户"      => "require",
            "type|账户类型" => "require",
            "name|姓名"    => "require",
            "account|账号" => "require",
        ];
        $check_data = [
            "id"      => $id,
            "type"    => $type,
            "name"    => $name,
            "account" => $account
        ];

        $validate = new Validate($rule);
        if (!$validate->check($check_data)){
            return comReturn(false,$validate->getError());
        }

        $token            = $this->request->header('Token','');
        $partnerInfo      = $this->tokenGeyPartnerInfo($token);
        $pid              = $partnerInfo['pid'];

        $partnerCommonObj = new PartnerCommon();
        $res = $partnerCommonObj->editWithdrawalAccount("$pid","$id","$type","$account","$name","$bank");
        return $res;
    }

    /**
     * 获取余额以及提现状态
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getWithdrawalInfo()
    {
        $token           = $this->request->header('Token','');
        $partnerInfo     = $this->tokenGeyPartnerInfo($token);
        $pid             = $partnerInfo['pid'];
        $account_balance = $partnerInfo['account_balance'];

        $partnerCommonObj = new PartnerCommon();

        $withdrawal_info = $partnerCommonObj->checkPartnerWithdrawalStatus($pid);

        $res['account_balance'] = $account_balance;
        $res['withdrawal_info'] = $withdrawal_info;

        return comReturn(true,config('return_message.success'),$res);
    }

    /**
     * 提现申请提交
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function withdrawalPost()
    {
        $id      = $this->request->param('id','');//账户id
        $money   = $this->request->param('money','');//提现金额

        $rule = [
            "id|账户"    => "require",
            "money|账号" => "require",
        ];
        $check_data = [
            "id"    => $id,
            "money" => $money
        ];
        $validate = new Validate($rule);
        if (!$validate->check($check_data)){
            return comReturn(false,$validate->getError());
        }

        $token           = $this->request->header('Token','');
        $partnerInfo     = $this->tokenGeyPartnerInfo($token);
        $pid             = $partnerInfo['pid'];
        $account_balance = $partnerInfo['account_balance'];
        $account_freeze  = $partnerInfo['account_freeze'];

        $partnerCommonObj = new PartnerCommon();
        $withdrawal_info = $partnerCommonObj->checkPartnerWithdrawalStatus($pid);

        if (!empty($withdrawal_info)) {
            return comReturn(false,config('message.have_withdrawal_order'));
        }

        /*获取合伙人提现单笔下限值(元) On*/
        $partner_cash_lower_limit = $this->getSettingInfo("partner_cash_lower_limit");
        $sysMoneyDown = $partner_cash_lower_limit['partner_cash_lower_limit'];
        /*获取合伙人提现单笔下限值(元) Off*/

        if ($money < $sysMoneyDown){
            return comReturn(false,config('message.withdrawal_insufficient').$sysMoneyDown);
        }

        if ($account_balance < $money) {
            return comReturn(false, config('message.balance_insufficient'));
        }

        Db::startTrans();
        try {
            $withdrawalPostReturn = $partnerCommonObj->withdrawalPost("$pid", "$id", "$money", $account_balance, $account_freeze);

            if (!$withdrawalPostReturn) {
                return comReturn(false, config('message.withdrawal_error'));
            }
            Db::commit();
            return comReturn(true, config('return_message.success'));
        } catch (Exception $e) {
            Db::rollback();
            return comReturn(false, $e->getMessage());
        }
    }

    /**
     * 重新申请提现
     * @return array
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function withdrawalAgain()
    {
        $partner_caid = $this->request->param('partner_caid',"");//提现申请id
        $rule = [
            "partner_caid|单据" => "require",
        ];
        $check_data = [
            "partner_caid" => $partner_caid,
        ];
        $validate = new Validate($rule);
        if (!$validate->check($check_data)) {
            return comReturn(false, $validate->getError());
        }

        $partnerWithdrawalInfo = Db::name('bill_partner_withdrawals')
            ->where('partner_caid',$partner_caid)
            ->find();

        $partnerWithdrawalInfo = json_decode(json_encode($partnerWithdrawalInfo),true);

        $status = $partnerWithdrawalInfo['status'];
        $amount = $partnerWithdrawalInfo['amount'];

        if ($status != config('account.withdrawal_status')['wait_check']['key']) {
            //如果不是提现审核中,不可进行操作
            return comReturn(false,config('message.status_no_checked'));
        }

        $params = [
            "status"      => config('account.withdrawal_status')['fail']['key'],
            "is_finish"   => 1,
            "review_time" => time(),
            "review_user" => "user",
            "updated_at"  => time()
        ];

        $partnerCommonObj = new PartnerCommon();

        Db::startTrans();
        try{
            //更新提现账单信息
            $updatePartnerWithdrawalInfoReturn = $partnerCommonObj->updatePartnerWithdrawalInfo($partner_caid,$params);
            if (!$updatePartnerWithdrawalInfoReturn){
                return comReturn(false,config('return_message.fail'));
            }

            //更新合伙人账户信息以及插入明细信息
            $token = $this->request->header('Token', '');
            $partnerInfo = $this->tokenGeyPartnerInfo($token);
            $pid = $partnerInfo['pid'];

            $orderCommonObj = new OrderCommon();
            $updatePartnerAccountReturn = $orderCommonObj->updatePartnerAccount("$pid","$partner_caid","3",$amount);
            if (!$updatePartnerAccountReturn){
                return comReturn(false,config('return_message.fail'));
            }
            Db::commit();
            return comReturn(true,config('return_message.success'));
        }catch (Exception $e) {
            Db::rollback();
            return comReturn(false,$e->getMessage());
        }
    }

    /**
     * 提现申请失败确认
     * @return array
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function confirmResult()
    {
        $partner_caid = $this->request->param('partner_caid',"");//提现申请id

        $rule = [
            "partner_caid|单据" => "require",
        ];
        $check_data = [
            "partner_caid" => $partner_caid,
        ];
        $validate = new Validate($rule);
        if (!$validate->check($check_data)) {
            return comReturn(false, $validate->getError());
        }

        $partnerWithdrawalInfo = Db::name('bill_partner_withdrawals')
            ->where('partner_caid',$partner_caid)
            ->find();

        $partnerWithdrawalInfo = json_decode(json_encode($partnerWithdrawalInfo),true);

        $status = $partnerWithdrawalInfo['status'];

        if ($status == config('account.withdrawal_status')['wait_check']['key']) {
            //如果是提现审核中,不可进行操作
            return comReturn(false,config('message.status_no_action'));
        }

        $is_finish = $partnerWithdrawalInfo['is_finish'];

        if ($is_finish == 1){
            return comReturn(false,config('message.order_finished'));
        }

        $params = [
            "is_finish"  => 1,
            "updated_at" => time()
        ];

        $partnerCommonObj = new PartnerCommon();

        $updatePartnerWithdrawalReturn = $partnerCommonObj->updatePartnerWithdrawalInfo($partner_caid,$params);

        if (!$updatePartnerWithdrawalReturn){
            return comReturn(false,config('return_message.fail'));
        }
        return comReturn(true,config('return_message.success'));
    }
}