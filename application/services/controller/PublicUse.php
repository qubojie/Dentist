<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/11/6
 * Time: 下午4:46
 */
namespace app\services\controller;

use think\Controller;

class PublicUse extends Controller
{
    /**
     * 获取系统设置中指定key
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getSysSettingKey()
    {
        $keys = $this->request->param('key','');
        if (empty($keys)) {
            return comReturn(false,config('return_message.unauthorized_access'));
        }

        $res = $this->getSettingInfo($keys);

        return comReturn(true,config('return_message.success'),$res);
    }
}