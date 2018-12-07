<?php

/**
 * @Author: zhangtao
 * @Date:   2018-11-09 15:15:11
 * @Last Modified by:   admin
 * @Last Modified time: 2018-11-29 19:05:16
 */
namespace app\admin\controller\system;

use app\common\controller\SysAdminAuth;
use app\admin\model\SysSetting;
use think\Controller;
use think\Exception;
use think\Hook;
use think\Db;
use think\Env;
use think\Request;
use think\Validate;

class Setting extends SysAdminAuth
{

    /**
     * 设置类型列表
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function settingTypeList(Request $request)
    {
        try{
            $sysSettingModel = new SysSetting();

            $ktype_arr = $sysSettingModel
                         ->group("ktype")
                         ->field("ktype")
                         ->select();

            $res = json_decode(json_encode($ktype_arr),true);
            $res_arr = [];
            $mn = [];
            foreach ($res as $k => $v){
                foreach ($v as $m => $n){
                    //$mn[] = $n;
                    if ($n == "card"){
                        $mn[$k]["key"] = $n;
                        $mn[$k]["name"] = config("sys_type.card");
                    }

                    if ($n == "reserve"){
                        $mn[$k]["key"] = $n;
                        $mn[$k]["name"] = config("sys_type.reserve");
                    }

                    if ($n == "sms" ){
                        $mn[$k]["key"] = $n;
                        $mn[$k]["name"] = config("sys_type.sms");
                    }

                    if ($n == "sys" ){
                        $mn[$k]["key"] = $n;
                        $mn[$k]["name"] = config("sys_type.sys");
                    }

                    if ($n == "user" ){
                        $mn[$k]["key"] = $n;
                        $mn[$k]["name"] = config("sys_type.user");
                    }

                    if ($n == "cash" ){
                        $mn[$k]["key"] = $n;
                        $mn[$k]["name"] = config("sys_type.cash");
                    }

                    if ($n == "order" ){
                        $mn[$k]["key"] = $n;
                        $mn[$k]["name"] = config("sys_type.order");
                    }
                }
            }
            foreach ($mn as $key => $value) {
                $mn2[] = $value;
            }
            return comReturn(true,config("return_message.get_success"),$mn2);
        }catch (Exception $e){
            return comReturn(false, $e->getMessage(), '', 500);
        }

    }

    /**
     * 根据类型查找相应下的数据
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function settingList(Request $request)
    {
        $ktype = $request->param("ktype","");

        try{
            if (!empty($ktype)){
                $sysSettingModel = new SysSetting();

                $coloum = [
                    "key",
                    "ktype",
                    "key_title",
                    "key_des",
                    "vtype",
                    "select_cont",
                    "value",
                    "default_value",
                    "is_sys"
                ];

                $res = $sysSettingModel
                       ->where('ktype',$ktype)
                       ->field($coloum)
                       ->order('sort asc')
                       ->select();
                return comReturn(true,config("return_message.get_success"),$res);

            }else{
                return comReturn(false,config("return_message.lack_param"));
            }
        }catch (Exception $e){
            return comReturn(false, $e->getMessage(), '', 500);
        }

    }

    /**
     * 类型详情编辑提交
     * @param Request $request
     * @return array
     */
    public function edit(Request $request)
    {
        $key    = $request->param("key","");
        $value  = $request->param("value","");

        try{
            $rule = [
                "key"       => "require",
                "value|内容" => "require"
            ];

            $request_res = [
                "key"   => $key,
                "value" => $value,
            ];

            $validate = new Validate($rule);

            if (!$validate->check($request_res)){
                return comReturn(false,$validate->getError(), '', 500);
            }

            $Token = Request::instance()->header("Token","");
            $admin = $this->tokenGetAdminInfo($Token);//获取管理员信息

            $update_data = [
                "value"         => $value,
                "last_up_time"  => time(),
                "last_up_admin" => $admin['user_name']
            ];

            Db::startTrans();

            $sysSettingModel = new SysSetting();

            // 获取修改之前的数据
            $keys = array_keys($update_data);
            $databefore = $this->updateBefore("sys_setting", "key", $key, $keys);

            $is_ok = $sysSettingModel
                ->where("key",$key)
                ->update($update_data);
            if ($is_ok){
                Db::commit();

                //记录日志
                $logtext = $this->checkDifAfter($databefore,$update_data);
                $logtext .= "(KEY:".$key.")";
                $logtext = $this->infoAddClass($logtext, 'text-edit');
                $route = $this->request->routeInfo();
                $route_tran = $this->routeTranslation($route, 'sys_menu');
                $this->addSysLog(time(), $admin['user_name'], $route_tran.$logtext, $request->ip());

                // //获取当前登录管理员
                // $Token = Request::instance()->header("Token","");
                // $admin = $this->tokenGetAdminInfo($Token);
                // $action_user = $admin['user_name'];
                // //添加至系统操作日志
                // $this->addSysLog(time(),$action_user,"编辑系统设置 -> $key($value)",$request->ip());

                return comReturn(true,config("return_message.success"));
            }else{
                Db::rollback();
                return comReturn(false,config("return_message.fail"));
            }
        }catch (Exception $e){
            return comReturn(false, $e->getMessage(), '', 500);
        }
    }

    /**
     * 新增系统设置
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function add(Request $request)
    {

        $key           = $request->param("key","");
        $ktype         = $request->param("ktype","");
        $sort          = $request->param("sort","");
        $key_title     = $request->param("key_title","");
        $key_des       = $request->param("key_des","");
        $vtype         = $request->param("vtype","");
        $select_cont   = $request->param("select_cont","");
        $value         = $request->param("value","");
        $default_value = $request->param("default_value","");

        try{
            //获取当前登录管理员
            $Token = Request::instance()->header("Token","");
            $admin = $this->tokenGetAdminInfo($Token);
            $action_user = $admin['user_name'];

            $admin_id = $admin['id'];

            $rule = [
                "key"                   => "require|unique_me:sys_setting|max:50",
                "ktype"                 => "require|max:10",
                "sort|排序"             => "require|number|unique_me:sys_setting",
                "key_title|标题"        => "require|unique_me:sys_setting|max:40",
                "key_des|描述"          => "require|max:200",
                "vtype|内容类型"         => "require|max:20",
                "value|内容"            => "require|max:2000",
                "default_value|默认内容" => "require|max:2000"
            ];

            $request_res = [
                "key"           => $key,
                "ktype"         => $ktype,
                "sort"          => $sort,
                "key_title"     => $key_title,
                "key_des"       => $key_des,
                "vtype"         => $vtype,
                "value"         => $value,
                "default_value" => $default_value,
            ];

            $validate = new Validate($rule);

            if (!$validate->check($request_res)){
                return comReturn(false,$validate->getError(),null);
            }

            $sysSettingModel = new SysSetting();

            //要写入的数据
            $insert_data = [
                "key"           => $key,
                "ktype"         => $ktype,
                "sort"          => $sort,
                "key_title"     => $key_title,
                "key_des"       => $key_des,
                "vtype"         => $vtype,
                "select_cont"   => $select_cont,
                "value"         => $value,
                "default_value" => $default_value,
                "last_up_time"  => time(),
                "last_up_admin" => $admin_id
            ];

            Db::startTrans();

            $is_ok = $sysSettingModel
                ->insert($insert_data);
            if ($is_ok){
                Db::commit();

                //添加至系统操作日志
                $this->addSysLog(time(),$action_user,"添加系统设置 -> $key($value)",$request->ip());

                return comReturn(true,config("return_message.success"));
            }else{
                Db::rollback();
                return comReturn(false,config("return_message.fail"));
            }
        }catch (Exception $e){
            return comReturn(false, $e->getMessage(), '', 500);
        }

    }
}
