<?php
/**
 * 定时任务方法定义.
 * User: qubojie
 * Date: 2018/10/12
 * Time: 下午2:42
 */
$crond_list = array(
    '*' => [
//        'app\index\controller\ChangeStatus::changeOrderStatus',
    ],//每分钟执行
    '00:00'      => [
//        'app\index\controller\ChangeStatus::AutoDeleteCallMessage',
    ],  //每周执行
    '*-01 00:00' => [],  //每月--------
    '*:00'       => [],  //每小时---------
);

return $crond_list;