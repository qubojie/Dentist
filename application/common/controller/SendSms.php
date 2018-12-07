<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/10/16
 * Time: 下午1:23
 */
namespace app\common\controller;


use think\Cache;
use think\Validate;

class SendSms
{
    const userid    = "13092";
    const account   = "gskeji";//账号
    const password  = "lasj8338BGLE";//密码

    /**
     * 发送
     * @param $phone
     * @param string $content
     * @return bool
     */
    public static function send($phone,$content = "")
    {
        if($phone == null)
            return false;

        $code = getRandCode(4);

        if (empty($content)){
            $content = '【牙闺蜜】您的验证码为 %$code%，如非本人操作，请忽略。';
        }

        //缓存验证码
        Cache::set("sms_verify_code_" . $phone, $code, 1800);

        $content = str_replace('%$code%',$code,$content);

        $post_data = array();
        $post_data['userid']   = self::userid;
        $post_data['account']  = self::account;
        $post_data['password'] = self::password;
        $post_data['content']  = $content;
        $post_data['mobile']   = $phone;
        $post_data['sendtime'] = '';
        $url='http://www.duanxin10086.com/sms.aspx?action=send';
        $o='';
        foreach ($post_data as $k=>$v)
        {
            $o.="$k=".urlencode($v).'&';
        }

        $post_data=substr($o,0,-1);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        $xml = simplexml_load_string($result);
        if((string)$xml->returnstatus == 'Success'){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 发送验证码
     * @param $phone
     * @return string
     */
    public static function sendCode($phone)
    {
        $rule = [
            "phone|电话号码"  => "require|regex:1[0-9]{1}[0-9]{9}",
        ];
        $check_data = [
            "phone"  => $phone,
        ];
        $validate = new Validate($rule);
        if (!$validate->check($check_data)){
            return comReturn(false,$validate->getError());
        }

        $is_ok = SendSms::send($phone);

        if (!$is_ok){
            return comReturn(false,config("sms.send_fail"));
        }
        return comReturn(true,config("sms.send_success"));
    }


    /**
     * 验证验证码
     * @param $phone
     * @param $code
     * @return string
     */
    public static function checkCode($phone,$code)
    {
        $rule = [
            "phone|电话号码"  => "require|regex:1[0-9]{1}[0-9]{9}",
            "code|验证码"     => "require",
        ];
        $check_data = [
            "phone"  => $phone,
            "code"   => $code,
        ];
        $validate = new Validate($rule);
        if (!$validate->check($check_data)){
            return comReturn(false,$validate->getError());
        }

        $cache_code = Cache::get("sms_verify_code_" . $phone);

        if ($cache_code != $code) {
            return comReturn(false, config("sms.verify_fail"));
        }

        //如果验证成功,则删除缓存
        Cache::rm("sms_verify_code_" . $phone);

        return comReturn(true, config("sms.verify_success"));
    }
}