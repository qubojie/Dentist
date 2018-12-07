<?php

/**
 * @Author: zhangtao
 * @Date:   2018-11-09 09:54:47
 * @Last Modified by:   admin
 * @Last Modified time: 2018-12-04 16:02:35
 */
namespace app\admin\controller\system;

use app\common\controller\SysAdminAuth;
use app\admin\model\SysLog;
use think\Request;
use think\Exception;

class Log extends SysAdminAuth
{
    /**
     * 系统日志列表
     * @param Request $request
     * @return \think\Paginator
     * @throws \think\exception\DbException
     */
    public function sysLogList(Request $request)
    {
        $pagesize   = $request->param("pagesize",config('PAGESIZE'));//当前页,不传时为10
        $nowPage    = $request->param("nowPage","1");

        if (empty($pagesize)){
            $pagesize = config('PAGESIZE');
        }

        $before_date  = $request->param("before_date", "");//起始时间
        $after_date   = $request->param("after_date", "");//截止时间

        $orderBy['filter']['orderBy'] = $request->has('orderBy') ? $request->param('orderBy') : 'log_time';
        $orderBy['filter']['sort'] = $request->has('sort') ? $request->param('sort') : 'DESC';

        $where = [];
        $where1 = "";
        $where2 = "";
        if ($before_date != "") {
            $where1 = array('egt', $before_date);
        }
        if ($after_date != "") {
            $after_date = strtotime(date("Y-m-d 23:59:59", $after_date));
            $where2 = array('elt', $after_date);
        }
        if (!empty($where1) && !empty($where2)) {
            $where['log_time'] = array($where1, $where2, 'and');
        }elseif(!empty($where1)){
            $where['log_time'] = $where1;
        }elseif(!empty($where2)){
            $where['log_time'] = $where2;
        }

        $config = [
            "page" => $nowPage,
        ];

        //处理排序条件
        $field_array = array("action_user");
        if (in_array($orderBy['filter']['orderBy'], $field_array)) {
            $orderBy['filter']['orderBy'] = fieldOrderByEncode($orderBy['filter']['orderBy']);
        }

        try{
            $sysLogModel = new SysLog();

            $log_list = $sysLogModel
                        ->where($where)
                        ->order($orderBy['filter']['orderBy'],$orderBy['filter']['sort'])
                        ->paginate($pagesize,false,$config);

            if ($log_list){
                return comReturn(true,config("return_message.success"),$log_list);

            }else{
                return comReturn(false,config("return_message.fail"));
            }
        }catch (Exception $e){
            return comReturn(false, $e->getMessage(), '', 500);
        }

    }
}