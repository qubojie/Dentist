<?php

/**
 * 医护人员
 * @Author: zhangtao
 * @Date:   2018-10-23 11:26:35
 * @Last Modified by:   admin
 * @Last Modified time: 2018-12-03 15:43:04
 */

namespace app\shopadmin\controller;

use app\common\controller\ShopAdminAuth;
use app\services\controller\ImageUpload;
use app\shopadmin\model\ShopAdmin;
use app\shopadmin\model\Shop;
use app\shopadmin\model\ShopDoctor;
use think\Env;
use think\Log;
use think\Request;
use think\Validate;
use think\Db;

class Doctor extends ShopAdminAuth
{

    /**
     * 医护人员信息
     * @param Request $request
     * @return array
     */
    public function index(Request $request){

        $sid        = $request->param("sid","");
        if (empty($sid)) {
            return comReturn(false, config("return_message.error_status_code")['lack_param_necessary']['value'], '', config("return_message.error_status_code")['lack_param_necessary']['key']);
        }

        $pagesize   = $request->param("pagesize",config('PAGESIZE'));//显示个数,不传时为10

        if (empty($pagesize)) $pagesize = config('PAGESIZE');

        $nowPage    = $request->param("nowPage","1");

        $keyword    = $request->param("keyword","");


        $orderBy['filter']['orderBy'] = $request->has('orderBy') ? $request->param('orderBy') : 'created_at';
        $orderBy['filter']['sort'] = $request->has('sort') ? $request->param('sort') : 'desc';

        // var_dump($order);

        $config = [
            "page" => $nowPage,
        ];

        $where = [];
        if (!empty($keyword)){
            $where["doc_id|doctor_name"] = ["like","%$keyword%"];
        }

        try{
            $doctorModel = new ShopDoctor();

            //处理排序条件
            $field_array = array("doctor_name", "doctor_duty", "doctor_title");
            if (in_array($orderBy['filter']['orderBy'], $field_array)) {
                $orderBy['filter']['orderBy'] = fieldOrderByEncode($orderBy['filter']['orderBy']);
            }

            $doctor_list = $doctorModel
                            ->where($where)
                            ->where('is_delete', 0)
                            ->where('sid', $sid)
                            // ->order('updated_at desc')
                            ->order($orderBy['filter']['orderBy'], $orderBy['filter']['sort'])
                            ->field('doc_id,sid,eid,doctor_name,doctor_title,doctor_duty,doctor_img,doctor_desc,sort,is_enable,created_at,updated_at')
                            ->paginate($pagesize,false,$config);

            if ($doctor_list) {
                return comReturn(true,config("return_message.success"),$doctor_list);
            }else{
                return comReturn(false,config("return_message.fail"), '', 500);
            }
        }catch (Exception $e){
            return comReturn(false, $e->getMessage(), '', 500);
        }


    }


    /**
     * 添加医护人员信息
     * @param Request $request
     * @return array
     */
    public function addDoctor(Request $request)
    {

        $Token = Request::instance()->header("Token","");
        $sid = $request->param("sid","");//商铺id
        if (empty($sid)) {
            return comReturn(false, config("return_message.error_status_code")['lack_param_necessary']['value'], '', config("return_message.error_status_code")['lack_param_necessary']['key']);
        }else{
            $auth = $this->tokenJudgeAuth($Token, $sid);
            if (!$auth) {
                return comReturn(false,config('user.no_auth'), '', 500);
            }
        }

        $doctor_name  = $request->param("doctor_name","");//名称
        $doctor_title = $request->param("doctor_title","");//医护职称
        $doctor_duty  = $request->param("doctor_duty","");//医护职责
        $doctor_desc  = $request->param("doctor_desc","");//医生简介
        $doctor_img   = $request->param("doctor_img","");//医生头像
        $is_enable    = $request->param("is_enable","");//是否启用  0否 1是
        $sort         = $request->param("sort","");//排序

        $doctor['sid']          = $sid;
        $doctor['doctor_name']  = $doctor_name;
        $doctor['doctor_title'] = $doctor_title;
        $doctor['doctor_duty']  = $doctor_duty;
        $doctor['doctor_desc']  = $doctor_desc;
        $doctor['doctor_img']   = $doctor_img;
        $doctor['sort']         = $sort;

        Log::info("医护参数  ---- ".var_export($doctor,true));

        try{
            //规则验证
            $rule = [
                "sid"                  => "require",//商铺id
                "doctor_name|医护名称"  => "require",
                "doctor_title|医护职称" => "require",
                "doctor_duty|医护职责"  => "require",
                "doctor_img|医护头像"   => "require",
            ];
            $check_data = [
                "sid"          => $sid,
                "doctor_name"  => $doctor_name,
                "doctor_title" => $doctor_title,
                "doctor_duty"  => $doctor_duty,
                "doctor_img"   => $doctor_img,
            ];

            $validate = new Validate($rule);
            if (!$validate->check($check_data)){
                return comReturn(false,$validate->getError(), '', 500);
            }

            $admin = $this->tokenGetAdmin($Token);

            $doctor['eid'] = $admin['eid'];

            $shopDoctorModel = new ShopDoctor();

            Db::startTrans();

            $doc_id = $shopDoctorModel->insertGetId_ex($doctor, true, $is_enable, 0);
            if ($doc_id > 0) {
                Db::commit();

                //记录日志
                $logtext = "(DOC_ID:".$doc_id.")";
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
        }catch (Exception $e){
            return comReturn(false, $e->getMessage(), '', 500);
        }
    }

    /**
     * 获取医护人员详细信息
     * @param Request $request
     * @return array
     */
    public function getDoctorDetail(Request $request){

        $doc_id = $request->param("doc_id","");

        try{
            //规则验证
            $rule = [
                "doc_id|医护id" => "require",
            ];
            $check_data = [
                "doc_id" => $doc_id,
            ];

            $validate = new Validate($rule);
            if (!$validate->check($check_data)){
                return comReturn(false,$validate->getError(), '', 500);
            }

            $shopDoctorModel = new ShopDoctor();

            $detail = $shopDoctorModel
                    ->field('doc_id,sid,eid,doctor_name,doctor_title,doctor_duty,doctor_img,doctor_desc,sort,is_enable')
                    ->where('doc_id', $doc_id)
                    ->where('is_delete', 0)
                    ->find();


            if ($detail != null) {

                //判断权限
                $Token = Request::instance()->header("Token","");
                $auth = $this->tokenJudgeAuth($Token, $detail['sid']);
                if (!$auth) {
                    return comReturn(false,config('user.no_auth'), '', 500);
                }

                return comReturn(true,config("return_message.success"),$detail);
            }else{
                return comReturn(false,config('return_message.fail'), '', 500);
            }
        }catch (Exception $e){
            return comReturn(false, $e->getMessage(), '', 500);
        }

    }

    /**
     * 编辑医护人员信息
     * @param Request $request
     * @return array
     */
    public function editDoctor(Request $request)
    {
        $Token = Request::instance()->header("Token","");
        $doc_id = $request->param("doc_id","");//商铺id

        try{
            $shopDoctorModel = new ShopDoctor();

            if (empty($doc_id)) {
                return comReturn(false,config('return_meaasge.fail'), '', 500);
            }else{

                $doctor_data = $shopDoctorModel
                        ->field('sid')
                        ->where('doc_id', $doc_id)
                        ->where('is_delete', 0)
                        ->find();
                $auth = $this->tokenJudgeAuth($Token, $doctor_data['sid']);
                if (!$auth) {
                    return comReturn(false,config('user.no_auth'), '', 500);
                }
            }

            $doctor_name  = $request->param("doctor_name","");//名称
            $doctor_title = $request->param("doctor_title","");//医护职称
            $doctor_duty  = $request->param("doctor_duty","");//医护职责
            $doctor_desc  = $request->param("doctor_desc","");//医生简介
            $doctor_img   = $request->param("doctor_img","");//医生头像
            $sort         = $request->param("sort","");//排序
            $is_enable    = $request->param("is_enable","");//是否启用  0否 1是


            $doctor['doctor_name']  = $doctor_name;
            $doctor['doctor_title'] = $doctor_title;
            $doctor['doctor_duty']  = $doctor_duty;
            $doctor['doctor_desc']  = $doctor_desc;
            $doctor['doctor_img']   = $doctor_img;
            $doctor['sort']         = $sort;
            $doctor['is_enable']    = $is_enable;

            Log::info("医护参数  ---- ".var_export($doctor,true));

            //规则验证
            $rule = [
                "doctor_name|医护名称"         => "require",
                "doctor_title|医护职称"        => "require",
                "doctor_duty|医护职责"         => "require",
                "doctor_img|医护头像"          => "require",
            ];
            $check_data = [
                "doctor_name"         => $doctor_name,
                "doctor_title"        => $doctor_title,
                "doctor_duty"         => $doctor_duty,
                "doctor_img"          => $doctor_img,
            ];

            $validate = new Validate($rule);
            if (!$validate->check($check_data)){
                return comReturn(false,$validate->getError(), '', 500);
            }

            $doctor['updated_at'] = time();

            // 获取修改之前的数据
            $keys = array_keys($doctor);
            $databefore = $this->updateBefore("shop_doctor", "doc_id", $doc_id, $keys);

            Db::startTrans();

            $is_ok = $shopDoctorModel
                    ->where('doc_id',$doc_id)
                    ->update($doctor);
            if ($is_ok) {
                Db::commit();

                //记录日志
                $logtext = $this->checkDifAfter($databefore,$doctor);
                $logtext .= "(DOC_ID:".$doc_id.")";
                $logtext = $this->infoAddClass($logtext, 'text-edit');
                $route = $this->request->routeInfo();
                $route_tran = $this->routeTranslation($route, 'shop_menu');
                $admin = $this->tokenGetAdmin($Token);//获取管理员信息
                $this->addSysLog(time(), $admin['log_name'], $route_tran.$logtext, $request->ip(), $doctor_data['sid']);

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
     * 删除医护
     * @param Request $request
     * @return array
     */
    public function delDoctor(Request $request)
    {

        $Token  = Request::instance()->header("Token","");
        $doc_id = $request->param("doc_id","");//商铺id

        try{
            $shopDoctorModel = new ShopDoctor();

            if (empty($doc_id)) {
                return comReturn(false,config('return_meaasge.fail'), '', 500);
            }else{

                $doctor_data = $shopDoctorModel
                        ->field('sid')
                        ->where('doc_id', $doc_id)
                        ->where('is_delete', 0)
                        ->find();
                $auth = $this->tokenJudgeAuth($Token, $doctor_data['sid']);
                if (!$auth) {
                    return comReturn(false,config('user.no_auth'), '', 500);
                }
            }

            $res = $shopDoctorModel
                    ->where('doc_id', $doc_id)
                    ->update(['is_delete' => 1]);

            if ($res) {

                //记录日志
                $logtext = "(DOC_ID:".$doc_id.")";
                $logtext = $this->infoAddClass($logtext, 'text-del');
                $route = $this->request->routeInfo();
                $route_tran = $this->routeTranslation($route, 'shop_menu');
                $admin = $this->tokenGetAdmin($Token);//获取管理员信息
                $this->addSysLog(time(), $admin['log_name'], $route_tran.$logtext, $request->ip(), $doctor_data['sid']);

                return comReturn(true,config('return_message.success'));
            }else{
                return comReturn(false,config('return_meaasge.fail'), '', 500);
            }
        }catch (Exception $e){
            return comReturn(false, $e->getMessage(), '', 500);
        }

    }

    /**
     * 获取商铺下的医护
     * @param Request $request
     * @return array
     */
    public function getDoctorBySid(Request $request){

        $sid = $request->param("sid","");

        try{
            //规则验证
            $rule = [
                "sid|医护id" => "require",
            ];
            $check_data = [
                "sid" => $sid,
            ];

            $validate = new Validate($rule);
            if (!$validate->check($check_data)){
                return comReturn(false,$validate->getError(), '', 500);
            }

            //判断权限
            $Token = Request::instance()->header("Token","");
            $auth = $this->tokenJudgeAuth($Token, $sid);
            if (!$auth) {
                return comReturn(false,config('user.no_auth'), '', 500);
            }

            $shopDoctorModel = new ShopDoctor();

            $list = $shopDoctorModel
                    ->field('doc_id,sid,eid,doctor_name,doctor_title,doctor_duty,doctor_img')
                    ->where('sid', $sid)
                    ->where('is_delete', 0)
                    ->select();

            if ($list != null) {
                return comReturn(true,config("return_message.success"),$list);
            }else{
                return comReturn(false,config('return_message.fail'), '', 500);
            }
        }catch (Exception $e){
            return comReturn(false, $e->getMessage(), '', 500);
        }
    }

    /**
     * 改变医护状态
     * @param Request $request
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function enableDoctor(Request $request)
    {
        $Token = Request::instance()->header("Token","");
        $doc_id    = $request->param("doc_id", "");//医护id
        $is_enable = $request->param("is_enable","");//是否启用  0否 1是

        try{
            $doctorModel = new ShopDoctor();

            if (empty($doc_id)) {
                return comReturn(false,config('return_meaasge.fail'), '', 500);
            }else{

                $doctor_data = $doctorModel
                        ->field('sid')
                        ->where('doc_id', $doc_id)
                        ->where('is_delete', 0)
                        ->find();
                $auth = $this->tokenJudgeAuth($Token, $doctor_data['sid']);
                if (!$auth) {
                    return comReturn(false,config('user.no_auth'), '', 500);
                }
            }

            $rule = [
                "doc_id|医护id"      => "require",
                "is_enable|启用状态" => "require",
            ];
            $check_data = [
                "doc_id"    => $doc_id,
                "is_enable" => $is_enable,
            ];
            $validate = new Validate($rule);
            if (!$validate->check($check_data)){
                return comReturn(false,$validate->getError(), '', 500);
            }

            $doctor['is_enable']  = $is_enable;
            $doctor['updated_at'] = time();

            Log::info("切换医护状态 ----- ".var_export($doctor,true));

            // 获取修改之前的数据
            $keys = array_keys($doctor);
            $databefore = $this->updateBefore("shop_doctor", "doc_id", $doc_id, $keys);

            $res = $doctorModel
                ->where('doc_id',$doc_id)
                ->update($doctor);

            if ($res == false){
                return comReturn(false,"修改失败", '', 500);
            }

            //记录日志
            $logtext = $this->checkDifAfter($databefore,$doctor);
            $logtext .= "(DOC_ID:".$doc_id.")";
            $logtext = $this->infoAddClass($logtext, 'text-edit');
            $route = $this->request->routeInfo();
            $route_tran = $this->routeTranslation($route, 'shop_menu');
            $admin = $this->tokenGetAdmin($Token);//获取管理员信息
            $this->addSysLog(time(), $admin['log_name'], $route_tran.$logtext, $request->ip(), $doctor_data['sid']);

            return comReturn(true,config("return_message.error_status_code")['ok']['value']);
        }catch (Exception $e){
            return comReturn(false,$e->getMessage(), '', 500);
        }
    }

    /**
     * 改变医护排序
     * @param Request $request
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function sortDoctor(Request $request)
    {
        $Token = Request::instance()->header("Token","");
        $doc_id    = $request->param("doc_id", "");//医护id
        $sort = $request->param("sort","");//医护排序

        try{
            $doctorModel = new ShopDoctor();

            if (empty($doc_id)) {
                return comReturn(false,config('return_meaasge.fail'), '', 500);
            }else{

                $doctor_data = $doctorModel
                        ->field('sid')
                        ->where('doc_id', $doc_id)
                        ->where('is_delete', 0)
                        ->find();
                $auth = $this->tokenJudgeAuth($Token, $doctor_data['sid']);
                if (!$auth) {
                    return comReturn(false,config('user.no_auth'));
                }
            }

            $rule = [
                "doc_id|医护id" => "require",
                "sort|启用状态" => "require",
            ];
            $check_data = [
                "doc_id" => $doc_id,
                "sort"  => $sort,
            ];
            $validate = new Validate($rule);
            if (!$validate->check($check_data)){
                return comReturn(false,$validate->getError(), '', 500);
            }

            $doctor['sort']       = $sort;
            $doctor['updated_at'] = time();

            Log::info("切换医护状态 ----- ".var_export($doctor,true));

            // 获取修改之前的数据
            $keys = array_keys($doctor);
            $databefore = $this->updateBefore("shop_doctor", "doc_id", $doc_id, $keys);

            $res = $doctorModel
                ->where('doc_id',$doc_id)
                ->update($doctor);

            if ($res == false){
                return comReturn(false,"修改失败", '', 500);
            }

            //记录日志
            $logtext = $this->checkDifAfter($databefore,$doctor);
            $logtext .= "(DOC_ID:".$doc_id.")";
            $logtext = $this->infoAddClass($logtext, 'text-edit');
            $route = $this->request->routeInfo();
            $route_tran = $this->routeTranslation($route, 'shop_menu');
            $admin = $this->tokenGetAdmin($Token);//获取管理员信息
            $this->addSysLog(time(), $admin['log_name'], $route_tran.$logtext, $request->ip(), $doctor_data['sid']);

            return comReturn(true,config("return_message.error_status_code")['ok']['value']);
        }catch (Exception $e){
            return comReturn(false,$e->getMessage(), '', 500);
        }
    }



}