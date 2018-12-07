<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/10/16
 * Time: 下午1:45
 */
namespace app\common\controller;

use think\Controller;

class SignValidation extends Controller
{
    const TOKEN = 'qubojie';

    /**
     * 加密验证
     * @param $timeStamp
     * @param $randomStr
     * @param $signature
     * @return bool
     */
    public static function respond($timeStamp,$randomStr,$signature)
    {
        $arr['timeStamp'] = $timeStamp;
        $arr['randomStr'] = $randomStr;
        $arr['token']     = self::TOKEN;
        //按照首字母大小写顺序排序 以value排序
        sort($arr,SORT_STRING);
        //拼接成字符串
        $str = implode($arr);
        //进行加密
        $signature_me = sha1($str);
        $signature_me = md5($signature_me);
        //转换成大写
        $signature_me = strtoupper($signature_me);

        if ($signature_me != $signature){
            return false;
        }
        return true;
    }
}