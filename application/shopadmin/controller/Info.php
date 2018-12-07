<?php

/**
 * 店铺
 * @Author: zhangtao
 * @Date:   2018-10-18 12:53:29
 * @Last Modified by:   admin
 * @Last Modified time: 2018-12-04 12:01:56
 */
namespace app\shopadmin\controller;

use app\common\controller\ShopAdminAuth;
use app\services\controller\ImageUpload;
use app\shopadmin\model\ShopAdmin;
use app\shopadmin\model\Shop;
use app\shopadmin\model\ShopImage;
use think\Env;
use think\Log;
use think\Request;
use think\Validate;
use think\Db;

class Info extends ShopAdminAuth
{

    /**
     * 店铺及管理员信息
     * @param Request $request
     * @return array
     */
    public function index(){
        $Token = Request::instance()->header("Token","");

        try{
            $list = $this->tokenGetList($Token);
            $list1 = $this->tokenGetAdmin($Token);

            $res = [
                'result'  => true,
                'message' => config('return_message.success'),
                'data'    => $list,
                'eid'     => $list1['eid'],
                'code'    => 200
            ];
            $res = json($res);

            return $res;
        }catch (Exception $e){
            return comReturn(false, $e->getMessage(), '', 500);
        }

    }

	/**
	 * 添加店铺及管理员信息
     * @param Request $request
     * @return array
	 */
	public function addShop(Request $request)
	{
        $Token = Request::instance()->header("Token","");

        $eid                 = $request->param("eid","");//企业id
        $shop_name           = $request->param("shop_name","");//商铺名称
        $shop_operating_time = $request->param("shop_operating_time","");//营业时间
        $shop_phone          = $request->param("shop_phone","");//店铺电话

        $username            = $request->param("username","");//用户名
        $phone               = $request->param("phone","");//手机号
        $name                = $request->param("name","");//真实姓名
        $password            = $request->param("password","");//默认密码

        try{
            //生成sid
            $sid = generateReadableUUID("S");

            $shop['eid']                 = $eid;
            $shop['sid']                 = $sid;
            $shop['shop_name']           = $shop_name;
            $shop['shop_operating_time'] = $shop_operating_time;
            $shop['shop_phone']          = $shop_phone;
            $shop['status']              = 1;

            $shop_admin['eid']      = $eid;
            $shop_admin['sid']      = $sid;
            $shop_admin['username'] = $username;
            $shop_admin['name']     = $name;
            $shop_admin['phone']    = $phone;
            $shop_admin['password'] = jmPassword($password);

            Log::info("店铺参数  ---- ".var_export($shop,true));
            Log::info("店长参数  ---- ".var_export($shop_admin,true));

            //规则验证
            $rule = [
                "eid"                         => "require",//企业id
                "shop_name|店铺名称"           => "require",
                "shop_operating_time|营业时间" => "require",
                "shop_phone|店铺电话"          => "require",
                "username|管理员用户名"        => "require",
                "name|真实姓名"               => "require",
                "phone|电话"                  => "require",
                "password|密码"               => "require",
            ];
            $check_data = [
                "eid"                 => $eid,
                "shop_name"           => $shop_name,
                "shop_operating_time" => $shop_operating_time,
                "shop_phone"          => $shop_phone,
                "username"            => $username,
                "name"                => $name,
                "phone"               => $phone,
                "password"            => $password,
            ];

            $validate = new Validate($rule);
            if (!$validate->check($check_data)){
                return comReturn(false,$validate->getError(), '', 500);
            }

            //判断是否有重复项
            $data = $this->checkField('shop', 'shop_name', $shop_name);
            if ($data){
                return comReturn(false,config('shop.has_shop_name'));
            }
            //管理员-判断是否重复
            $data = $this->checkField('shop_admin', 'username', $username);
            if ($data){
                return comReturn(false,config('shop_admin.has_username'));
            }

            $shop_admin['type']           = 1;
            $shop_admin['status']         = config("shop_admin.status")['able']['key'];
            $shop_admin['lastlogin_time'] = time();
            $shop_admin['avatar']         = $this->getSettingInfo('sys_default_avatar')['sys_default_avatar'];
            // var_dump($shop_admin);die;

            $shopModel = new Shop();
            $shopAdminModel = new ShopAdmin();

            Db::startTrans();
            $is_ok = $shopModel->insert_ex($shop, true);
            if ($is_ok !== false){

                $aid = $shopAdminModel->insertGetId_ex($shop_admin, true);

                if ($aid > 0) {
                    Db::commit();

                    //记录日志
                    $logtext = "(SID:".$sid."),(AID:".$aid.")";
                    $logtext = $this->infoAddClass($logtext, 'text-add');
                    $route = $this->request->routeInfo();
                    $route_tran = $this->routeTranslation($route, 'shop_menu');
                    $admin = $this->tokenGetAdmin($Token);//获取管理员信息
                    $this->addSysLog(time(), $admin['log_name'], $route_tran.$logtext, $request->ip(), $sid);

                    return comReturn(true,config('return_message.success'));
                }else{
                    Db::rollback();
                    return comReturn(false,config('return_message.fail'), '', 500);
                }

            }else{
                Db::rollback();
                return comReturn(false,config('return_message.fail'), '', 500);
            }
        }catch (Exception $e){
            return comReturn(false, $e->getMessage(), '', 500);
        }
	}

    /**
     * 获取管理员及店铺简略信息
     * @param Request $request
     * @return array
     */
    public function getSimpleInfo(Request $request){
        $Token = Request::instance()->header("Token","");
        $sid            = $request->param("sid","");

        try{
            //规则验证
            $rule = [
                "sid|店铺id"           => "require",
            ];
            $check_data = [
                "sid"                 => $sid,
            ];

            $validate = new Validate($rule);
            if (!$validate->check($check_data)){
                return comReturn(false,$validate->getError(), '', 500);
            }

            $shopModel = new Shop();

            $info['shop'] = $shopModel
                            ->field('sid,shop_name,shop_phone,shop_operating_time,shop_desc')
                            ->where('sid', $sid)
                            ->find();

            $adminModel = new ShopAdmin();

            $info['admin'] = $adminModel
                             ->field('aid,username,phone,name')
                             ->where('sid', $sid)
                             ->find();

            if ($info) {

                return comReturn(true,config("return_message.success"),$info);
            }else{
                return comReturn(false,config('return_message.fail'), '', 500);
            }
        }catch (Exception $e){
            return comReturn(false, $e->getMessage(), '', 500);
        }

    }


    /**
     * 编辑管理员信息
     * @param Request $request
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function editSimpleShopAdmin(Request $request)
    {
        $Token = Request::instance()->header("Token","");
        $aid            = $request->param("aid","");
        $username       = $request->param("username","");//管理员用户名
        $password       = $request->param("password","");//管理员密码
        $name           = $request->param("name","");//真实姓名
        $phone          = $request->param("phone","");//手机号

        try{
            $rule = [
                "aid"                    => "require",
                "username|管理员姓名"     => "require|unique:shop_admin",
                "name|真实姓名"           => "require",
                "phone|手机号"            => "require",
            ];
            $check_data = [
                "aid"          => $aid,
                "username"     => $username,
                "name"         => $name,
                "phone"        => $phone,
            ];
            $validate = new Validate($rule);
            if (!$validate->check($check_data)){
                return comReturn(false,$validate->getError(), '', 500);
            }

            $shopAdminModel = new ShopAdmin();

            $shop_admin_info = $shopAdminModel
                               ->where("aid", $aid)
                               ->field("sid")
                               ->find();

            $shopadmin['username']       = $username;
            $shopadmin['name']           = $name;
            $shopadmin['phone']          = $phone;
            // $shopadmin['lastlogin_time'] = time();
            $shopadmin['updated_at']     = time();

            if (!empty($password)) {
                $shopadmin['password']   = jmPassword($password);
            }

            Log::info("编辑管理员信息 ----- ".var_export($shopadmin,true));


            // 获取修改之前的数据
            $keys = array_keys($shopadmin);
            $databefore = $this->updateBefore("shop_admin", "aid", $aid, $keys);

            $res = $shopAdminModel
                ->where('aid',$aid)
                ->update($shopadmin);

            if ($res == false){
                return comReturn(false,config("return_message.fail"), '', 500);
            }

            //记录日志
            $logtext = $this->checkDifAfter($databefore,$shopadmin);
            $logtext .= "(AID:".$aid.")";
            $logtext = $this->infoAddClass($logtext, 'text-edit');
            $route = $this->request->routeInfo();
            $route_tran = $this->routeTranslation($route, 'shop_menu');
            $admin = $this->tokenGetAdmin($Token);//获取管理员信息

            $this->addSysLog(time(), $admin['log_name'], $route_tran.$logtext, $request->ip(), $shop_admin_info['sid']);

            return comReturn(true,config("return_message.success"));
        }catch (Exception $e){
            return comReturn(false, $e->getMessage(), '', 500);
        }

    }

    /**
     * 编辑店铺简略信息
     * @param Request $request
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function editSimpleShop(Request $request)
    {
        $Token = Request::instance()->header("Token","");
        $sid                 = $request->param("sid","");
        $shop_name           = $request->param("shop_name","");//店铺名字
        $shop_phone          = $request->param("shop_phone","");//店铺电话
        $shop_operating_time = $request->param("shop_operating_time", "");//营业时间

        try{
            $rule = [
                "sid|店铺id"                   => "require",
                "shop_name|店铺名称"           => "require|unique:shop",
                "shop_phone|店铺电话"          => "require",
                "shop_operating_time|营业时间" => "require",
            ];
            $check_data = [
                "sid"                 => $sid,
                "shop_name"           => $shop_name,
                "shop_phone"          => $shop_phone,
                "shop_operating_time" => $shop_operating_time,
            ];
            $validate = new Validate($rule);
            if (!$validate->check($check_data)){
                return comReturn(false,$validate->getError(), '', 500);
            }

            // 判断是否有重复项
            // $count = $shopModel
            //          ->field("COUNT(1) AS count")
            //          ->where("shop_name", $shop_name)
            //          ->where("sid <> '".$sid."'")
            //          ->find();
            // if ($count['count'] > 1){
            //     return comReturn(false,config('shop.has_shop_name'));
            // }

            $shop['shop_name']           = $shop_name;
            $shop['shop_phone']          = $shop_phone;
            $shop['shop_operating_time'] = $shop_operating_time;
            $shop['shop_desc']           = $request->param("shop_desc","");
            $shop['updated_at']          = time();

            Log::info("编辑店铺简略信息 ----- ".var_export($shop,true));

            $shopModel = new Shop();

            // 获取修改之前的数据
            $keys = array_keys($shop);
            $databefore = $this->updateBefore("shop", "sid", $sid, $keys);

            $res = $shopModel
                ->where('sid',$sid)
                ->update($shop);

            if ($res == false){
                return comReturn(false,config("return_message.fail"), '', 500);
            }

            //记录日志
            $logtext = $this->checkDifAfter($databefore,$shop);
            $logtext .= "(SID:".$sid.")";
            $logtext = $this->infoAddClass($logtext, 'text-edit');
            $route = $this->request->routeInfo();
            $route_tran = $this->routeTranslation($route, 'shop_menu');
            $admin = $this->tokenGetAdmin($Token);//获取管理员信息

            $this->addSysLog(time(), $admin['log_name'], $route_tran.$logtext, $request->ip(), $sid);

            return comReturn(true,config("return_message.success"));
        }catch (Exception $e){
            return comReturn(false, $e->getMessage(), '', 500);
        }

    }

    /**
     * 获取店铺详细信息
     * @param Request $request
     * @return array
     */
    public function getShopDetail(Request $request){
        // $Token = Request::instance()->header("Token","");
        $sid = $request->param("sid","");

        try{
            if (empty($sid)) {
                return comReturn(false, config("return_message.error_status_code")['lack_param_necessary']['value'], '', config("return_message.error_status_code")['lack_param_necessary']['key']);
            }

            $shopModel = new Shop();

            $detail = $shopModel
                    ->field('sid,eid,status,shop_name,shop_operating_time,shop_phone,shop_address,shop_lng,shop_lat,shop_desc')
                    ->where('sid', $sid)
                    ->find();

            if ($detail != null) {

                //获取商品图片
                $imageModel = new ShopImage();
                $image = $imageModel
                         ->field("image")
                         ->where("sid", $sid)
                         ->select();
                $shop_image = '';
                foreach ($image as $k => $v) {
                    $shop_image .= $v['image'];
                    if ($k+1 != count($image)) {
                        $shop_image .= ',';
                    }
                }
                $detail['shop_image'] = $shop_image;

                return comReturn(true,config("return_message.success"),$detail);
            }else{
                return comReturn(false,config('return_message.fail'), '', 500);
            }
        }catch (Exception $e){
            return comReturn(false, $e->getMessage(), '', 500);
        }

    }

    /**
     * 编辑店铺详细信息
     * @param Request $request
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function editShopDetail(Request $request)
    {

        $Token = Request::instance()->header("Token","");

        $sid = $request->param("sid","");

        try{
            if (empty($sid)) {
                return comReturn(false, config("return_message.error_status_code")['lack_param_necessary']['value'], '', config("return_message.error_status_code")['lack_param_necessary']['key']);
            }else{
                $auth = $this->tokenJudgeAuth($Token, $sid);
                if (!$auth) {
                    return comReturn(false,config('user.no_auth'), '', 500);
                }
            }

            $shop_name           = $request->param("shop_name","");//商铺名称
            $shop_operating_time = $request->param("shop_operating_time","");//营业时间
            $shop_phone          = $request->param("shop_phone","");//店铺电话
            $shop_lng            = $request->param("shop_lng","");//经度
            $shop_lat            = $request->param("shop_lat","");//纬度
            $shop_address        = $request->param("shop_address","");//详细地址
            $shop_desc           = $request->param("shop_desc","");//商铺简介

            $rule = [
                "sid"                         => "require",
                "shop_name|商铺名称"           => "require",
                "shop_operating_time|营业时间" => "require",
                "shop_phone|商铺电话"          => "require",
                "shop_address|详细地址"       => "require",
                "shop_desc|商铺简介"          => "require",
            ];
            $check_data = [
                "sid"                 => $sid,
                "shop_name"           => $shop_name,
                "shop_operating_time" => $shop_operating_time,
                "shop_phone"          => $shop_phone,
                "shop_address"        => $shop_address,
                "shop_desc"           => $shop_desc,
            ];
            $validate = new Validate($rule);
            if (!$validate->check($check_data)){
                return comReturn(false,$validate->getError(), '', 500);
            }

            if (empty($shop_lng) || empty($shop_lat)) {
                return comReturn(false,"请在地图上定位店铺位置", '', 500);
            }

            $shopModel = new Shop();
            $shop_before = $shopModel
                            ->where('sid', $sid)
                            ->field('shop_name')
                            ->find();
            if ($shop_before['shop_name'] != $shop_name) {
                // 判断是否有重复项
                $data = $this->checkField('shop', 'shop_name', $shop_name);
                if ($data){
                    return comReturn(false,config('shop.has_shop_name'), '', 500);
                }
            }

            $shop['shop_name']           = $shop_name;
            $shop['shop_operating_time'] = $shop_operating_time;
            $shop['shop_phone']          = $shop_phone;
            $shop['shop_lng']            = $shop_lng;
            $shop['shop_lat']            = $shop_lat;
            $shop['shop_address']        = $shop_address;
            $shop['shop_desc']           = $shop_desc;
            $shop['updated_at']          = time();

            Log::info("修改商铺信息 ----- ".var_export($shop,true));


            // 获取修改之前的数据
            $keys = array_keys($shop);
            $databefore = $this->updateBefore("shop", "sid", $sid, $keys);

            $res = $shopModel
                ->where('sid',$sid)
                ->update($shop);

            if ($res == false){
                return comReturn(false,config("return_message.fail"), '', 500);
            }

            //记录日志
            $logtext = $this->checkDifAfter($databefore,$shop);
            $logtext .= "(SID:".$sid.")";
            $logtext = $this->infoAddClass($logtext, 'text-edit');
            $route = $this->request->routeInfo();
            $route_tran = $this->routeTranslation($route, 'shop_menu');
            $admin = $this->tokenGetAdmin($Token);//获取管理员信息
            $this->addSysLog(time(), $admin['log_name'], $route_tran.$logtext, $request->ip(), $sid);


            //插入图片
            $shop_image = $request->param("shop_image","");//商品多图
            $imageModel = new ShopImage();
            $imageModel->where('sid', $sid)->delete();
            if (!empty($shop_image)) {
                $image_arr = explode(',', $shop_image);
                if (is_array($image_arr)) {
                    $image['sid'] = $sid;
                    foreach ($image_arr as $key => $value) {
                        if (!empty($value)) {
                            if ($key == 0) {
                                $image['title'] = "封面";
                            }else{
                                $image['title'] = "图".$key;
                            }
                            $image['sort'] = $key;
                            $image['image'] = $value;
                            $imageModel->insert($image);
                        }
                    }
                }
            }

            return comReturn(true,config("return_message.success"));
        }catch (Exception $e){
            return comReturn(false, $e->getMessage(), '', 500);
        }
    }

    /**
     * 店铺开闭店
     * @param Request $request
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function setShopStatus(Request $request)
    {
        $Token = Request::instance()->header("Token","");

        $sid   = $request->param("sid","");

        if (empty($sid)) {
            return comReturn(false, config("return_message.error_status_code")['lack_param_necessary']['value'], '', config("return_message.error_status_code")['lack_param_necessary']['key']);
        }else{
            $auth = $this->tokenJudgeAuth($Token, $sid);
            if (!$auth) {
                return comReturn(false,config("return_message.error_status_code")['purview_short']['value'], '', config("return_message.error_status_code")['purview_short']['key']);
            }
        }

        try{

            $status = $request->param("status","");//操作 0开店 1闭店

            $rule = [
                "sid"            => "require",
                "status|操作状态" => "require",
            ];
            $check_data = [
                "sid"    => $sid,
                "status" => $status,
            ];
            $validate = new Validate($rule);
            if (!$validate->check($check_data)){
                return comReturn(false,$validate->getError(), '', 500);
            }

            $shopModel = new Shop();
            //检查信息是否完整
            if ($status == 0) {
                $shop_before = $shopModel
                                ->where('sid', $sid)
                                ->field('shop_name,shop_phone,shop_address,shop_lng,shop_lat,shop_operating_time,shop_desc')
                                ->find();
                if (empty($shop_before['shop_name'])) {
                    return comReturn(false,"商铺名称不能为空,如未保存请保存后重试", '', 500);
                }

                $rule = [
                    "shop_name|商铺名称"           => "require",
                    "shop_phone|商铺电话"          => "require",
                    "shop_address|商铺地址"        => "require",
                    "shop_operating_time|营业时间" => "require",
                    "shop_desc|商铺简介"           => "require",
                ];
                $check_data = [
                    "shop_name"           => $shop_before['shop_name'],
                    "shop_phone"          => $shop_before['shop_phone'],
                    "shop_address"        => $shop_before['shop_address'],
                    "shop_operating_time" => $shop_before['shop_operating_time'],
                    "shop_desc"           => $shop_before['shop_desc'],
                ];
                $validate = new Validate($rule);
                if (!$validate->check($check_data)){
                    return comReturn(false,$validate->getError().",如未保存请保存后重试", '', 500);
                }

                if (empty($shop_before['shop_lng']) || empty($shop_before['shop_lat'])) {
                    return comReturn(false,"请在地图上定位店铺位置,如未保存请保存后重试", '', 500);
                }

                $where['sid']  = $sid;
                $where['type'] = 0;
                $where['sort'] = 0;
                $shopImageModel = new ShopImage();
                $count = $shopImageModel
                         ->where($where)
                         ->count();
                if ($count <= 0) {
                    return comReturn(false,"请上传商铺图片,如未保存请保存后重试", '', 500);
                }
            }

            $shop['status']     = $status;
            $shop['updated_at'] = time();

            Log::info("修改商铺状态 ----- ".var_export($shop,true));

            // 获取修改之前的数据
            $keys = array_keys($shop);
            $databefore = $this->updateBefore("shop", "sid", $sid, $keys);

            $res = $shopModel
                ->where('sid',$sid)
                ->update($shop);

            if ($res == false){
                return comReturn(false,config("return_message.fail"), '', 500);
            }

            //记录日志
            $logtext = $this->checkDifAfter($databefore,$shop);
            $logtext .= "(SID:".$sid.")";
            $logtext = $this->infoAddClass($logtext, 'text-edit');
            $route = $this->request->routeInfo();
            $route_tran = $this->routeTranslation($route, 'shop_menu');
            $admin = $this->tokenGetAdmin($Token);//获取管理员信息
            $this->addSysLog(time(), $admin['log_name'], $route_tran.$logtext, $request->ip(), $sid);

            return comReturn(true,config("return_message.success"));
        }catch (Exception $e){
            return comReturn(false, $e->getMessage(), '', 500);
        }
    }
    /**
     * 普通管理员修改密码
     * @param Request $request
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function editPassword(Request $request)
    {
        $Token = Request::instance()->header("Token","");

        $sid                 = $request->param("sid","");

        if (empty($sid)) {
            return comReturn(false, config("return_message.error_status_code")['lack_param_necessary']['value'], '', config("return_message.error_status_code")['lack_param_necessary']['key']);
        }else{
            $auth = $this->tokenJudgeAuth($Token, $sid);
            if (!$auth) {
                return comReturn(false,config("return_message.error_status_code")['purview_short']['value'], '', config("return_message.error_status_code")['purview_short']['key']);
            }
        }

        try{

            $password_old = $request->param("password_old","");//原密码
            $password_new = $request->param("password_new","");//新密码

            $rule = [
                "password_old|原密码" => "require",
                "password_new|新密码" => "require",
            ];
            $check_data = [
                "password_old" => $password_old,
                "password_new" => $password_new,
            ];
            $validate = new Validate($rule);
            if (!$validate->check($check_data)){
                return comReturn(false,$validate->getError(), '', 500);
            }

            $shopAdminModel = new ShopAdmin();
            $shopAdmin_info = $shopAdminModel
                              ->where("sid", $sid)
                              ->field("password")
                              ->find();
            if (jmPassword($password_old) != $shopAdmin_info['password']) {
                return comReturn(false, "原密码输入有误", '', 500);
            }

            $shopadmin['password']   = jmPassword($password_new);
            $shopadmin['updated_at'] = time();

            Log::info("修改管理员密码 ----- ".var_export($shopadmin,true));

            // 获取修改之前的数据
            $keys = array_keys($shopadmin);
            $databefore = $this->updateBefore("shop_admin", "sid", $sid, $keys);

            $res = $shopAdminModel
                   ->where('sid',$sid)
                   ->update($shopadmin);

            if ($res == false){
                return comReturn(false,config("return_message.fail"), '', 500);
            }

            //记录日志
            $logtext = $this->checkDifAfter($databefore,$shopadmin);
            $logtext .= "(SID:".$sid.")";
            $logtext = $this->infoAddClass($logtext, 'text-edit');
            $route = $this->request->routeInfo();
            $route_tran = $this->routeTranslation($route, 'shop_menu');
            $admin = $this->tokenGetAdmin($Token);//获取管理员信息
            $this->addSysLog(time(), $admin['log_name'], $route_tran.$logtext, $request->ip(), $sid);

            return comReturn(true,config("return_message.success"));
        }catch (Exception $e){
            return comReturn(false, $e->getMessage(), '', 500);
        }
    }



}