<?php

/**
 * @Author: zhangtao
 * @Date:   2018-10-18 11:29:42
 * @Last Modified by:   admin
 * @Last Modified time: 2018-10-23 14:05:25
 */
namespace app\shopadmin\model;

use think\Model;

class ShopDoctor extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'dts_shop_doctor';

    protected $primaryKey = 'doc_id';

    public $column_details = [
        'doc_id',
        'doctor_name',
        'doctor_title',
        'doctor_duty',
        'doctor_img',
        'doctor_desc'
    ];
}