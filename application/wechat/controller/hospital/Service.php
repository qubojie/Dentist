<?php
/**
 * 服务.
 * User: qubojie
 * Date: 2018/10/22
 * Time: 下午3:21
 */
namespace app\wechat\controller\hospital;

use app\common\controller\CommonAuth;
use app\wechat\model\DtsGoods;
use app\wechat\model\DtsGoodsImage;
use think\Env;
use think\Validate;

class Service extends CommonAuth
{
    /**
     * 服务列表
     * @return string
     * @throws \think\exception\DbException
     */
    public function serviceList()
    {
        $sid       = $this->request->param('sid','');//医院id
        $page_size = $this->request->param("page_size",config('xcx_page_size'));//显示个数,不传时为10
        $now_page  = $this->request->param("now_page","1");

        if (empty($page_size)) $page_size = config('xcx_page_size');
        if (empty($now_page)) $now_page   = 1;

        $config = [
            "page" => $now_page,
        ];

        $rule = [
            "sid|医院" => "require",
        ];
        $check_data = [
            "sid" => $sid,
        ];
        $validate = new Validate($rule);
        if (!$validate->check($check_data)){
            return comReturn(false,$validate->getError());
        }

        $dtsGoodsModel = new DtsGoods();

        $column_list_gsh = $dtsGoodsModel->column_list_gsh;

        $res = $dtsGoodsModel
            ->alias('g')
            ->join('goods_category gc','gc.cat_id = g.cat_id','LEFT')
            ->join('goods_image gi','gi.gid = g.gid','LEFT')
            ->where('g.sid',$sid)
            ->where('g.status',config('goods.status')['in_sale']['key'])
            ->where('g.is_delete',0)
            ->group('g.gid')
            ->order('g.is_top DESC,g.sort,g.sn,g.created_at DESC')
            ->field('gc.cat_id,gc.cat_name')
            ->field($column_list_gsh)
            ->field('gi.image')
            ->paginate($page_size,false,$config);

        $res = json_decode(json_encode($res),true);
        if (!empty($res)) {
            $data = $res['data'];
            $imageView = Env::get("QINIU_DENTIST_SERVICE_IMG");
            for ($i = 0; $i < count($data); $i ++) {
                $image = $data[$i]['image'];
                $data[$i]['image'] = $image."?$imageView";
            }

            $res['data'] = $data;
        }

        return comReturn(true,config('return_message.success'),$res);
    }

    /**
     * 服务详情
     * @return string
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function serviceDetails()
    {
        $gid = $this->request->param('gid','');//商品id
        $rule = [
            "gid|商品" => "require",
        ];
        $check_data = [
            "gid" => $gid,
        ];
        $validate = new Validate($rule);
        if (!$validate->check($check_data)){
            return comReturn(false,$validate->getError());
        }

        $dtsGoodsModel = new DtsGoods();

        $column_details_gsh = $dtsGoodsModel->column_details_gsh;

        $res = $dtsGoodsModel
            ->alias('g')
            ->join('shop s','s.sid = g.sid')
            ->where('g.gid',$gid)
            ->where('g.status',config('goods.status')['in_sale']['key'])
            ->where('g.is_delete',0)
            ->field('s.shop_name')
            ->field($column_details_gsh)
            ->find();

        $res = json_decode(json_encode($res),true);

        if (!empty($res)){
            $goodsImageModel = new DtsGoodsImage();
            $goods_image = $goodsImageModel
                ->where('gid',$gid)
                ->where('type',config('goods.image_type')['gallery']['key'])
                ->order('sort_id')
                ->field('image')
                ->select();
            if (!empty($goods_image)) {
                $imageView = Env::get("QINIU_DENTIST_SERVICE_BAN");
                for ($i  = 0; $i < count($goods_image); $i ++) {
                    $image = $goods_image[$i]['image'];
                    $goods_image[$i]['image'] = "$image"."?$imageView";
                }
            }
            $res['goods_image'] = $goods_image;
        }

        /*记录点击量 On*/
        $dtsGoodsModel
            ->where('gid',$gid)
            ->setInc('view_num');
        /*记录点击量 Off*/

        return comReturn(true,config('return_message.success'),$res);
    }
}