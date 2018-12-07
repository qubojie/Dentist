<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/10/26
 * Time: 下午2:34
 */
namespace app\common\controller;

use app\shopadmin\model\Shop;
use app\shopadmin\model\ShopGoods;
use app\wechat\model\BillOrderGoods;
use app\wechat\model\DtsGoods;
use think\Controller;
use think\Db;

class GoodsCommon extends Controller
{
    /**
     * 分类id或者关键字获取服务列表
     * @param $keywords
     * @param $lng
     * @param $lat
     * @param $page_size
     * @param $now_page
     * @param string $is_classify
     * @return array
     * @throws \think\exception\DbException
     */
    public function keywordsGetGoodsList($keywords,$lng,$lat,$page_size,$now_page,$is_classify = "")
    {
        if (empty($page_size)) $page_size = config('xcx_page_size');
        if (empty($now_page)) $now_page   = 1;

//        $lng = "117.169787";//经度
//        $lat = "39.106538";//维度

        $config = [
            "page" => $now_page,
        ];

        $where = [];
        $keywords_where = [];
        if (!empty($is_classify)){
            //分类查询
            $where['g.cat_id'] = ['eq',$is_classify];
        }
        if (!empty($keywords)){
            $keywords_where['s.shop_name|s.shop_address|g.goods_name'] = ['like',"%$keywords%"];
        }

        $goodsModel = new ShopGoods();

        if (!empty($lng) && !empty($lat)){
            $field = "s.sid,s.shop_name,(2 * 6378.137* ASIN(SQRT(POW(SIN(3.1415926535898*(".$lat."-s.shop_lat)/360),2)+COS(3.1415926535898*".$lat."/180)* COS(s.shop_lat * 3.1415926535898/180)*POW(SIN(3.1415926535898*(".$lng."-s.shop_lng)/360),2))))*1000 as distance";
            $order = "distance,g.is_top DESC,g.sort,g.created_at DESC";
        }else{
            $field = "s.sid,s.shop_name";
            $order = "g.is_top DESC,g.sort,g.created_at DESC";
        }

        $res = $goodsModel
            ->alias('g')
            ->join('shop s','s.sid = g.sid')
            ->join('goods_image gi','gi.gid = g.gid','LEFT')
            ->where($where)
            ->where($keywords_where)
            ->where('g.is_delete',0)
            ->where('g.status',config('goods.status')['in_sale']['key'])
            ->where('s.status',config('shop_status.in_business')['key'])
            ->field('gi.image goods_image')
            ->field($field)
            ->field('g.gid,g.goods_name,g.goods_sketch,truncate(g.goods_original_price,2) goods_original_price,truncate(g.goods_price,2) goods_price')
            ->group("g.gid")
            ->order($order)
            ->paginate($page_size,false,$config);

        $res  = json_decode(json_encode($res),true);

        if (!empty($lng) && !empty($lat)){
            /*处理距离排序数据  On*/
            $data = $res['data'];
            $res['data'] = Distance::disposeDistance($data);
            /*处理距离排序数据  Off*/
        }
        return $res;
    }

    /**
     * 根据订单id获取商品id
     * @param $oid
     * @return array|false|mixed|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function oidGetGid($oid)
    {
        $orderGoodsModel = new BillOrderGoods();

        $goodsOrderInfo = $orderGoodsModel
            ->where('oid',$oid)
            ->field('gid')
            ->find();

        $goodsOrderInfo = json_decode(json_encode($goodsOrderInfo),true);

        return $goodsOrderInfo;
    }

    /**
     * 加或者减商品的购买次数
     * @param $set "Inc +  ; Dec -"
     * @param $num "加或者减的数量"
     * @param $gid "商品id"
     * @return bool
     * @throws \think\Exception
     */
    public function goodsBuyNumChange($set,$num,$gid)
    {
        $goodsModel = new DtsGoods();
        if ($set == 'Inc'){
            $res = $goodsModel
                ->where('gid',$gid)
                ->setInc('buy_num',$num);
        }else{
            $res = $goodsModel
                ->where('gid',$gid)
                ->setDec('buy_num',$num);
        }

        if ($res !== false){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 商品id获取店铺信息
     * @param $gid
     * @return array|false|mixed|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function gidGetShopInfo($gid)
    {
        $shopModel = new Shop();

        $res = $shopModel
            ->alias('s')
            ->join("goods g",'g.sid = s.sid')
            ->where('g.gid',$gid)
            ->field('s.sid,s.account_balance,s.account_freeze,s.account_cash')
            ->find();

        $res = json_decode(json_encode($res),true);

        return $res;
    }
}