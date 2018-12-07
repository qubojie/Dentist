<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/11/2
 * Time: 下午2:42
 */
namespace app\wechatpublic\controller\partner;

use app\common\controller\PartnerAuth;
use app\common\controller\PartnerCommon;

class Index extends PartnerAuth
{
    /**
     * 收益
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function earnings()
    {
        $now_page    = $this->request->param('now_page','');
        $page_size   = $this->request->param('page_size','');
        $token       = $this->request->header('Token','');
        $partnerInfo = $this->tokenGeyPartnerInfo($token);
        $pid         = $partnerInfo['pid'];

        $partnerCommonObj = new PartnerCommon();
        /*收益和 On*/
        $earnings_sum = $partnerCommonObj->pidGetEarningsSum($pid);
        /*收益和 Off*/

        /*获取收益列表 On*/
        $earnings_list = $partnerCommonObj->pidGetEarningsList($pid,$page_size,$now_page);
        /*获取收益列表 Off*/
        $res = [
            "earnings_sum"  => $earnings_sum,
            "earnings_list" => $earnings_list
        ];
        return comReturn(true,config('return_message.success'),$res);
    }
}