<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/10/9
 * Time: 下午6:07
 */
return [
// 是否开启路由
    'url_route_on'           => true,
    // 路由使用完整匹配
    'route_complete_match'   => false,
    // 路由配置文件（支持配置多个）
    'route_config_file'      => ['route' , 'admin_route' , 'wechat_public_route'],
    // 是否强制使用路由
    'url_route_must'         => true,

    'return_message' => [
        'unauthorized_access' => '非法访问',
        'signature_invalid'   => '签名无效',
        'success'             => '成功',
        'fail'                => '失败',
        'password_dif'        => '密码不匹配',
        'send_fail'           => '发送失败'
    ],

    //短信
    'sms' => [
        'send_success'   => '发送成功',
        'send_fail'      => '发送失败',
        'verify_success' => '验证成功',
        'verify_fail'    => '验证码不匹配',
    ],

    //上传
    'upload' => [
        'fail'              => '上传失败',
        'success'           => '上传成功',
        'choose_img'        => '请选择上传的图片',
        'created_path_fail' => '创建保存图片的路径失败',
        'over_big'          => '图片格式不正确或超过2M'
    ],

    //申请加盟
    'join' => [
        'status' => [
            'wait_check'  => ['key' => '0','name' => '待审核'],
            'check_ok'    => ['key' => '1','name' => '审核通过'],
            'check_error' => ['key' => '2','name' => '审核未通过'],
            'stop_use'    => ['key' => '3','name' => '暂停使用'],
            'cancelled'   => ['key' => '9','name' => '已注销'],
        ],
    ],
];