<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/10/23
 * Time: 下午4:25
 */
namespace app\admin\model;

use think\Model;

class SysResourceCategory extends Model
{
    protected $table = 'dts_sys_resource_category';

    protected $primaryKey = 'cat_id';
}