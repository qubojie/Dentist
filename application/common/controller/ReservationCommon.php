<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/10/25
 * Time: 上午10:13
 */
namespace app\common\controller;

use app\wechat\model\BillOrderReserve;
use think\Controller;

class ReservationCommon extends Controller
{
    /**
     * 检测当前订单是否已有未处理完毕的预约
     * @param $oid
     * @param $gid
     * @return bool
     */
    public function checkIsReservation($oid,$gid)
    {
        $wait_confirm = config('order.reserve_status')['wait_confirm']['key'];
        $confirmed    = config('order.reserve_status')['confirmed']['key'];
        $to_the_shop  = config('order.reserve_status')['to_the_shop']['key'];

        $status_str = $wait_confirm . "," . $confirmed . "," . $to_the_shop;

        $billOrderReserveModel = new BillOrderReserve();

        $res = $billOrderReserveModel
            ->where('oid',$oid)
            ->where('gid',$gid)
            ->where('status','IN',$status_str)
            ->count();

        if ($res > 0){
            return false;
        }else{
            return true;
        }
    }

    /**
     * 创建预约
     * @param $params
     * @return bool
     */
    public function createReservation($params)
    {
        $billOrderReserveModel = new BillOrderReserve();

        $res = $billOrderReserveModel
            ->insert($params);

        if ($res !== false){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 检测当前订单是否有预约信息(待确认或已确认)
     * @param $oid
     * @return array|false|mixed|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function checkHaveReservation($oid)
    {
        $billOrderReserveModel = new BillOrderReserve();

        $wait_confirm = config('order.reserve_status')['wait_confirm']['key'];//待确认
        $confirmed    = config('order.reserve_status')['confirmed']['key'];//已确认

        $status_str = $wait_confirm . "," . $confirmed;

        $res = $billOrderReserveModel
            ->where('oid',$oid)
            ->where('status','IN',$status_str)
            ->find();

        $res = json_decode(json_encode($res),true);

        return $res;
    }

    /**
     * 更新预约信息
     * @param $params
     * @param $rid
     * @return bool
     */
    public function updateReservationInfo($params,$rid)
    {
        $billOrderReserveModel = new BillOrderReserve();
        $res = $billOrderReserveModel
            ->where('rid',$rid)
            ->update($params);

        if ($res !== false){
            return true;
        }else{
            return false;
        }
    }

    /**
     * rid获取预约详情
     * @param $rid
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function ridGetReservationDetails($rid)
    {
        $billOrderReserveModel = new BillOrderReserve();
        $res = $billOrderReserveModel
            ->where('rid',$rid)
            ->find();

        return $res;
    }
}