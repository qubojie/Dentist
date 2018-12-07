<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/10/15
 * Time: 下午2:01
 */
namespace app\admin\hook;

class Category
{
    function index()
    {
        echo "Category类型钩子中的index方法";
    }

    function index_7()
    {
        echo "Category类型钩子中的index方法,权重较低";
    }

    function getCode()
    {
        $code = getRandCode();
        if (empty($code)){
            echo comReturn(false,config("return_message.send_fail"));die;
        }
    }

    function jmToken()
    {
        jmToken(time());
    }
}