<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/10/25
 * Time: 上午10:17
 */
namespace app\wechat\model;

use think\Model;

class HomeBanner extends Model
{
    protected $table = 'dts_home_banner';

    protected $primaryKey = 'id';

    public $column = [
        "id",
        "banner_title",
        "banner_img",
        "type",//banner 类型 0 用店铺  1商品分类  2 wap 链接
        "type_id",
        "link"
    ];
}