<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/10/25
 * Time: 上午10:36
 */
namespace app\wechat\model;

use think\Model;

class BillOrderReserve extends Model
{
    protected $table = 'dts_bill_order_reserve';

    protected $primaryKey = 'rid';
}