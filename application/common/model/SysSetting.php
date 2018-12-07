<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/11/6
 * Time: 下午3:34
 */
namespace app\common\model;

use think\Model;

class SysSetting extends Model
{
    protected $table = 'dts_sys_setting';

    protected $primaryKey = 'key';
    protected $primaryKey2 = 'ktype';
}