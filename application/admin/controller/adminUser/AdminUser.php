<?php

/**
 * @Author: zhangtao
 * @Date:   2018-11-08 15:20:53
 * @Last Modified by:   admin
 * @Last Modified time: 2018-12-03 11:03:53
 */
namespace app\admin\controller\adminUser;

use app\common\controller\SysAdminAuth;
use app\admin\model\SysAdminUser;
use app\common\controller\SendSms;
use app\admin\model\SysRole;
use think\Controller;
use think\Db;
use think\Exception;
use think\Request;
use think\Validate;

class AdminUser extends SysAdminAuth
{
    /**
     * 管理员列表
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index(Request $request)
    {

        $result = array();

        try{
            $adminModel = new SysAdminUser();

            $orderBy['filter']['orderBy'] = $request->has('orderBy') ? $request->param('orderBy') : 'sau.created_at';
            $orderBy['filter']['sort'] = $request->has('sort') ? $request->param('sort') : 'ASC';

            $pagesize   = $request->param("pagesize",config('PAGESIZE'));//显示个数,不传时为10
            if (empty($pagesize)) $pagesize = config('PAGESIZE');

            $nowPage    = $request->param("nowPage","1");

            $user_name = $request->param("user_name", "");//用户名
            $role_id   = $request->param("role_id","");// 角色id

            $where = [];

            if (!empty($user_name)){
                $where["sau.user_name"] = ["like","%$user_name%"];
            }
            if (!empty($role_id)){
                $where["sau.role_id"] = $role_id;
            }

            $config = [
                "page" => $nowPage
            ];

            //为排序条件加上别名
            if ($orderBy['filter']['orderBy'] == 'user_sn')
            {
                $orderBy['filter']['orderBy'] = 'sau.user_sn';
            }
            else if($orderBy['filter']['orderBy'] == 'user_name')
            {
                $orderBy['filter']['orderBy'] = 'sau.user_name';
            }
            else if ($orderBy['filter']['orderBy'] == 'phone')
            {
                $orderBy['filter']['orderBy'] = 'sau.phone';
            }
            else if($orderBy['filter']['orderBy'] == 'created_at')
            {
                $orderBy['filter']['orderBy'] = 'sau.created_at';
            }
            else if($orderBy['filter']['orderBy'] == 'updated_at')
            {
                $orderBy['filter']['orderBy'] = 'sau.updated_at';
            }
            else if($orderBy['filter']['orderBy'] == 'role_name')
            {
                $orderBy['filter']['orderBy'] = 'sr.role_name';
            }

            //处理排序条件
            $field_array = array("sr.role_name");
            if (in_array($orderBy['filter']['orderBy'], $field_array)) {
                $orderBy['filter']['orderBy'] = fieldOrderByEncode($orderBy['filter']['orderBy']);
            }


            $result = $adminModel
                      ->alias("sau")
                      ->join("sys_role sr","sr.role_id = sau.role_id", "LEFT")
                      ->where('is_delete','0')
                      ->where($where)
                      ->order($orderBy['filter']['orderBy'],$orderBy['filter']['sort'])
                      // ->order("updated_at desc")
                      ->field("sau.id,sau.user_sn,sau.user_name,sau.avatar,sau.phone,sau.email,sau.last_ip,sau.action_list,sau.lang_type,sau.is_delete,sau.is_sys,sau.created_at,sau.updated_at")
                      ->field("sr.role_id,sr.role_name,sr.role_describe")
                      ->paginate($pagesize,false,$config);

            if ($result){
                return comReturn(true,config("return_message.success"),$result);

            }else{
                return comReturn(false,config("return_message.fail"), '', 500);
            }
        }catch (Exception $e){
            return comReturn(false,$e->getMessage(), '', 500);
        }
    }


    /**
     * 添加管理员
     * @return array
     */
    public function add(Request $request)
    {

        $user_name              = $request->param("user_name","");            //用户名
        $phone                  = $request->param("phone","");                //电话号码
        $email                  = $request->param("email","");                //邮箱
        $password               = $request->param("password","");             //密码
        $password_confirmation  = $request->param("password_confirmation",""); //确认密码
        $role_id                = $request->param("role_id","");              //角色id
        $user_sn                = $request->param("user_sn","");              //工号
        $avatar                 = $request->param("avatar","");              //头像


        try{
            $rule = [
                "user_name|账号"                 => "require|unique:sys_admin_user",
                "password|密码"                  => "require",
                "password_confirmation|确认密码" => "require",
                "role_id|角色分配"               => "require",
                "user_sn|工号"                   => "unique:sys_admin_user",
                "phone|电话"                     => "regex:1[3-8]{1}[0-9]{9}|unique:sys_admin_user",
                "email|邮箱"                     => "email|unique:sys_admin_user",
                "avatar|头像"                    => "require",
            ];

            $request_res = [
                "user_name"              => $user_name,
                "phone"                  => $phone,
                "email"                  => $email,
                "password"               => $password,
                "password_confirmation"  => $password_confirmation,
                "role_id"                => $role_id,
                "user_sn"                => $user_sn,
                "avatar"                 => $avatar,
            ];

            $validate = new Validate($rule);

            if (!$validate->check($request_res)){
                return comReturn(false,$validate->getError(),null);
            }

            if ($password !== $password_confirmation){
                return comReturn(false,config("return_message.password_dif"),null);
            }

            $sysRoleModel = new SysRole();
            $action_list_res =  $sysRoleModel
                                ->where('role_id',$role_id)
                                ->field('action_list')
                                ->find();
            $action_list_res = json_decode($action_list_res,true);
            $action_list = $action_list_res['action_list'];


            $adminUserModel = new SysAdminUser();

            Db::startTrans();
            $time = time();

            $insert_data = [
                "user_name"              => $user_name,
                "phone"                  => $phone,
                "email"                  => $email,
                "password"               => jmPassword($password),
                "role_id"                => $role_id,
                "user_sn"                => $user_sn,
                "action_list"            => $action_list,
                "nav_list"               => "all",
                "lang_type"              => "E",
                "avatar"                 => $avatar,
            ];

            $id = $adminUserModel
                  ->insertGetId_ex($insert_data, true, false, 0);

            if ($id){
                Db::commit();
                $this_info = $adminUserModel
                             ->alias('sau')
                             ->join('sys_role sr','sr.role_id = sau.role_id')
                             ->where("id",$id)
                             ->field("sau.user_sn,sau.user_name,sau.role_id,sau.phone,sau.updated_at,sau.last_ip,sau.is_sys,sau.email,sau.created_at,sau.avatar")
                             ->field('sr.role_name,sr.role_describe')
                             ->find();

                $Token = Request::instance()->header("Token","");
                //记录日志
                $logtext = "(ID:".$id.")";
                $logtext = $this->infoAddClass($logtext, 'text-add');
                $route = $this->request->routeInfo();
                $route_tran = $this->routeTranslation($route, 'sys_menu');
                $admin = $this->tokenGetAdminInfo($Token);//获取管理员信息
                $this->addSysLog(time(), $admin['user_name'], $route_tran.$logtext, $request->ip());

                return comReturn(true,config("return_message.success"),$this_info);
            }else{
                Db::rollback();
                return comReturn(false,config("return_message.fail"), '', 500);
            }
        }catch (Exception $e){
            return comReturn(false,$e->getMessage(), '', 500);
        }
    }
    /**
     * 编辑管理员
     * @return array
     */
    public function edit(Request $request)
    {
        $id                     = $this->request->param("id","");
        $user_name              = $this->request->param("user_name","");
        $role_id                = $this->request->param("role_id","");
        $user_sn                = $this->request->param("user_sn","");
        $email                  = $this->request->param("email","");
        $password               = $this->request->param("password","");             //密码
        $password_confirmation  = $this->request->param("password_confirmation",""); //确认密码
        $phone                  = $this->request->param("phone","");
        $avatar                 = $this->request->param("avatar","");

        try{
            $rule = [
                "id"              => "require",
                "user_name|用户名" => "require|unique:sys_admin_user",
                "role_id|角色分配" => "require",
                "user_sn|工号"     => "unique:sys_admin_user",
                "email|邮箱"       => "email|unique:sys_admin_user",
                "phone|电话号码"   => "regex:1[3-8]{1}[0-9]{9}|unique:sys_admin_user",
                "avatar|头像"      => "require",
            ];

            $request_res = [
                "id"                    => $id,
                "user_name"             => $user_name,
                "role_id"               => $role_id,
                "user_sn"               => $user_sn,
                "email"                 => $email,
                "phone"                 => $phone,
                "avatar"                 => $avatar,
            ];

            $validate = new Validate($rule);

            if (!$validate->check($request_res)){
                return comReturn(false,$validate->getError(), '', 500);
            }

            $update_data = [
                "user_name"  => $user_name,
                "role_id"    => $role_id,
                "user_sn"    => $user_sn,
                "email"      => $email,
                "phone"      => $phone,
                "avatar"     => $avatar,
                "updated_at" => time()
            ];
            if (!empty($password)) {
                if ($password !== $password_confirmation){
                    return comReturn(false,config("return_message.password_dif"), '', 500);
                }else{
                    $update_data['password'] = jmPassword($password);
                }
            }

            $sysModel = new SysAdminUser();

            // 获取修改之前的数据
            $keys = array_keys($update_data);
            $databefore = $this->updateBefore("sys_admin_user", "id", $id, $keys);

            Db::startTrans();
            $res = $sysModel
                    ->where("id",$id)
                    ->update($update_data);

            if ($res !== false){
                Db::commit();

                $Token = Request::instance()->header("Token","");
                //记录日志
                $logtext = $this->checkDifAfter($databefore,$update_data);
                $logtext .= "(ID:".$id.")";
                $logtext = $this->infoAddClass($logtext, 'text-edit');
                $route = $this->request->routeInfo();
                $route_tran = $this->routeTranslation($route, 'sys_menu');
                $admin = $this->tokenGetAdminInfo($Token);//获取管理员信息
                $this->addSysLog(time(), $admin['user_name'], $route_tran.$logtext, $request->ip());

                return comReturn(true,config("return_message.success"));
            }else{
                Db::rollback();
                return comReturn(false,config("return_message.fail"), '', 500);
            }

        }catch (Exception $e){
            return comReturn(false,$e->getMessage(), '', 500);
        }
    }

    /**
     * 管理员删除
     * @return array
     */
    public function delete(Request $request)
    {
        $ids = $this->request->param("id","");

        try{
            if (!empty($ids)){
                $id_array = explode(",",$ids);

                //查看当前登录管理员是否有删除权限
                $Token = Request::instance()->header("Token","");

                $admin_info = Db::name("sys_admin_user")
                              ->where("remember_token",$Token)
                              ->field("is_sys")
                              ->find();
                if (!empty($admin_info)){
                    $is_sys = $admin_info["is_sys"];
                    if ($is_sys){
                        $sysAdminUserModel = new SysAdminUser();

                        $update_data = [
                            "is_delete" => "1"
                        ];
                        Db::startTrans();
                        try{
                            $is_ok = false;

                            foreach ($id_array as $id_l){
                                $is_ok = $sysAdminUserModel->where("id",$id_l)->update($update_data);
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

                                return comReturn(true,config("return_message.success"));
                            }else{
                                return comReturn(false,config("return_message.fail"), '', 500);
                            }

                        }catch (Exception $e){
                            Db::rollback();
                            return comReturn(false,$e->getMessage(), '', 500);
                        }
                    }else{
                        return comReturn(false,config("return_message.purview_short"), '', 500);
                    }
                }else{
                    return comReturn(false,config("return_message.fail"), '', 500);
                }
            }else{
                return comReturn(false,config("return_message.param_not_empty"), '', 500);
            }
        }catch (Exception $e){
            return comReturn(false,$e->getMessage(), '', 500);
        }
    }
}