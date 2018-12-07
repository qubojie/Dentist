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

    Route::group(['name' => 'adminUser'],function (){
        Route::get('index','adminUser.User/index');
        Route::get('test','adminUser.User/test');

    });
    Route::group(['name' => 'index'],function (){
        Route::get('index','Index/index');
        Route::post('adds','Index/add');
    });
});