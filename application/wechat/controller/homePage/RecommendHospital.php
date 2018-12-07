<?php
/**
 * 主页推荐.
 * User: qubojie
 * Date: 2018/10/22
 * Time: 下午2:58
 */
namespace app\wechat\controller\homePage;

use app\common\controller\CommonAuth;
use app\common\controller\Distance;
use app\shopadmin\model\Shop;
use app\wechat\model\DtsGoods;
use think\Controller;
use think\Db;
use think\Env;

class RecommendHospital extends CommonAuth
{
    /**
     * 推荐医院
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $lng  = $this->request->param('lng','');// "经度"
        $lat  = $this->request->param('lat','');// "维度"

        //TODO '首页推荐显示个数'
        $limit_number = 3;

//        $lng = "117.169787";//经度
//        $lat = "39.106538";//维度
        $shopModel = new Shop();

        if (!empty($lng) && !empty($lat)){
            $field = "s.sid,s.status,s.shop_name,s.shop_phone,s.shop_address,s.shop_lng,s.shop_lat,s.shop_desc,s.shop_operating_time,(2 * 6378.137* ASIN(SQRT(POW(SIN(3.1415926535898*(".$lat."-shop_lat)/360),2)+COS(3.1415926535898*".$lat."/180)* COS(shop_lat * 3.1415926535898/180)*POW(SIN(3.1415926535898*(".$lng."-shop_lng)/360),2))))*1000 as distance";
            $order = "distance";

        }else{
            $field = "s.sid,s.status,s.shop_name,s.shop_phone,s.shop_address,s.shop_lng,s.shop_lat,s.shop_desc,s.shop_operating_time";
            $order = "s.created_at";
        }

        $res = $shopModel
            ->alias('s')
            ->join('shop_image si','si.sid = s.sid','LEFT')
            ->where('s.status',config('shop_status.in_business')['key'])
            ->group('s.sid')
            ->field('si.image')
            ->field($field)
            ->order($order)
            ->limit($limit_number)
            ->select();
        $res = json_decode(json_encode($res),true);

        if (!empty($res)) {
            $imageView = Env::get('QINIU_DENTIST_HOME_HOS');
            for ($i = 0; $i < count($res); $i++) {
                $image = $res[$i]['image'];
                $res[$i]['image'] = $image."?$imageView";
            }
        }

        if (!empty($lng) && !empty($lat)){
            /*处理距离排序数据  On*/
            $res = Distance::disposeDistance($res);
            /*处理距离排序数据  Off*/
        }

        $result['hospital'] = $res;

        if (!empty($res)) {
            $service = [];
            foreach ($res as $key => $val) {
                $sid = $res[$key]['sid'];
                $service_info = $this->recommendService($sid,$limit_number);
                $service      = array_merge_recursive($service,$service_info);
            }

            $result['service'] = $service;
        }

        return comReturn(true,config('return_message.success'),$result);
    }

    /**
     * 推荐服务
     * @param $sid
     * @param $limit_number
     * @return false|mixed|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function recommendService($sid,$limit_number)
    {
        $dtsGoodsModel = new DtsGoods();

        $column_list_gsh = $dtsGoodsModel->column_list_gsh;

        $res = $dtsGoodsModel
            ->alias('g')
            ->join('goods_category gc','gc.cat_id = g.cat_id','LEFT')
            ->join('goods_image gi','gi.gid = g.gid','LEFT')
            ->where('g.sid',$sid)
            ->where('g.status',config('goods.status')['in_sale']['key'])
            ->where('g.is_delete',0)
            ->order('g.is_top DESC,g.sort,g.sn,g.created_at DESC')
            ->group('g.gid')
            ->field('gc.cat_id,gc.cat_name')
            ->field($column_list_gsh)
            ->field('gi.image')
            ->limit($limit_number)
            ->select();
        $res = json_decode(json_encode($res),true);

        if (!empty($res)) {
            $imageView = Env::get('QINIU_DENTIST_HOME_SERVISE');
            for ($i = 0; $i < count($res); $i++) {
                $image = $res[$i]['image'];
                $res[$i]['image'] = $image."?$imageView";
            }
        }

        return $res;
    }
}