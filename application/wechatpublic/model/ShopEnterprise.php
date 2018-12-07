<?php
/**
 * 店铺企业信息表.
 * User: qubojie
 * Date: 2018/10/16
 * Time: 下午3:51
 */
namespace app\wechatpublic\model;

use think\Model;

class ShopEnterprise extends Model
{
    protected $table = 'dts_shop_enterprise';

    protected $primaryKey = 'eid';

    public $timestamps = false;

}