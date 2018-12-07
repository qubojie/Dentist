<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/10/16
 * Time: 下午2:22
 */
namespace app\common\controller;

use think\Controller;
use think\exception\HttpException;
use think\Request;

class CommonAuth extends Controller
{
    /**
     * 初始化方法,其余控制器继承此方法，进行判断登录
     * @
     */
    public function _initialize()
    {
        parent::_initialize();
        $method = Request::instance()->method();

        if ( $method != "OPTIONS"){

            $timeStamp = Request::instance()->header("timeStamp","");
            $randomStr = Request::instance()->header("randomStr","");
            $signature = Request::instance()->header("signature","");

            if (empty($timeStamp) || empty($randomStr) || empty($signature)){
                throw new HttpException(403,config('return_message.unauthorized_access'));
            }

            $is_true = SignValidation::respond("$timeStamp","$randomStr","$signature");

            if (!$is_true){
                throw new HttpException(403,config('return_message.signature_invalid'));
            }
        }
    }
}