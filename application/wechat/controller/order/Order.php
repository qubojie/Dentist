<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/10/24
 * Time: 下午2:58
 */
namespace app\wechat\controller\order;
use app\common\controller\GoodsCommon;
use app\common\controller\OrderCommon;
use app\common\controller\ReservationCommon;
use app\common\controller\UserCommon;
use app\common\controller\WechatAuth;
use app\wechat\controller\WechatPay;
use app\wechat\model\BillOrderGoods;
use think\Db;
use think\Exception;
use think\Validate;

class Order extends WechatAuth
{
    /**
     * 创建订单
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function createdOrder()
    {
        $token = $this->request->header('Token','');

        $gids = $this->request->param('gid','');//服务id多个以 , 逗号隔开

        $rule = [
            "gid|服务" => "require",
        ];
        $check_data = [
            "gid" => $gids,
        ];
        $validate = new Validate($rule);
        if (!$validate->check($check_data)){
            return comReturn(false,$validate->getError());
        }

        /*获取登陆用户信息 On*/
        $userInfo = $this->tokenGetUserInfo($token);
        $uid = $userInfo->uid;
        /*获取登陆用户信息 Off*/

        /*判断用户是否绑定手机号码  On*/
        $userCommonObj = new UserCommon();
        $is_bind_phone = $userCommonObj->uidCheckUserIsBindPhone($uid);
        if ($is_bind_phone === false) {
            return comReturn(false,config("return_message.please_bind_phone"));
        }
        /*判断用户是否绑定手机号码  Off*/


        $oid     = generateReadableUUID("O");
        $gid_arr = explode(',',$gids);

        Db::startTrans();
        try{
            $orderCommonObj = new OrderCommon();
            $order_amount = 0;
            for ($i = 0; $i < count($gid_arr); $i ++) {
                $gid = $gid_arr[$i];
                /*获取商品id On*/
                $goodsInfo             = $this->gidGetInfo($gid);
                $sid                   = $goodsInfo->sid;
                $sn                    = $goodsInfo->sn;
                $cat_id                = $goodsInfo->cat_id;
                $goods_name            = $goodsInfo->goods_name;
                $goods_sketch          = $goodsInfo->goods_sketch;
                $goods_original_price  = $goodsInfo->goods_original_price;
                $goods_price           = $goodsInfo->goods_price;
                $goods_content         = $goodsInfo->goods_content;
                /*获取商品id Off*/
                $order_amount += $goods_price;//订单总价

                /*创建订单明细 On*/
                $cid  = generateReadableUUID("S");
                $orderInfoRes = $orderCommonObj->createBillOrderGoods("$cid","$oid","$gid","$sid","$sn","$cat_id","$goods_name","$goods_sketch","$goods_original_price","$goods_price","$goods_content");
                if (!$orderInfoRes){
                    return comReturn(false,config('return_message.abnormal_action') . " - 002");
                }
                /*创建订单明细 Off*/
            }
            /*创建订单 On*/
            $discount   = 0;
            $deal_way   = 'wxapp';
            $cus_remark = "";//买家留言
            $orderRes = $orderCommonObj->createdOrder("$oid","$uid","$sid","$order_amount","$order_amount","$discount","$deal_way","$cus_remark");
            if (!$orderRes){
                return comReturn(false,config('return_message.abnormal_action') . " - 001");
            }
            /*创建订单 Off*/
            Db::commit();
            return comReturn(true,config('return_message.success'),$oid);

        }catch (Exception $e){
            Db::rollback();
            return comReturn(false,$e->getMessage());
        }
    }

    /**
     * 检测订单状态是否可支付
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function checkOrderStatus()
    {
        $order_id = $this->request->param("order_id","");

        $orderCommonObj = new OrderCommon();

        $res = $orderCommonObj->checkOrderStatus($order_id);

        return $res;
    }

    /**
     * 取消未支付订单
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function cancelOrder()
    {
        $oid = $this->request->param('oid','');//订单id
        $rule = [
            "oid|订单" => "require",
        ];
        $check_data = [
            "oid" => $oid,
        ];
        $validate = new Validate($rule);
        if (!$validate->check($check_data)){
            return comReturn(false,$validate->getError());
        }

        /*查看订单状态是否可被取消 On*/
        $orderCommonObj = new OrderCommon();
        $res = $orderCommonObj->checkOrderCanCancel($oid);
        if (!$res){
            return comReturn(false,config('order.status_no_cancel'));
        }
        /*查看订单状态是否可被取消 Off*/

       /*更新订单 On*/
        $params = [
            "sale_status"   => config('order.sale_status')['cancel']['key'],
            "cancel_user"   => config('order.cancel_user')['user']['key'],
            "cancel_time"   => time(),
            "auto_cancel"   => 0,
            "cancel_reason" => config('order.cancel_user')['user']['name'],
            "updated_at"    => time()
        ];
        $updatedOrderRes = $orderCommonObj->updatedOrder($params,$oid);
        if (!$updatedOrderRes){
            return comReturn(false,config('order.cancel_fail'));
        }
       /*更新订单 Off*/
        return comReturn(true,config('return_message.success'));
    }

    /**
     * 退款
     * @return array|string
     */
    public function refund()
    {
        $order_id      = $this->request->param('order_id','');
        $total_fee     = $this->request->param('total_fee','');
        $refund_fee    = $this->request->param('refund_fee','');
        $out_refund_no = $this->request->param('out_refund_no','');
        $cancel_reason = $this->request->param('cancel_reason','');

        $rule = [
            "order_id|订单id"      => "require",
            "total_fee|总价格"     => "require",
            "refund_fee|退款额度"  => "require",
            "out_refund_no|回单号"  => "require",
            "cancel_reason|取消原因" => "require",
        ];
        $check_data = [
            "order_id"      => $order_id,
            "total_fee"     => $total_fee,
            "refund_fee"    => $refund_fee,
            "out_refund_no" => $out_refund_no,
            "cancel_reason" => $cancel_reason,
        ];
        $validate = new Validate($rule);
        if (!$validate->check($check_data)){
            return comReturn(false,$validate->getError());
        }

        $wechatPayObj = new WechatPay();

        $res = $wechatPayObj->reFund($order_id,$total_fee,$refund_fee,$out_refund_no);

        if (!$res['result']) return $res;

        Db::startTrans();
        try{
            $orderCommonObj = new OrderCommon();
            /*处理订单为退款状态 On*/
            $params = [
                "sale_status"   => config('order.sale_status')['cancel']['key'],
                "cancel_user"   => config('order.cancel_user')['user']['key'],
                "cancel_time"   => time(),
                "auto_cancel"   => 0,
                "is_refund"     => 1,
                "refund_amount" => $refund_fee,
                "cancel_reason" => $cancel_reason,
                "updated_at"    => time()
            ];
            $updateOrderRes = $orderCommonObj->updatedOrder($params,$order_id);
            if (!$updateOrderRes){
                return comReturn(false,config('return_message.abnormal_action') . " - 001");
            }
            /*处理订单为退款状态 Off*/

            /*处理订单详情状态 On*/
            $orderInfoParams = [
                "status"     => config('order.info_status')['refund']['key'],
                "updated_at" => time()
            ];
            $updateOrderInfoRes = $orderCommonObj->updatedOrderInfo($orderInfoParams,$order_id);
            if (!$updateOrderInfoRes){
                return comReturn(false,config('return_message.abnormal_action') . " - 002");
            }
            /*处理订单详情状态 Off*/

            /*处理预约信息状态 On*/
            $reservationParams = [
                "status"      => config('order.reserve_status')['cancel']['key'],
                "cancel_type" => config('order.reserve_cancel_type')['user_cancelled']['key'],
                "cancel_user" => config('order.reserve_cancel_type')['user_cancelled']['name'],
                "cancel_desc" => $cancel_reason,
                "cancel_time" => time(),
                "updated_at"  => time(),
            ];


            $reservationCommonObj = new ReservationCommon();

            /*查看当前订单是否已有预约信息 On*/
            $checkHaveReservationRes = $reservationCommonObj->checkHaveReservation($order_id);
            if (!empty($checkHaveReservationRes)){
                $rid = $checkHaveReservationRes['rid'];
                $updateReservationRes = $reservationCommonObj->updateReservationInfo($reservationParams,$rid);
                if (!$updateReservationRes){
                    return comReturn(false,config('return_message.abnormal_action') . " - 003");
                }
                /*处理预约信息状态 Off*/
            }
            /*查看当前订单是否已有预约信息 Off*/

            /*获取订单信息 On*/
//            $orderInfo = $orderCommonObj->getOrderInfo($order_id);
//            $orderInfo = json_decode(json_encode($orderInfo),true);

//            $pid = $orderInfo['pid'];//合伙人id
//            $p_commission   = $orderInfo['p_commission'];//合伙人佣金
//            $deal_shop_gain = $orderInfo['deal_shop_gain'];//商家实际收入
//            $commission     = $orderInfo['commission'];//平台佣金
            /*获取订单信息 Off*/

            /*更改商品购买次数 On*/
            $goodsCommonObj = new GoodsCommon();
            $goodsOrderInfo = $goodsCommonObj->oidGetGid($order_id);
            if (empty($goodsOrderInfo)){
                return comReturn(false,config('order.order_not_exist'));
            }
            $gid = $goodsOrderInfo['gid'];
            //执行购买次数变更
            $goodsBuyNumChangeReturn = $goodsCommonObj->goodsBuyNumChange("Dec","1","$gid");
            if (!$goodsBuyNumChangeReturn){
                return comReturn(false,config('return_message.abnormal_action') . " - 004");
            }
            /*更改商品购买次数 Off*/

            /*获取店铺信息 On*/
//            $shopInfo = $goodsCommonObj->gidGetShopInfo($gid);

//            $sid = $shopInfo['sid'];
//            $old_account_freeze = $shopInfo['account_freeze'];
            /*获取店铺信息 Off*/

            /*店铺账户明细 On*/
           /* $shopAccountParams = [
                "sid"         => $sid,
                "freeze"      => "-".$deal_shop_gain,
                "last_freeze" => bcsub($old_account_freeze,$deal_shop_gain,4),
                "change_type" => config('account.shop_account_change_type')['sys']['key'],
                "action_user" => config('account.shop_account_change_type')['sys']['name'],
                "action_type" => config('account.shop_account_action_type')['order_refund']['key'],
                "oid"         => $order_id,
                "deal_amount" => $refund_fee,
//                "charge"      => "-".$commission,
                "action_desc" => config('account.shop_account_action_type')['order_refund']['name'],
//                "finish_status" => 1,
                "created_at" => time(),
                "updated_at" => time()
            ];

            $insertShopAccountReturn = $orderCommonObj->insertShopAccount($shopAccountParams);
            if (!$insertShopAccountReturn){
                return comReturn(false,config('return_message.abnormal_action') . " - 005");
            }*/
            /*店铺账户明细 Off*/

            /*店铺信息 On*/
           /* $shopParams = [
                "account_freeze" => bcsub($old_account_freeze, $deal_shop_gain,4),
                "updated_at"     => time()
            ];
            $updatedOrderInfoReturn = $orderCommonObj->updateShopInfo($shopParams,$sid);
            if (!$updatedOrderInfoReturn){
                return comReturn(false,config('return_message.abnormal_action') . " - 006");
            }*/
            /*店铺信息 Off*/

            /*合伙人明细以及账户更新 On*/
            /*if (!empty($pid)) {
                //查询是否存在此合伙人
                $partnerInfo = $orderCommonObj->getPartnerInfo($pid);
                if (!empty($partnerInfo)){
                    //存在
                    $account_freeze_old = $partnerInfo['account_freeze'];//合伙人账户冻结金额(旧的)
                    //更新明细
                    $partnerAccountParams = [
                        "pid"           => $pid,
                        "freeze"        => "-".$p_commission,
                        "last_freeze"   => bcsub($account_freeze_old,$p_commission,4),
                        "change_type"   => config("account.partner_account_change_type")['sys']['key'],
                        "action_user"   => config("account.partner_account_change_type")['sys']['name'],
                        "action_type"   => config("account.partner_account_action_type")['order_refund']['key'],
                        "oid"           => $order_id,
                        "deal_price"    => $refund_fee,
//                        "finish_status" => 1,
                        "action_desc"   => config("account.partner_account_action_type")['order_refund']['name'],
                        "action_time"   => time()
                    ];

                    $insertPartnerAccountReturn = $orderCommonObj->insertPartnerAccount($partnerAccountParams);
                    if (!$insertPartnerAccountReturn){
                        return comReturn(false,config('return_message.abnormal_action') . " - 007");
                    }
                    //更新账户
                    $partnerParams = [
                        "account_freeze" => bcsub($account_freeze_old,$p_commission,4),
                    ];
                    $updatePartnerInfoReturn = $orderCommonObj->updatePartnerInfo($partnerParams,$pid);
                    if (!$updatePartnerInfoReturn){
                        return comReturn(false,config('return_message.abnormal_action') . " - 008");
                    }
                }

            }*/
            /*合伙人明细以及账户更新 Off*/
            Db::commit();
            return comReturn(true,config('return_message.success'));

        }catch (Exception $e){
            Db::rollback();
            return comReturn(false,$e->getMessage());
        }
    }

    /**
     * TODO 模拟微信支付回调方法处理
     */
    public function callBackMn()
    {
        $order_id = $this->request->param("order_id","");


        $sale_status = config("order.sale_status")['wait_use']['key'];
        $pay_no      = uniqueNumber("12");
        $deal_amount = 45000;
        $payable_amount = 0;

        $params = [
            "sale_status"    => $sale_status,
            "pay_time"       => time(),
            "pay_no"         => $pay_no,
            "payable_amount" => $deal_amount,
            "deal_amount"    => $payable_amount,
            "updated_at"     => time()
        ];

        $orderCommonObj = new OrderCommon();

        Db::startTrans();
        try{
            /*更新订单状态 Off*/
            $updateOrderRes = $orderCommonObj->updatedOrder($params,$order_id);
            /*更新订单状态 On*/
            if (!$updateOrderRes){
                echo '<xml> <return_code><![CDATA[FAIL]]></return_code> <return_msg><![CDATA['.config('return_message.abnormal_action') . " - 001".']]></return_msg> </xml>';
                die;
            }

            $infoParams = [
                "status"     => config("order.info_status")['wait_use']['key'],
                "updated_at" => time()
            ];

            /*更新订单详细 On*/
            $updateOrderInfoRes = $orderCommonObj->updatedOrderInfo($infoParams,$order_id);
            if (!$updateOrderInfoRes){
                echo '<xml> <return_code><![CDATA[FAIL]]></return_code> <return_msg><![CDATA['.config('return_message.abnormal_action') . " - 002".']]></return_msg> </xml>';
                die;
            }
            /*更新订单详细 Off*/

            Db::commit();
            echo '<xml> <return_code><![CDATA[SUCCESS]]></return_code> <return_msg><![CDATA[OK]]></return_msg> </xml>';
            die;

        }catch (Exception $e){
            Db::rollback();
            return comReturn(false,$e->getMessage());
        }
    }

    /**
     * oid获取订单信息
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getOrderInfo()
    {
        $oid = $this->request->param('oid','');

        $orderGoodsModel = new BillOrderGoods();

        $res  =$orderGoodsModel
            ->alias('og')
            ->join('goods_image gi','gi.gid = og.gid','LEFT')
            ->join('shop s','s.sid = og.sid')
            ->where('og.oid',$oid)
            ->field('s.sid,s.shop_name,s.shop_phone,s.shop_address,s.shop_lng,s.shop_lat')
            ->field('gi.image goods_image')
            ->field('og.goods_name,truncate(og.goods_original_price,2) goods_original_price,truncate(og.goods_price,2) goods_price,og.goods_sketch')
            ->find();

        return comReturn(true,config('return_message.success'),$res);

    }
}