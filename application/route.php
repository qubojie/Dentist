<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/10/12
 * Time: 下午3:00
 */
use think\Route;
Route::group(['name' => 'admin' , 'prefix' => 'admin/'],function (){

    Route::group(['name' => 'adminUser'],function (){
        Route::get('index','adminUser.User/index');

    });
    Route::group(['name' => 'index'],function (){
        Route::get('index','Index/index');
        Route::post('adds','Index/add');
    });
});