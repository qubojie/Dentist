<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/10/24
 * Time: 下午3:58
 */
namespace app\wechat\model;

use think\Model;

class BillOrderGoods extends Model
{
    protected $table = 'dts_bill_order_goods';

    protected $primaryKey = 'cid';
}