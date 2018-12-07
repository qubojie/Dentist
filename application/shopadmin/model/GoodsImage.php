<?php

/**
 * @Author: zhangtao
 * @Date:   2018-10-18 11:29:42
 * @Last Modified by:   admin
 * @Last Modified time: 2018-10-24 17:43:59
 */
namespace app\shopadmin\model;

use think\Model;

class GoodsImage extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'dts_goods_image';

    protected $primaryKey = 'gid,type,sort_id';
}