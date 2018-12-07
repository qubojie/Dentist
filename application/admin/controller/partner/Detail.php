<?php

/**
 * 合伙人详细信息
 * @Author: zhangtao
 * @Date:   2018-11-05 11:32:51
 * @Last Modified by:   admin
 * @Last Modified time: 2018-11-29 12:24:18
 */
namespace app\admin\controller\partner;

use app\common\controller\SysAdminAuth;
use app\admin\model\SysAdminUser;
use app\wechatpublic\model\Partner;
use think\Controller;
use think\Db;
use think\Exception;
use think\Request;
use think\Validate;
use think\Cache;

class Detail extends SysAdminAuth{
    /**
     * 合伙人详细信息
     * @param Request $request
     * @return array
     */
    public function index(Request $request){

        $pid = $request->param("pid", "");//合伙人id

        try{
            //规则验证
            $rule = [
                "pid|合伙人id" => "require",
            ];
            $check_data = [
                "pid" => $pid,
            ];

            $validate = new Validate($rule);
            if (!$validate->check($check_data)){
                return comReturn(false,$validate->getError(), '', 500);
            }

            $partnerModel = new Partner();

            $partner_info = $partnerModel
                            ->where("pid",$pid)
                            ->field('pid,phone,name,qr_code,wxid,mp_openid,is_attention_wx,nickname,avatar,sex,province,city,country,account_balance,account_freeze,account_cash,register_way,register_time,lastlogin_time,status,review_user,review_time,review_desc,created_at,updated_at')
                            ->find();

            if ($partner_info) {
                //保留两位小数
                $decimal = $this->getSettingInfo("sys_show_number_decimal")['sys_show_number_decimal'];
                $partner_info['account_balance'] = sprintf("%.".$decimal."f", $partner_info['account_balance']);
                $partner_info['account_freeze']  = sprintf("%.".$decimal."f", $partner_info['account_freeze']);
                $partner_info['account_cash']    = sprintf("%.".$decimal."f", $partner_info['account_cash']);

                return comReturn(true,config("return_message.success"),$partner_info);
            }else{
                return comReturn(false,config("return_message.fail"));
            }
        }catch (Exception $e){
            return comReturn(false, $e->getMessage(), '', 500);
        }

    }
}