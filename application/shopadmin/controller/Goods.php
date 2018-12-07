<?php

/**
 * 商品管理
 * @Author: zhangtao
 * @Date:   2018-10-23 11:26:35
 * @Last Modified by:   admin
 * @Last Modified time: 2018-12-04 12:18:13
 */

namespace app\shopadmin\controller;

use app\common\controller\ShopAdminAuth;
use app\services\controller\ImageUpload;
use app\shopadmin\model\ShopAdmin;
use app\shopadmin\model\Shop;
use app\shopadmin\model\ShopDoctor;
use app\shopadmin\model\ShopGoods;
use app\shopadmin\model\ShopGoodsCategory;
use app\shopadmin\model\GoodsImage;
use think\Env;
use think\Log;
use think\Request;
use think\Validate;
use think\Db;

class Goods extends ShopAdminAuth
{

    /**
     * 商品列表
     * @param Request $request
     * @return array
     */
    public function index(Request $request){

        $sid = $request->param("sid","");
        if (empty($sid)) {
            return comReturn(false, config("return_message.error_status_code")['lack_param_necessary']['value'], '', config("return_message.error_status_code")['lack_param_necessary']['key']);
        }

        $pagesize = $request->param("pagesize",config('PAGESIZE'));//显示个数,不传时为10

        if (empty($pagesize)) $pagesize = config('PAGESIZE');

        $nowPage = $request->param("nowPage","1");

        $keyword = $request->param("keyword","");


        $orderBy['filter']['orderBy'] = $request->has('orderBy') ? $request->param('orderBy') : 'created_at';
        $orderBy['filter']['sort'] = $request->has('sort') ? $request->param('sort') : 'desc';

        $config = [
            "page" => $nowPage,
        ];

        $where = [];
        if (!empty($keyword)){
            $where["gid|goods_name"] = ["like","%$keyword%"];
        }

        try{
            $goodsModel = new ShopGoods();

            //处理排序条件
            $field_array = array("goods_name");
            if (in_array($orderBy['filter']['orderBy'], $field_array)) {
                $orderBy['filter']['orderBy'] = fieldOrderByEncode($orderBy['filter']['orderBy']);
            }

            $goods_list = $goodsModel
                          ->where($where)
                          ->where('is_delete', 0)
                          ->where('sid', $sid)
                          // ->order('updated_at desc')
                          ->order($orderBy['filter']['orderBy'], $orderBy['filter']['sort'])
                          ->field('gid,sid,eid,status,cat_id,goods_name,goods_sketch,goods_original_price,goods_price,goods_content,view_num,is_top,sort,created_at,updated_at')
                          ->paginate($pagesize,false,$config);


            if ($goods_list) {

                $decimal = $this->getSettingInfo("sys_show_number_decimal")['sys_show_number_decimal'];

                //提取商品图片
                $imageModel = new GoodsImage();
                foreach ($goods_list as $key => $value) {

                    $image_arr = $imageModel
                                 ->where('gid', $value['gid'])
                                 ->field('image')
                                 ->select();
                    $goods_image = '';
                    $goods_list[$key]['goods_image'] = '';
                    foreach ($image_arr as $k => $v) {
                        $goods_image .= $v['image'];
                        if ($k+1 != count($image_arr)) {
                            $goods_image .= ',';
                        }
                    }
                    $goods_list[$key]['goods_image'] = $goods_image;

                    $goods_list[$key]['goods_original_price'] = sprintf("%.".$decimal."f", $value['goods_original_price']);
                    $goods_list[$key]['goods_price'] = sprintf("%.".$decimal."f", $value['goods_price']);
                }

                return comReturn(true,config("return_message.success"),$goods_list);
            }else{
                return comReturn(false,config("return_message.fail"), '', 500);
            }
        }catch (Exception $e){
            return comReturn(false, $e->getMessage(), '', 500);
        }

    }

    /**
     * 获取全部分类
     * @param Request $request
     * @return array
     */
    public function getCategory()
    {
        try{
            $cateModel = new ShopGoodsCategory();

            $cateList = $cateModel
                        ->where('is_delete', 0)
                        ->where('is_enable', 1)
                        ->field('cat_id,cat_name,cat_image,sort')
                        ->order('sort')
                        ->select();

            if ($cateList) {
                return comReturn(true,config("return_message.success"),$cateList);
            }else{
                return comReturn(false,config("return_message.fail"), '', 500);
            }
        }catch (Exception $e){
            return comReturn(false, $e->getMessage(), '', 500);
        }

    }


    /**
     * 添加商品
     * @param Request $request
     * @return array
     */
    public function addGoods(Request $request)
    {

        $Token = Request::instance()->header("Token","");
        $sid   = $request->param("sid","");//商铺id
        if (empty($sid)) {
            return comReturn(false, config("return_message.error_status_code")['lack_param_necessary']['value'], '', config("return_message.error_status_code")['lack_param_necessary']['key']);
        }else{
            $auth = $this->tokenJudgeAuth($Token, $sid);
            if (!$auth) {
                return comReturn(false,config('user.no_auth'), '', 500);
            }
        }

        $cat_id               = $request->param("cat_id","");//分类id
        $goods_name           = $request->param("goods_name","");//商品名称
        $goods_sketch         = $request->param("goods_sketch","");//商品简述
        $goods_original_price = $request->param("goods_original_price","");//商品原价
        $goods_price          = $request->param("goods_price","");//商品售价
        $goods_content        = $request->param("goods_content","");//商品原价
        $sort                 = $request->param("sort","");//商品排序
        $status               = $request->param("status","");//商品状态 在售0   下架1
        $is_top               = $request->param("is_top","");//置顶标记 0默认 1置顶

        try{
            //生成sid
            $gid = generateReadableUUID("G");

            $goods['gid']                  = $gid;
            $goods['sid']                  = $sid;
            $goods['cat_id']               = $cat_id;
            $goods['goods_name']           = $goods_name;
            $goods['goods_sketch']         = $goods_sketch;
            $goods['goods_original_price'] = $goods_original_price;
            $goods['goods_price']          = $goods_price;
            $goods['goods_content']        = $goods_content;
            $goods['sort']                 = $sort;
            $goods['status']               = $status;
            $goods['is_top']               = $is_top;

            Log::info("商品参数  ---- ".var_export($goods,true));

            //规则验证
            $rule = [
                "sid"                          => "require",//商铺id
                "goods_name|商品名称"           => "require",
                "goods_sketch|商品简述"         => "require",
                "goods_original_price|商品原价" => "require",
                "goods_price|商品售价"          => "require|gt:0",
                // "goods_content|其他描述"        => "require",
                "sort|排序序号"                 => "require",
                "status|状态"                   => "require",
            ];
            $check_data = [
                "sid"                  => $sid,
                "goods_name"           => $goods_name,
                "goods_sketch"         => $goods_sketch,
                "goods_original_price" => $goods_original_price,
                "goods_price"          => $goods_price,
                // "goods_content"        => $goods_content,
                "sort"                 => $sort,
                "status"               => $status,
            ];

            $validate = new Validate($rule);
            if (!$validate->check($check_data)){
                return comReturn(false,$validate->getError(), '', 500);
            }

            $admin = $this->tokenGetAdmin($Token);

            $goods['eid'] = $admin['eid'];

            $shopGoodsModel = new ShopGoods();

            Db::startTrans();
            $is_ok = $shopGoodsModel->insert_ex($goods, true, false, 0);
            if ($is_ok) {
                Db::commit();

                //记录日志
                $logtext = "(GID:".$gid.")";
                $logtext = $this->infoAddClass($logtext, 'text-add');
                $route = $this->request->routeInfo();
                $route_tran = $this->routeTranslation($route, 'shop_menu');
                $admin = $this->tokenGetAdmin($Token);//获取管理员信息
                $this->addSysLog(time(), $admin['log_name'], $route_tran.$logtext, $request->ip(), $sid);

                //插入图片
                $goods_image = $request->param("goods_image","");//商品多图
                if (!empty($goods_image)) {
                    $image_arr = explode(',', $goods_image);
                    if (is_array($image_arr)) {
                        $imageModel = new GoodsImage();
                        $image['gid'] = $gid;
                        foreach ($image_arr as $key => $value) {
                            if (!empty($value)) {
                                $image['sort_id'] = $key;
                                $image['image'] = $value;
                                $imageModel->insert($image);
                            }
                        }
                    }
                }

                return comReturn(true,config('return_message.success'));
            }else{
                Db::rollback();
                return comReturn(false,config('return_message.fail'), '', 500);
            }
        }catch (Exception $e){
            return comReturn(false, $e->getMessage(), '', 500);
        }
    }

    /**
     * 编辑商品信息
     * @param Request $request
     * @return array
     */
    public function editGoods(Request $request)
    {
        $Token = Request::instance()->header("Token","");
        $gid   = $request->param("gid","");//商品id

        try{
            $shopGoodsModel = new ShopGoods();

            if (empty($gid)) {
                return comReturn(false,"缺少gid", '', 500);
            }else{

                $goods_data = $shopGoodsModel
                        ->field('sid')
                        ->where('gid', $gid)
                        ->where('is_delete', 0)
                        ->find();
                $auth = $this->tokenJudgeAuth($Token, $goods_data['sid']);
                if (!$auth) {
                    return comReturn(false,config('user.no_auth'), '', 500);
                }
            }

            $cat_id               = $request->param("cat_id","");//分类id
            $goods_name           = $request->param("goods_name","");//商品名称
            $goods_sketch         = $request->param("goods_sketch","");//商品简述
            $goods_original_price = $request->param("goods_original_price","");//商品原价
            $goods_price          = $request->param("goods_price","");//商品售价
            $goods_content        = $request->param("goods_content","");//商品原价
            $sort                 = $request->param("sort","");//商品排序
            $status               = $request->param("status","");//商品状态  在售0   下架1
            $is_top               = $request->param("is_top","");//置顶标记 0默认 1置顶

            $goods['cat_id']               = $cat_id;
            $goods['goods_name']           = $goods_name;
            $goods['goods_sketch']         = $goods_sketch;
            $goods['goods_original_price'] = $goods_original_price;
            $goods['goods_price']          = $goods_price;
            $goods['goods_content']        = $goods_content;
            $goods['sort']                 = $sort;
            $goods['status']               = $status;
            $goods['is_top']               = $is_top;

            Log::info("商品参数  ---- ".var_export($goods,true));

            //规则验证
            $rule = [
                "goods_name|商品名称"           => "require",
                "goods_sketch|商品简述"         => "require",
                "goods_original_price|商品原价" => "require",
                "goods_price|商品售价"          => "require|gt:0",
                // "goods_content|其他描述"        => "require",
                "sort|排序序号"                 => "require",
                "status|状态"                   => "require",
            ];
            $check_data = [
                "goods_name"           => $goods_name,
                "goods_sketch"         => $goods_sketch,
                "goods_original_price" => $goods_original_price,
                "goods_price"          => $goods_price,
                // "goods_content"        => $goods_content,
                "sort"                 => $sort,
                "status"               => $status,
            ];

            $validate = new Validate($rule);
            if (!$validate->check($check_data)){
                return comReturn(false,$validate->getError(), '', 500);
            }

            $goods['updated_at'] = time();

            // 获取修改之前的数据
            $keys = array_keys($goods);
            $databefore = $this->updateBefore("goods", "gid", $gid, $keys);

            Db::startTrans();

            $is_ok = $shopGoodsModel
                    ->where('gid',$gid)
                    ->update($goods);
            if ($is_ok) {
                Db::commit();

                //记录日志
                $logtext = $this->checkDifAfter($databefore,$goods);
                $logtext .= "(GID:".$gid.")";
                $logtext = $this->infoAddClass($logtext, 'text-edit');
                $route = $this->request->routeInfo();
                $route_tran = $this->routeTranslation($route, 'shop_menu');
                $admin = $this->tokenGetAdmin($Token);//获取管理员信息
                $this->addSysLog(time(), $admin['log_name'], $route_tran.$logtext, $request->ip(), $goods_data['sid']);

                //上传商品图片
                $goods_image = $request->param("goods_image","");//商品多图
                if (!empty($goods_image)) {
                    $image_arr = explode(',', $goods_image);
                    if (is_array($image_arr)) {
                        $imageModel = new GoodsImage();
                        $imageModel->where('gid', $gid)->delete();
                        $image['gid'] = $gid;
                        foreach ($image_arr as $key => $value) {
                            if (!empty($value)) {
                                $image['sort_id'] = $key;
                                $image['image'] = $value;
                                $imageModel->insert($image);
                            }
                        }
                    }
                }

                return comReturn(true,config('return_message.success'));
            }else{
                Db::rollback();
                return comReturn(false,config('return_message.fail'), '', 500);
            }
        }catch (Exception $e){
            return comReturn(false, $e->getMessage(), '', 500);
        }
    }


    /**
     * 删除商品
     * @param Request $request
     * @return array
     */
    public function delGoods(Request $request)
    {

        $Token = Request::instance()->header("Token","");
        $gid   = $request->param("gid","");//商品id

        try{
            $shopGoodsModel = new ShopGoods();

            if (empty($gid)) {
                return comReturn(false,config('return_meaasge.fail'), '', 500);
            }else{

                $goods_data = $shopGoodsModel
                        ->field('sid')
                        ->where('gid', $gid)
                        ->where('is_delete', 0)
                        ->find();
                $auth = $this->tokenJudgeAuth($Token, $goods_data['sid']);
                if (!$auth) {
                    return comReturn(false,config('user.no_auth'), '', 500);
                }
            }

            $res = $shopGoodsModel
                    ->where('gid', $gid)
                    ->update(['is_delete' => 1]);

            if ($res) {

                //记录日志
                $logtext = "(GID:".$gid.")";
                $logtext = $this->infoAddClass($logtext, 'text-del');
                $route = $this->request->routeInfo();
                $route_tran = $this->routeTranslation($route, 'shop_menu');
                $admin = $this->tokenGetAdmin($Token);//获取管理员信息
                $this->addSysLog(time(), $admin['log_name'], $route_tran.$logtext, $request->ip(), $goods_data['sid']);

                //删除相关图片
                // $imageModel = new ShopImage();
                // $imageModel->where('gid', $gid)->delete();

                return comReturn(true,config('return_message.success'));
            }else{
                return comReturn(false,config('return_meaasge.fail'), '', 500);
            }
        }catch (Exception $e){
            return comReturn(false, $e->getMessage(), '', 500);
        }

    }

    /**
     * 置顶
     * @param Request $request
     * @return array
     */
    public function setGoodsTop(Request $request)
    {
        $Token = Request::instance()->header("Token","");
        $gid   = $request->param("gid","");//商品id

        try{
            $shopGoodsModel = new ShopGoods();

            if (empty($gid)) {
                return comReturn(false,config('return_meaasge.fail'), '', 500);
            }else{

                $goods_data = $shopGoodsModel
                        ->field('sid')
                        ->where('gid', $gid)
                        ->where('is_delete', 0)
                        ->find();
                $auth = $this->tokenJudgeAuth($Token, $goods_data['sid']);
                if (!$auth) {
                    return comReturn(false,config('user.no_auth'), '', 500);
                }
            }

            $is_top = $request->param("is_top","");//置顶状态

            $goods['is_top'] = $is_top;

            //规则验证
            $rule = [
                "gid|商品id"      => "require",
                "is_top|置顶状态" => "require",
            ];
            $check_data = [
                "gid"    => $gid,
                "is_top" => $is_top,
            ];

            $validate = new Validate($rule);
            if (!$validate->check($check_data)){
                return comReturn(false,$validate->getError(), '', 500);
            }

            $goods['updated_at'] = time();

            // 获取修改之前的数据
            $keys = array_keys($goods);
            $databefore = $this->updateBefore("goods", "gid", $gid, $keys);

            Db::startTrans();

            $is_ok = $shopGoodsModel
                    ->where('gid',$gid)
                    ->update($goods);
            if ($is_ok) {
                Db::commit();

                //记录日志
                $logtext = $this->checkDifAfter($databefore,$goods);
                $logtext .= "(GID:".$gid.")";
                $logtext = $this->infoAddClass($logtext, 'text-edit');
                $route = $this->request->routeInfo();
                $route_tran = $this->routeTranslation($route, 'shop_menu');
                $admin = $this->tokenGetAdmin($Token);//获取管理员信息
                $this->addSysLog(time(), $admin['log_name'], $route_tran.$logtext, $request->ip(), $goods_data['sid']);

                return comReturn(true,config('return_message.success'));
            }else{
                Db::rollback();
                return comReturn(false,config('return_message.fail'), '', 500);
            }
        }catch (Exception $e){
            return comReturn(false, $e->getMessage(), '', 500);
        }
    }

    /**
     * 改变商品状态
     * @param Request $request
     * @return array
     */
    public function enableGoods(Request $request)
    {
        $Token = Request::instance()->header("Token","");
        $gid   = $request->param("gid","");//商品id

        try{
            $shopGoodsModel = new ShopGoods();

            if (empty($gid)) {
                return comReturn(false,config('return_meaasge.fail'), '', 500);
            }else{

                $goods_data = $shopGoodsModel
                        ->field('sid')
                        ->where('gid', $gid)
                        ->where('is_delete', 0)
                        ->find();
                $auth = $this->tokenJudgeAuth($Token, $goods_data['sid']);
                if (!$auth) {
                    return comReturn(false,config('user.no_auth'), '', 500);
                }
            }

            $status = $request->param("status","");//商品状态  在售0   下架1

            $goods['status'] = $status;

            //规则验证
            $rule = [
                "gid|商品id"      => "require",
                "status|商品状态" => "require",
            ];
            $check_data = [
                "gid"    => $gid,
                "status" => $status,
            ];

            $validate = new Validate($rule);
            if (!$validate->check($check_data)){
                return comReturn(false,$validate->getError(), '', 500);
            }

            $goods['updated_at'] = time();

            // 获取修改之前的数据
            $keys = array_keys($goods);
            $databefore = $this->updateBefore("goods", "gid", $gid, $keys);

            Db::startTrans();

            $is_ok = $shopGoodsModel
                    ->where('gid',$gid)
                    ->update($goods);
            if ($is_ok) {
                Db::commit();

                //记录日志
                $logtext = $this->checkDifAfter($databefore,$goods);
                $logtext .= "(GID:".$gid.")";
                $logtext = $this->infoAddClass($logtext, 'text-edit');
                $route = $this->request->routeInfo();
                $route_tran = $this->routeTranslation($route, 'shop_menu');
                $admin = $this->tokenGetAdmin($Token);//获取管理员信息
                $this->addSysLog(time(), $admin['log_name'], $route_tran.$logtext, $request->ip(), $goods_data['sid']);

                return comReturn(true,config('return_message.success'));
            }else{
                Db::rollback();
                return comReturn(false,config('return_message.fail'), '', 500);
            }
        }catch (Exception $e){
            return comReturn(false, $e->getMessage(), '', 500);
        }
    }

    /**
     * 改变商品排序
     * @param Request $request
     * @return array
     */
    public function sortGoods(Request $request)
    {
        $Token = Request::instance()->header("Token","");
        $gid   = $request->param("gid","");//商品id

        try{
            $shopGoodsModel = new ShopGoods();

            if (empty($gid)) {
                return comReturn(false,config('return_meaasge.fail'), '', 500);
            }else{

                $goods_data = $shopGoodsModel
                        ->field('sid')
                        ->where('gid', $gid)
                        ->where('is_delete', 0)
                        ->find();
                $auth = $this->tokenJudgeAuth($Token, $goods_data['sid']);
                if (!$auth) {
                    return comReturn(false,config('user.no_auth'), '', 500);
                }
            }

            $sort = $request->param("sort","");//商品排序

            $goods['sort'] = $sort;

            //规则验证
            $rule = [
                "gid|商品id" => "require",
                "sort|排序"  => "require",
            ];
            $check_data = [
                "gid"  => $gid,
                "sort" => $sort,
            ];

            $validate = new Validate($rule);
            if (!$validate->check($check_data)){
                return comReturn(false,$validate->getError(), '', 500);
            }

            $goods['updated_at'] = time();

            // 获取修改之前的数据
            $keys = array_keys($goods);
            $databefore = $this->updateBefore("goods", "gid", $gid, $keys);

            Db::startTrans();

            $is_ok = $shopGoodsModel
                    ->where('gid',$gid)
                    ->update($goods);
            if ($is_ok) {
                Db::commit();

                //记录日志
                $logtext = $this->checkDifAfter($databefore,$goods);
                $logtext .= "(GID:".$gid.")";
                $logtext = $this->infoAddClass($logtext, 'text-edit');
                $route = $this->request->routeInfo();
                $route_tran = $this->routeTranslation($route, 'shop_menu');
                $admin = $this->tokenGetAdmin($Token);//获取管理员信息
                $this->addSysLog(time(), $admin['log_name'], $route_tran.$logtext, $request->ip(), $goods_data['sid']);

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