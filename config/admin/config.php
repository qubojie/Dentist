<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/10/15
 * Time: 上午10:20
 */
return [
    // 'order' => [
    //     'order_not_exist' => '订单不存在'
    // ],

    'finance' => [
        'insufficient_adjust' => '余额不足，调账失败',
        'no_pid'              => '合伙人id不能为空',
        'no_sid'              => '店铺id不能为空',
    ],
    /*
     * 钩子
     *
     */
    // 是否开启钩子编译缓存，开启后只需要编译一次，以后都将成为惰性加载，如果安装了新的钩子，需要先调用Hook::clearCache() 清除缓存
//    'jntoo_hook_cache'=>false,
    // 钩子是否使用think钩子系统
//    'jntoo_hook_call'=>false ,
    /**
     * 某个文件夹下hook加载，配置文件方法实现
     * jntoo_hook_path => [
     *     [
     *          'path'=>'你的路径', // 路径尾部必须加斜杠 "/"
     *          'pattern'=> '规则,类的匹配规则' 例如：'/plugin\\\\module\\\\hook\\\\([0-9a-zA-Z_]+)/'
     *     ],
     *     ....
     * ]
     */
    /*'jntoo_hook_path'=>[
        [
            'path' => 'common/Hook/',
            'pattern' => '/plugin\\\\module\\\\hook\\\\([0-9a-zA-Z_]+)/'
        ],
    ],*/
    /**
     *  多模块目录下自动搜索，配置文件方法实现
     * 'jntoo_hook_plugin' => [
     *     [,
     *          'path'=>'你的app模块路径'
     *          'pattern'=> '规则,类的匹配规则' 例如：'/plugin\\\\([0-9a-zA-Z_]+)\\\\hook\\\\([0-9a-zA-Z_]+)/'
     *     ],
     *     ....
     * ]
     */
    /*'jntoo_hook_plugin'=>[
        [
            'path' => APP_PATH,
            'pattern' => '/plugin\\\\([0-9a-zA-Z_]+)\\\\hook\\\\([0-9a-zA-Z_]+)/'
        ]
    ],*/
];