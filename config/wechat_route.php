<?php
/**
 * 客户小程序路由群组.
 * User: qubojie
 * Date: 2018/10/22
 * Time: 下午1:42
 */
use think\Route;

Route::group(['name' => 'wechat' ,  'prefix' => 'wechat/'],function (){
    Route::rule('wechatLogin','ThirdLogin/wechatLogin');
    //
    Route::rule('getJmInfo','ThirdLogin/getJmInfo','post|options');
    //获取店铺标签
    Route::rule('getShopTag','Index/getShopTag','get|options');
    //刷新token
    Route::rule('refreshToken','Index/refreshToken','post|options');
    //支付
    Route::group(['name' => 'pay'] , function (){
        //小程序支付
        Route::rule('smallApp','WechatPay/smallApp');
        //H5支付
        Route::rule('h5Pay','WechatPay/h5Pay');
        //公众号支付
        Route::rule('publicPay','WechatPay/publicPay');
        //扫码支付
        Route::rule('scavengingPay','WechatPay/scavengingPay');
        //异步回调地址
        Route::rule('notify','WechatPay/notify');
    });

    //授权
    Route::group(['name' => 'auth'],function (){
        //微信授权
        Route::rule('wxLogin','WechatAuth/login','get|options');
        //获取授权信息
        Route::rule('getInfo','WechatAuth/getInfo','get|options');
    });

    //主页
    Route::group(['name' => 'homePage'] , function (){
        //首页Banner
        Route::rule('banner','homePage.Banner/index','get|options');
        //首页分类
        Route::rule('classify','homePage.Classify/index','get|options');
        //筛选数据
        Route::rule('filterData','homePage.Classify/filterData','post|options');
        //首页推荐医院
        Route::rule('recommendHospital','homePage.RecommendHospital/index','get|options');
        //首页推荐服务
        Route::rule('recommendService','homePage.RecommendService/index','get|options');
    });

    //医院
    Route::group(['name' => 'hospital'], function (){
        //医院列表
        Route::rule('hospitalLists','hospital.Index/hospitalLists','post|options');
        //医院详情
        Route::rule('hospitalDetails','hospital.Index/hospitalDetails','get|options');
        //医护介绍
        Route::rule('doctorIntroduce','hospital.Index/doctorIntroduce','get|options');
        //服务列表
        Route::rule('serviceList','hospital.Service/serviceList','post|options');
        //服务详情
        Route::rule('serviceDetails','hospital.Service/serviceDetails','get|options');
        //预约提交
        Route::rule('appointment','hospital.Service/appointmentPost','post|options');
    });

    //订单或者预约
    Route::group(['name' => 'order'] , function (){
        //创建订单
        Route::rule('createdOrder','order.Order/createdOrder','post|options');
        //获取时间选择区间
        Route::rule('getReservationTime','order.Reservation/getReservationTime','get|options');
        //预约提交
        Route::rule('reservationPost','order.Reservation/reservationPost','post|options');
        //获取预约单据详情
        Route::rule('reservationDetails','order.Reservation/reservationDetails','get|options');
        //修改预约
        Route::rule('editReservation','order.Reservation/editReservation','post|options');
        //取消预约
        Route::rule('cancelReservation','order.Reservation/cancelReservation','post|options');
        //TODO 模拟支付回调接口
        Route::rule('callBackMn','order.Order/callBackMn','post|options');
        //取消未支付订单
        Route::rule('cancelOrder','order.Order/cancelOrder','post|options');
        //退款
        Route::rule('refund','order.Order/refund','post|options');
        //oid获取订单信息
        Route::rule('getOrderInfo','order.Order/getOrderInfo','get|options');
    });

    //我的
    Route::group(['name' => 'myCenter'] , function (){
        //我的信息
        Route::rule('myInfo','myCenter.Index/myInfo','get|options');
        //绑定手机号码
        Route::rule('bindPhone','myCenter.Index/bindPhone','post|options');
        //解除绑定手机号
        Route::rule('unbindPhone','myCenter.Index/unbindPhone','post|options');
        //订单列表
        Route::rule('orderList','myCenter.Order/orderList','get|options');
        //订单详情
        Route::rule('orderDetails','myCenter.Order/orderDetails','get|options');
    });
});