<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/10/24
 * Time: 下午3:28
 */
namespace app\common\controller;

use app\wechat\model\BillOrder;
use app\wechat\model\BillOrderGoods;
use app\wechatpublic\model\Partner;
use app\shopadmin\model\PartnerAccount;
use app\shopadmin\model\Shop;
use app\shopadmin\model\ShopAccount;
use think\Controller;
use think\Db;

class OrderCommon extends Controller
{
    /**
     * 创建订单公共
     * @param $oid
     * @param $gid
     * @param $uid
     * @param $sid
     * @param $order_amount
     * @param $payable_amount
     * @param $discount
     * @param $deal_way
     * @param string $cus_remark
     * @return bool
     */
    public function createdOrder($oid,$uid,$sid,$order_amount,$payable_amount,$discount,$deal_way,$cus_remark = "")
    {
        $sale_status = config('order.sale_status')['wait_pay']['key'];
        $pay_type    = config('pay_type.wxpay')['key'];
        $params = [
            "oid"            => $oid,//单号 ‘O’
            "uid"            => $uid,//商品所有者的用户id
            "sid"            => $sid,//店铺ID
            "sale_status"    => $sale_status,//单据状态  0待付款）  1付款完成待使用   2 已使用交易完成     9交易取消
            "deal_time"      => time(),
            "pay_type"       => $pay_type,//支付方式   微信‘wx’    支付宝 ‘alipay’
            "order_amount"   => $order_amount,//订单金额
            "payable_amount" => $payable_amount,//应付且未付金额
            "discount"       => $discount,//折扣 暂保留
            "deal_way"       => $deal_way,//成交方式  wxapp  微信小程序
            "cus_remark"     => $cus_remark,
            "created_at"     => time(),
            "updated_at"     => time()
        ];

        $billOrderModel = new BillOrder();

        $res = $billOrderModel
            ->insert($params);

        if ($res !== false){
            return true;
        }else{
            return false;
        }
    }


    /**
     * 创建订单明细表
     * @param $cid
     * @param $oid
     * @param $gid
     * @param $sid
     * @param $sn
     * @param $cat_id
     * @param $goods_name
     * @param $goods_sketch
     * @param $goods_original_price
     * @param $goods_price
     * @param $goods_content
     * @return bool
     */
    public function createBillOrderGoods($cid,$oid,$gid,$sid,$sn,$cat_id,$goods_name,$goods_sketch,$goods_original_price,$goods_price,$goods_content)
    {
        $status = config('order.info_status')['no_pay']['key'];
        $verify_code = uniqueNumber("8");
        $params = [
            "cid"                  => $cid,
            "oid"                  => $oid,
            "gid"                  => $gid,
            "sid"                  => $sid,
            "sn"                   => $sn,
            "cat_id"               => $cat_id,
            "goods_name"           => $goods_name,
            "goods_sketch"         => $goods_sketch,
            "goods_original_price" => $goods_original_price,
            "goods_price"          => $goods_price,
            "goods_content"        => $goods_content,
            "status"               => $status,
            "verify_code"          => $verify_code,
            "created_at"           => time(),
            "updated_at"           => time()
        ];

        $billOrderGoodsModel = new BillOrderGoods();

        $res = $billOrderGoodsModel
            ->insert($params);

        if ($res !== false){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 更新订单数据
     * @param $params
     * @param $order_id
     * @return bool
     */
    public function updatedOrder($params,$order_id)
    {
        $orderModel = new BillOrder();

        $res = $orderModel
            ->where('oid',$order_id)
            ->update($params);

        if ($res !== false){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 更新订单详细数据
     * @param $params
     * @param $order_id
     * @return bool
     */
    public function updatedOrderInfo($params,$order_id)
    {
        $orderInfoModel = new BillOrderGoods();

        $res = $orderInfoModel
            ->where('oid',$order_id)
            ->update($params);

        if ($res !== false){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 判断订单是否可支付
     * @param $order_id
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function checkOrderStatus($order_id)
    {
        $orderModel = new BillOrder();

        $res = $orderModel
            ->where('oid',$order_id)
            ->field("sale_status")
            ->find();
        $res = json_decode(json_encode($res),true);

        if (empty($res)){
            return comReturn(false,config('order.order_not_exist'));
        }
        $sale_status = $res['sale_status'];

        if ($sale_status == config('order.sale_status')['wait_use']['key']) {
            return comReturn(false,config('order.order_payed'));
        }

        if ($sale_status == config('order.sale_status')['used']['key']) {
            return comReturn(false,config('order.order_status_error'));
        }

        if ($sale_status == config('order.sale_status')['user_cancel']['key'] || $sale_status == config('order.sale_status')['cancel']['key']) {
            return comReturn(false,config('order.order_canceled'));
        }

        return comReturn(true,config('order.can_pay'));
    }

    /**
     * 判断订单是否可直接取消
     * @param $oid
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function checkOrderCanCancel($oid)
    {
        $orderModel = new BillOrder();

        $res = $orderModel
            ->where('oid',$oid)
            ->field("sale_status")
            ->find();
        $res = json_decode(json_encode($res),true);

        $sale_status = $res['sale_status'];

        if ($sale_status == config('order.sale_status')['wait_pay']['key']) {
            return true;
        }
        return false;

    }

    /**
     * 根据oid获取订单信息
     * @param $order_id
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getOrderInfo($order_id)
    {
        $orderModel = new BillOrder();

        $res = $orderModel
            ->where('oid',$order_id)
            ->find();

        return $res;
    }

    /**
     * 根据oid获取商品信息
     * @param $order_id
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getOrderGoodsInfo($order_id)
    {
        $orderGoodsModel = new BillOrderGoods();

        $res = $orderGoodsModel
            ->where('oid',$order_id)
            ->find();

        return $res;
    }

    /**
     * 检测订单是否已支付且未消费
     * @param $order_id
     * @return bool
     */
    public function checkOrderCanConsumption($order_id)
    {
        $sale_status = config('order.sale_status')['wait_use']['key'];
        $orderModel = new BillOrder();

        $res = $orderModel
            ->where('oid',$order_id)
            ->where('sale_status',$sale_status)
            ->count();

        if ($res > 0){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 支付金额获取比例信息
     * @param $money
     * @param $pid
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function payableAmountGetPercentage($money,$pid)
    {
        $keys = "partner_commission_scale,shop_commission_scale";
        $keys_value = $this->getSettingInfo($keys);

        $p_commission_scale = $keys_value['partner_commission_scale'];//百分之十  合伙人佣金比例
        $commission_scale   = $keys_value['shop_commission_scale'];//百分之二十  平台佣金比例

        $p_commission = bcmul($money,(bcdiv($p_commission_scale,100,4)),4);//合伙人佣金

        $commission   = bcmul($money,(bcdiv($commission_scale,100,4)),4);//平台佣金

        $shop_gain    = $money;

        //商家实际收入只减去平台佣金
        $deal_shop_gain = bcsub($money,$commission,4);//商家实际收入

        if ($pid == "Dentist") {
            $deal_commission = $commission;
        }else{
            $deal_commission = bcsub($commission,$p_commission,4);//平台实收佣金
        }

        $res = [
            "p_commission"       => $p_commission,//合伙人佣金
            "p_commission_scale" => $p_commission_scale,//合伙人佣金比例
            "shop_gain"          => $shop_gain,//商铺收入
            "deal_shop_gain"     => $deal_shop_gain,//商铺实际收入
            "commission_scale"   => $commission_scale,//平台佣金比例
            "commission"         => $commission,//平台佣金
            "deal_commission"    => $deal_commission,//平台实际佣金
        ];

        return $res;
    }

    /**
     * 插入店铺账户明细
     * @param $params
     * @return bool
     */
    public function insertShopAccount($params)
    {
        $res = Db::name("shop_account")
            ->insert($params);

        if ($res !== false){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 更新店铺信息
     * @param $params
     * @param $sid
     * @return bool
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function updateShopInfo($params,$sid)
    {
        $res = Db::name('shop')
            ->where('sid',$sid)
            ->update($params);
        if ($res !== false){
            return true;
        }else{
            return false;
        }
    }

    /**
     * pid获取合伙人信息
     * @param $pid
     * @return array|false|mixed|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getPartnerInfo($pid)
    {
        $res = Db::name('partner')
            ->where('pid',$pid)
            ->find();

        $res = json_decode(json_encode($res),true);

        return $res;
    }

    /**
     * 合伙人账户明细插入
     * @param $params
     * @return bool
     */
    public function insertPartnerAccount($params)
    {
        $res = Db::name('partner_account')
            ->insert($params);

        if ($res !== false){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 更新合伙人信息
     * @param $params
     * @param $pid
     * @return bool
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function updatePartnerInfo($params,$pid)
    {
        $res = Db::name("partner")
            ->where('pid',$pid)
            ->update($params);

        if ($res !== false){
            return true;
        }else{
            return false;
        }
    }


    /**
     * 提现通过或拒绝 更新合伙人数据
     * @param $pid
     * @param $oid
     * @param $status 2提现通过 3提现拒绝
     * @param $amount 提现金额
     * @return bool
     */
    public function updatePartnerAccount($pid, $oid, $status, $amount)
    {
        $partnerModel = new Partner();
        //合伙人账户数据
        $partner_data = $partnerModel
                        ->where("pid", $pid)
                        ->field("account_balance,account_freeze,account_cash")
                        ->find();

        //更新合伙人账户
        $partner['account_freeze'] = bcsub($partner_data['account_freeze'], $amount, 4);
        if ($status == 2) {
            $partner['account_cash']   = bcadd($partner_data['account_cash'], $amount, 4);

            $account['cash']        = $amount;
            $account['last_cash']   = $partner['account_cash'];
            $account['action_type'] = config("account.shop_account_action_type")['end_withdraw']['key'];
            $account['action_desc'] = config("account.shop_account_action_type")['end_withdraw']['name'];
        }else if($status == 3){
            $partner['account_balance'] = bcadd($partner_data['account_balance'], $amount, 4);

            $account['balance']      = $amount;
            $account['last_balance'] = $partner['account_balance'];
            $account['action_type']  = config("account.shop_account_action_type")['fail_withdraw']['key'];
            $account['action_desc']  = config("account.shop_account_action_type")['fail_withdraw']['name'];
        }
        $res1 = $partnerModel
                ->where("pid", $pid)
                ->update($partner);

        if ($res1) {
            //更新钱包明细
            $accountModel = new PartnerAccount();
            $account['pid']         = $pid;
            $account['freeze']      = "-".$amount;
            $account['last_freeze'] = $partner['account_freeze'];
            $account['change_type'] = 2;
            $account['action_user'] = "sys";
            $account['oid']         = $oid;
            $account['created_at']  = time();
            $account['updated_at']  = time();
            $res2 = $accountModel->insert($account);
            if ($res2) {
                return true;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }


    /**
     * 提现通过或拒绝 更新店铺数据
     * @param $sid
     * @param $oid
     * @param $status 2提现通过 3提现拒绝
     * @param $amount 提现金额
     * @return bool
     */
    public function updateShopAccount($sid, $oid, $status, $amount)
    {
        $shopModel = new Shop();
        //店铺账户数据
        $shop_data = $shopModel
                     ->where("sid", $sid)
                     ->field("account_balance,account_freeze,account_cash")
                     ->find();

        //更新店铺账户
        $shop['account_freeze'] = bcsub($shop_data['account_freeze'], $amount, 4);
        if ($status == 2) {
            $shop['account_cash']   = bcadd($shop_data['account_cash'], $amount, 4);

            $account['cash']        = $amount;
            $account['last_cash']   = $shop['account_cash'];
            $account['action_type'] = config("account.shop_account_action_type")['end_withdraw']['key'];
            $account['action_desc'] = config("account.shop_account_action_type")['end_withdraw']['name'];
        }else if($status == 3){
            $shop['account_balance'] = bcadd($shop_data['account_balance'], $amount, 4);

            $account['balance']      = $amount;
            $account['last_balance'] = $shop['account_balance'];
            $account['action_type']  = config("account.shop_account_action_type")['fail_withdraw']['key'];
            $account['action_desc']  = config("account.shop_account_action_type")['fail_withdraw']['name'];
        }
        $res1 = $shopModel
                ->where("sid", $sid)
                ->update($shop);

        if ($res1) {
            //更新钱包明细
            $accountModel = new ShopAccount();
            $account['sid']         = $sid;
            $account['freeze']      = "-".$amount;
            $account['last_freeze'] = $shop['account_freeze'];
            $account['change_type'] = 2;
            $account['action_user'] = "sys";
            $account['oid']         = $oid;
            $account['created_at']  = time();
            $account['updated_at']  = time();
            $res2 = $accountModel->insert($account);
            if ($res2) {
                return true;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }
}