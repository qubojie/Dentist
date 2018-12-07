<?php
/**
 * 主页 Banner.
 * User: qubojie
 * Date: 2018/10/22
 * Time: 下午2:16
 */
namespace app\wechat\controller\homePage;

use app\common\controller\CommonAuth;
use app\wechat\model\HomeBanner;
use think\Env;

class Banner extends CommonAuth
{
    /**
     * Banner列表
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $homeBannerModel = new HomeBanner();

        $column = $homeBannerModel->column;

        $res = $homeBannerModel
            ->where('is_enable',1)
            ->where('is_delete',0)
            ->order('sort,created_at DESC')
            ->field($column)
            ->select();
        $res = json_decode(json_encode($res),true);

        if (!empty($res)) {
            $imageView = Env::get('QINIU_DENTIST_HOME_BANNER');

            for ($i = 0; $i < count($res); $i ++) {
                $banner_img = $res[$i]['banner_img'];
                $res[$i]['banner_img'] = $banner_img."?$imageView";
            }
        }


        return comReturn(true,config('return_message.success'),$res);
    }
}