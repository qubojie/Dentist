<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/10/12
 * Time: 下午3:00
 */
use think\Route;

//管理后台路由群组
Route::group(['name' => 'shopAdmin' , 'prefix' => 'shopadmin/'],function (){

    Route::group(['name' => 'shopAdminManage'],function (){
        //登录
        Route::rule('login','Login/login','post|options');
        //发送验证码
        Route::rule('sendSms','Login/sendSms','post|options');
        //超级管理员重置密码
        Route::rule('resetPassword','Login/resetPassword','post|options');
        //管理后台首页
        Route::rule('index','Info/index', 'post|options');
        //添加店铺及管理员信息
        Route::rule('add','Info/addShop', 'post|options');
        //获取管理员及店铺简略信息
        Route::rule('getSimpleInfo','Info/getSimpleInfo', 'post|options');
        //编辑管理员信息
        Route::rule('editSimpleShopAdmin','Info/editSimpleShopAdmin', 'post|options');
        //编辑店铺简略信息
        Route::rule('editSimpleShop','Info/editSimpleShop', 'post|options');

        //菜单列表
        Route::rule('menuList','Menus/index', 'post|options');
        //获取店铺详细信息
        Route::rule('getShopDetail','Info/getShopDetail', 'post|options');
        //编辑店铺详细信息
        Route::rule('editShopDetail','Info/editShopDetail', 'post|options');

        //店铺开闭店
        Route::rule('setShopStatus','Info/setShopStatus', 'post|options');

        //普通管理员修改密码
        Route::rule('editPassword','Info/editPassword', 'post|options');

    });

    //医护人员
    Route::group(['name' => 'doctor'],function (){
        //医护人员列表（分页）
        Route::rule('doctorList','Doctor/index', 'post|options');
        //添加医护人员
        Route::rule('addDoctor','Doctor/addDoctor', 'post|options');
        //获取医护人员详细信息
        Route::rule('getDoctorDetail','Doctor/getDoctorDetail', 'post|options');
        //编辑医护人员信息
        Route::rule('editDoctor','Doctor/editDoctor', 'post|options');
        //删除医护
        Route::rule('delDoctor','Doctor/delDoctor', 'post|options');
        //切换医护状态
        Route::rule('enableDoctor','Doctor/enableDoctor', 'post|options');
        //改变医护排序
        Route::rule('sortDoctor','Doctor/sortDoctor', 'post|options');
    });

    //商品
    Route::group(['name' => 'goods'],function (){
        //服务商品列表（分页）
        Route::rule('goodsList','Goods/index', 'post|options');
        //添加服务商品
        Route::rule('addGoods','Goods/addGoods', 'post|options');
        //编辑服务商品
        Route::rule('editGoods','Goods/editGoods', 'post|options');
        //删除服务商品
        Route::rule('delGoods','Goods/delGoods', 'post|options');
        //商品置顶
        Route::rule('setGoodsTop','Goods/setGoodsTop', 'post|options');
        //改变商品状态
        Route::rule('enableGoods','Goods/enableGoods', 'post|options');
        //改变商品排序
        Route::rule('sortGoods','Goods/sortGoods', 'post|options');

        //服务商品分类
        Route::rule('getCategory','Goods/getCategory', 'post|options');
        //商铺下的医护
        Route::rule('getDoctorBySid','Doctor/getDoctorBySid', 'post|options');
    });

    //预约
    Route::group(['name' => 'reserve'],function (){
        //预约列表（分页）
        Route::rule('reserveList','Reserve/index', 'post|options');
        //确认预约
        Route::rule('reserveConfirm','Reserve/reserveConfirm', 'post|options');
        //用户到店
        Route::rule('userArrive','Reserve/userArrive', 'post|options');
        //消费完成
        Route::rule('consumptionComplete','Reserve/consumptionComplete', 'post|options');
        //取消预约
        Route::rule('reserveCancel','Reserve/reserveCancel', 'post|options');
    });

    //订单
    Route::group(['name' => 'order'],function (){
        //订单列表类型
        Route::rule('orderStatus','Order/orderStatus', 'post|options');
        //订单列表（分页）
        Route::rule('orderList','Order/index', 'post|options');
    });

    //统计
    Route::group(['name' => 'statistics'],function (){
        //订单列表（分页）
        Route::rule('statisticsList','Statistics/index', 'post|options');
    });

    //资产
    Route::group(['name' => 'asset'],function (){
        //店铺信息
        Route::rule('assetInfo','Asset/index', 'post|options');
        //近期交易记录
        Route::rule('dealRecord','Asset/dealRecord', 'post|options');
        //添加提现账户
        Route::rule('addWithdrawAccount','Asset/addWithdrawAccount', 'post|options');
        //删除提现账户
        Route::rule('delWithdrawAccount','Asset/delWithdrawAccount', 'post|options');
        //提现申请
        Route::rule('withdrawCash','Asset/withdrawCash', 'post|options');
    });

    //素材库
    Route::group(['name' => 'sourceMaterial'] , function (){
        //素材列表
        Route::rule('index','MaterialLibrary/index','post|options');
        //素材上传-后台传
        Route::rule('upload','MaterialLibrary/upload','post|options');
        //素材上传-前台传
        Route::rule('uploadFile','MaterialLibrary/uploadFile','post|options');
        //素材删除
        Route::rule('delete','MaterialLibrary/delete','post|options');
        //素材移动至新的分类
        Route::rule('moveMaterial','MaterialLibrary/moveMaterial','post|options');
        //素材分类列表
        Route::rule('categoryList','MaterialCategory/categoryList','post|options');
        //素材分类添加
        Route::rule('categoryAdd','MaterialCategory/categoryAdd','post|options');
        //素材分类删除
        Route::rule('categoryDelete','MaterialCategory/categoryDelete','post|options');
        //素材分类编辑
        Route::rule('categoryEdit','MaterialCategory/categoryEdit','post|options');

        //获取七牛Token
        Route::rule('getUploadToken','MaterialLibrary/getUploadToken','post|options');
    });

});