<?php

/**
 * 左侧菜单栏
 * @Author: zhangtao
 * @Date:   2018-10-22 12:08:46
 * @Last Modified by:   admin
 * @Last Modified time: 2018-11-20 12:31:59
 */

namespace app\shopadmin\controller;

use app\common\controller\ShopAdminAuth;
use app\services\controller\ImageUpload;
use app\shopadmin\model\ShopMenu;
use think\Log;
use think\Request;
use think\Validate;
use think\Db;

class Menus extends ShopAdminAuth{

    /**
     * 后台菜单列表获取
     * @param Request $request
     */
    public function index(Request $request)
    {
        $result = array('menu' => array());

        try{
            $menus = new ShopMenu();
            $menus_all =  $menus
                ->where('is_show_menu','1')
                ->field('id,title,title_img,url')
                // ->where($where)
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
}