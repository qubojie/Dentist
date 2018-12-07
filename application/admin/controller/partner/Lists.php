<?php

/**
 * 合伙人列表
 * @Author: zhangtao
 * @Date:   2018-11-05 11:32:51
 * @Last Modified by:   admin
 * @Last Modified time: 2018-12-04 15:46:21
 */
namespace app\admin\controller\partner;

use app\common\controller\SysAdminAuth;
use app\admin\model\SysAdminUser;
use app\wechatpublic\model\Partner;
use think\Controller;
use think\Db;
use think\Exception;
use think\Request;
use think\Validate;
use think\Cache;

class Lists extends SysAdminAuth{
    /**
     * 合伙人列表
     * @param Request $request
     * @return array
     */
    public function index(Request $request){

        $pagesize   = $request->param("pagesize",config('PAGESIZE'));//显示个数,不传时为10

        if (empty($pagesize)) $pagesize = config('PAGESIZE');

        $nowPage    = $request->param("nowPage","1");

        $pid     = $request->param("pid","");
        $keyword = $request->param("keyword","");//关键字 昵称真实姓名手机号
        $status  = $request->param("status",0);//已提交资料待审核 0 ，审核通过1，审核未通过 2   暂时停用 3   已注销9

        $before_date = $request->param("before_date","");//起始时间
        $after_date  = $request->param("after_date","");//截止时间

        $orderBy['filter']['orderBy'] = $request->has('orderBy') ? $request->param('orderBy') : 'created_at';
        $orderBy['filter']['sort'] = $request->has('sort') ? $request->param('sort') : 'desc';

        $config = [
            "page" => $nowPage,
        ];

        $where = [];
        $where['status'] = $status;
        if (!empty($pid)){
            $where["pid"] = $pid;
        }
        if (!empty($phone)){
            $where["nickname|name|phone"] = ["like","%$phone%"];
        }
        if (!empty($is_attention_wx)){
            $where["is_attention_wx"] = $is_attention_wx;
        }
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
            $where['register_time'] = array($where1, $where2, 'and');
        }elseif(!empty($where1)){
            $where['register_time'] = $where1;
        }elseif(!empty($where2)){
            $where['register_time'] = $where2;
        }

        try{
            $partnerModel = new Partner();

            //处理排序条件
            $field_array = array("name");
            if (in_array($orderBy['filter']['orderBy'], $field_array)) {
                $orderBy['filter']['orderBy'] = fieldOrderByEncode($orderBy['filter']['orderBy']);
            }

            $partner_list = $partnerModel
                            ->where($where)
                            // ->order('updated_at desc')
                            ->order($orderBy['filter']['orderBy'], $orderBy['filter']['sort'])
                            ->field('pid,phone,name,qr_code,wxid,mp_openid,is_attention_wx,nickname,avatar,sex,province,city,country,account_balance,account_freeze,account_cash,register_way,register_time,lastlogin_time,status,review_user,review_time,review_desc,created_at,updated_at')
                            ->paginate($pagesize,false,$config);

            if ($partner_list) {
                //保留两位小数
                $decimal = $this->getSettingInfo("sys_show_number_decimal")['sys_show_number_decimal'];
                foreach ($partner_list as $key => $value) {
                    $partner_list[$key]['account_balance'] = sprintf("%.".$decimal."f", $value['account_balance']);
                    $partner_list[$key]['account_freeze']  = sprintf("%.".$decimal."f", $value['account_freeze']);
                    $partner_list[$key]['account_cash']    = sprintf("%.".$decimal."f", $value['account_cash']);

                    $partner_list[$key]['review_desc'] = htmlspecialchars_decode($value['review_desc']);
                }

                $partner_list = $this->getSysAdminLog($partner_list, "pid");

                return comReturn(true,config("return_message.success"),$partner_list);
            }else{
                return comReturn(false,config("return_message.fail"));
            }
        }catch (Exception $e){
            return comReturn(false, $e->getMessage(), '', 500);
        }


    }

    /**
     * 合伙人状态列表
     * @return array
     */
    public function partnerStatus(){
        $status_list = $this->getStatus("partner");;
        return comReturn(true,config("return_message.success"), $status_list);
    }
}