<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/10/23
 * Time: 下午2:46
 */
namespace app\common\controller;

use Qiniu\Auth;
use Qiniu\Storage\UploadManager;
use think\Controller;
use think\Env;
use think\Request;

vendor("qiniu.autoload");

class QiNiuUpload extends Controller
{
    /**
     * 七牛云上传
     * @param $doc  '上传文档容器名'
     * @param $prefix   '前缀'
     * @param $type '类型'
     * @return string
     * @throws \Exception
     */
    public function upload($doc,$prefix,$type)
    {
        $file = Request::instance()->file($doc);

        if (empty($file)){
            return $this->uploadReturn(false,config('upload.choose_file'));
        }

        // 要上传图片的本地路径
        $filePath = $file->getRealPath();

        //按照type判断允许的扩展名
        if ($type == '0'){
            $allowExt = 'jpeg,JPEG,jpg,JPG,png,PNG,bmp,BMP,gif,GIF,ico,ICO,pcx,PCX,tiff,TIFF,tga,TGA,exif,EXIF,fpx,FPX,svg,SVG';
        }elseif ($type == '1'){
            $allowExt = 'mov,3gp,mp4,flv,wmv,avi,rm,rmvb,mp3,aac,wma';
        }elseif ($type == '2'){
            $allowExt = 'doc,txt,xls';
        }

        //后缀
        $ext = pathinfo($file->getInfo('name'), PATHINFO_EXTENSION);
        if (!in_array($ext, explode(',',$allowExt))){
            return $this->uploadReturn(false,config('upload.type_error'));
        }

        //上传到七牛后保存的文件名
        $key = $prefix . "/" .substr(md5($file->getRealPath()), 0, 5) . date('YmdHis') . rand(0, 9999) . '.' . $ext;
        // 需要填写你的 Access Key 和 Secret Key
        $accessKey = Env::get("QINIU_ACCESS_KEY");
        $secretKey = Env::get("QINIU_SECRET_KEY");

        //构建鉴权对象
        $auth = new Auth($accessKey, $secretKey);

        //要上传的空间
        $bucket = Env::get("QINIU_IMG_BUCKET");
        //空间绑定的域名
        $domain = Env::get("QINIU_IMG_URL");

        $token = $auth->uploadToken($bucket);

        //初始化 UploadManager 对象并进行文件的上传
        $uploadMgr = new UploadManager();

        //调用 UploadManager 的 putFile 方法进行文件的上传
        list($ret, $err) = $uploadMgr->putFile($token, $key, $filePath);

        if ($err !== null){
            return $this->uploadReturn(false,$err);
        }

        $ret["size"] = $file->getInfo('size');
        $ret["url"] = $domain;
        $ret["extension"] = $ext;
        return $this->uploadReturn(true,config('upload.success'),$ret);
    }


    /**
     * 七牛云上传--区分平台端与商铺端
     * @param $doc  '上传文档容器名'
     * @param $prefix   '前缀'
     * @param $type '类型'
     * @param $sid 有sid时上传到商铺端 没有则上传到平台端
     * @return string
     * @throws \Exception
     */
    public function upload_ex($doc,$prefix,$type, $sid="")
    {
        $file = Request::instance()->file($doc);

        if (empty($file)){
            return $this->uploadReturn(false,config('upload.choose_file'));
        }

        // 要上传图片的本地路径
        $filePath = $file->getRealPath();

        //按照type判断允许的扩展名
        if ($type == '0'){
            $allowExt = 'jpeg,JPEG,jpg,JPG,png,PNG,bmp,BMP,gif,GIF,ico,ICO,pcx,PCX,tiff,TIFF,tga,TGA,exif,EXIF,fpx,FPX,svg,SVG';
        }elseif ($type == '1'){
            $allowExt = 'mov,3gp,mp4,flv,wmv,avi,rm,rmvb,mp3,aac,wma';
        }elseif ($type == '2'){
            $allowExt = 'doc,txt,xls';
        }

        //后缀
        $ext = pathinfo($file->getInfo('name'), PATHINFO_EXTENSION);
        if (!in_array($ext, explode(',',$allowExt))){
            return $this->uploadReturn(false,config('upload.type_error'));
        }

        //上传到七牛后保存的文件名
        $key = $prefix . "/" .substr(md5($file->getRealPath()), 0, 5) . date('YmdHis') . rand(0, 9999) . '.' . $ext;
        // 需要填写你的 Access Key 和 Secret Key
        $accessKey = Env::get("QINIU_ACCESS_KEY");
        $secretKey = Env::get("QINIU_SECRET_KEY");

        //构建鉴权对象
        $auth = new Auth($accessKey, $secretKey);

        //要上传的空间
        $bucket = Env::get("QINIU_IMG_BUCKET");
        //空间绑定的域名
        if (!empty($sid)) {
            $domain = Env::get("QINIU_IMG_URL");
        }else{
            $domain = Env::get("QINIU_SYS_URL");
        }

        $token = $auth->uploadToken($bucket);

        //初始化 UploadManager 对象并进行文件的上传
        $uploadMgr = new UploadManager();

        //调用 UploadManager 的 putFile 方法进行文件的上传
        list($ret, $err) = $uploadMgr->putFile($token, $key, $filePath);

        if ($err !== null){
            return $this->uploadReturn(false,$err);
        }

        $ret["size"] = $file->getInfo('size');
        $ret["url"] = $domain;
        $ret["extension"] = $ext;
        return $this->uploadReturn(true,config('upload.success'),$ret);
    }

    protected function uploadReturn($result,$message,$data = null)
    {
        return [
            "result"  => $result,
            "message" => $message,
            "data"    => $data
        ];
    }

    /**
     * 服务端直传七牛
     * @param $value
     * @param $prefix
     * @param $filePath
     * @return array
     * @throws \Exception
     */
    public function serverUpload($value,$prefix,$filePath)
    {
        // 需要填写你的 Access Key 和 Secret Key
        $accessKey = Env::get("QINIU_ACCESS_KEY");
        $secretKey = Env::get("QINIU_SECRET_KEY");

        //构建鉴权对象
        $auth = new Auth($accessKey, $secretKey);

        //要上传的空间
        $bucket = Env::get("QINIU_IMG_BUCKET");
        //空间绑定的域名
        $domain = Env::get("QINIU_IMG_URL");

        $token = $auth->uploadToken($bucket);

        //上传到七牛后保存的文件名
        $key = $prefix . "/" .$value . date('YmdHis') . rand(0, 9999) . '.png';

        //初始化 UploadManager 对象并进行文件的上传
        $uploadMgr = new UploadManager();

        //调用 UploadManager 的 putFile 方法进行文件的上传
        list($ret, $err) = $uploadMgr->putFile($token, $key, $filePath);

        if ($err !== null){
            return $this->uploadReturn(false,$err);
        }

        $url = "http://" . $domain . "/" . $ret['key'];

        //删除本地文件
        @unlink($filePath);

        return $this->uploadReturn(true,config('upload.success'),$url);
    }
}