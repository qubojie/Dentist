<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/10/9
 * Time: 下午6:08
 */

return [
    'hostname'        => \think\Env::get('DB_HOST'),
    // 数据库名
    'database'        => \think\Env::get('DB_DATABASE'),
    // 用户名
    'username'        => \think\Env::get('DB_USERNAME'),
    // 密码
    'password'        => \think\Env::get('DB_PASSWORD'),
    // 数据库表前缀
    'prefix'          => \think\Env::get('DB_PREFIX'),
    'hostport'        => \think\Env::get('DB_PORT'),
    // 数据库编码默认采用utf8
    'charset'         => 'utf8mb4',

];