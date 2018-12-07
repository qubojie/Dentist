<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/10/12
 * Time: 下午3:00
 */
use think\Route;

//管理后台路由群组
Route::group(['name' => 'admin' , 'prefix' => 'admin/'],function (){

    //登录模块
    Route::group(['name' => 'adminLogin'],function (){
        Route::rule('login','adminLogin.Login/login','get|post|options');
    });

    //菜单模块
    Route::group(['name' => 'menu'],function (){
        //左侧菜单
        Route::rule('menuList','menu.Menus/index','post|options');
        //全部菜单
        Route::rule('allMenuList','menu.Menus/lists','post|options');
        //菜单小红点
        Route::rule('menuRedDot','menu.Menus/menuRedDot','post|options');
    });

    //首页模块
    Route::group(['name' => 'firstpage'],function (){
        //年月日统计数
        Route::rule('index','statistics.Index/index','get|post|options');
        //统计总数
        Route::rule('totalNum','statistics.Index/totalNum','get|post|options');
        //图表数据
        Route::rule('chartData','statistics.Index/chartData','get|post|options');
    });

    //客户模块
    Route::group(['name' => 'user'],function (){
        //客户列表
        Route::rule('userList','user.Lists/index','post|options');
        //客户详细信息
        Route::rule('userDetail','user.Detail/index','post|options');
    });

    //医院模块
    Route::group(['name' => 'enterprise'],function (){
        //医院列表
        Route::rule('enterpriseList','enterprise.Enterprise/index','post|options');
        //医院状态列表
        Route::rule('enterpriseStatus','enterprise.Enterprise/enterpriseStatus','post|options');
        //医院审核
        Route::rule('enterpriseAudit','enterprise.Enterprise/enterpriseAudit','post|options');
        //订单列表
        Route::rule('orderList','enterprise.Order/index','post|options');
        //订单状态列表
        Route::rule('orderStatus','enterprise.Order/orderStatus','post|options');
    });

    //合伙人模块
    Route::group(['name' => 'partner'],function (){
        //合伙人列表
        Route::rule('partnerList','partner.Lists/index','post|options');
        //合伙人详细信息
        Route::rule('partnerDetail','partner.Detail/index','post|options');
        //合伙人状态
        Route::rule('partnerStatus','partner.Lists/partnerStatus','post|options');
        //合伙人审核
        Route::rule('partnerAudit','partner.Audit/partnerAudit','post|options');
    });

    //财务模块
    Route::group(['name' => 'finance'],function (){
        //合伙人提现状态
        Route::rule('partnerWithdrawalsStatus','finance.PartnerFinance/partnerWithdrawalsStatus','post|options');
        //合伙人提现列表
        Route::rule('partnerWithdrawalsList','finance.PartnerFinance/index','post|options');
        //合伙人提现审核
        Route::rule('partnerWithdrawalsAudit','finance.PartnerFinance/partnerWithdrawalsAudit','post|options');
        //店铺提现列表
        Route::rule('shopWithdrawalsList','finance.ShopFinance/index','post|options');
        //店铺提现审核
        Route::rule('shopWithdrawalsAudit','finance.ShopFinance/shopWithdrawalsAudit','post|options');
        //合伙人列表
        Route::rule('partnerAccountList','finance.PartnerFinance/partnerAccountList','post|options');
        //合伙人资金调整
        Route::rule('partnerAccountAdjust','finance.PartnerFinance/partnerAccountAdjust','post|options');
        //合伙人钱包明细
        Route::rule('partnerAccountDetail','finance.PartnerFinance/partnerAccountDetail','post|options');
        //店铺列表
        Route::rule('shopAccountList','finance.ShopFinance/shopAccountList','post|options');
        //店铺资金调整
        Route::rule('shopAccountAdjust','finance.ShopFinance/shopAccountAdjust','post|options');
        //店铺钱包明细
        Route::rule('shopAccountDetail','finance.ShopFinance/shopAccountDetail','post|options');
        //ceshi
        Route::rule('ceshi','finance.PartnerFinance/ceshi','post|options');
    });


    //系统设置
    Route::group(['name' => 'system'],function (){
        //管理员列表
        Route::rule('adminUserList','adminUser.AdminUser/index','get|post|options');
        //添加管理员
        Route::rule('addAdminUser','adminUser.AdminUser/add','get|post|options');
        //编辑管理员
        Route::rule('editAdminUser','adminUser.AdminUser/edit','get|post|options');
        //删除管理员
        Route::rule('delAdminUser','adminUser.AdminUser/delete','get|post|options');

        //角色列表
        Route::rule('adminRoleList','adminRole.Roles/index','get|post|options');
        //添加角色
        Route::rule('addAdminRole','adminRole.Roles/add','get|post|options');
        //编辑角色
        Route::rule('editAdminRole','adminRole.Roles/edit','get|post|options');
        //删除角色
        Route::rule('delAdminRole','adminRole.Roles/delete','get|post|options');

        //系统类型设置
        Route::rule('settingTypeList','system.Setting/settingTypeList','get|post|options');
        //系统设置
        Route::rule('settingList','system.Setting/settingList','get|post|options');
        //编辑系统设置
        Route::rule('editSetting','system.Setting/edit','get|post|options');
        //添加系统设置
        Route::rule('addSetting','system.Setting/add','get|post|options');

        //系统日志
        Route::rule('sysLogList','system.Log/sysLogList','get|post|options');


        //下拉列表
        Route::rule('selectList','system.SelectList/index','get|post|options');
    });

    //素材库
    Route::group(['name' => 'sourceMaterial'] , function (){
        //素材列表
        Route::rule('index','matreial.MaterialLibrary/index','post|options');
        //素材上传-后台传
        Route::rule('upload','matreial.MaterialLibrary/upload','post|options');
        //素材上传-前台传
        Route::rule('uploadFile','matreial.MaterialLibrary/uploadFile','post|options');
        //素材删除
        Route::rule('delete','matreial.MaterialLibrary/delete','post|options');
        //素材移动至新的分类
        Route::rule('moveMaterial','matreial.MaterialLibrary/moveMaterial','post|options');
        //素材分类列表
        Route::rule('categoryList','matreial.MaterialCategory/categoryList','post|options');
        //素材分类添加
        Route::rule('categoryAdd','matreial.MaterialCategory/categoryAdd','post|options');
        //素材分类删除
        Route::rule('categoryDelete','matreial.MaterialCategory/categoryDelete','post|options');
        //素材分类编辑
        Route::rule('categoryEdit','matreial.MaterialCategory/categoryEdit','post|options');

        //获取七牛Token
        Route::rule('getUploadToken','matreial.MaterialLibrary/getUploadToken','post|options');
    });


    //小程序设置
    Route::group(['name' => 'smallProgram'],function (){
        //分类列表
        Route::rule('categoryList','smallProgram.Category/index','post|options');
        //添加分类
        Route::rule('addCategory','smallProgram.Category/addCategory','post|options');
        //编辑分类
        Route::rule('editCategory','smallProgram.Category/editCategory','post|options');
        //删除分类
        Route::rule('delCategory','smallProgram.Category/delCategory','post|options');
        //改变分类启用状态
        Route::rule('enableCategory','smallProgram.Category/enableCategory','post|options');
        //改变分类启用状态
        Route::rule('sortCategory','smallProgram.Category/sortCategory','post|options');

        //banner列表
        Route::rule('homeBannerList','smallProgram.Banner/index','post|options');
        //添加banner
        Route::rule('addHomeBanner','smallProgram.Banner/addHomeBanner','post|options');
        //编辑banner
        Route::rule('editHomeBanner','smallProgram.Banner/editHomeBanner','post|options');
        //删除banner
        Route::rule('delHomeBanner','smallProgram.Banner/delHomeBanner','post|options');
        //改变banner状态
        Route::rule('enableHomeBanner','smallProgram.Banner/enableHomeBanner','post|options');
        //改变banner排序
        Route::rule('sortHomeBanner','smallProgram.Banner/sortHomeBanner','post|options');
    });

});