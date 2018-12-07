<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/10/23
 * Time: 下午4:06
 */
namespace app\shopadmin\model;

use think\Model;

class ResourceCategory extends Model
{
    protected $table = 'dts_resource_category';

    protected $primaryKey = 'cat_id';
}