<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/10/22
 * Time: 下午3:37
 */
namespace app\wechat\controller\myCenter;

use app\common\controller\WechatAuth;
use app\wechat\model\BillOrder;
use think\Validate;

class Order extends WechatAuth
{
    /**
     * 订单列表
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function orderList()
    {
        $token     = $this->request->header('Token','');
        $page_size = $this->request->header('page_size','');
        $now_page  = $this->request->header('now_page','');
        if (empty($page_size)) $page_size = config('page_size');
        if (empty($now_page)) $now_page   = 1;
        /*获取登陆用户信息 On*/
        $userInfo = $this->tokenGetUserInfo($token);
        $uid = $userInfo->uid;
        /*获取登陆用户信息 Off*/
        $billOrderModel = new BillOrder();

        $config = [
            "page" => $now_page,
        ];

        $column_list_gsh = $billOrderModel->column_list_gsh;

        $res = $billOrderModel
            ->alias('bo')
            ->join('bill_order_goods bog',"bog.oid = bo.oid")
            ->join('shop s','s.sid = bo.sid')
            ->join('goods_image gi','gi.gid = bog.gid','LEFT')
            ->join('bill_order_reserve bor','bor.oid = bo.oid','LEFT')
            ->where('bo.uid',$uid)
            ->where('bor.status','neq',config('order.reserve_status')['cancel']['key'])
            ->group('bo.oid')
            ->field('s.shop_name')
            ->field('gi.image goods_image')
            ->field('bog.goods_name,bog.goods_sketch,truncate(bog.goods_original_price,2) goods_original_price,truncate(bog.goods_price,2) goods_price,bog.status')
            ->field($column_list_gsh)
            ->field('bor.rid,bor.reserve_time,bor.status reserve_status,bor.reserve_remark')
            ->order('bo.created_at DESC')
            ->paginate($page_size,false,$config);

        return comReturn(true,config('return_message.success'),$res);
    }

    /**
     * 订单详情
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function orderDetails()
    {
        $oid = $this->request->param('oid','');
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

        $billOrderModel = new BillOrder();

        $column_details_gsh = $billOrderModel->column_details_gsh;

        $res = $billOrderModel
            ->alias('bo')
            ->join('bill_order_goods bog',"bog.oid = bo.oid")
            ->join('shop s','s.sid = bo.sid')
            ->join('bill_order_reserve bor','bor.oid = bo.oid','LEFT')
            ->where('bo.oid',$oid)
            ->field('s.shop_name,s.shop_phone,s.shop_address,s.shop_lng,s.shop_lat')
            ->field('bog.goods_name,bog.goods_sketch,truncate(bog.goods_original_price,2) goods_original_price,truncate(bog.goods_price,2) goods_price,bog.verify_code,bog.status,bog.verify_time')
            ->field($column_details_gsh)
            ->field('bor.reserve_time,bor.status reserve_status')
            ->find();

        return comReturn(true,config('return_message.success'),$res);
    }
}