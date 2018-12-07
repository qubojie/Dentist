<?php

/**
 * @Author: zhangtao
 * @Date:   2018-11-22 17:44:48
 * @Last Modified by:   admin
 * @Last Modified time: 2018-11-23 12:06:25
 */
namespace app\admin\controller\system;

use app\common\controller\SysAdminAuth;
use app\admin\model\HomeBanner;
use app\shopadmin\model\Shop;
use app\admin\model\GoodsCategory;
use app\admin\model\SysRole;
use think\Log;
use think\Request;
use think\Validate;
use think\Db;

class SelectList extends SysAdminAuth
{

    /**
     * 各种下拉列表
     * @param Request $request
     * @return array
     */
    public function index(Request $request){
        $type = $request->param("type","");//类型

        try{
            $rule = [
                "type|类型"             => "require"
            ];

            $check_data = [
                "type"       => $type
            ];

            $validate = new Validate($rule);

            if (!$validate->check($check_data)){
                return comReturn(false,$validate->getError(), '', 500);
            }
            if ($type == "banner") {
                $shopModel = new Shop();
                $shopList = $shopModel
                            ->where("status", 0)
                            ->field("sid,shop_name")
                            ->select();

                $categoryModel = new GoodsCategory();
                $categoryList = $categoryModel
                                ->where("is_delete", 0)
                                ->where("is_enable", 1)
                                ->field("CAST(cat_id AS CHAR) AS cat_id,cat_name")
                                ->select();

                $res = [
                    'result'       => true,
                    'message'      => config("return_message.error_status_code")['ok']['value'],
                    'shopList'     => $shopList,
                    'categoryList' => $categoryList,
                    'code'         => 200
                ];

                return $res;
            }else if($type == "admin"){

                $roleModel = new SysRole();
                $roleList = $roleModel
                            ->field("role_id,role_name")
                            ->select();

                return comReturn(true,config("return_message.error_status_code")['ok']['value'],$roleList);
            }else{
                return comReturn(true,config("return_message.error_status_code")['ok']['value'],'');
            }

        }catch (Exception $e){
            return comReturn(false,$e->getMessage(), '', 500);
        }
    }


}