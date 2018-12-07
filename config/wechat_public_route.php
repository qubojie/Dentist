<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/10/16
 * Time: 下午2:46
 */
use think\Route;

//公众号路由群组
Route::group(['name' => 'wechatPublic' , 'prefix' => 'wechatPublic/'],function (){

    //授权
    Route::group(['name' => 'auth'],function (){
        //微信授权
        Route::rule('wxLogin','WechatAuth/login','get|options');
        //获取授权信息
        Route::rule('getInfo','WechatAuth/getInfo','get|options');
        //发送验证码
        Route::rule('sendCode','AuthShop/sendCode','post|options');
        //验证验证码
        Route::rule('checkCode','AuthShop/checkCode','post|options');
    });

    //加盟
    Route::group(['name' => 'join'],function (){
        //授权id验证是否已加盟
        Route::rule('isApply','JoinIn/isApply','post|options');
        //申请加盟
        Route::rule('apply','JoinIn/apply','post|options');

    });
});