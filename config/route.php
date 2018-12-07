<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/10/9
 * Time: 下午6:08
 */
use think\Route;
Route::rule("test","test/Index/index");
/*Route::group(['name' => 'admin' , 'prefix' => 'admin/'],function (){

    Route::group(['name' => 'adminUser'],function (){
        Route::get('index','adminUser.User/index');

    });
    Route::group(['name' => 'index'],function (){
        Route::get('index','Index/index');
        Route::post('adds','Index/add');
    });
});*/

//图片上传
Route::rule('imageUpload','services/ImageUpload/uploadLocal','post|options');

//获取系统设置指定key值
Route::rule('getSysSettingKey','services/PublicUse/getSysSettingKey','get|options');