<?php

/**
 * 首页banner
 * @Author: zhangtao
 * @Date:   2018-11-22 14:29:25
 * @Last Modified by:   admin
 * @Last Modified time: 2018-12-03 15:45:10
 */
namespace app\admin\controller\smallProgram;

use app\common\controller\SysAdminAuth;
use app\admin\model\HomeBanner;
use app\shopadmin\model\Shop;
use app\admin\model\GoodsCategory;
use think\Log;
use think\Request;
use think\Validate;
use think\Db;

class Banner extends SysAdminAuth
{

    /**
     * 首页banner
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

            $config = [
                "page" => $nowPage
            ];

            $bannerModel = new HomeBanner();

            $result = $bannerModel
                      ->where("is_delete", 0)
                      ->field("id,banner_img,type,type_id,link,sort,is_enable,created_at,updated_at")
                      // ->order("updated_at desc")
                      ->order($orderBy['filter']['orderBy'], $orderBy['filter']['sort'])
                      ->paginate($pagesize,false,$config);

            $shopModel     = new Shop();
            $categoryModel = new GoodsCategory();
            foreach ($result as $key => $value) {
                if ($value['type'] == 0) {
                    $shop = $shopModel
                            ->where("sid", $value['type_id'])
                            ->field("shop_name")
                            ->find();
                    $result[$key]['type_name'] = $shop['shop_name'];
                }else if($value['type'] == 1){
                    $category = $categoryModel
                                ->where("cat_id", $value['type_id'])
                                ->field("cat_name")
                                ->find();
                    $result[$key]['type_name'] = $category['cat_name'];
                }else if($value['type'] == 2){
                    $result[$key]['type_name'] = $value['link'];
                }
                $result[$key]['type_ids'] = $value['type_id'];
            }

            return comReturn(true,config("return_message.error_status_code")['ok']['value'],$result);
        }catch (Exception $e){
            return comReturn(false,$e->getMessage(), '', 500);
        }
    }

    /**
     * 添加首页banner
     * @param Request $request
     * @return array
     */
    public function addHomeBanner(Request $request)
    {

        $banner_img = $request->param("banner_img","");//banner图片
        $type       = $request->param("type","");//类型 0店铺  1商品分类  2wap链接
        $type_id    = $request->param("type_id","");//店铺或商品分类id
        $link       = $request->param("link","");//wap链接
        $is_enable  = $request->param("is_enable","");//wap链接
        $sort       = $request->param("sort",0);//排序

        try{
            $rule = [
                "banner_img|banner图片" => "require",
                "type|类型"             => "require"
            ];

            $check_data = [
                "banner_img" => $banner_img,
                "type"       => $type
            ];

            if ($type == 0) {
                $rule['type_id|店铺id'] = "require";
                $check_data['type_id'] = $type_id;

                $shopModel = new Shop();
                $count = $shopModel->where("sid", $type_id)->field("count(1) AS count")->find();
                if ($count['count'] <= 0) {
                    return comReturn(false,"请选择店铺", '', 500);
                }
            }else if ($type == 1) {
                $rule['type_id|商品分类id'] = "require";
                $check_data['type_id']    = $type_id;

                $categoryModel = new GoodsCategory();
                $count = $categoryModel->where("cat_id", $type_id)->field("count(1) AS count")->find();
                if ($count['count'] <= 0) {
                    return comReturn(false,"请选择商品分类", '', 500);
                }
            }else{
                $rule['link|链接']   = "require";
                $check_data['link'] = $link;
            }

            $validate = new Validate($rule);

            if (!$validate->check($check_data)){
                return comReturn(false,$validate->getError(), '', 500);
            }


            //写入数据,返回id
            $insert_data = [
                "banner_img" => $banner_img,
                "type_id"    => $type_id,
                "type"       => $type,
                "link"       => $link,
                "sort"       => $sort
            ];

            Db::startTrans();
            $bannerModel = new HomeBanner();
            $id = $bannerModel->insertGetId_ex($insert_data, true, $is_enable, 0);
            if (!empty($id)){
                Db::commit();

                $Token = Request::instance()->header("Token","");
                //记录日志
                $logtext = "(ID:".$id.")";
                $logtext = $this->infoAddClass($logtext, 'text-add');
                $route = $this->request->routeInfo();
                $route_tran = $this->routeTranslation($route, 'sys_menu');
                $admin = $this->tokenGetAdminInfo($Token);//获取管理员信息
                $this->addSysLog(time(), $admin['user_name'], $route_tran.$logtext, $request->ip());

                return comReturn(true,"banner添加成功");

            }else{
                Db::rollback();
                return comReturn(false,"banner添加失败", '', 500);
            }
        }catch (Exception $e){
            return comReturn(false,$e->getMessage(), '', 500);
        }
    }

    /**
     * 编辑首页banner
     * @param Request $request
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function editHomeBanner(Request $request)
    {
        $id         = $request->param("id", "");
        $banner_img = $request->param("banner_img","");//banner图片
        $type       = $request->param("type","");//类型 0店铺  1商品分类  2wap链接
        $type_id    = $request->param("type_id","");//店铺或商品分类id
        $link       = $request->param("link","");//wap链接
        $is_enable  = $request->param("is_enable","");//是否启用  0否 1是
        $sort       = $request->param("sort",0);//排序

        try{

            if (empty($id)){
                return comReturn(false,"banner id不能为空", '', 500);
            }

            $rule = [
                "banner_img|banner图片" => "require",
                "type|类型"             => "require"
            ];
            $check_data = [
                "banner_img" => $banner_img,
                "type"       => $type
            ];

            if ($type == 0) {
                $rule['type_id|店铺id'] = "require";
                $check_data['type_id'] = $type_id;

                $shopModel = new Shop();
                $count = $shopModel->where("sid", $type_id)->field("count(1) AS count")->find();
                if ($count['count'] <= 0) {
                    return comReturn(false,"请选择店铺", '', 500);
                }
            }else if ($type == 1) {
                $rule['type_id|商品分类id'] = "require";
                $check_data['type_id']    = $type_id;

                $categoryModel = new GoodsCategory();
                $count = $categoryModel->where("cat_id", $type_id)->field("count(1) AS count")->find();
                if ($count['count'] <= 0) {
                    return comReturn(false,"请选择商品分类", '', 500);
                }
            }else{
                $rule['link|链接']   = "require";
                $check_data['link'] = $link;
            }

            $validate = new Validate($rule);
            if (!$validate->check($check_data)){
                return comReturn(false,$validate->getError(), '', 500);
            }

            $banner['banner_img'] = $banner_img;
            $banner['type']       = $type;
            $banner['type_id']    = $type_id;
            $banner['link']       = $link;
            $banner['sort']       = $sort;
            $banner['is_enable']  = $is_enable;
            $banner['updated_at'] = time();

            Log::info("编辑首页banner ----- ".var_export($banner,true));

            $bannerModel = new HomeBanner();

            // 获取修改之前的数据
            $keys = array_keys($banner);
            $databefore = $this->updateBefore("home_banner", "id", $id, $keys);

            $res = $bannerModel
                ->where('id',$id)
                ->update($banner);

            if ($res == false){
                return comReturn(false,"编辑首页banner失败", '', 500);
            }

            $Token = Request::instance()->header("Token","");
            //记录日志
            $logtext = $this->checkDifAfter($databefore,$banner);
            $logtext .= "(ID:".$id.")";
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
     * 删除首页banner
     * @param Request $request
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function delHomeBanner(Request $request)
    {

        $ids = $request->param("id","");

        try{
            if (!empty($ids)){
                $id_list = explode(",", $ids);

                $bannerModel = new HomeBanner();
                Db::startTrans();
                try{
                    $banner['is_delete']  = 1;
                    $banner['updated_at'] = time();
                    $is_ok = true;

                    foreach ($id_list as $key => $value) {
                        $is_delete = $bannerModel
                            ->where("id",$value)
                            ->update($banner);
                        if (!$is_delete) {
                            $is_ok = false;
                        }
                    }

                    if ($is_ok){
                        Db::commit();

                        $Token = Request::instance()->header("Token","");
                        //记录日志
                        $logtext = "(ID:".$ids.")";
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
                return comReturn(false,'banner id不能为空');
            }
        }catch (Exception $e){
            return comReturn(false,$e->getMessage(), '', 500);
        }
    }


    /**
     * 改变banner状态
     * @param Request $request
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function enableHomeBanner(Request $request)
    {
        $id        = $request->param("id", "");//banner id
        $is_enable = $request->param("is_enable","");//banner状态

        try{
            $rule = [
                "id|banner id"      => "require",
                "is_enable|启用状态" => "require",
            ];
            $check_data = [
                "id"        => $id,
                "is_enable" => $is_enable,
            ];
            $validate = new Validate($rule);
            if (!$validate->check($check_data)){
                return comReturn(false,$validate->getError(), '', 500);
            }

            $banner['is_enable']  = $is_enable;
            $banner['updated_at'] = time();

            Log::info("改变banner启动状态 ----- ".var_export($banner,true));

            $bannerModel = new HomeBanner();

            // 获取修改之前的数据
            $keys = array_keys($banner);
            $databefore = $this->updateBefore("home_banner", "id", $id, $keys);

            $res = $bannerModel
                ->where('id',$id)
                ->update($banner);

            if ($res == false){
                return comReturn(false,"修改失败", '', 500);
            }

            $Token = Request::instance()->header("Token","");
            //记录日志
            $logtext = $this->checkDifAfter($databefore,$banner);
            $logtext .= "(ID:".$id.")";
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
     * 改变banner排序
     * @param Request $request
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function sortHomeBanner(Request $request)
    {
        $id    = $request->param("id", "");//banner id
        $sort = $request->param("sort","");//banner排序

        try{
            $rule = [
                "id|banner id" => "require",
                "sort|顺序"    => "require",
            ];
            $check_data = [
                "id"   => $id,
                "sort" => $sort,
            ];
            $validate = new Validate($rule);
            if (!$validate->check($check_data)){
                return comReturn(false,$validate->getError(), '', 500);
            }

            $banner['sort']       = $sort;
            $banner['updated_at'] = time();

            Log::info("改变banner排序 ----- ".var_export($banner,true));

            $bannerModel = new HomeBanner();

            // 获取修改之前的数据
            $keys = array_keys($banner);
            $databefore = $this->updateBefore("home_banner", "id", $id, $keys);

            $res = $bannerModel
                ->where('id',$id)
                ->update($banner);

            if ($res == false){
                return comReturn(false,"修改失败", '', 500);
            }

            $Token = Request::instance()->header("Token","");
            //记录日志
            $logtext = $this->checkDifAfter($databefore,$banner);
            $logtext .= "(ID:".$id.")";
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