<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/10/15
 * Time: 下午12:07
 */
namespace app\admin\validate;

use think\Validate;

class User extends Validate
{
    //验证规则
    protected $rule = [
        'name' => 'require|max:25',
        'email' => 'email',
    ];

    //返回信息
    protected $message = [
        'name.require' => '名称不能为空',
        'name.max'     => '名称最多不超过25个字符',
        'email.email'  => '邮箱格式不正确'
    ];

    //验证场景
    protected $scene = [
        'add' => ['name','email'],
        'delete' => ['name'],
    ];
}