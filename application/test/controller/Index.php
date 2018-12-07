<?php
namespace app\test\controller;

use app\common\controller\SignValidation;

class Index
{
    const TOKEN = "DENTIST";

    public function index()
    {
        $timeStamp = "1539672965";
        $randomStr = "qubojie";

        $arr['timeStamp'] = $timeStamp;
        $arr['randomStr'] = $randomStr;
        $arr['token']     = self::TOKEN;
        //按照首字母大小写顺序排序
        sort($arr,SORT_STRING);
        //拼接成字符串
        $str = implode($arr);
        //进行加密
        $signature_me = sha1($str);
        $signature_me = md5($signature_me);
        //转换成大写
        $signature_me = strtoupper($signature_me);
        return $signature_me;
    }
}
