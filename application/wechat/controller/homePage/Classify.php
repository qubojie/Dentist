<?php
/**
 * 首页分类.
 * User: qubojie
 * Date: 2018/10/22
 * Time: 下午2:48
 */
namespace app\wechat\controller\homePage;

use app\common\controller\CommonAuth;
use app\common\controller\GoodsCommon;
use app\shopadmin\model\ShopGoodsCategory;

class Classify extends CommonAuth
{
    /**
     * 分类列表
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $goodsCategoryModel = new ShopGoodsCategory();

        $res = $goodsCategoryModel
            ->where('is_enable',1)
            ->where('is_delete',0)
            ->order('sort')
            ->field('cat_id,cat_name,cat_image')
            ->select();

        return comReturn(true,config("return_message.success"),$res);
    }

    /**
     * 筛选数据
     * @throws \think\exception\DbException
     */
    public function filterData()
    {
        $cat_id    = $this->request->param('cat_id','');//分类id
        $keywords  = $this->request->param('keywords','');//关键字
        $lng       = $this->request->param('lng','');// "经度"
        $lat       = $this->request->param('lat','');// "维度"
        $now_page  = $this->request->param('now_page','');
        $page_size = $this->request->param('page_size','');

        $goodsCommonObj = new GoodsCommon();

        $res = $goodsCommonObj->keywordsGetGoodsList("$keywords","$lng","$lat","$page_size","$now_page","$cat_id");

        return comReturn(true,config('return_message.success'),$res);
    }
}