<?php
/**
 * 医院列表.
 * User: qubojie
 * Date: 2018/10/22
 * Time: 下午3:20
 */
namespace app\wechat\controller\hospital;

use app\common\controller\CommonAuth;
use app\common\controller\Distance;
use app\shopadmin\model\Shop;
use app\shopadmin\model\ShopDoctor;
use app\shopadmin\model\ShopImage;
use think\Env;
use think\Exception;
use think\Validate;

class Index extends CommonAuth
{
    /**
     * 医院列表
     * @return array
     */
    public function hospitalLists()
    {
        $lng       = $this->request->param('lng','');// "经度"
        $lat       = $this->request->param('lat','');// "维度"
        $page_size = $this->request->param("page_size",config('xcx_page_size'));//显示个数,不传时为10
        $now_page  = $this->request->param("now_page","1");
        if (empty($page_size)) $page_size = config('xcx_page_size');
        if (empty($now_page)) $now_page = 1;
//        $lng = "117.169787";//经度
//        $lat = "39.106538";//维度

        if (!empty($lng) && !empty($lat)){
            $field = "s.sid,s.eid,s.shop_name,s.shop_phone,s.shop_address,s.shop_desc,s.shop_operating_time,(2 * 6378.137* ASIN(SQRT(POW(SIN(3.1415926535898*(".$lat."-shop_lat)/360),2)+COS(3.1415926535898*".$lat."/180)* COS(shop_lat * 3.1415926535898/180)*POW(SIN(3.1415926535898*(".$lng."-shop_lng)/360),2))))*1000 as distance";
            $order = "distance";
        }else{
            $field = "s.sid,s.eid,s.shop_name,s.shop_phone,s.shop_address,s.shop_desc,s.shop_operating_time";
            $order = "s.created_at";
        }

        $config = [
            "page" => $now_page,
        ];

        try {
            $shopModel = new Shop();
            $res = $shopModel
                ->alias('s')
                ->join('shop_image si','si.sid = s.sid','LEFT')
                ->where("s.status",config('shop_status.in_business')['key'])
                ->group('s.sid')
                ->field('si.type image_type,si.title image_title,si.image shop_image')
                ->field($field)
                ->order($order)
                ->paginate($page_size,false,$config);
            $res = json_decode(json_encode($res),true);
            if (!empty($res)) {
                $data = $res['data'];
                $imageView = Env::get("QINIU_HOSTPAT_LIST");
                for ($i = 0; $i < count($data); $i ++) {
                    $shop_image = $data[$i]['shop_image'];
                    $data[$i]['shop_image'] = $shop_image."?$imageView";
                }
                $res['data'] = $data;
            }
            if (!empty($lng) && !empty($lat)){
                $data = $res['data'];
                /*处理距离排序数据,转为为 m和km可读性  On*/
                $res['data'] = Distance::disposeDistance($data);
                /*处理距离排序数据  Off*/
            }
            return comReturn(true,config('return_message.success'),$res);
        } catch (Exception $e) {
            return comReturn(false, $e->getMessage(), null, 500);
        }
    }

    /**
     * 医院详情
     * @return array
     */
    public function hospitalDetails()
    {
        $sid = $this->request->param("sid","");
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
        try {
            $shopModel = new Shop();

            $column_details = $shopModel->column_details;

            foreach ($column_details as $key => $val)
            {
                $column_details[$key] = "s.".$val;
            }

            $res = $shopModel
                ->alias('s')
                ->where('s.sid',$sid)
                ->field($column_details)
                ->find();

            $res = json_decode(json_encode($res),true);
            if (!empty($res)) {
                $shopImageModel = new ShopImage();
                $shop_image_res = $shopImageModel
                    ->where('sid',$sid)
                    ->order("sort")
                    ->field("image")
                    ->select();
                $shop_image_res = json_decode(json_encode($shop_image_res),true);
                $imageView = Env::get('QINIU_HOSTPAT_TITLE_IMG');
                $image_res = [];
                foreach ($shop_image_res as $key => $val) {
                    $image_res[$key] = $shop_image_res[$key]['image']."?$imageView";
                }
                $res['shop_image'] = $image_res;
            }

            return comReturn(true,config("return_message.success"),$res);
        } catch (Exception $e) {
            return comReturn(false, $e->getMessage(), null, 500);
        }
    }

    /**
     * 医护介绍
     * @return array
     */
    public function doctorIntroduce()
    {
        $sid = $this->request->param("sid","");

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

        try {
            $shopDoctorModel = new ShopDoctor();
            $column_details  = $shopDoctorModel->column_details;
            $res = $shopDoctorModel
                ->where('sid',$sid)
                ->where('is_enable',1)
                ->where('is_delete',0)
                ->order('sort')
                ->field($column_details)
                ->select();
            $res = json_decode(json_encode($res),true);
            if (!empty($res)) {
                $imageView = Env::get('QINIU_DOCTOR_AVATAR');
                for ($i  = 0; $i < count($res); $i ++) {
                    $doctor_img = $res[$i]['doctor_img'];
                    $res[$i]['doctor_img'] = $doctor_img."?$imageView";
                }
            }
            return comReturn(true,config("return_message.success"),$res);
        } catch (Exception $e) {
            return comReturn(false, $e->getMessage(), null, 500);
        }
    }
}