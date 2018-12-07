<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/10/25
 * Time: 下午4:11
 */
namespace app\common\controller;

use app\wechat\model\BillOrder;
use app\wechat\model\User;
use app\wechatpublic\model\Partner;
use think\Controller;
use think\Env;

class UserCommon extends Controller
{
    /**
     * 更新用户信息
     * @param $params
     * @param $uid
     * @return bool
     */
    public function updateUserInfo($params,$uid)
    {
        $userModel = new User();

        $res = $userModel
            ->where('uid',$uid)
            ->update($params);

        if ($res !== false){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 根据uid检测待处理或待使用的订单数
     * @param $uid
     * @return int|string
     */
    public function getDealOrderNum($uid)
    {
        $wait_pay = config('order.sale_status')['wait_pay']['key'];
        $wait_use = config('order.sale_status')['wait_use']['key'];

        $sale_status = $wait_pay . "," . $wait_use;

        $billOrderModel = new BillOrder();

        $res = $billOrderModel
            ->where('uid',$uid)
            ->where('sale_status','IN',$sale_status)
            ->count();

        return $res;
    }

    /**
     * 查询指定uid用户是否有已消费订单
     * @param $uid
     * @return bool
     */
    public function checkUserHaveOrderInfo($uid)
    {
        $billOrderModel = new BillOrder();

        $wait_use = config('order.sale_status')['wait_use']['key'];
        $used     = config('order.sale_status')['used']['key'];

        $sale_status = "$wait_use,$used";

        $res = $billOrderModel
            ->where('uid',$uid)
            ->where('sale_status','IN',$sale_status)
            ->count();

        if ($res > 0){
            //如果有,则返回true
            return true;
        }else{
            //如果没有,则返回false
            return false;
        }
    }

    /**
     * 检测用户是否有推荐人
     * @param $uid
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function checkUserHavePartner($uid)
    {
        $userModel = new \app\wechatpublic\model\User();

        $res = $userModel
            ->where('uid',$uid)
            ->field('pid')
            ->find();

        $res = json_decode(json_encode($res),true);

        $pid = $res['pid'];

        if (!empty($pid)) {
            //有推荐人返回true
            return true;
        }else{
            //无推荐人返回false
            return false;
        }
    }

    /**/
    public function uidCheckUserIsBindPhone($uid)
    {
        $userModel = new User();

        $userInfo = $userModel
            ->where("uid",$uid)
            ->field("phone")
            ->find();

        $userInfo = json_decode(json_encode($userInfo),true);

        if (empty($userInfo)) {
            return false;
        }

        $phone = $userInfo['phone'];

        if (empty($phone) || $phone == NULL) {
           return false;
        }
        return true;
    }

    /**
     * 获取用户的微信授权解密信息
     * @param $sessionKey
     * @param $encryptedData
     * @param $iv
     */
    public function getUserWxUniqueId($sessionKey,$encryptedData,$iv)
    {
        //1：wx后台得到 $appid
        $appid = Env::get("WECHAT_XCX_APPID");
        //2：[客户端]发送到服务器js_code（服务器用js_code换取sessionKey）  $sessionKey = 'tiihtNczf5v6AKRyjwEUhQ==';

        //3：[客户端]发送到服务器（客户端getUserInfo()获取）
        /*$encryptedData="CiyLU1Aw2KjvrjMdj8YKliAjtP4gsMZM
                        QmRzooG2xrDcvSnxIMXFufNstNGTyaGS
                        9uT5geRa0W4oTOb1WT7fJlAC+oNPdbB+
                        3hVbJSRgv+4lGOETKUQz6OYStslQ142d
                        NCuabNPGBzlooOmB231qMM85d2/fV6Ch
                        evvXvQP8Hkue1poOFtnEtpyxVLW1zAo6
                        /1Xx1COxFvrc2d7UL/lmHInNlxuacJXw
                        u0fjpXfz/YqYzBIBzD6WUfTIF9GRHpOn
                        /Hz7saL8xz+W//FRAUid1OksQaQx4CMs
                        8LOddcQhULW4ucetDf96JcR3g0gfRK4P
                        C7E/r7Z6xNrXd2UIeorGj5Ef7b1pJAYB
                        6Y5anaHqZ9J6nKEBvB4DnNLIVWSgARns
                        /8wR2SiRS7MNACwTyrGvt9ts8p12PKFd
                        lqYTopNHR1Vf7XjfhQlVsAJdNiKdYmYV
                        oKlaRv85IfVunYzO0IKXsyl7JCUjCpoG
                        20f0a04COwfneQAGGwd5oa+T8yO5hzuy
                        Db/XcxxmK01EpqOyuxINew==";*/

            //4：[客户端]发送到服务器（客户端getUserInfo()获取）
            //        $iv = 'r7BXXKkLb8qrSNn05n0qiA==';


        //用appid、sessionKey初始化“解密处理类
        $pc = new WXBizDataCrypt();

        //执行解密，正确返回$data，错误返回$errCode
        $errCode = $pc->decryptData("$sessionKey","$appid","$encryptedData","$iv",$data );


        if ($errCode == 0) { //0表示正常
            print($data . " ");
        } else {
            print($errCode . "");
        }
    }

    /**
     * 更新用户信息
     * @param $params
     * @param $uid
     * @return bool
     */
    public function updatePartnerInfo($params,$uid)
    {
        $userModel = new Partner();

        $res = $userModel
            ->where('pid',$uid)
            ->update($params);

        if ($res !== false){
            return true;
        }else{
            return false;
        }
    }
}