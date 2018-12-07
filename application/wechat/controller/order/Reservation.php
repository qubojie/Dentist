<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/10/24
 * Time: 下午3:05
 */
namespace app\wechat\controller\order;

use app\common\controller\OrderCommon;
use app\common\controller\ReservationCommon;
use app\common\controller\WechatAuth;
use think\Controller;
use think\Exception;
use think\Validate;

class Reservation extends WechatAuth
//class Reservation extends Controller
{
    /**
     * 获取时间选择区间
     * @return array
     */
    public function getReservationTime()
    {
        try {
            $interval       = $this->getSettingInfo("sys_reserve_time")['sys_reserve_time'];
            $interval_array = explode(",", $interval);
            $time_select    = getTimeInterval($interval_array[0], $interval_array[1]);
            return comReturn(true,config("return_message.success"),$time_select);
        } catch (Exception $e) {
            return comReturn(false, $e->getMessage(), null, 500);
        }

    }

    /**
     * 预约提交
     * @return array
     */
    public function reservationPost()
    {
        $oid            = $this->request->param("oid","");//订单id
        $reserve_time   = $this->request->param("reserve_time","");//预约时间
        $reserve_name   = $this->request->param("reserve_name","");//联系人
        $reserve_phone  = $this->request->param("reserve_phone","");//联系电话
        $reserve_remark = $this->request->param("reserve_remark","");//备注

        $rule = [
            "oid|订单id"             => "require",
            "reserve_time|预约时间"   => "require",
            "reserve_name|联系人"     => "require",
            "reserve_phone|联系电话"  => "require|regex:1[0-9]{1}[0-9]{9}",
            "reserve_remark|备注"     => "max:200:",
        ];
        $check_data = [
            "oid"            => $oid,
            "reserve_time"   => $reserve_time,
            "reserve_name"   => $reserve_name,
            "reserve_phone"  => $reserve_phone,
            "reserve_remark" => $reserve_remark,
        ];
        $validate = new Validate($rule);
        if (!$validate->check($check_data)){
            return comReturn(false,$validate->getError());
        }

        try {
            $orderCommonObj = new OrderCommon();

            /*检测订单是否已支付待使用 On*/
            $orderCanConsumptionRes = $orderCommonObj->checkOrderCanConsumption($oid);

            if (!$orderCanConsumptionRes){
                return comReturn(false,config('order.status_no_reserve'));
            }
            /*检测订单是否已支付待使用 Off*/

            /*获取订单信息 On*/
            $orderGoodsInfo = $orderCommonObj->getOrderGoodsInfo($oid);
            if (empty($orderGoodsInfo)){
                return comReturn(false,config('order.order_error'));
            }
            $gid = $orderGoodsInfo->gid;//商品id
            /*获取订单信息 Off*/

            /*检测当前用户是否对该订单已有未处理的预约 On*/
            $reservationCommonObj = new ReservationCommon();
            $isReservationRes = $reservationCommonObj->checkIsReservation($oid,$gid);

            if (!$isReservationRes){
                return comReturn(false,config('order.reserved_no_continue'));
            }
            /*检测当前用户是否对该订单已有未处理的预约 Off*/

            /*获取用户信息On*/
            $token = $this->request->header('Token','');
            $userInfo = $this->tokenGetUserInfo($token);
            if (empty($userInfo)){
                return comReturn(false,config('return_message.abnormal_action'));
            }
            $uid = $userInfo->uid;
            /*获取用户信息Off*/

            $reserve_time = strtotime($reserve_time);

            /*创建预约 On*/
            $rid    = generateReadableUUID("R");
            $status = config('order.reserve_status')['wait_confirm']['key'];
            $params = [
                "rid"            => $rid,
                "oid"            => $oid,
                "gid"            => $gid,
                "uid"            => $uid,
                "status"         => $status,
                "reserve_name"   => $reserve_name,
                "reserve_phone"  => $reserve_phone,
                "reserve_time"   => $reserve_time,
                "reserve_remark" => $reserve_remark,
                "created_at"     => time(),
                "updated_at"     => time()
            ];
            $createReserveRes = $reservationCommonObj->createReservation($params);
            if (!$createReserveRes){
                return comReturn(false,config('return_message.abnormal_action') . " - 001");
            }
            /*创建预约 Off*/
            return comReturn(true,config('return_message.success'));
        } catch (Exception $e) {
            return comReturn(false, $e->getMessage(), null, 500);
        }
    }

    /**
     * 获取预约详情
     * @return array
     */
    public function reservationDetails()
    {
        $rid = $this->request->param("rid","");//订单id
        $rule = [
            "rid|订单id" => "require",
        ];
        $check_data = [
            "rid" => $rid,
        ];
        $validate = new Validate($rule);
        if (!$validate->check($check_data)){
            return comReturn(false,$validate->getError());
        }
        try {
            $reservationCommonObj = new ReservationCommon();
            $res = $reservationCommonObj->ridGetReservationDetails($rid);
            return comReturn(true, config('return_message.success'), $res);
        } catch (Exception $e) {
            return comReturn(false, $e->getMessage(), null, 500);
        }
    }

    /**
     * 修改预约
     * @return array
     */
    public function editReservation()
    {
        $rid            = $this->request->param("rid","");//订单id
        $reserve_time   = $this->request->param("reserve_time","");//预约时间
        $reserve_name   = $this->request->param("reserve_name","");//联系人
        $reserve_phone  = $this->request->param("reserve_phone","");//联系电话
        $reserve_remark = $this->request->param("reserve_remark","");//备注

        $rule = [
            "rid|订单id"             => "require",
            "reserve_time|预约时间"   => "require",
            "reserve_name|联系人"     => "require",
            "reserve_phone|联系电话"  => "require|regex:1[0-9]{1}[0-9]{9}",
            "reserve_remark|备注"    => "max:200:",
        ];
        $check_data = [
            "rid"            => $rid,
            "reserve_time"   => $reserve_time,
            "reserve_name"   => $reserve_name,
            "reserve_phone"  => $reserve_phone,
            "reserve_remark" => $reserve_remark,
        ];
        $validate = new Validate($rule);
        if (!$validate->check($check_data)){
            return comReturn(false,$validate->getError());
        }

        $reserve_time = strtotime($reserve_time);

        /*修改预约 On*/
        $status = config('order.reserve_status')['wait_confirm']['key'];
        $params = [
            "status"         => $status,
            "reserve_name"   => $reserve_name,
            "reserve_phone"  => $reserve_phone,
            "reserve_time"   => $reserve_time,
            "reserve_remark" => $reserve_remark,
            "updated_at"     => time()
        ];
        $reservationCommonObj = new ReservationCommon();
        $createReserveRes     = $reservationCommonObj->updateReservationInfo($params,"$rid");
        if (!$createReserveRes){
            return comReturn(false,config('return_message.abnormal_action') . " - 001");
        }
        /*修改预约 Off*/
        return comReturn(true,config('return_message.success'));
    }

    /**
     * 取消预约
     * @return array
     */
    public function cancelReservation()
    {
        $oid         = $this->request->param("oid","");//订单id
        $cancel_desc = $this->request->param("cancel_desc",'');//取消原因
        $rule = [
            "oid|订单"            => "require",
            "cancel_desc|取消原因" => "require",
        ];
        $check_data = [
            "oid"         => $oid,
            "cancel_desc" => $cancel_desc,
        ];
        $validate = new Validate($rule);
        if (!$validate->check($check_data)){
            return comReturn(false,$validate->getError());
        }
        try {
            $reservationCommonObj = new ReservationCommon();

            /*查看当前订单是否已有预约信息 On*/
            $checkHaveReservationRes = $reservationCommonObj->checkHaveReservation($oid);
            if (empty($checkHaveReservationRes)){
                return comReturn(false,config('order.reserve_no_cancel'));
            }
            /*查看当前订单是否已有预约信息 Off*/

            $rid = $checkHaveReservationRes['rid'];

            $params = [
                "status"      => config('order.reserve_status')['cancel']['key'],
                "cancel_type" => config('order.reserve_cancel_type')['user_cancelled']['key'],
                "cancel_user" => config('order.reserve_cancel_type')['user_cancelled']['name'],
                "cancel_desc" => $cancel_desc,
                "cancel_time" => time(),
                "updated_at"  => time()
            ];

            /*更新预约状态 On*/
            $updateReservationInfoRes = $reservationCommonObj->updateReservationInfo($params,$rid);

            if (!$updateReservationInfoRes){
                return comReturn(false,config('return_message.fail'));
            }
            /*更新预约状态 Off*/

            return comReturn(true,config('return_message.success'));
        } catch (Exception $e) {
            return comReturn(false, $e->getMessage(), null, 500);
        }
    }
}