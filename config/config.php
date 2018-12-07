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
    'route_config_file'      => ['route' , 'admin_route' , 'wechat_public_route', 'shop_admin_route' , 'wechat_route'],
    // 是否强制使用路由
    'url_route_must'         => true,

    // 默认输出类型
    'default_return_type'    => 'json',

    //页大
    'page_size'              => 20,

    'xcx_page_size'          => 5,

    'default_password'       => '000000',

    'return_message' => [
        'unauthorized_access' => '非法访问',
        'signature_invalid'   => '签名无效',
        'success'             => '成功',
        'fail'                => '失败',
        'password_dif'        => '密码不匹配',
        'send_fail'           => '发送失败',
        'abnormal_action'     => '异常操作',
        'phone_exist'         => '电话号码已绑定其他账户',
        "purview_short"       => "权限不足",
        "param_not_empty"     => "非法操作",
        "get_success"         => "获取成功",
        "lack_param"          => "缺少参数",
        "please_bind_phone"   => "请绑定手机号码",

        "error_status_code" => [
            "ok"                   => ["key" => 10000, "value" => "成功"],
            "token_overdue"        => ["key" => 10001, "value" => "登陆超时，请重新登陆"],
            "token_invalid"        => ["key" => 10002, "value" => "登陆超时或账号在其他地方登陆，请重新登陆"],
            "lack_param_necessary" => ["key" => 10003, "value" => "缺少必要参数"],
            "lack_param"           => ["key" => 10004, "value" => "缺少参数"],
            "purview_short"        => ["key" => 10005, "value" => "权限不足"],
            "param_not_empty"      => ["key" => 10006, "value" => "非法操作"],
            "login_fail"           => ["key" => 10007, "value" => "登录失败，请核对账户名密码"],
            "operate_fail"         => ["key" => 10008, "value" => "操作失败"],
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | 系统设置配置
    |--------------------------------------------------------------------------
    */
    'sys_type' => [
        'sys'     => "系统设置",
        'order'   => "订单设置",
        'cash'    => "提现设置",
        'card'    => "会籍卡设置",
        'reserve' => "预约设置",
        'sms'     => "短信设置",
        'user'    => "用户设置",
    ],

    /*
   |--------------------------------------------------------------------------
   | 二维码规则配置
   |--------------------------------------------------------------------------
   */
    'qr_code' => [
        //前缀
        'prefix' => [
            '0' => ['key' => 'PARTNER' , 'name' => '合伙人推荐码'],
        ],
        //分隔符
        'delimiter' => ['key' => '=' , 'name' => '分隔符'],
        //进入页面
        'page'      => ['key' => 'pages/index/main' , 'name' => '进入页面'],
        //宽度
        'width'     => ['key' => '430' , 'name' => '宽度'],
        //小程序生成二维码是否透明底色 true OR false
        'is_hyaline'=> ['key' => true, 'name' => '是']
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
        'choose_file'       => '请选择上传的文件',
        'choose_img'        => '请选择上传的图片',
        'created_path_fail' => '创建保存图片的路径失败',
        'over_big'          => '图片格式不正确或超过2M',
        'type_error'        => '该类型不被允许',
    ],

    //申请加盟
    'join' => [
        'new_join' => ['key' => 'J001','name' => '请申请加盟'],
        'old_join' => ['key' => 'J002','name' => '已申请加盟'],

        'status' => [
            'wait_check'  => ['key' => '0','name' => '待审核'],
            'check_ok'    => ['key' => '1','name' => '审核通过'],
            'check_error' => ['key' => '2','name' => '审核未通过'],
            'stop_use'    => ['key' => '3','name' => '暂停使用'],
            'cancelled'   => ['key' => '9','name' => '已注销'],
        ],
    ],

    //合伙人
    'partner' => [
        //用户状态
        'status' => [
            'wait_check' => ['key' => '0' , 'name' => '待审核'],
            'check_ok'   => ['key' => '1' , 'name' => '审核通过'],
            'check_no'   => ['key' => '2' , 'name' => '审核未通过'],
            'stop_use'   => ['key' => '3' , 'name' => '暂停使用'],
            'cancelled'  => ['key' => '9' , 'name' => '已注销']
        ],
    ],

    //登录
    'login' => [
        'fail'              => '登录失败',
        'success'           => '登录成功',
        'overdue'           => '登录失效,请重新登陆',
        'fail_re'           => '登陆失败,请重试',
    ],

    //管理员状态
    'shop_admin' => [
        'has_phone'    => '已有此手机号',
        'has_username' => '已有此用户名',
        'status' => [
            'able'      => ['key' => '0','name' => '正常'],
            'unable'    => ['key' => '1','name' => '停用']
        ],
        'has_not_user'  => '无此用户',
        'is_not_super_admin' => '非店主不能修改密码',
    ],
    //店铺状态
    'shop' => [
        'has_shop_name' => '已有店铺名称',
        'status' => [
            'able'      => ['key' => '0','name' => '正常'],
            'unable'    => ['key' => '1','name' => '停用']
        ],
        'had_audit' => '已经审核过，不能重复审核',
    ],
    //商品
    'goods' =>[
        'status' => [
            'sold_out' => ['key' => '0' , 'name' => '下架'],
            'in_sale'  => ['key' => '1' , 'name' => '在售'],
            'disable'  => ['key' => '3' , 'name' => '禁用']
        ],
        'image_type' => [
            'gallery'      => ['key' => '0' , 'name' => '图库'],
            'share_figure' => ['key' => '1' , 'name' => '分享图'],
        ],
    ],

    'pay_type' => [
        'wxpay'  => ['key' => 'wx' , 'name' => '微信支付'],
        'alipay' => ['key' => 'alipay' ,'name' => '支付宝支付']
    ],

    //账户
    'account' => [
        //店铺明细账变更状态
        'shop_account_change_type' => [
            'user'  => ['key' => '0', 'name' => 'cus'],
            'admin' => ['key' => '1', 'name' => 'admin'],
            'sys'   => ['key' => '2', 'name' => 'sys'],
        ],

        //店铺明细操作类型
        'shop_account_action_type' => [
            'order_pay'                  => ['key' => '100' , 'name' => '订单支付完成'],//100 订单支付完成（冻结账户+）
            'order_complete'             => ['key' => '101' , 'name' => '订单交易完成'],//101 订单交易完成 （冻结账户- 余额账户+）
            'order_refund'               => ['key' => '109' , 'name' => '订单取消退款'],//109 订单取消退款 （冻结账户-）
            'platform_deduct_commission' => ['key' => '700' , 'name' => '平台扣除订单佣金'],//700 平台扣除订单佣金（余额账户-）
            'platform_deduct_poundage'   => ['key' => '705' , 'name' => '平台扣除提现手续费'],//705 平台扣除提现手续费（余额账户-）
            'start_withdraw'             => ['key' => '800' , 'name' => '提现'],//800 提现（冻结账户+  余额账户-）
            'end_withdraw'               => ['key' => '801' , 'name' => '提现完成'],//801 提现完成（冻结账户- 提现账户+）
            'fail_withdraw'              => ['key' => '802' , 'name' => '提现失败'],//802 提现失败 （冻结账户-  余额账户+)
            'insufficient_withdraw'      => ['key' => '803' , 'name' => '提现金额不足'],//803 提现金额不足
            'gt_withdraw'                => ['key' => '804' , 'name' => '提现金额必须大于'],//803 提现金额必须大于1
            'other'                      => ['key' => '900' , 'name' => '财务调整'],//900 账务调整
        ],

        //合伙人明细账变更状态
        'partner_account_change_type' => [
            'user'  => ['key' => '0', 'name' => 'cus'],
            'admin' => ['key' => '1', 'name' => 'admin'],
            'sys'   => ['key' => '2', 'name' => 'sys'],
        ],

        //合伙人明细账操作类型
        'partner_account_action_type' => [
            'order_pay'                  => ['key' => '100' , 'name' => '订单支付完成'],//100 订单支付完成（冻结账户+）
            'order_complete'             => ['key' => '101' , 'name' => '订单交易完成'],//101 订单交易完成 （冻结账户- 余额账户+）
            'order_refund'               => ['key' => '109' , 'name' => '订单取消退款'],//109 订单取消退款 （冻结账户-）
            'platform_deduct_commission' => ['key' => '700' , 'name' => '平台扣除订单佣金'],//700 平台扣除订单佣金（余额账户-）
            'platform_deduct_poundage'   => ['key' => '705' , 'name' => '平台扣除提现手续费'],//705 平台扣除提现手续费（余额账户-）
            'start_withdraw'             => ['key' => '800' , 'name' => '提现'],//800 提现（冻结账户+  余额账户-）
            'end_withdraw'               => ['key' => '801' , 'name' => '提现完成'],//801 提现完成（冻结账户- 提现账户+）
            'fail_withdraw'              => ['key' => '802' , 'name' => '提现失败'],//802 提现失败 （冻结账户-  余额账户+)
            'other'                      => ['key' => '900' , 'name' => '财务调整'],//900 账务调整
            'account_commission'         => ['key' => '500' , 'name' => '账户佣金收入'],//500 账户佣金收入（余额账户+）
        ],

        //提现账户类型
        'withdrawal_type' => [
            'wx'     => ['key' => 'wx' , 'name' => '微信'],
            'alipay' => ['key' => 'alipay' , 'name' => '支付宝'],
            'bank'   => ['key' => 'bank' , 'name' => '银行'],
        ],

        //提现单据状态
        'withdrawal_status' => [
            'wait_check'  => ['key' => '1' , 'name' => '提交待审核'],
            'success'     => ['key' => '2' , 'name' => '提现完成'],
            'fail'        => ['key' => '3' , 'name' => '提现失败'],
        ],

        //暂不支持的提现方式
        'not_support_withdrawal_type' => '暂不支持的提现方式',


    ],

    //订单
    'order' => [
        'order_error'          => '订单异常',
        'order_not_exist'      => '订单不存在',
        'order_payed'          => '订单已支付,请勿重复操作',
        'order_status_error'   => '订单状态异常,请勿支付',
        'order_canceled'       => '订单已取消',
        'can_pay'              => '请支付',
        'reserved_no_continue' => '已预约,不可重复预约',
        'status_no_reserve'    => '当前状态不可进行预约操作',
        'reserve_no_cancel'    => '当前状态不可取消预约',
        'status_no_cancel'     => '当前状态不可直接取消',
        'cancel_fail'          => '取消订单失败',

        //订单状态
        'sale_status' => [
            'wait_pay'    => ['key' => '0' , 'name' => '代付款'],
            'wait_use'    => ['key' => '1' , 'name' => '付款完成待使用'],
            'used'        => ['key' => '2' , 'name' => '已使用交易完成'],
            'user_cancel' => ['key' => '8' , 'name' => '用户手动取消'],
            'cancel'      => ['key' => '9' , 'name' => '交易取消']
        ],
        //订单详情状态
        'info_status' => [
            'no_pay'    => ['key' => '0' , 'name' => '未付款'],
            'wait_use'  => ['key' => '1' , 'name' => '待使用'],
            'used'      => ['key' => '2' , 'name' => '已使用'],
            'refund'    => ['key' => '3' , 'name' => '已退款']
        ],
        //预约状态
        'reserve_status' => [
            'wait_confirm'  => ['key' => '0' , 'name' => '待确认'],
            'confirmed'     => ['key' => '1' , 'name' => '已确认'],
            'to_the_shop'   => ['key' => '2' , 'name' => '到店'],
            'cancel'        => ['key' => '9' , 'name' => '已取消'],
        ],
        //订单取消人
        'cancel_user' => [
            'sys'     => ['key' => 'sys' ,     'name' => '系统'],
            'user'    => ['key' => 'user' ,    'name' => '用户手动取消'],
            'service' => ['key' => 'service' , 'name' => '后台'],
        ],
        //预约取消类型
        'reserve_cancel_type' => [
            'not_cancelled'  => ['key' => '0' , 'name' => '未取消'],
            'user_cancelled' => ['key' => '1' , 'name' => '用户取消'],
            'shop_cancelled' => ['key' => '2' , 'name' => '店铺取消'],
            'sys_cancelled'  => ['key' => '3' , 'name' => '系统取消'],
        ],

        //点击完成消费时提示消息
        'reserve_complete_error' => [
            '0' => '此预约还未确认',
            '1' => '用户还未到店',
            '3' => '此预约已完成',
        ],

        //预约时间上限
        'reserve_time_up_limit' => '8',
        //预约时间下限
        'reserve_time_down_limit' => '18',

        //订单列表类型
        'order_status' => [
            ['key' => '1' , 'name' => '待付款'],
            ['key' => '2' , 'name' => '付款待使用'],
            ['key' => '3' , 'name' => '已使用'],
            ['key' => '4' , 'name' => '已退款'],
            ['key' => '5' , 'name' => '已取消'],
        ],
    ],

    'user' => [
        'no_auth' => '您没有权限操作',
    ],

    //日志
    'log' => [
        'action_type' => [
            'reserve_confirm'      => '预约确认',
            'user_arrive_shop'     => '用户到店',
            'consumption_complete' => '消费完成',
            'reserve_cancel'       => '预约取消'
        ],
        'sys_admin_log_action' => [
            'e_verified'      => '企业认证完成',
            'e_unverified'    => '企业认证驳回',
            'p_verified'      => '合伙人认证完成',
            'p_unverified'    => '合伙人认证驳回',
        ]
    ],

    //素材库
    'material' => [
        'sc_have_child' =>  '改分类下存在素材,请先删除素材',
    ],


    //企业
    'enterprise' => [
        //企业审核类型
        'status' => [
            ['key' => '0' , 'name' => '待审核'],
            ['key' => '1' , 'name' => '已通过'],
            ['key' => '2' , 'name' => '未通过'],
            ['key' => '3' , 'name' => '停用'],
            ['key' => '9' , 'name' => '已注销'],
        ],
    ],

    //订单状态
    'order_status' => [
        //企业审核类型
        'status' => [
            ['key' => '1' , 'name' => '待付款'],
            ['key' => '2' , 'name' => '付款待使用'],
            ['key' => '3' , 'name' => '已使用'],
            ['key' => '4' , 'name' => '已退款'],
            ['key' => '5' , 'name' => '已取消'],
        ],
    ],


];