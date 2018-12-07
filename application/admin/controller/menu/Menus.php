<?php

/**
 * 左侧菜单栏
 * @Author: zhangtao
 * @Date:   2018-10-22 12:08:46
 * @Last Modified by:   admin
 * @Last Modified time: 2018-11-20 12:30:45
 */

namespace app\admin\controller\menu;

use app\common\controller\SysAdminAuth;
use app\wechatpublic\model\Partner;
use app\wechatpublic\model\ShopEnterprise;
use app\admin\model\BillPartnerWithdrawals;
use app\shopadmin\model\BillShopWithdrawals;
use app\admin\model\SysAdminUser;
use app\admin\model\SysMenu;
use think\Log;
use think\Request;
use think\Validate;
use think\Db;
use think\Exception;

class Menus extends SysAdminAuth
{
    /**
     * 后台菜单列表获取
     * @param Request $request
     */
    public function index(Request $request)
    {
        $Token = Request::instance()->header("Token","");

        try{
            //查看当前用户角色
            $sysAdminUserModel = new SysAdminUser();

            $action_list_str = $sysAdminUserModel
                ->alias('sau')
                ->join('sys_role sr','sr.role_id = sau.role_id')
                ->where('remember_token',$Token)
                ->field('sr.action_list')
                ->find();

            $action_list = $action_list_str['action_list'];
            $where = [];
            if ($action_list == 'all'){
                $where = [];
            }else{
                $action_list = explode(",", $action_list);
                $where['id'] = array('IN',$action_list);//查询字段的值在此范围之内的做显示
            }

            $result = array('menu' => array());

            $menus = new SysMenu();
            $menus_all =  $menus
                ->where('is_show_menu','1')
                ->where($where)
                ->select();

            $menus_all = json_decode(json_encode($menus_all),true);

            for ($i=0;$i<count($menus_all);$i++){
                $id = $menus_all[$i]['id'];
                $parent = substr($id,0,3);
                $level  = substr($id,3,3);
                $last   = substr($id,-3);

                if ($level == "000"){
                    $result['menu'][] = $menus_all[$i];
                }else{
                    if ($last == 0) {
                        $level2[] = $menus_all[$i];
                    }else{
                        $level3[] = $menus_all[$i];
                    }
                }


            }
            if (isset($level2)){
                for ($m=0;$m<count($result["menu"]);$m++){
                    $menu_id = $result["menu"][$m]["id"];
                    $menu_id_p = substr($menu_id,0,3);
                    for ($n=0;$n<count($level2);$n++){
                        $level2_id = $level2[$n]['id'];
                        $parent_level2 = substr($level2_id,0,3);
                        if ($menu_id_p == $parent_level2){
                            $result["menu"][$m]["children"][] = $level2[$n];
                        }
                    }
                }
            }

            return comReturn(true,"获取成功",$result);
        }catch (Exception $e){
            return comReturn(false, $e->getMessage(), '', 500);
        }

    }

    /**
     * 获取所有的设置列表
     * @param Request $request
     * @return array
     */
    public function lists(Request $request)
    {

        $result = array();

        try{
            $menus = new SysMenu();
            $menus_all =  $menus
                ->where('is_show_menu','1')
                ->select();

            $menus_all = json_decode(json_encode($menus_all),true);


            for ($i=0;$i<count($menus_all);$i++){
                $id = $menus_all[$i]['id'];
                $parent = substr($id,0,3);
                $level  = substr($id,3,3);
                $last   = substr($id,-3);

                if ($level == "000"){
                    $result[] = $menus_all[$i];
                }else{
                    if ($last == 0) {
                        $level2[] = $menus_all[$i];
                    }else{
                        $level3[] = $menus_all[$i];
                    }
                }
            }
            if (isset($level2)){
                for ($m=0;$m<count($result);$m++){
                    $menu_id = $result[$m]["id"];
                    $menu_id_p = substr($menu_id,0,3);
                    for ($n=0;$n<count($level2);$n++){
                        $level2_id = $level2[$n]['id'];
                        $parent_level2 = substr($level2_id,0,3);
                        if ($menu_id_p == $parent_level2){
                            $result[$m]["children"][] = $level2[$n];
                        }
                    }
                }
            }

            return comReturn(true,"获取成功",$result);
        }catch (Exception $e){
            return comReturn(false, $e->getMessage(), '', 500);
        }

    }


    /**
     * 小红点统计
     */
    public function menuRedDot()
    {
        try{
            $partnerModel = new Partner();

            $enterpriseModel = new ShopEnterprise();

            $p_withdrawalsModel = new BillPartnerWithdrawals();

            $s_withdrawalsModel = new BillShopWithdrawals();

            //合伙人待审核
            $partnerListCount = $partnerModel
                                ->where("status",config("partner.status")['wait_check']['key'])
                                ->count();

            //医院待审核
            $enterpriseListCount = $enterpriseModel
                                   ->where("status",config("enterprise.status")[0]['key'])
                                   ->count();

            //合伙人提现待审核
            $p_withdrawalsCount = $p_withdrawalsModel
                                  ->where("status",1)
                                  ->count();

            //店铺提现待审核
            $s_withdrawalsCount = $s_withdrawalsModel
                                  ->where("status",1)
                                  ->count();

            $res = [
                "partner"                => $partnerListCount,
                "partnerList"            => $partnerListCount,
                "enterprise"             => $enterpriseListCount,
                "enterpriseList"         => $enterpriseListCount,
                "finance"                => $p_withdrawalsCount+$s_withdrawalsCount,
                "partnerWithdrawalsList" => $p_withdrawalsCount,
                "shopWithdrawalsList"    => $s_withdrawalsCount
            ];

            return comReturn(true,config("return_message.success"),$res);
        }catch (Exception $e){
            return comReturn(false, $e->getMessage(), '', 500);
        }

    }
}