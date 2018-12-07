<?php
namespace app\admin\controller;

use think\Controller;
use app\common\Hook;

class Index extends Controller
{
    public function index()
    {
        Hook::run();
        Hook::call('Category' , 'getCode');

        return comReturn(true,config('return_message.password_dif'));
    }

    public function add()
    {
       $data = [
           'name'   => '薰儿',
           'age'    => '18',
           'height' => '170',
           'weight' => '98'
       ];
       return comReturn(true,config('return_message.success'),$data);
    }
}
