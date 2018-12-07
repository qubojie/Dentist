<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/10/16
 * Time: 下午3:41
 */
namespace app\wechatpublic\controller;

use app\common\controller\CommonAuth;
use app\common\controller\SendSms;
use app\services\controller\ImageUpload;
use app\wechatpublic\model\ShopEnterprise;
use think\Env;
use think\Log;
use think\Request;
use think\Validate;

class JoinIn extends CommonAuth
{
    /**
     * 首页根据wxid判断用户申请加盟信息
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function indexIsApply()
    {
        $wxid = $this->request->param("wxid","");//code
        $rule = [
            "wxid" => "require",
        ];
        $check_data = [
            "wxid" => $wxid,
        ];

        $validate = new Validate($rule);
        if (!$validate->check($check_data)){
            return comReturn(false,$validate->getError());
        }

        $res = $this->checkIsJoin($wxid);//判断是否加盟

        if (!empty($res)) {
            /*获取系统设置key => value On*/
            $keys = $this->getSettingInfo("sys_shop_manage_url,service_phone,service_time");

            $res['sys_shop_manage_url'] = $keys['sys_shop_manage_url'];
            $res['service_phone']       = $keys['service_phone'];
            $res['service_time']        = $keys['service_time'];
            /*获取系统设置key => value Off*/
        }

        return comReturn(true,config('return_message.success'),$res);
    }

    /**
     * 授权并判断是否已加盟
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function isApply(Request $request)
    {
        $code = $request->param("code","");//code

        $rule = [
            "code" => "require",
        ];
        $check_data = [
            "code" => $code,
        ];

        $validate = new Validate($rule);
        if (!$validate->check($check_data)){
            return comReturn(false,$validate->getError());
        }
        /*根据code获取授权信息 On*/
        $wechatAuthObj = new WechatAuth();
        $access_token  = $wechatAuthObj->getUserAccessToken($code);
        if (!empty($access_token->errcode)){
            return comReturn(false,$access_token->errmsg);
        }
        $userInfo      = $wechatAuthObj->getUserInfo($access_token);
        $openid        = $userInfo['openid'];
        $unionid       = $userInfo['unionid'];
        /*根据code获取授权信息 Off*/

        $res = $this->checkIsJoin($unionid);//判断是否加盟

        if (empty($res)){
            return comReturn(true,config('join.new_join')['key'],$userInfo);
        }

        /*获取系统设置key => value On*/
        $keys = $this->getSettingInfo("sys_shop_manage_url,service_phone,service_time");

        $res['sys_shop_manage_url'] = $keys['sys_shop_manage_url'];
        $res['service_phone']       = $keys['service_phone'];
        $res['service_time']        = $keys['service_time'];
        /*获取系统设置key => value Off*/

        return comReturn(true,config('join.old_join')['key'],$res);
    }

    /**
     * 申请加盟
     * @param Request $request
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function apply(Request $request)
    {
        $wxid          = $request->param("wxid","");//unid
        $openid        = $request->param("openid","");//
        $a_phone       = $request->param("a_phone","");//管理员手机号
        $code          = $request->param("code","");//验证码
        $a_name        = $request->param("a_name","");//管理员姓名
        $p_name        = $request->param("p_name","");//法人姓名
        $p_idno        = $request->param("p_idno","");//法人身份证号
        $idcard_img1   = $request->param("idcard_img1","");//身份证正面照
        $idcard_img2   = $request->param("idcard_img2","");//身份证反面照
        $e_name        = $request->param("e_name","");//企业全称
        $e_license_img = $request->param("e_license_img","");//营业执照照片
        $e_idno        = $request->param("e_idno","");//企业营业执照信用代码

        $params = $request->param();

        $rule = [
            "wxid"                    => "require",
            "openid"                  => "require",
            "a_phone|电话号码"         => "require|regex:1[3-8]{1}[0-9]{9}|unique:shop_enterprise",
            "code|验证码"              => "require",
            "a_name|管理员姓名"         => "require",
            "p_name|法人姓名"          => "require",
            "p_idno|法人身份证号"       => "require",
            "idcard_img1|身份证正面照"  => "require",
            "idcard_img2|身份证反面照"  => "require",
            "e_name|企业全称"           => "require|unique:shop_enterprise",
            "e_license_img|营业执照照片" => "require",
            "e_idno|企业营业执照信用代码" => "require|unique:shop_enterprise",
        ];
        $check_data = [
            "wxid"          => $wxid,
            "openid"        => $openid,
            "a_phone"       => $a_phone,
            "code"          => $code,
            "a_name"        => $a_name,
            "p_name"        => $p_name,
            "p_idno"        => $p_idno,
            "idcard_img1"   => $idcard_img1,
            "idcard_img2"   => $idcard_img2,
            "e_name"        => $e_name,
            "e_license_img" => $e_license_img,
            "e_idno"        => $e_idno,
        ];
        $validate = new Validate($rule);
        if (!$validate->check($check_data)){
            return comReturn(false,$validate->getError());
        }
        /*验证验证码 On*/
        $is_pp = SendSms::checkCode($a_phone,$code);
        if (!$is_pp['result']) return $is_pp;
        /*验证验证码 Off*/

        /*验证身份证号码 On*/
       /* $p_idno_check = checkIdCard($p_idno);
        if (!$p_idno_check){
            return comReturn(false,config('message.p_idno_invalid'));
        }*/
        /*验证身份证号码 Off*/

        /*判断是否加盟 On*/
        $isJoin = $this->checkIsJoin($wxid);
        if (!empty($isJoin)){
            return comReturn(false,config('join.old_join')['name']);
        }
        /*判断是否加盟 Off*/

        $eid = generateReadableUUID("E");

        $params['eid']        = $eid;
        $params['status']     = config("join.status")['wait_check']['key'];
        $params['created_at'] = time();
        $params['updated_at'] = time();

        /*移动图片 On*/
        $imageUploadObj = new ImageUpload();
        $idcard_img1 = $imageUploadObj->moveImage("$idcard_img1","$eid","idcard_img1");
        if (!$idcard_img1){
            return comReturn(false,config('return_message.fail'));
        }
        $idcard_img2 = $imageUploadObj->moveImage("$idcard_img2","$eid","idcard_img2");
        if (!$idcard_img2){
            return comReturn(false,config('return_message.fail'));
        }
        $e_license_img = $imageUploadObj->moveImage("$e_license_img","$eid","e_license_img");
        if (!$e_license_img){
            return comReturn(false,config('return_message.fail'));
        }
        $params['idcard_img1']   = Env::get('IMG_YM_PATH').'/'.$idcard_img1;
        $params['idcard_img2']   = Env::get('IMG_YM_PATH').'/'.$idcard_img2;
        $params['e_license_img'] = Env::get('IMG_YM_PATH').'/'.$e_license_img;
        /*移动图片 Off*/
        $shopEnterpriseModel = new ShopEnterprise();
        $params = removeArrayKey($params,"code");

        $res = $shopEnterpriseModel
            ->insert($params);

        if ($res == false){
            return comReturn(false,config("return_message.fail"));
        }

        $info = $this->eidGetJoinInfo($eid);

        return comReturn(true,config("return_message.success"),$info);

    }


    /**
     * 重新提交申请
     * @param Request $request
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function editApply(Request $request)
    {
        $eid           = $request->param("eid","");
        $a_phone       = $request->param("a_phone","");//管理员手机号
        $code          = $request->param("code","");//验证码
        $a_name        = $request->param("a_name","");//管理员姓名
        $p_name        = $request->param("p_name","");//法人姓名
        $p_idno        = $request->param("p_idno","");//法人身份证号
        $idcard_img1   = $request->param("idcard_img1","");//身份证正面照
        $idcard_img2   = $request->param("idcard_img2","");//身份证反面照
        $e_name        = $request->param("e_name","");//企业全称
        $e_license_img = $request->param("e_license_img","");//营业执照照片
        $e_idno        = $request->param("e_idno","");//企业营业执照信用代码

        $rule = [
            "eid"                     => "require",
            "a_phone|电话号码"         => "require|regex:1[3-8]{1}[0-9]{9}|unique:shop_enterprise",
            "code|验证码"              => "require",
            "a_name|管理员姓名"         => "require",
            "p_name|法人姓名"          => "require",
            "p_idno|法人身份证号"       => "require",
            "e_name|企业全称"           => "require|unique:shop_enterprise",
            "e_idno|企业营业执照信用代码" => "require|unique:shop_enterprise",
        ];
        $check_data = [
            "eid"           => $eid,
            "a_phone"       => $a_phone,
            "code"          => $code,
            "a_name"        => $a_name,
            "p_name"        => $p_name,
            "p_idno"        => $p_idno,
            "e_name"        => $e_name,
            "e_idno"        => $e_idno,
        ];
        $validate = new Validate($rule);
        if (!$validate->check($check_data)){
            return comReturn(false,$validate->getError());
        }

        /*验证验证码 On*/
        $is_pp = SendSms::checkCode($a_phone,$code);
        if (!$is_pp['result']) return $is_pp;
        /*验证验证码 Off*/

        $params = $request->param();

        $params['updated_at'] = time();
        $params['status']     = config("join.status")['wait_check']['key'];
        $params = removeArrayKey($params,"code");

        /*移动图片 On*/
        $imageUploadObj = new ImageUpload();

        $dateTime = date("YmdHis",time());

        if (!empty($idcard_img1)) {
            $image_name = $dateTime."idcard_img1";
            $idcard_img1 = $imageUploadObj->moveImage("$idcard_img1","$eid","$image_name");
            if (!$idcard_img1){
                return comReturn(false,config('return_message.fail'));
            }

            $params['idcard_img1']   = Env::get('IMG_YM_PATH').'/'.$idcard_img1;
        }

        if (!empty($idcard_img2)) {
            $image_name = $dateTime."idcard_img2";
            $idcard_img2 = $imageUploadObj->moveImage("$idcard_img2","$eid","$image_name");
            if (!$idcard_img2){
                return comReturn(false,config('return_message.fail'));
            }
            $params['idcard_img2']   = Env::get('IMG_YM_PATH').'/'.$idcard_img2;
        }

        if (!empty($e_license_img)) {
            $image_name = $dateTime."e_license_img";
            $e_license_img = $imageUploadObj->moveImage("$e_license_img","$eid","$image_name");
            if (!$e_license_img){
                return comReturn(false,config('return_message.fail'));
            }
            $params['e_license_img'] = Env::get('IMG_YM_PATH').'/'.$e_license_img;
        }
        /*移动图片 Off*/

        $params = removeArrayKey($params,'eid');

        Log::info("重新申请加盟 ----- ".var_export($params,true));

        $shopEnterpriseModel = new ShopEnterprise();

        $res = $shopEnterpriseModel
            ->where('eid',$eid)
            ->update($params);

        if ($res === false){
            return comReturn(false,config("return_message.fail"));
        }

        $info = $this->eidGetJoinInfo($eid);

        return comReturn(true,config("return_message.success"),$info);
    }
}