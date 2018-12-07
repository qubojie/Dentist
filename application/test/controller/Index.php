<?php
namespace app\test\controller;

use app\common\controller\QiNiuUpload;
use app\common\controller\SignValidation;
use app\common\controller\WxQrCode;
use app\wechat\controller\WechatPay;
use wxpay\Notify;

class Index
{
    const TOKEN = "DENTIST";

    /**
     * 退款
     * @return array
     */
    public function index()
    {
        $weChatPayObj = new WechatPay();
        $order_id = $_POST['order_id'];
        $res = $weChatPayObj->reFund("$order_id","1","1","$order_id");
        return $res;
    }

    /**
     * 生成小程序二维码
     * @throws \Exception
     */
    public function createQrCode()
    {
        $pid = "P18110212185299CB";

        $wxQcCode = new WxQrCode();
        $res = $wxQcCode->create($pid);

        dump($res);die;
    }

    /**
     * 模拟支付回调
     * @param $data
     * @throws \WxPayException
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function notifyProcessTest()
    {
        $data = array (
            'appid'          => 'wxf420fdf2f8daa869',
            'attach'         => 'reservation',
            'bank_type'      => 'CFT',
            'cash_fee'       => '20000',
            'fee_type'       => 'CNY',
            'is_subscribe'   => 'N',
            'mch_id'         => '1500135512',
            'nonce_str'      => 'rtz6rqp1vbc1wqazgi1oxxvidlazzp3u',
            'openid'         => 'oFftc5b0RuG5uOZn2Ld-fnBGBlyQ',
            'out_trade_no'   => 'O181101141246FFD5',
            'result_code'    => 'SUCCESS',
            'return_code'    => 'SUCCESS',
            'sign'           => '11E66D92E6D136D78D8818AEE07CC963',
            'time_end'       => '20181031111339',
            'total_fee'      => '20000',
            'trade_type'     => 'JSAPI',
            'transaction_id' => '4200000223201810312032854630',
        );

        $notifyObject= new Notify();

        $msg = "测试";

        $res = $notifyObject->NotifyProcess($data, $msg);

        dump($res);die;
    }

    /**
     * 七牛上传
     * @return array
     * @throws \Exception
     */
    public function uploadQiNiu()
    {
        $qiNiuObj = new QiNiuUpload();
        $genre  = 'file';//上传的文件容器参数名称

        $prefix = 'admin_source';

        $res = $qiNiuObj->upload("$genre","$prefix","0");
        return comReturn(true,'success',$res);
    }

    /**
     * 获取签名
     * @return string
     */
    public function getSign()
    {
        $timeStamp = "1539672965";
        $randomStr = "qubojie";

        $arr['timeStamp'] = $timeStamp;
        $arr['randomStr'] = $randomStr;
        $arr['token']     = self::TOKEN;
        //按照首字母大小写顺序排序
        sort($arr,SORT_STRING);
        //拼接成字符串
        $str = implode($arr);
        //进行加密
        $signature_me = sha1($str);
        $signature_me = md5($signature_me);
        //转换成大写
        $signature_me = strtoupper($signature_me);
        return $signature_me;
    }


}
