<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/10/16
 * Time: 下午3:09
 */
namespace app\services\controller;

use app\common\controller\CommonAuth;
use think\Env;
use think\Image;
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

//        $file = \request()->file("image");
        $file_base64 = $request->param('image','');

        if (empty($file_base64)){
            return comReturn(false,config('upload.choose_img'));
        }

        //处理单图上传
//        $res = $this->upload($file_path);
        $save_path = $file_path.date('Ymd',time()).'/';
        $res = $this->base64_upload($save_path);

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

        $info = $file->validate(['size' => 8192000,'ext' => 'jpg,png,jpeg,gif'])->move($save_path);

        if ($info){
            $image_info = $info->getFilename();
            $path_url = $save_path.date('Ymd',time()).'/'.$image_info;
            $text = '仅适用于牙闺蜜审核认证';
            $this->water($path_url,$text);//图片加水印
            $image_src[]["pic_src"] = $path_url;
            return comReturn(true,config('upload.success'),$image_src);
        }else{
            return comReturn(false,config('upload.over_big'));
        }
    }

    protected function base64_upload($save_path)
    {
        $ret = true;
        if (!file_exists($save_path)){
            $ret = @mkdir($save_path,0777,true);
        }
        if (!$ret){
            return comReturn(false,config('upload.created_path_fail'));
        }
        $base64_image = \request()->param('image');

        $base_img = str_replace('data:image/png;base64,', '', $base64_image);
        $base_img = str_replace('data:image/jpeg;base64,', '', $base_img);
        $base_img = str_replace('data:image/jpg;base64,', '', $base_img);

        $output_file = time().rand(100,999).'.png';
        $path_url = $save_path.$output_file;
        $ifp = fopen( $path_url, "wb" );
        fwrite( $ifp, base64_decode( $base_img) );
        fclose( $ifp );

        $text = '仅适用于牙闺蜜审核认证';
        $this->water($path_url,$text);//图片加水印
        $image_src[]["pic_src"] = $path_url;
        return comReturn(true,config('upload.success'),$image_src);
    }

    /**
     * 图片加水印
     * @param $image_url
     */
    public function water($image_url,$text)
    {
        $image = Image::open($image_url);
        // 返回图片的宽度
        $width = $image->width();
        // 返回图片的高度
        $height = $image->height();
        // 返回图片的类型
        $type = $image->type();
        // 返回图片的mime类型
        $mime = $image->mime();
        // 返回图片的尺寸数组 0 图片宽度 1 图片高度
        $size = $image->size();

//        $image->text($text."3",'static/pf.ttf','14','#D1CECA',Image::WATER_CENTER,0,30)->save($image_url);

        $image->tilewater(__PUBLIC__."static/images/sy.png","50")->save($image_url);

//        list($src_w,$src_h) = getimagesize(__PUBLIC__."static/images/sy.png");



    }

    /**
     * 移动文件至指定位置
     * @param $file_src
     * @param $eid
     * @param $name
     * @return bool|string
     */
    public function moveImage($file_src,$eid,$name = '')
    {
        $pic_name_arr = explode("/",$file_src);
        $pic_name     = end($pic_name_arr);
        $pic_ext_arr  = explode(".",$pic_name);
        $pic_ext      = $pic_ext_arr[1];
        $move_path    = Env::get("IMG_FILE_PATH").$eid."/";
        //移动文件
        if (!file_exists($move_path)){
            $ret =  @mkdir($move_path,0777,true);
        }else{
            $ret = true;
        }
        if ($ret){
            $is_ok =  copy($file_src,$move_path.$name.".".$pic_ext);
            if ($is_ok){
                $finish_path = $eid."/".$name.".".$pic_ext;
                @unlink($file_src);
                return $finish_path;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }
}