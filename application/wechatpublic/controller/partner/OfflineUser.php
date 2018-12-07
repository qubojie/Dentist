<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/11/2
 * Time: 下午3:41
 */
namespace app\wechatpublic\controller\partner;

use app\common\controller\PartnerAuth;
use app\common\controller\PartnerCommon;

class OfflineUser extends PartnerAuth
{
    /**
     * 下线列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function offlineList()
    {
        $token  =$this->request->header('Token','');
        $partnerInfo = $this->tokenGeyPartnerInfo($token);
        $pid         = $partnerInfo['pid'];

        $partnerCommonObj = new PartnerCommon();
        $res = $partnerCommonObj->pidGetOfflineList($pid);

        return comReturn(true,config('return_message.success'),$res);
    }
}