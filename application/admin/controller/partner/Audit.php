<?php

/**
 * 合伙人列表
 * @Author: zhangtao
 * @Date:   2018-11-05 11:32:51
 * @Last Modified by:   admin
 * @Last Modified time: 2018-11-26 16:39:58
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

class Audit extends SysAdminAuth{
    /**
     * 合伙人审核
     * @param Request $request
     * @return array
     */
    public function partnerAudit(Request $request){

        $pid    = $request->param("pid", "");
        $status = $request->param("status", "");//1通过 2不通过
        $desc   = $request->param("desc", "");
        $desc   = htmlspecialchars($desc);

        try{
            //规则验证
            $rule = [
                "pid|合伙人id"      => "require",//合伙人id
                "status|审核结果" => "require"
            ];
            $check_data = [
                "pid"    => $pid,
                "status" => $status
            ];

            $partnerModel = new Partner();
            $parther_info = $partnerModel
                            ->where("pid", $pid)
                            ->field("nickname,name")
                            ->find();
            if ($status == 2) {
                $rule['desc|原因'] = "require";
                $check_data['desc'] = $desc;
                $partner['review_desc'] = $desc;
                $content = "合伙人认证不通过 -> ".$parther_info['nickname']."(".$pid.")".",原因：".$desc;
                $action = 'p_unverified';
            }else{
                $content = "合伙人认证通过 -> ".$parther_info['nickname']."(".$pid.")";
                $action = 'p_verified';
            }

            $validate = new Validate($rule);
            if (!$validate->check($check_data)){
                return comReturn(false,$validate->getError(), '', 500);
            }

            $Token = Request::instance()->header("Token","");
            $admin = $this->tokenGetAdminInfo($Token);

            $partner['status']      = $status;
            $partner['review_user'] = $admin['user_name'];
            $partner['review_time'] = time();


            Db::startTrans();
            $res = $partnerModel
                   ->where("pid", $pid)
                   ->update($partner);
            if ($res) {
                Db::commit();

                //获取当前登录管理员
                $Token = Request::instance()->header("Token","");
                $admin = $this->tokenGetAdminInfo($Token);
                $action_user = $admin['user_name'];
                //添加至系统管理
                $this->addSysLog(time(),$action_user,$content,$request->ip());
                //添加至系统操作日志
                if ($status == 2) {
                    $this->addSysAdminLog($pid, $action, $action_user, $desc);
                }else{
                    $this->addSysAdminLog($pid, $action, $action_user);
                }

                //记录日志
                // $logtext = "(DOC_ID:".$doc_id.")";
                // $logtext = $this->infoAddClass($logtext, 'text-add');
                // $route = $this->request->routeInfo();
                // $route_tran = $this->routeTranslation($route, 'shop_menu');
                // $admin = $this->tokenGetAdmin($Token);//获取管理员信息
                // $this->addSysLog(time(), $admin['log_name'], $route_tran.$logtext, $request->ip(), $admin['sid']);

                return comReturn(true,config('return_message.success'));
            }else{
                Db::rollback();
                return comReturn(false,config('return_message.fail'), '', 500);
            }
        }catch (Exception $e){
            return comReturn(false, $e->getMessage(), '', 500);
        }
    }
}