<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/10/23
 * Time: 上午10:28
 */
namespace app\wechat\model;

use think\Model;

class DtsGoods extends Model
{
    protected $table = 'dts_goods';

    protected $primaryKey = 'gid';

    public  $column_list = [
        'gid',
        'goods_name',
        'goods_sketch',
        'goods_original_price',
        'goods_price',
        'view_num',
    ];

    public  $column_list_gsh = [
        'g.gid',
        'g.goods_name',
        'g.goods_sketch',
        'truncate(g.goods_original_price,2) goods_original_price',
        'truncate(g.goods_price,2) goods_price',
        'g.view_num',
    ];
    public  $column_details = [
        'gid',
        'eid',
        'sid',
        'sn',
        'cat_id',
        'goods_name',
        'goods_sketch',
        'goods_original_price',
        'goods_price',
        'goods_content',
        'view_num',
    ];
    public  $column_details_gsh = [
        'g.gid',
        'g.eid',
        'g.sid',
        'g.sn',
        'g.cat_id',
        'g.goods_name',
        'g.goods_sketch',
        'truncate(g.goods_original_price,2) goods_original_price',
        'truncate(g.goods_price,2) goods_price',
        'g.goods_content',
        'g.view_num',
    ];
}