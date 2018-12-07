<?php

/**
 * @Author: zhangtao
 * @Date:   2018-10-18 13:02:11
 * @Last Modified by:   admin
 * @Last Modified time: 2018-11-01 17:33:16
 */

namespace app\shopadmin\model;

use think\Model;

class BillShopWithdrawals extends Model
{
    protected $table = 'dts_bill_shop_withdrawals';

    protected $primaryKey = 'shop_caid';

}