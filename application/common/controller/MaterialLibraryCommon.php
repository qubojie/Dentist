<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/10/23
 * Time: 下午4:11
 */
namespace app\common\controller;

use Qiniu\Auth;
use app\admin\model\SysResourceFile;
use app\shopadmin\model\ResourceFile;
use think\Controller;
use think\Db;
use think\Exception;
use think\Env;
use think\Validate;
use think\Cache;

vendor("qiniu.autoload");

class MaterialLibraryCommon extends Controller
{
    /**
     * 素材列表
     * @param $type
     * @param $cat_id
     * @param $page_size
     * @param $now_page
     * @param string $sid
     * @return string|\think\Paginator
     * @throws \think\exception\DbException
     */
    public function index($type,$cat_id,$page_size,$now_page,$sid = "")
    {
        if (empty($page_size)) $page_size = config('page_size');
        if (empty($now_page))  $now_page  = 1;

        $rule = [
            "type|类型"     => "require",
            "cat_id|分类id" => "require",
        ];
        $check_data = [
            "type"    => $type,
            "cat_id"  => $cat_id,
        ];
        $validate = new Validate($rule);
        if (!$validate->check($check_data)){
            return comReturn(false,$validate->getError());
        }

        $cat_where['cat_id'] = ['eq',$cat_id];
        $where['type']       = ['eq',$type];

        $config = [
            "page" => $now_page,
        ];

        if (!empty($sid)){
            //商户
            $resourceFileModel = new ResourceFile();
            $where_s['sid'] = ['eq',$sid];
        }else{
            //平台
            $resourceFileModel = new SysResourceFile();
            $where_s = [];
        }
        $res = $resourceFileModel
            ->where($where_s)
            ->where($where)
            ->where($cat_where)
            ->order('sort')
            ->paginate($page_size,false,$config);

        return comReturn(true,config("params.SUCCESS"),$res);
    }

    /**
     * 素材上传-后台传
     * @param $type
     * @param $cat_id
     * @param $prefix
     * @param string $sid
     * @return string
     * @throws \Exception
     */
    public function upload($type,$cat_id,$prefix,$sid = "")
    {
        $genre  = 'file';//上传的文件容器参数名称

        //验证
        $rule = [
            "type|素材类型"    => "require",
            "cat_id|分类id"   => "number",
        ];
        $check_data = [
            "type"   => $type,
            "cat_id" => $cat_id,
        ];

        $validate = new Validate($rule);
        if (!$validate->check($check_data)){
            return comReturn(false,$validate->getError());
        }

        $qiNiuUpload = new QiNiuUpload();
        $upload = $qiNiuUpload->upload_ex("$genre","$prefix","$type", $sid);

        if (isset($upload['result']) && !$upload['result']){
            return comReturn(false, $upload['message']);
        }

        $link           = 'http://' . $upload['data']['url'] . '/' . $upload['data']['key'];

        $file_size      = $upload['data']['size'];
        $file_extension = $upload['data']['extension'];

        if (!empty($sid)){
            $params = [
                'sid'            => $sid,
                'cat_id'         => $cat_id,
                'type'           => $type,
                'link'           => $link,
                'file_size'      => $file_size,
                'file_extension' => $file_extension,
                'sort'           => '500',
                'created_at'     => time(),
                'updated_at'     => time(),
            ];

            $resourceFileModel = new ResourceFile();

            $res = $resourceFileModel
                ->insert($params);
        }else{
            $params = [
                'cat_id'         => $cat_id,
                'type'           => $type,
                'link'           => $link,
                'file_size'      => $file_size,
                'file_extension' => $file_extension,
                'sort'           => '500',
                'created_at'     => time(),
                'updated_at'     => time(),
            ];

            $resourceFileModel = new SysResourceFile();

            $res = $resourceFileModel
                ->insert($params);
        }

        if ($res !== false){
            return comReturn(true, config("return_message.success"));
        }

        return comReturn(false, config("return_message.fail"));
    }

    /**
     * 素材上传-前台传
     * @param $type
     * @param $cat_id
     * @param $prefix
     * @param string $sid
     * @return string
     * @throws \Exception
     */
    public function uploadFile($type,$cat_id,$link,$file_size,$file_extension,$sid = "")
    {
        //验证
        $rule = [
            "type|素材类型"             => "require",
            "link|链接"                 => "require",
            "file_size|文件大小"        => "require",
            "file_extension|文件扩展名" => "require",
            "cat_id|分类id"             => "number",
        ];
        $check_data = [
            "type"           => $type,
            "link"           => $link,
            "file_size"      => $file_size,
            "file_extension" => $file_extension,
            "cat_id"         => $cat_id,
        ];

        $validate = new Validate($rule);
        if (!$validate->check($check_data)){
            return comReturn(false,$validate->getError());
        }

        //空间绑定的域名
        if (!empty($sid)) {
            $link = 'http://'.Env::get("QINIU_IMG_URL")."/".$link;
        }else{
            $link = 'http://'.Env::get("QINIU_SYS_URL")."/".$link;
        }

        if (!empty($sid)){
            $params = [
                'sid'            => $sid,
                'cat_id'         => $cat_id,
                'type'           => $type,
                'link'           => $link,
                'file_size'      => $file_size,
                'file_extension' => $file_extension,
                'sort'           => '500',
                'created_at'     => time(),
                'updated_at'     => time(),
            ];

            $resourceFileModel = new ResourceFile();

            $res = $resourceFileModel
                ->insert($params);
        }else{
            $params = [
                'cat_id'         => $cat_id,
                'type'           => $type,
                'link'           => $link,
                'file_size'      => $file_size,
                'file_extension' => $file_extension,
                'sort'           => '500',
                'created_at'     => time(),
                'updated_at'     => time(),
            ];

            $resourceFileModel = new SysResourceFile();

            $res = $resourceFileModel
                ->insert($params);
        }

        if ($res !== false){
            return comReturn(true, config("return_message.success"));
        }

        return comReturn(false, config("return_message.fail"));
    }

    /**
     * 素材删除
     * @param $ids
     * @param $sid
     * @return string
     */
    public function delete($ids,$sid = "")
    {
        //验证
        $rule = [
            "id|素材id" => "require",
        ];
        $check_data = [
            "id" => $ids,
        ];

        $validate = new Validate($rule);
        if (!$validate->check($check_data)){
            return comReturn(false,$validate->getError());
        }

        $ids = explode(",",$ids);

        if (!empty($sid)){
            $resourceFileModel = new ResourceFile();
            $where_s['sid'] = ['eq',$sid];
        }else{
            $resourceFileModel = new SysResourceFile();
            $where_s = [];
        }

        Db::startTrans();
        try{
            foreach ($ids as $id){
                $res = $resourceFileModel
                    ->where('id',$id)
                    ->where($where_s)
                    ->delete();
                if ($res == false){
                    return comReturn(false,config('return_message.fail'));
                }
            }
            Db::commit();
            return comReturn(true,config('return_message.success'));
        }catch (Exception $e){
            Db::rollback();
            return comReturn(false,$e->getMessage());
        }
    }

    /**
     * 移动至指定分类
     * @param $type
     * @param $ids
     * @param $cat_id
     * @param $sid
     * @return string
     */
    public function moveMaterial($type,$ids,$cat_id,$sid = "")
    {
        $rule = [
            "type|素材类型"   => "require",
            "id|素材id"      => "require",
            "cat_id|分类id"  => "require",
        ];
        $check_data = [
            "type"    => $type,
            "id"      => $ids,
            "cat_id"  => $cat_id,
        ];
        $validate = new Validate($rule);
        if (!$validate->check($check_data)){
            return comReturn(false,$validate->getError());
        }

        $where['type'] = $type;

        $ids = explode(",",$ids);//将素材id 以逗号分割为数组

        if (!empty($sid)){
            $resourceFileModel  = new ResourceFile();
            $where_s['sid'] = ['eq',$sid];
        }else{
            $resourceFileModel = new SysResourceFile();
            $where_s = [];
        }

        Db::startTrans();
        try{
            foreach ($ids as $id){
                $params = [
                    "cat_id"     => $cat_id,
                    "type"       => $type,
                    "updated_at" => time()
                ];

                $is_ok = $resourceFileModel
                    ->where('id',$id)
                    ->where($where_s)
                    ->update($params);

                if ($is_ok == false){
                    return comReturn(false,config('return_message.fail'));
                }
            }

            Db::commit();
            return comReturn(true,config('return_message.success'));
        }catch (Exception $e){
            Db::rollback();
            return comReturn(false,$e->getMessage());
        }
    }


    /**
     * 组装上传Token
     * @param $type
     * @param $ids
     * @param $cat_id
     * @param $sid
     * @return string
     */
    public function createToken($sid = ""){
        if (!empty($sid)) {
            // $prefix = 'shop_source';
            $cache_name = 'QINIU_SHOP_TOKEN';
            //要上传的空间
            $bucket = Env::get("QINIU_IMG_BUCKET");
        }else{
            // $prefix = 'source';
            $cache_name = 'QINIU_ADMIN_TOKEN';
            //要上传的空间
            $bucket = Env::get("QINIU_SYS_BUCKET");
        }

        // Cache::rm($cache_name);
        $server_token = Cache::get($cache_name);
        if (!empty($server_token)) return $server_token;

        // 需要填写你的 Access Key 和 Secret Key
        $accessKey = Env::get("QINIU_ACCESS_KEY");
        $secretKey = Env::get("QINIU_SECRET_KEY");

        //构建鉴权对象
        $auth = new Auth($accessKey, $secretKey);
        // $key = [
        //    "saveKey" => $prefix."/".md5(time().generateReadableUUID("QBJ"))
        // ];

        $times = 24 * 60 * 60;
        $token = $auth->uploadToken("$bucket", null, $times+3600);

        Cache::set($cache_name,"$token","$times");

        return $token;
    }



}