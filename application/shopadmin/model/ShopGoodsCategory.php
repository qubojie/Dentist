<?php

/**
 * @Author: zhangtao
 * @Date:   2018-10-18 11:29:42
 * @Last Modified by:   admin
 * @Last Modified time: 2018-10-23 18:05:15
 */
namespace app\shopadmin\model;

use think\Model;

class ShopGoodsCategory extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'dts_goods_category';

    protected $primaryKey = 'cat_id';
}