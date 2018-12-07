<?php

/**
 * @Author: zhangtao
 * @Date:   2018-10-18 13:02:11
 * @Last Modified by:   admin
 * @Last Modified time: 2018-10-31 16:20:03
 */

namespace app\shopadmin\model;

use think\Model;

class ShopAdminLog extends Model
{
    protected $table = 'dts_shop_admin_log';

    protected $primaryKey = 'log_id';

}