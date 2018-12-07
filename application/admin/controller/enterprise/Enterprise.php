<?php

/**
 * @Author: zhangtao
 * @Date:   2018-11-05 14:05:45
 * @Last Modified by:   admin
 * @Last Modified time: 2018-12-03 15:44:12
 */

namespace app\admin\controller\Enterprise;

use app\common\controller\SysAdminAuth;
use app\wechatpublic\model\ShopEnterprise;
use app\shopadmin\model\ShopAdmin;
use app\wechat\model\User;
use think\Controller;
use think\Db;
use think\Exception;
use think\Request;
use think\Validate;
use think\Cache;
use app\common\controller\SendSms;

class Enterprise extends SysAdminAuth{
    /**
     * 企业列表
     * @param Request $request
     * @return array
     */
    public function index(Request $request){

        $pagesize   = $request->param("pagesize",config('PAGESIZE'));//显示个数,不传时为10

        if (empty($pagesize)) $pagesize = config('PAGESIZE');

        $nowPage    = $request->param("nowPage","1");

        $status = $request->param("status",0);//已提交资料待审核 0 ，审核通过1，审核未通过 2   暂时停用 3   已注销9
        $e_name = $request->param("e_name","");//企业全称

        $orderBy['filter']['orderBy'] = $request->has('orderBy') ? $request->param('orderBy') : 'created_at';
        $orderBy['filter']['sort'] = $request->has('sort') ? $request->param('sort') : 'desc';

        $config = [
            "page" => $nowPage,
        ];

        $where = [];
        $where["status"] = $status;
        if (!empty($e_name)){
            $where["e_name"] = ["like","%$e_name%"];
        }
        try{
            //处理排序条件
            $field_array = array("e_name", "p_name", "a_name");
            if (in_array($orderBy['filter']['orderBy'], $field_array)) {
                $orderBy['filter']['orderBy'] = fieldOrderByEncode($orderBy['filter']['orderBy']);
            }

            $enterpriseModel = new ShopEnterprise();

            $enterprise_list = $enterpriseModel
                               ->where($where)
                               ->field('eid,status,wxid,openid,a_phone,a_name,p_name,p_idno,idcard_img1,idcard_img2,e_name,e_license_img,e_idno,review_time,review_desc,review_user,created_at,updated_at')
                               ->order($orderBy['filter']['orderBy'], $orderBy['filter']['sort'])
                               ->paginate($pagesize,false,$config);

            if ($enterprise_list) {
                foreach ($enterprise_list as $key => $value) {
                    $enterprise_list[$key]['review_desc'] = htmlspecialchars_decode($value['review_desc']);
                }

                $enterprise_list = $this->getSysAdminLog($enterprise_list, "eid");

                return comReturn(true,config("return_message.success"),$enterprise_list);
            }else{
                return comReturn(false,config("return_message.fail"), '', 500);
            }
        }catch (Exception $e){
            return comReturn(false,$e->getMessage(), '', 500);
        }


    }

    /**
     * 店铺状态列表
     * @return array
     */
    public function enterpriseStatus(){
        $status_list = [
            [
                "key" => 0, "name"=> "待审核"
            ],
            [
                "key" => 1, "name"=> "已通过"
            ],
            [
                "key" => 2, "name"=> "未通过"
            ]
        ];
        return comReturn(true,config("return_message.success"), $status_list);
    }

    /**
     * 企业审核
     * @param Request $request
     * @return array
     */
    public function enterpriseAudit(Request $request){

        $eid    = $request->param("eid", "");
        $status = $request->param("status", "");//1通过 2不通过
        $desc   = $request->param("desc", "");
        // $desc = nl2br(htmlspecialchars(addslashes($desc)));
        $desc   = htmlspecialchars($desc);
// var_dump($desc);exit;
        try{
            //规则验证
            $rule = [
                "eid|企业id"      => "require",//商铺id
                "status|审核结果" => "require"
            ];
            $check_data = [
                "eid"    => $eid,
                "status" => $status
            ];

            if ($status == 2) {
                $rule['desc|原因'] = "require";
                $check_data['desc'] = $desc;
                $enterprise['review_desc'] = $desc;
            }else{
                $enterprise['tmp_pwd'] = getRandCode(6);
            }

            $validate = new Validate($rule);
            if (!$validate->check($check_data)){
                return comReturn(false,$validate->getError(), '', 500);
            }

            $enterpriseModel = new ShopEnterprise();
            //获取企业信息
            $enterprise_info = $enterpriseModel
                          ->where("eid", $eid)
                          ->field("status,a_phone,a_name,e_name")
                          ->find();
            if ($enterprise_info['status'] != 0) {
                return comReturn(false,config('shop.had_audit'));
            }

            $Token = Request::instance()->header("Token","");
            $admin = $this->tokenGetAdminInfo($Token);

            $enterprise['status']      = $status;
            $enterprise['review_user'] = $admin['user_name'];
            $enterprise['review_time'] = time();


            Db::startTrans();

            $res = $enterpriseModel
                   ->where("eid", $eid)
                   ->update($enterprise);
            if ($res) {

                if ($status == 1) {

                    $shopAdminModel = new ShopAdmin();
                    $shopadmin['eid']      = $eid;
                    $shopadmin['type']     = 0;
                    $shopadmin['status']   = 0;
                    $shopadmin['phone']    = $enterprise_info['a_phone'];
                    $shopadmin['username'] = $enterprise_info['a_phone'];
                    $shopadmin['name']     = $enterprise_info['a_name'];
                    $shopadmin['password'] = jmPassword($enterprise['tmp_pwd']);
                    $shopadmin['avatar']   = $this->getSettingInfo('sys_default_avatar')['sys_default_avatar'];
                    $aid = $shopAdminModel->insertGetId_ex($shopadmin, true);
                    if ($aid > 0) {
                        $phone = $shopadmin['phone'];
                        $res = SendSms::send($phone,'【牙闺蜜】您的医院'.$enterprise_info['e_name'].'已通过审核，初始登录密码为'.$enterprise['tmp_pwd'].'，如非本人操作，请及时反馈在线客服。');
                    }

                    $content = "企业认证通过 -> ".$enterprise_info['e_name']."(".$eid.")";
                    $action = 'e_verified';
                }else{
                    $phone = $enterprise_info['a_phone'];
                    $res = SendSms::send($phone,'【牙闺蜜】您的医院'.$enterprise_info['e_name'].'未通过审核，原因：'.$desc.'，请及时修改');

                    $content = "企业认证不通过 -> ".$enterprise_info['e_name']."(".$eid.")".",原因：".$desc;
                    $action = 'e_unverified';
                }

                Db::commit();


                //获取当前登录管理员
                $Token = Request::instance()->header("Token","");
                $admin = $this->tokenGetAdminInfo($Token);
                $action_user = $admin['user_name'];
                //添加至系统管理
                $this->addSysLog(time(),$action_user,$content,$request->ip());
                //添加至系统操作日志
                if ($status == 2) {
                    $this->addSysAdminLog($eid, $action, $action_user, $desc);
                }else{
                    $this->addSysAdminLog($eid, $action, $action_user);
                }

                return comReturn(true,config('return_message.success'));
            }else{
                Db::rollback();
                return comReturn(false,config('return_message.fail'), '', 500);
            }
        }catch (Exception $e){
            return comReturn(false,$e->getMessage(), '', 500);
        }
    }
}