<?php
namespace app\wechat\controller;

use app\common\controller\CommonAuth;
use app\shopadmin\model\ShopTag;
use app\wechat\model\User;
use think\Validate;

class Index extends CommonAuth
{
    /**
     * 获取店铺标签
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getShopTag()
    {
        $tag_type = $this->request->param('tag_type','');//标签类型

        $shopTagModel = new ShopTag();

        $res = $shopTagModel
            ->where('tag_type',$tag_type)
            ->order('sort,created_at DESC')
            ->select();

        return comReturn(true,config('return_message.success'),$res);
    }

    /**
     * 刷新token
     * @return array
     */
    public function refreshToken()
    {
        $token = $this->request->header('Token','');

        $rule = [
            "Token|令牌" => "require",
        ];
        $check_data = [
            "Token" => $token,
        ];
        $validate = new Validate($rule);
        if (!$validate->check($check_data)){
            return comReturn(false,$validate->getError());
        }

        $userModel = new User();

        $new_token = jmToken(time().$token);

        $params = [
            'remember_token' => $new_token,
            'token_lastime'  => time(),
            'updated_at'     => time()
        ];

        $res = $userModel
            ->where('remember_token',$token)
            ->update($params);

        if ($res){
            return comReturn(true,config('return_message.success'),$params);
        }else{
            return comReturn(false,config('return_message.fail'));
        }
    }
}
