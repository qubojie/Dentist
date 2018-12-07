<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/10/16
 * Time: 下午3:41
 */
namespace app\wechatpublic\controller;

use app\common\controller\CommonAuth;
use app\wechatpublic\model\ShopEnterprise;
use think\Request;
use think\Validate;

class JoinIn extends CommonAuth
{
    /**
     * 是否已加盟
     * @param Request $request
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function isApply(Request $request)
    {
        $wxid    = $request->param("wxid","");//unid
        $openid  = $request->param("openid","");//

        $rule = [
            "wxid"                    => "require",
            "openid"                  => "require",
        ];
        $check_data = [
            "wxid"          => $wxid,
            "openid"        => $openid,
        ];
        $validate = new Validate($rule);
        if (!$validate->check($check_data)){
            return comReturn(false,$validate->getError());
        }

        $shopEnterpriseModel = new ShopEnterprise();

        $res = $shopEnterpriseModel
            ->where("wxid",$wxid)
            ->find();

        $res = json_decode(json_encode($res),true);

        return comReturn(true,config('return_message.success'),$res);
    }

    /**
     * 申请加盟
     * @param Request $request
     * @return string
     */
    public function apply(Request $request)
    {
        $wxid          = $request->param("wxid","");//unid
        $openid        = $request->param("openid","");//
        $a_phone       = $request->param("a_phone","");//管理员手机号
        $a_name        = $request->param("a_name","");//管理员姓名
        $p_name        = $request->param("p_name","");//法人姓名
        $p_idno        = $request->param("p_idno","");//法人身份证号
        $idcard_img1   = $request->param("idcard_img1","");//身份证正面照
        $idcard_img2   = $request->param("idcard_img2","");//身份证反面照
        $e_name        = $request->param("e_name","");//企业全称
        $e_license_img = $request->param("e_license_img","");//营业执照照片
        $e_idno        = $request->param("e_idno","");//企业营业执照信用代码

        $rule = [
            "wxid"                    => "require",
            "openid"                  => "require",
            "a_phone|电话号码"         => "require|regex:1[3-8]{1}[0-9]{9}",
            "a_name|管理员姓名"         => "require",
            "p_name|法人姓名"          => "require",
            "p_idno|法人身份证号"       => "require",
            "idcard_img1|身份证正面照"  => "require",
            "idcard_img2|身份证反面照"  => "require",
            "e_name|企业全称"           => "require",
            "e_license_img|营业执照照片" => "require",
            "e_idno|企业营业执照信用代码" => "require",
        ];
        $check_data = [
            "wxid"          => $wxid,
            "openid"        => $openid,
            "a_phone"       => $a_phone,
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

        $params = $request->param();

        $params['eid']        = generateReadableUUID("E");
        $params['status']     = config("join.status")['wait_check']['key'];
        $params['created_at'] = time();
        $params['updated_at'] = time();

        $shopEnterpriseModel = new ShopEnterprise();

        $res = $shopEnterpriseModel
            ->insert($params);

        if ($res == false){
            return comReturn(false,config("return_message.fail"));
        }

        return comReturn(true,config("return_message.success"));

    }
}