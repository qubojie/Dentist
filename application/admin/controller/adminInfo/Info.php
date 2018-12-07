<?php

/**
 * 管理后台
 * @Author: zhangtao
 * @Date:   2018-10-18 12:53:29
 * @Last Modified by:   admin
 * @Last Modified time: 2018-11-27 10:04:34
 */
namespace app\admin\controller\adminInfo;

use app\common\controller\ShopAdminAuth;
use app\services\controller\ImageUpload;
use app\admin\model\ShopAdmin;
use app\admin\model\Shop;
use think\Env;
use think\Log;
use think\Request;
use think\Validate;
use think\Db;
use think\Exception;

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

            if ($list != null) {

                $res = [
                    'result'  => true,
                    'message' => config('return_message.success'),
                    'data'    => $list,
                    'eid'     => $list[0]['eid']
                ];
                $res = json($res);

                return $res;
            }else{
                return comReturn(false,config('return_message.fail'), '', 500);
            }
        }catch (Exception $e){
            return comReturn(false,$e->getMessage(), '', 500);
        }
    }

	/**
	 * 添加店铺及管理员信息
     * @param Request $request
     * @return array
	 */
	public function addShop(Request $request)
	{

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

            $shop['eid'] = $eid;
            $shop['sid'] = $sid;
            $shop['shop_name'] = $shop_name;
            $shop['shop_operating_time'] = $shop_operating_time;
            $shop['shop_phone'] = $shop_phone;

            $shop_admin['eid'] = $eid;
            $shop_admin['sid'] = $sid;
            $shop_admin['username'] = $username;
            $shop_admin['name'] = $name;
            $shop_admin['phone']    = $phone;
            $shop_admin['password'] = jmPassword($password);

            Log::info("店铺参数  ---- ".var_export($shop,true));
            Log::info("店长参数  ---- ".var_export($shop_admin,true));

            //规则验证
            $rule = [
                "eid"                         => "require",//企业id
                "shop_name|店铺名称"           => "require",
                "shop_operating_time|营业时间" => "require",
                "shop_phone|店铺电话"          => "require|regex:1[3-8]{1}[0-9]{9}",
                "username|管理员用户名"        => "require",
                "name|真实姓名"               => "require",
                "phone|电话"                  => "require|regex:1[3-8]{1}[0-9]{9}",
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
            $data = $this->checkShop('shop_name', $shop_name);
            if ($data){
                return comReturn(false,config('shop.has_shop_name'), '', 500);
            }
            //管理员-判断是否重复
            $data = $this->checkShopAdmin('username', $username);
            if ($data){
                return comReturn(false,config('shop_admin.has_username'), '', 500);
            }

            $shop_admin['type']           = 1;
            $shop_admin['status']         = config("shop_admin.status")['able']['key'];
            $shop_admin['lastlogin_time'] = time();

            Db::startTrans();

            $shopModel = new Shop();
            $shopAdminModel = new ShopAdmin();
            $is_ok = $shopModel->insert_ex($shop,true);
            if ($is_ok !== false){
                $is_ok_2 = $shopAdminModel->insert_ex($shop_admin, true);

                if ($is_ok_2 !== false) {
                    Db::commit();
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
            return comReturn(false,$e->getMessage(), '', 500);
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
    public function editShopAdmin(Request $request)
    {
        $aid            = $request->param("aid","");
        $username       = $request->param("username","");//管理员用户名
        $password       = $request->param("password","");//管理员密码
        $name           = $request->param("name","");//真实姓名
        $phone          = $request->param("phone","");//手机号

        try{
            $rule = [
                "aid"                    => "require",
                "username|管理员姓名"     => "require",
                "password|管理员密码"     => "require",
                "name|真实姓名"           => "require",
                "phone|手机号"            => "require",
            ];
            $check_data = [
                "aid"          => $aid,
                "username"     => $username,
                "password"     => $password,
                "name"         => $name,
                "phone"        => $phone,
            ];
            $validate = new Validate($rule);
            if (!$validate->check($check_data)){
                return comReturn(false,$validate->getError(), '' ,500);
            }

            // 判断是否有重复项
            $data = $this->checkShopAdmin('username', $username);
            if ($data){
                return comReturn(false,config('shop_admin.has_username'), '', 500);
            }

            $shopadmin['username'] = $username;
            $shopadmin['password'] = $password;
            $shopadmin['name']     = $name;
            $shopadmin['phone']    = $phone;
            $shopadmin['lastlogin_time'] = time();
            $shopadmin['updated_at'] = time();
            $shopadmin['password']   = jmPassword($password);

            Log::info("编辑管理员信息 ----- ".var_export($shopadmin,true));

            $shopAdminModel = new ShopAdmin();

            //获取修改之前的数据
            $databefore = updateBefore("shop_admin", "aid", $aid);

            $res = $shopAdminModel
                ->where('aid',$aid)
                ->update($shopadmin);

            if ($res == false){
                return comReturn(false,config("return_message.fail"), '', 500);
            }

            return comReturn(true,config("return_message.success"));
        }catch (Exception $e){
            return comReturn(false,$e->getMessage(), '', 500);
        }
    }
}