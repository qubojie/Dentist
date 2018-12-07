<?php

/**
 * @Author: zhangtao
 * @Date:   2018-11-08 15:59:30
 * @Last Modified by:   admin
 * @Last Modified time: 2018-11-29 16:51:23
 */
namespace app\admin\controller\adminRole;

use app\common\controller\SysAdminAuth;
use app\admin\model\SysAdminUser;
use app\admin\model\SysLog;
use app\admin\model\SysMenu;
use app\admin\model\SysRole;
use think\Controller;
use think\Db;
use think\Exception;
use think\Request;
use think\Validate;

class Roles extends SysAdminAuth
//class Roles extends Controller
{
    /**
     * @api {POST} roles/index 角色列表
     * @apiGroup Admin
     * @apiVersion 1.0.0
     *
     * @apiParam {header} Authorization token
     *
     * @apiSampleRequest http://localhost/admin/roles/index
     *
     * @apiErrorExample {json} 错误返回:
     *     {
     *       "result"  : false ,
     *       "message" : "【删除失败】或【权限不足】或【未找到相关管理员信息】" ,
     *       "data"    : null
     *     }
     * @apiSuccessExample {json} 成功返回:
     *     {
     *       "result"   : true ,
     *       "message"  : "获取成功" ,
     *       "data"     : {
     *          "total": 3,
     *          "per_page": "20",
     *          "current_page": 1,
     *          "data": [
     *              {
     *                  "role_id": 1,
     *                  "role_name": "超级管理员",
     *                  "action_list": "all",
     *                  "role_describe": "as",
     *                  "is_sys": 1
     *              },
     *              {
     *                  "role_id": 2,
     *                  "role_name": "测试管理员1",
     *                  "action_list": "999000000,999100000,999100100,999100200,999100300,999200000,999200100",
     *                  "role_describe": "描述测试",
     *                  "is_sys": 0
     *              }
     *          ]
     *
     *       }
     *     }
     */
    public function index(Request $request)
    {

        $pagesize   = $request->param("pagesize",config('PAGESIZE'));//显示个数,不传时为10
        if (empty($pagesize)) $pagesize = config('PAGESIZE');

        try{
            $nowPage    = $request->param("nowPage","1");

            $orderBy['filter']['orderBy'] = $request->has('orderBy') ? $request->param('orderBy') : 'role_id';
            $orderBy['filter']['sort'] = $request->has('sort') ? $request->param('sort') : 'asc';

            $roleModel = new SysRole();

            $config = [
                "page" => $nowPage
            ];

            //处理排序条件
            if ($orderBy['filter']['orderBy'] == 'role_name')
            {
                $orderBy['filter']['orderBy'] = fieldOrderByEncode('role_name');
            }

            $roles = $roleModel
                     ->order($orderBy['filter']['orderBy'], $orderBy['filter']['sort'])
                     ->paginate($pagesize,false,$config);
            $result = $roles;

            return comReturn(true,"获取成功",$result);
        }catch (Exception $e){
            return comReturn(false,$e->getMessage(), '', 500);
        }

        // if ($nowPage <= $pageNum){
        // }else{
        //     return comReturn(true,"暂无更多",$result);
        // }

    }

    /**
     * @api {POST} roles/add 角色添加
     * @apiGroup Admin
     * @apiVersion 1.0.0
     *
     * @apiParam {header} Authorization token
     * @apiParam {String} role_name 角色名
     * @apiParam {String} role_describe 角色描述
     * @apiParam {String} action (可选参数)所选权限id,以逗号作为分隔符拼接
     *
     * @apiSampleRequest http://localhost/admin/roles/add
     *
     * @apiErrorExample {json} 错误返回:
     *     {
     *       "result"  : false ,
     *       "message" : "【角色添加失败】或【角色名已存在】或【角色描述不能为空】" ,
     *       "data"    : null
     *     }
     * @apiSuccessExample {json} 成功返回:
     *     {
     *       "result"   : true ,
     *       "message"  : "角色添加成功" ,
     *       "data"     : null
     *     }
     */
    public function add(Request $request)
    {

        $role_name       = $request->param("role_name","");
        $role_describe   = $request->param("role_describe","");
        $action_list     = $request->param("action_list","");

        try{
            $rule = [
                "role_name|角色名"       => "require|max:60|unique:sys_role",
                "role_describe|角色描述" => "require"
            ];

            $request_res = [
                "role_name"     => $role_name,
                "role_describe" => $role_describe
            ];

            $validate = new Validate($rule);

            if (!$validate->check($request_res)){
                return comReturn(false,$validate->getError(), '', 500);
            }

            //写入数据,返回id
            $insert_data = [
                "role_name"     => $role_name,
                "role_describe" => $role_describe,
                "action_list"   => $action_list
            ];
            Db::startTrans();
            $sysRole = new SysRole();
            $role_id = $sysRole->insertGetId($insert_data);
            if (!empty($role_id)){
                Db::commit();

                //获取当前登录管理员
                $Token = Request::instance()->header("Token","");

                //记录日志
                $logtext = "(ROLE_ID:".$role_id.")";
                $logtext = $this->infoAddClass($logtext, 'text-add');
                $route = $this->request->routeInfo();
                $route_tran = $this->routeTranslation($route, 'sys_menu');
                $admin = $this->tokenGetAdminInfo($Token);//获取管理员信息
                $this->addSysLog(time(), $admin['user_name'], $route_tran.$logtext, $request->ip());

                return comReturn(true,"角色添加成功");

            }else{
                Db::rollback();
                return comReturn(false,"角色添加失败", '', 500);
            }
        }catch (Exception $e){
            return comReturn(false,$e->getMessage(), '', 500);
        }
    }

    /**
     * 角色编辑
     * @param Request $request
     * @return array
     */
    public function edit(Request $request)
    {

        $role_id        = $request->param("role_id","");
        $role_name      = $request->param("role_name","");
        $role_describe  = $request->param("role_describe","");
        $action_list    = $request->param("action_list","");

        try{
            $rule = [
                "role_id|角色id"         => "require",
                "role_name|角色名"       => "require|max:60|unique:sys_role",
                "role_describe|角色描述" => "require"
            ];

            $request_res = [
                "role_id"       => $role_id,
                "role_name"     => $role_name,
                "role_describe" => $role_describe
            ];

            $validate = new Validate($rule);

            if (!$validate->check($request_res)){
                return comReturn(false,$validate->getError(), '', 500);
            }

            //更新数据,返回
            $update_data = [
                "role_name"     => $role_name,
                "role_describe" => $role_describe,
                "action_list"   => $action_list
            ];

            //查看老数据
            $sysRoleModel = new SysRole();
            $old_info     = $sysRoleModel->where("role_id",$role_id)
                            ->field("role_name,role_describe,action_list")
                            ->find()
                            ->toArray();

            // 获取修改之前的数据
            $keys = array_keys($update_data);
            $databefore = $this->updateBefore("sys_role", "role_id", $role_id, $keys);

            Db::startTrans();
            $is_ok = $sysRoleModel
                ->where("role_id",$role_id)
                ->update($update_data);
            if ($is_ok !== false){
                Db::commit();

                $Token = Request::instance()->header("Token","");
                //记录日志
                $logtext = $this->checkDifAfter($databefore,$update_data);
                $logtext .= "(ROLE_ID:".$role_id.")";
                $logtext = $this->infoAddClass($logtext, 'text-edit');
                $route = $this->request->routeInfo();
                $route_tran = $this->routeTranslation($route, 'sys_menu');
                $admin = $this->tokenGetAdminInfo($Token);//获取管理员信息
                $this->addSysLog(time(), $admin['user_name'], $route_tran.$logtext, $request->ip());

                // //获取当前登录管理员
                // $admin = $this->tokenGetAdminInfo($Token);
                // $action_user = $admin['user_name'];
                // //编辑至系统操作日志
                // $this->addSysLog(time(),$action_user,"编辑角色 -> $role_name",$request->ip());

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
     * 角色删除
     * @param Request $request
     * @return array
     */
    public function delete(Request $request)
    {

        $role_id  =$request->param("role_id","");

        try{
            if (!empty($role_id)){
                $sysAdminUserModel = new SysAdminUser();
                //查看当前角色是否有管理员
                $role_id_exist = $sysAdminUserModel
                    ->where('role_id',$role_id)
                    ->count();
                if (!$role_id_exist){
                    //可以删除
                    $sysRoleMode = new SysRole();
                    Db::startTrans();
                    try{
                        $is_delete = $sysRoleMode
                            ->where("role_id",$role_id)
                            ->delete();
                        if ($is_delete){
                            Db::commit();

                            $Token = Request::instance()->header("Token","");
                            //记录日志
                            $logtext = "(ROLE_ID:".$role_id.")";
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
                    return comReturn(false,'当前角色下存在管理员,不可删除', '', 500);
                }
            }else{
                return comReturn(false,'角色id不能为空', '', 500);
            }
        }catch (Exception $e){
            return comReturn(false,$e->getMessage(), '', 500);
        }
    }
}