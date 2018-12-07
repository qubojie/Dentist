<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/10/16
 * Time: 下午3:09
 */
namespace app\services\controller;

use app\common\controller\CommonAuth;
use think\Request;

class ImageUpload extends CommonAuth
{
    /**
     * 图片上传至本地
     * @param Request $request
     * @return array|string
     */
    public function uploadLocal(Request $request)
    {
        $type = $request->param('type','1');//1,身份证上传
        if ($type == 1){
            //身份证上传
            $file_path = 'upload/user_card/';

        }elseif ($type == 2){
            $file_path = 'upload/business_license/';
        }else{
            return comReturn(false,config("return_message.return_message"));
        }

        $file = \request()->file("image");

        if (empty($file)){
            return comReturn(false,config('upload.choose_img'));
        }

        //处理单图上传
        $res = $this->upload($file_path);

        return $res;

    }

    /**
     * 单图上传
     * @param $save_path
     * @return array
     */
    protected  function upload($save_path)
    {
        $ret = true;
        if (!file_exists($save_path)){
            $ret = @mkdir($save_path,0777,true);
        }
        if (!$ret){
            return comReturn(false,config('upload.created_path_fail'));
        }

        $file = \request()->file('image');

        $info = $file->validate(['size' => 2048000,'ext' => 'jpg,png,jpeg,gif'])->move($save_path);

        if ($info){
            $image_info = $info->getFilename();
            $image_src[]["pic_src"] = $save_path.date('Ymd',time()).'/'.$image_info;
            return comReturn(true,config('upload.success'),$image_src);
        }else{
            return comReturn(false,config('upload.over_big'));
        }
    }
}