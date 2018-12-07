<?php

/**
 * @Author: zhangtao
 * @Date:   2018-10-18 11:29:42
 * @Last Modified by:   admin
 * @Last Modified time: 2018-11-02 17:37:49
 */
namespace app\shopadmin\model;

use think\Model;

class ShopAdmin extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'dts_shop_admin';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['user_name', 'password'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    // protected $hidden = ['password'];
}