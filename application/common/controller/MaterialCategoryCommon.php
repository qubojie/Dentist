<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/10/23
 * Time: 下午4:55
 */
namespace app\common\controller;

use app\admin\model\SysResourceCategory;
use app\admin\model\SysResourceFile;
use app\shopadmin\model\ResourceCategory;
use app\shopadmin\model\ResourceFile;
use think\Controller;
use think\Validate;

class MaterialCategoryCommon extends Controller
{
    /**
     * 素材分类列表
     * @param $type
     * @param $sid
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index($type,$sid = "")
    {
        $rule = [
            "type|分类类型"  => "require",
        ];
        $check_data = [
            "type"  => $type,
        ];
        $validate = new Validate($rule);
        if (!$validate->check($check_data)){
            return comReturn(false,$validate->getError());
        }

        $type_where['type'] = ['eq',$type];

        if (!empty($sid)){
            $resourceFileModel = new ResourceFile();
            $resourceCategoryModel = new ResourceCategory();
            $where_s['sid'] = ['eq',$sid];
        }else{
            $resourceFileModel = new SysResourceFile();
            $resourceCategoryModel = new SysResourceCategory();
            $where_s = [];
        }

        $catList = $resourceCategoryModel
            ->where($where_s)
            ->where($type_where)
            ->order('sort')
            ->select();

        $catList = json_decode(json_encode($catList),true);

        if (!empty($catList)){

            for ($i = 0; $i < count($catList); $i ++){
                $cat_id  = $catList[$i]['cat_id'];
                $cat_num = $resourceFileModel
                    ->where($where_s)
                    ->where("cat_id",$cat_id)
                    ->where($type_where)
                    ->count();
                $catList[$i]['cat_num'] = $cat_num;
            }
        }

        return comReturn(true,config("return_message.success"),$catList);
    }

    /**
     * 素材分类添加
     * @param $name
     * @param $type
     * @param string $sid
     * @return string
     */
    public function add($name,$type,$sid = "")
    {
        //验证
        $rule = [
            "type|分类类型" => "require",
            "name|分类名"  => "require|unique:resource_category",
        ];
        $check_data = [
            "name"  => $name,
            "type"  => $type
        ];
        $validate = new Validate($rule);
        if (!$validate->check($check_data)){
            return comReturn(false,$validate->getError());
        }

        $params = [
            "type"       => $type,
            "name"       => $name,
            "sort"       => '500',
            "created_at" => time(),
            "updated_at" => time()
        ];

        if (!empty($sid)){
            $resourceCategoryModel = new ResourceCategory();
            $params['sid'] = $sid;
        }else{
            $resourceCategoryModel = new SysResourceCategory();
        }

        $res = $resourceCategoryModel
            ->insert($params);

        if ($res !== false){
            return comReturn(true,config("return_message.success"));
        }else{
            return comReturn(false,config("return_message.fail"));
        }
    }

    /**
     * 素材分类删除
     * @param $cat_id
     * @param string $sid
     * @return string
     */
    public function delete($cat_id,$sid = "")
    {
        $rule = [
            "cat_id|分类id"  => "require",
        ];
        $check_data = [
            "cat_id"  => $cat_id,
        ];
        $validate = new Validate($rule);
        if (!$validate->check($check_data)){
            return comReturn(false,$validate->getError());
        }

        if (!empty($sid)){
            /*检测当前分类下是否存在未删除的素材 On*/
            $is_exist = $this->checkCategoryHaveSourceShop($cat_id);
            /*检测当前分类下是否存在未删除的素材 Off*/

            $resourceCategoryModel = new ResourceCategory();

            $where_s['sid'] = ['eq',$sid];
        }else{
            /*检测当前分类下是否存在未删除的素材 On*/
            $is_exist = $this->checkCategoryHaveSourceAdmin($cat_id);
            /*检测当前分类下是否存在未删除的素材 Off*/
            $resourceCategoryModel = new SysResourceCategory();

            $where_s = [];
        }

        if ($is_exist){
            return comReturn(false,config("material.sc_have_child"));
        }

        $is_delete = $resourceCategoryModel
            ->where($where_s)
            ->where("cat_id",$cat_id)
            ->delete();

        if ($is_delete !== false){
            return comReturn(true,config("return_message.success"));
        }else{
            return comReturn(false,config("return_message.success"));
        }
    }

    /**
     * 素材分类编辑
     * @param $cat_id
     * @param $name
     * @param $sort
     * @param $sid
     * @return string
     */
    public function edit($cat_id,$name,$sort,$sid = "")
    {
        $rule = [
            "cat_id|分类id" => "require",
            "name|名称"     => "require",
            "sort|排序"     => "require|number",
        ];
        $check_data = [
            "cat_id" => $cat_id,
            "name"   => $name,
            "sort"   => $sort
        ];
        $validate = new Validate($rule);
        if (!$validate->check($check_data)){
            return comReturn(false,$validate->getError());
        }

        if (!empty($sid)){
            $resourceCategoryModel = new ResourceCategory();
            $where_s['sid'] = ['eq',$sid];
        }else{
            $resourceCategoryModel = new SysResourceCategory();
            $where_s = [];
        }

        $params = [
            "name"       => $name,
            "sort"       => $sort,
            "updated_at" => time()
        ];

        $is_edit = $resourceCategoryModel
            ->where($where_s)
            ->where("cat_id",$cat_id)
            ->update($params);

        if ($is_edit !== false){
            return comReturn(true,config("return_message.success"));
        }else{
            return comReturn(false,config("return_message.fail"));
        }
    }

    /**
     * 检测商户当前素材分类下是否有素材
     * @param $cat_id
     * @return bool
     */
    public function checkCategoryHaveSourceShop($cat_id)
    {
        $resourceFileModel = new ResourceFile();

        $is_exist = $resourceFileModel
            ->where("cat_id",$cat_id)
            ->count();

        if ($is_exist > 0){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 检测平台当前素材分类下是否有素材
     * @param $cat_id
     * @return bool
     */
    public function checkCategoryHaveSourceAdmin($cat_id)
    {
        $resourceFileModel = new SysResourceFile();
        $is_exist = $resourceFileModel
            ->where("cat_id",$cat_id)
            ->count();
        if ($is_exist > 0){
            return true;
        }else{
            return false;
        }
    }
}