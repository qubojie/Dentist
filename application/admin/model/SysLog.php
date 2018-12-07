<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/10/15
 * Time: 下午6:59
 */
namespace app\admin\model;

use think\Model;

class SysLog extends Model
{

    protected $table = 'dts_sys_log';

    protected $primaryKey = 'log_id';
}