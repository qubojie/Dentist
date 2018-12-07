<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/10/12
 * Time: 下午4:26
 */
namespace app\admin\behavior;

use traits\controller\Jump;

class CheckToken
{
    use Jump;
    public function run(&$params)
    {
        dump(123);die;
    }
}