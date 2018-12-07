<?php
/**
 * 支付.
 * User: qubojie
 * Date: 2018/10/22
 * Time: 下午3:43
 */
namespace app\wechat\controller;

use app\common\controller\CommonAuth;
use app\common\controller\OrderCommon;
use think\Env;
use wxpay\JsapiPay;
use wxpay\NativePay;
use wxpay\Refund;
use wxpay\WapPay;

class WechatPay extends CommonAuth
{
    /**
     * 小程序支付
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function smallApp()
    {
        $order_id = $this->request->param("order_id","");

        $openId   = $this->request->param('openid','');

        $scene    = $this->request->param("scene","");//支付场景

        $orderCommonObj = new OrderCommon();
        $orderInfo = $orderCommonObj->getOrderInfo($order_id);
        $orderInfo = json_decode(json_encode($orderInfo),true);

        if (empty($orderInfo)){
            return comReturn(false,config('return_message.fail'));
        }

        $payable_amount = $orderInfo['payable_amount'];

        $params = [
            'body'         => Env::get("PAY_BODY"),
            'out_trade_no' => $order_id,
            'total_fee'    => $payable_amount * 100,
        ];

        $res = JsapiPay::getParams2($params,$openId,$scene);
        $res = json_decode($res,true);
        return comReturn(true,config('return_message.success'),$res);
    }

    /**
     * h5支付
     * @return string
     */
    public function h5Pay()
    {
        $order_id = $this->request->param("order_id","");

        $payable_amount = 1;

        $params = [
            'body'          => Env::get("PAY_BODY"),
            'out_trade_no'  => $order_id,
            'total_fee'     => $payable_amount * 100
        ];

        $redirect_url = Env::get("WEB_DOMAIN_NAME").'page/orderspay.html';//回调地址

        $res = WapPay::getPayUrl($params,$redirect_url);

        return $res;
    }

    /**
     * 公众号支付
     * @return \json数据，可直接填入js函数作为参数
     */
    public function publicPay()
    {
        $order_id = $this->request->param("order_id","");

        $payable_amount = 1;

        $params = [
            'body'          => Env::get("PAY_BODY"),
            'out_trade_no'  => $order_id,
            'total_fee'     => $payable_amount * 100
        ];

        if (isset($_GET['code'])){
            $code = $_GET['code'];
        }else{
            $code = '';
        }

        $res = JsapiPay::getPayParams($params,$code);
        return $res;
    }

    /**
     * 扫码支付
     * @return string
     * @throws \WxPayException
     */
    public function scavengingPay()
    {
        $order_id = $this->request->param("order_id","");

        $payable_amount = 1;

        $params = [
            "body"         => Env::get("PAY_BODY"),
            "out_trade_no" => $order_id,
            "total_fee"    => $payable_amount,
            "product_id"   => $order_id
        ];

        $res = NativePay::getPayImage($params);

        return $res;//这里返回 code_url
    }

    /**
     * 退款公共
     * @param $order_id
     * @param $total_fee
     * @param $refund_fee
     * @param $out_refund_no
     * @return array
     */
    public function reFund($order_id,$total_fee,$refund_fee,$out_refund_no)
    {
        $total_fee  = $total_fee * 100;
        $refund_fee = $refund_fee * 100;

        $params = [
            "out_trade_no"  => $order_id,
            "total_fee"     => $total_fee,
            "refund_fee"    => $refund_fee,
            "out_refund_no" => $out_refund_no,
        ];

        $result = Refund::exec($params);

        if (isset($result['return_code']) && $result['return_msg'] == "OK"){
            return comReturn(true,config("return_message.success"),$result);
        }else{
            $result = json_decode(json_encode($result),true);
            return comReturn(false,$result["return_msg"]);
        }
    }

    /**
     * 异步回调地址
     */
    public function notify()
    {
        $notify = new \wxpay\Notify();
        $notify->Handle();
    }
}