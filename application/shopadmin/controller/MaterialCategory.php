<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/10/23
 * Time: 下午4:01
 */
namespace app\shopadmin\controller;

use app\common\controller\MaterialCategoryCommon;
use app\common\controller\ShopAdminAuth;

class MaterialCategory extends ShopAdminAuth
{
    /**
     * 素材分类列表
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function categoryList()
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

        $type = $this->request->param("type","");//分类类型  0图片   1视频

        $materialCategoryCommonObj = new MaterialCategoryCommon();

        $res = $materialCategoryCommonObj->index($type,$sid);

        return $res;
    }

    /**
     * 素材分类添加
     * @return string
     */
    public function categoryAdd()
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
        $type = $this->request->param("type","");//分类类型  0图片   1视频
        $name = $this->request->param('name', '');

        $materialCategoryCommonObj = new MaterialCategoryCommon();

        $res = $materialCategoryCommonObj->add("$name","$type","$sid");

        return $res;
    }

    /**
     * 素材分类删除
     * @return string
     */
    public function categoryDelete()
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
        $cat_id = $this->request->param("cat_id","");//分类id

        $materialCategoryCommonObj = new MaterialCategoryCommon();

        $res = $materialCategoryCommonObj->delete("$cat_id","$sid");

        return $res;
    }

    /**
     * 素材分类编辑
     * @return string
     */
    public function categoryEdit()
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
        $cat_id = $this->request->param("cat_id","");//分类id
        $name   = $this->request->param("name","");//名称
        $sort   = $this->request->param("sort","500");//排序

        $materialCategoryCommonObj = new MaterialCategoryCommon();

        $res = $materialCategoryCommonObj->edit("$cat_id","$name","$sort","$sid");

        return $res;
    }
}