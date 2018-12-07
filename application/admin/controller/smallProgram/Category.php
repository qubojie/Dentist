<?php

/**
 * 商品分类增删改查
 * @Author: zhangtao
 * @Date:   2018-11-21 16:58:29
 * @Last Modified by:   admin
 * @Last Modified time: 2018-12-03 15:45:14
 */
namespace app\admin\controller\smallProgram;

use app\common\controller\SysAdminAuth;
use app\admin\model\GoodsCategory;
use app\shopadmin\model\ShopGoods;
use think\Env;
use think\Log;
use think\Request;
use think\Validate;
use think\Db;
use think\Exception;

class Category extends SysAdminAuth
{

    /**
     * 分类信息
     * @param Request $request
     * @return array
     */
    public function index(Request $request){
        $pagesize   = $request->param("pagesize",config('PAGESIZE'));//显示个数,不传时为10
        if (empty($pagesize)) $pagesize = config('PAGESIZE');

        try{
            $nowPage    = $request->param("nowPage","1");

            $orderBy['filter']['orderBy'] = $request->has('orderBy') ? $request->param('orderBy') : 'created_at';
            $orderBy['filter']['sort'] = $request->has('sort') ? $request->param('sort') : 'desc';

            $categoryModel = new GoodsCategory();

            $config = [
                "page" => $nowPage
            ];

            //处理排序条件
            $field_array = array("cat_name");
            if (in_array($orderBy['filter']['orderBy'], $field_array)) {
                $orderBy['filter']['orderBy'] = fieldOrderByEncode($orderBy['filter']['orderBy']);
            }

            $result = $categoryModel
                      ->where("is_delete", 0)
                      ->field("cat_id,cat_name,cat_image,sort,is_enable,created_at,updated_at")
                      // ->order("updated_at desc")
                      ->order($orderBy['filter']['orderBy'], $orderBy['filter']['sort'])
                      ->paginate($pagesize,false,$config);

            return comReturn(true,config("return_message.error_status_code")['ok']['value'],$result);
        }catch (Exception $e){
            return comReturn(false,$e->getMessage(), '', 500);
        }
    }

    /**
     * 添加分类信息
     * @param Request $request
     * @return array
     */
    public function addCategory(Request $request)
    {

        $cat_name  = $request->param("cat_name","");//分类名称
        $cat_image = $request->param("cat_image","");//分类图片
        $is_enable = $request->param("is_enable", "");//是否启用  0否 1是
        $sort      = $request->param("sort",0);//排序

        try{
            $rule = [
                "cat_name|分类名称"  => "require|unique:goods_category",
                "cat_image|分类图片" => "require"
            ];

            $check_data = [
                "cat_name"  => $cat_name,
                "cat_image" => $cat_image
            ];

            $validate = new Validate($rule);

            if (!$validate->check($check_data)){
                return comReturn(false,$validate->getError(), '', 500);
            }

            //写入数据,返回id
            $insert_data = [
                "cat_name"  => $cat_name,
                "cat_image" => $cat_image,
                "sort"      => $sort
            ];
            Db::startTrans();
            $categoryModel = new GoodsCategory();
            $cat_id = $categoryModel->insertGetId_ex($insert_data, true, $is_enable, 0);
            if (!empty($cat_id)){
                Db::commit();

                $Token = Request::instance()->header("Token","");
                //记录日志
                $logtext = "(CAT_ID:".$cat_id.")";
                $logtext = $this->infoAddClass($logtext, 'text-add');
                $route = $this->request->routeInfo();
                $route_tran = $this->routeTranslation($route, 'sys_menu');
                $admin = $this->tokenGetAdminInfo($Token);//获取管理员信息
                $this->addSysLog(time(), $admin['user_name'], $route_tran.$logtext, $request->ip());

                return comReturn(true,"分类添加成功");

            }else{
                Db::rollback();
                return comReturn(false,"分类添加失败", '', 500);
            }
        }catch (Exception $e){
            return comReturn(false,$e->getMessage(), '', 500);
        }
    }

    /**
     * 编辑分类信息
     * @param Request $request
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function editCategory(Request $request)
    {
        $cat_id    = $request->param("cat_id", "");//分类id
        $cat_name  = $request->param("cat_name","");//分类名称
        $cat_image = $request->param("cat_image","");//分类图片
        $is_enable = $request->param("is_enable","");//是否启用  0否 1是
        $sort      = $request->param("sort",0);//排序

        try{
            $rule = [
                "cat_id"            => "require",
                "cat_name|分类名称"  => "require|unique:goods_category",
                "cat_image|分类图片" => "require",
            ];
            $check_data = [
                "cat_id"    => $cat_id,
                "cat_name"  => $cat_name,
                "cat_image" => $cat_image
            ];
            $validate = new Validate($rule);
            if (!$validate->check($check_data)){
                return comReturn(false,$validate->getError(), '', 500);
            }

            $category['cat_id']     = $cat_id;
            $category['cat_name']   = $cat_name;
            $category['cat_image']  = $cat_image;
            $category['is_enable']  = $is_enable;
            $category['sort']       = $sort;
            $category['updated_at'] = time();

            Log::info("编辑分类信息 ----- ".var_export($category,true));

            $categoryModel = new GoodsCategory();

            // 获取修改之前的数据
            $keys = array_keys($category);
            $databefore = $this->updateBefore("goods_category", "cat_id", $cat_id, $keys);

            $res = $categoryModel
                ->where('cat_id',$cat_id)
                ->update($category);

            if ($res == false){
                return comReturn(false,"修改失败", '', 500);
            }

            $Token = Request::instance()->header("Token","");
            //记录日志
            $logtext = $this->checkDifAfter($databefore,$category);
            $logtext .= "(CAT_ID:".$cat_id.")";
            $logtext = $this->infoAddClass($logtext, 'text-edit');
            $route = $this->request->routeInfo();
            $route_tran = $this->routeTranslation($route, 'sys_menu');
            $admin = $this->tokenGetAdminInfo($Token);//获取管理员信息
            $this->addSysLog(time(), $admin['user_name'], $route_tran.$logtext, $request->ip());

            return comReturn(true,config("return_message.error_status_code")['ok']['value']);
        }catch (Exception $e){
            return comReturn(false,$e->getMessage(), '', 500);
        }
    }

    /**
     * 删除分类信息
     * @param Request $request
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function delCategory(Request $request)
    {

        $cat_id = $request->param("cat_id","");

        try{
            if (!empty($cat_id)){
                $goodsModel = new ShopGoods();
                //查询是否有此分类
                $cat_id_exist = $goodsModel
                    ->where('cat_id',$cat_id)
                    ->count();
                if (!$cat_id_exist){
                    //可以删除
                    $categoryModel = new GoodsCategory();
                    Db::startTrans();
                    try{
                        $category['is_delete']  = 1;
                        $category['updated_at'] = time();
                        $is_delete = $categoryModel
                            ->where("cat_id",$cat_id)
                            ->update($category);
                        if ($is_delete){
                            Db::commit();

                            $Token = Request::instance()->header("Token","");
                            //记录日志
                            $logtext = "(CAT_ID:".$cat_id.")";
                            $logtext = $this->infoAddClass($logtext, 'text-del');
                            $route = $this->request->routeInfo();
                            $route_tran = $this->routeTranslation($route, 'sys_menu');
                            $admin = $this->tokenGetAdminInfo($Token);//获取管理员信息
                            $this->addSysLog(time(), $admin['user_name'], $route_tran.$logtext, $request->ip());

                            return comReturn(true,"删除成功");
                        }
                    }catch (Exception $e){
                        Db::rollback();
                        return comReturn(false,$e->getMessage(), '', 500);
                    }
                }else{
                    return comReturn(false,'当前分类下存在商品，不能删除');
                }
            }else{
                return comReturn(false,'分类id不能为空');
            }
        }catch (Exception $e){
            return comReturn(false,$e->getMessage(), '', 500);
        }
    }

    /**
     * 启用停用分类
     * @param Request $request
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function enableCategory(Request $request)
    {
        $cat_id    = $request->param("cat_id", "");//分类id
        $is_enable = $request->param("is_enable","");//分类状态

        try{
            $rule = [
                "cat_id|分类id"      => "require",
                "is_enable|启用状态" => "require",
            ];
            $check_data = [
                "cat_id"    => $cat_id,
                "is_enable" => $is_enable,
            ];
            $validate = new Validate($rule);
            if (!$validate->check($check_data)){
                return comReturn(false,$validate->getError(), '', 500);
            }

            $category['is_enable']  = $is_enable;
            $category['updated_at'] = time();

            Log::info("改变分类启动状态 ----- ".var_export($category,true));

            $categoryModel = new GoodsCategory();

            // 获取修改之前的数据
            $keys = array_keys($category);
            $databefore = $this->updateBefore("goods_category", "cat_id", $cat_id, $keys);

            $res = $categoryModel
                ->where('cat_id',$cat_id)
                ->update($category);

            if ($res == false){
                return comReturn(false,"修改失败", '', 500);
            }

            $Token = Request::instance()->header("Token","");
            //记录日志
            $logtext = $this->checkDifAfter($databefore,$category);
            $logtext .= "(CAT_ID:".$cat_id.")";
            $logtext = $this->infoAddClass($logtext, 'text-edit');
            $route = $this->request->routeInfo();
            $route_tran = $this->routeTranslation($route, 'sys_menu');
            $admin = $this->tokenGetAdminInfo($Token);//获取管理员信息
            $this->addSysLog(time(), $admin['user_name'], $route_tran.$logtext, $request->ip());

            return comReturn(true,config("return_message.error_status_code")['ok']['value']);
        }catch (Exception $e){
            return comReturn(false,$e->getMessage(), '', 500);
        }
    }

    /**
     * 改变分类排序
     * @param Request $request
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function sortCategory(Request $request)
    {
        $cat_id = $request->param("cat_id", "");//分类id
        $sort   = $request->param("sort","");//分类状态

        try{
            $rule = [
                "cat_id|分类id" => "require",
                "sort|排序"     => "require",
            ];
            $check_data = [
                "cat_id" => $cat_id,
                "sort"   => $sort,
            ];
            $validate = new Validate($rule);
            if (!$validate->check($check_data)){
                return comReturn(false,$validate->getError(), '', 500);
            }

            $category['sort']  = $sort;
            $category['updated_at'] = time();

            Log::info("改变分类排序 ----- ".var_export($category,true));

            $categoryModel = new GoodsCategory();

            // 获取修改之前的数据
            $keys = array_keys($category);
            $databefore = $this->updateBefore("goods_category", "cat_id", $cat_id, $keys);

            $res = $categoryModel
                ->where('cat_id',$cat_id)
                ->update($category);

            if ($res == false){
                return comReturn(false,"修改失败", '', 500);
            }

            $Token = Request::instance()->header("Token","");
            //记录日志
            $logtext = $this->checkDifAfter($databefore,$category);
            $logtext .= "(CAT_ID:".$cat_id.")";
            $logtext = $this->infoAddClass($logtext, 'text-edit');
            $route = $this->request->routeInfo();
            $route_tran = $this->routeTranslation($route, 'sys_menu');
            $admin = $this->tokenGetAdminInfo($Token);//获取管理员信息
            $this->addSysLog(time(), $admin['user_name'], $route_tran.$logtext, $request->ip());

            return comReturn(true,config("return_message.error_status_code")['ok']['value']);
        }catch (Exception $e){
            return comReturn(false,$e->getMessage(), '', 500);
        }
    }



}