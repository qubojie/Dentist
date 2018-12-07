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

    //合伙人
    Route::group(['name' => 'partner'],function(){
        //获取分享签名
        Route::rule('getSignPackage','partner.JsSdk/getSignPackage','get|options');
        //授权登陆
        Route::rule('authLogin','partner.Auth/authLogin','post|options');
        //绑定信息,提交审核
        Route::rule('bindData','partner.Audit/bindData','post|options');
        //重新申请
        Route::rule('editApply','partner.Audit/editApply','post|options');
        //首页收益
        Route::rule('earnings','partner.Index/earnings','get|options');
        //好友列表
        Route::rule('offlineList','partner.OfflineUser/offlineList','get|options');
        //我的
        Route::rule('myInfo','partner.MyCenter/myInfo','get|options');
        //绑定手机号码
        Route::rule('bindPhone','partner.MyCenter/bindPhone','post|options');
        //解除绑定手机号码
        Route::rule('unbindPhone','partner.MyCenter/unbindPhone','post|options');
        //钱包预览
        Route::rule('walletPreview','partner.Wallet/walletPreview','get|options');
        //提现明细
        Route::rule('withdrawalDetail','partner.Wallet/withdrawalDetail','get|options');
        //提现到账账户列表
        Route::rule('withdrawalAccount','partner.Wallet/withdrawalAccount','get|options');
        //提现账户添加
        Route::rule('withdrawalAccountAdd','partner.Wallet/withdrawalAccountAdd','post|options');
        //提现到账账户编辑
        Route::rule('withdrawalAccountEdit','partner.Wallet/withdrawalAccountEdit','post|options');
        //获取余额以及提现状态信息
        Route::rule('getWithdrawalInfo','partner.Wallet/getWithdrawalInfo','get|options');
        //提现申请提交
        Route::rule('withdrawalPost','partner.Wallet/withdrawalPost','post|options');
        //重新申请提现
        Route::rule('withdrawalAgain','partner.Wallet/withdrawalAgain','post|options');
        //确认提现结果
        Route::rule('confirmResult','partner.Wallet/confirmResult','post|options');
    });

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
        //首页根据wxid判断加盟信息
        Route::rule('indexIsApply','JoinIn/indexIsApply','post|options');
        //授权id验证是否已加盟
        Route::rule('isApply','JoinIn/isApply','post|options');
        //申请加盟
        Route::rule('apply','JoinIn/apply','post|options');
        //重新提交加盟
        Route::rule('editApply','JoinIn/editApply','post|options');

    });
});