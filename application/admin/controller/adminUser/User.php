<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/10/15
 * Time: 下午5:02
 */
namespace app\admin\controller\adminUser;

use app\admin\model\SysAdminUser;
use app\common\controller\DateBaseAction;
use app\common\controller\SendSms;
use app\wechatpublic\model\ShopEnterprise;
use think\Controller;
use think\Db;

class User extends Controller
{
    /**
     * 管理员列表
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $adminUserModel = new SysAdminUser();

        $res = $adminUserModel->lists();

        return comReturn(true,config("order.order_not_exist"),$res);
    }

    public function test()
    {
       $phone = "13253995313";
       $res = SendSms::send($phone);
       if (!$res){
           return comReturn(false,config(""));
       }
       return comReturn(true,config(""));
    }
}