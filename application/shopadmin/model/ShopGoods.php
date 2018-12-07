<?php

/**
 * @Author: zhangtao
 * @Date:   2018-10-18 11:29:42
 * @Last Modified by:   admin
 * @Last Modified time: 2018-10-23 17:19:01
 */
namespace app\shopadmin\model;

use think\Model;

class ShopGoods extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'dts_goods';

    protected $primaryKey = 'gid';
}