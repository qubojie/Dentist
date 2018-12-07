<?php

/**
 * @Author: zhangtao
 * @Date:   2018-10-18 13:02:11
 * @Last Modified by:   admin
 * @Last Modified time: 2018-10-19 17:11:30
 */

namespace app\shopadmin\model;

use think\Model;

class Shop extends Model
{
    protected $table = 'dts_shop';

    protected $primaryKey = 'sid';

    public $column_details = [
        "sid",
        "eid",
        "status",
        "shop_name",
        "shop_phone",
        "shop_address",
        "shop_lng",
        "shop_lat",
        "shop_desc",
        "shop_operating_time",
        "shop_operating_time",
        "shop_operating_time",
    ];

}