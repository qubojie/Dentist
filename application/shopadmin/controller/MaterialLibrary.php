<?php
/**
 * 素材库管理.
 * User: qubojie
 * Date: 2018/10/23
 * Time: 下午3:06
 */
namespace app\shopadmin\controller;

use app\common\controller\MaterialLibraryCommon;
use app\common\controller\ShopAdminAuth;
use think\Env;
use Qiniu\Auth;

vendor("qiniu.autoload");

class MaterialLibrary extends ShopAdminAuth
{
    /**
     * 素材列表
     * @return string
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $sid  = $this->request->param("sid", "");
        if (empty($sid)) {
            return comReturn(false, config("return_message.error_status_code")['lack_param_necessary']['value'], '', config("return_message.error_status_code")['lack_param_necessary']['key']);
        }else{
            $res = $this->checkField("shop", "sid", $sid);
            if (!$res) {
                return comReturn(false, "无此店铺", '', 500);
            }
        }
        $type       = $this->request->param("type","");//类型 0 图片 ;  1 视频 ; 2 文件
        $cat_id     = $this->request->param("cat_id","");
        $page_size  = $this->request->param("page_size",config('page_size'));//显示个数,不传时为10
        $now_page   = $this->request->param("now_page","1");

        $materialLibraryCommonObj = new MaterialLibraryCommon();

        $res = $materialLibraryCommonObj->index("$type","$cat_id","$page_size","$now_page","$sid");

        return $res;
    }

    /**
     * 素材上传-后台传
     * @return string
     * @throws \Exception
     */
    public function upload()
    {
        $sid  = $this->request->param("sid", "");
        if (empty($sid)) {
            return comReturn(false, config("return_message.error_status_code")['lack_param_necessary']['value'], '', config("return_message.error_status_code")['lack_param_necessary']['key']);
        }else{
            $res = $this->checkField("shop", "sid", $sid);
            if (!$res) {
                return comReturn(false, "无此店铺", '', 500);
            }
        }

        $type   = $this->request->param("type","");//类型 0 图片 ;  1 视频 ; 2 文件
        $cat_id = $this->request->param("cat_id","");

        $prefix = 'shop_source';

        $materialLibraryCommonObj = new MaterialLibraryCommon();

        $res = $materialLibraryCommonObj->upload("$type","$cat_id","$prefix","$sid");

        return $res;
    }

    /**
     * 素材上传-前台传
     * @return string
     * @throws \Exception
     */
    public function uploadFile()
    {
        $sid  = $this->request->param("sid", "");
        if (empty($sid)) {
            return comReturn(false, config("return_message.error_status_code")['lack_param_necessary']['value'], '', config("return_message.error_status_code")['lack_param_necessary']['key']);
        }else{
            $res = $this->checkField("shop", "sid", $sid);
            if (!$res) {
                return comReturn(false, "无此店铺", '', 500);
            }
        }

        $type   = $this->request->param("type","");//类型 0 图片 ;  1 视频 ; 2 文件
        $link = $this->request->param("file","");//文件链接
        $file_size = $this->request->param("size","");//文件大小
        $file_extension = $this->request->param("extension","");//文件拓展名
        $cat_id = $this->request->param("cat_id","");//类型id

        $materialLibraryCommonObj = new MaterialLibraryCommon();

        $res = $materialLibraryCommonObj->uploadFile("$type","$cat_id","$link","$file_size","$file_extension","$sid");

        return $res;
    }

    /**
     * 素材删除
     * @return string
     */
    public function delete()
    {
        $sid  = $this->request->param("sid", "");
        if (empty($sid)) {
            return comReturn(false, config("return_message.error_status_code")['lack_param_necessary']['value'], '', config("return_message.error_status_code")['lack_param_necessary']['key']);
        }else{
            $res = $this->checkField("shop", "sid", $sid);
            if (!$res) {
                return comReturn(false, "无此店铺", '', 500);
            }
        }

        $ids = $this->request->param('id', '');

        $materialLibraryCommonObj = new MaterialLibraryCommon();

        $res = $materialLibraryCommonObj->delete($ids,$sid);

        return $res;
    }

    /**
     * 移动素材至指定分组
     * @return string
     */
    public function moveMaterial()
    {
        $sid  = $this->request->param("sid", "");
        if (empty($sid)) {
            return comReturn(false, config("return_message.error_status_code")['lack_param_necessary']['value'], '', config("return_message.error_status_code")['lack_param_necessary']['key']);
        }else{
            $res = $this->checkField("shop", "sid", $sid);
            if (!$res) {
                return comReturn(false, "无此店铺", '', 500);
            }
        }

        $type   = $this->request->param("type","");//素材类型
        $ids    = $this->request->param("id","");//素材id 多个以逗号隔开
        $cat_id = $this->request->param("cat_id","");//移动至的新分类id

        $materialLibraryCommonObj = new MaterialLibraryCommon();

        $res = $materialLibraryCommonObj->moveMaterial("$type","$ids","$cat_id","$sid");

        return $res;
    }

    /**
     * 获取七牛云上传token
     * @return string
     */
    public function getUploadToken()
    {
        $sid  = $this->request->param("sid", "");
        if (empty($sid)) {
            return comReturn(false, config("return_message.error_status_code")['lack_param_necessary']['value'], '', config("return_message.error_status_code")['lack_param_necessary']['key']);
        }else{
            $res = $this->checkField("shop", "sid", $sid);
            if (!$res) {
                return comReturn(false, "无此店铺", '', 500);
            }
        }

        $materialLibraryCommonObj = new MaterialLibraryCommon();

        $res = $materialLibraryCommonObj->createToken($sid);

        return comReturn(true, config("return_message.success"), $res);
        // return $token;
    }


}