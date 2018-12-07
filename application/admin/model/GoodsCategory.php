<?php

/**
 * @Author: zhangtao
 * @Date:   2018-11-21 17:04:52
 * @Last Modified by:   admin
 * @Last Modified time: 2018-11-21 17:05:54
 */

namespace app\admin\model;

use think\Model;

class GoodsCategory extends Model
{
    protected $table = 'dts_goods_category';

    protected $primaryKey = 'cat_id';
}