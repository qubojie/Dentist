<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/10/23
 * Time: 下午4:01
 */
namespace app\admin\controller\matreial;

use app\common\controller\MaterialCategoryCommon;
use app\common\controller\SysAdminAuth;

class MaterialCategory extends SysAdminAuth
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
        $type = $this->request->param("type","");//分类类型  0图片   1视频

        $materialCategoryCommonObj = new MaterialCategoryCommon();

        $res = $materialCategoryCommonObj->index($type);

        return $res;
    }

    /**
     * 素材分类添加
     * @return string
     */
    public function categoryAdd()
    {
        $type = $this->request->param("type","");//分类类型  0图片   1视频
        $name = $this->request->param('name', '');

        $materialCategoryCommonObj = new MaterialCategoryCommon();

        $res = $materialCategoryCommonObj->add("$name","$type");

        return $res;
    }

    /**
     * 素材分类删除
     * @return string
     */
    public function categoryDelete()
    {
        $cat_id = $this->request->param("cat_id","");//分类id

        $materialCategoryCommonObj = new MaterialCategoryCommon();

        $res = $materialCategoryCommonObj->delete("$cat_id");

        return $res;
    }

    /**
     * 素材分类编辑
     * @return string
     */
    public function categoryEdit()
    {
        $cat_id = $this->request->param("cat_id","");//分类id
        $name   = $this->request->param("name","");//名称
        $sort   = $this->request->param("sort","500");//排序

        $materialCategoryCommonObj = new MaterialCategoryCommon();

        $res = $materialCategoryCommonObj->edit("$cat_id","$name","$sort");

        return $res;
    }
}