<?php

/**
 * 支付宝支付
 * @Author: zhangtao
 * @Date:   2018-11-07 15:07:05
 * @Last Modified by:   admin
 * @Last Modified time: 2018-11-08 15:03:28
 */
namespace app\common\controller;

// use app\common\controller\CommonAuth;
// use app\common\controller\OrderCommon;
use think\Controller;
use think\Loader;
use think\Log;
use think\Env;
use think\Exception;

Loader::import('alipay.aop.AopClient',EXTEND_PATH,'.php');
Loader::import('alipay.aop.request.AlipayFundTransToaccountTransferRequest',EXTEND_PATH,'.php');
Loader::import('alipay.aop.request.AlipayFundTransOrderQueryRequest',EXTEND_PATH,'.php');
Loader::import('alipay.aop.SignData',EXTEND_PATH,'.php');


class AliPay extends Controller
{
    //应用ID
    public $appId = '2018082261160132';//正式版
    // public $appId = '2016092000553024';//测试版

    //支付宝网关
    public $gatewayUrl = 'https://openapi.alipay.com/gateway.do';//正式版
    // public $gatewayUrl = 'https://openapi.alipaydev.com/gateway.do';//测试版

    //开发者应用私钥，由开发者自己生成
    // public $rsaPrivateKeyFilePath = EXTEND_PATH."alipay/key/rsa_private_key_pkcs8.pem";//正式版
    public $rsaPrivateKeyFilePath = EXTEND_PATH."alipay/key/rsa_private_key_pkcs8.pem";//测试版

    //支付宝公钥，由支付宝生成
    public $alipayrsaPublicKey = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA53zU4Y07RUjfteCQlxI9zk2UAVt7L6wo/sj+o9KTbF8feQQJ69ncisc0f4ntifFviMXcQHYNTtTTq1tDR2FR8tEGAp7kLeNVXfdjOILYH4oEcRzJaZ+x8tpDf24QonKoamJqo8G3xx6yjJEgGKQ6DVLYi1jzLCVzPx79wG+7Dlb7Cfi180rrcEjS9x2PHGog5pxy49YJKEsAWMMO4KSfRHWSFoErHGB9km7LofiB4FyvXoGx+pste5sM+Y3ZOJMldLs/D440Hz+v//1s7BnGkPsvZICCZfnYLcGqIX+jFlCKwgcKCJV1vY49CY8BL2lOuAfIHmd5qEPC3Fy0UM1EWQIDAQAB';//正式版
    // public $alipayrsaPublicKey = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAwTjebvCmipw7QF0o6Pfizaa12WARj+fJhoYWJx9chwB0OPnacLpdNzUMjpAEswytZ45oAO63gTPZ1pgL3VTrXeKMdccyTQqh6DKQT5BfzCWd+reGT6LCFk+nbGmQmEBEQ+dgu0GBNEJSg0ZLyQto1/GN9Tvg8FQUBmtV+w9ZXleXL5BShS1j0DQ8Hm3TQJ5MTFjXYPhUA8UbmHbWE2pyTMcKoblS/KaHsVjW22vXh1Pskdi3AOqzoGwkc60CJSLD2s0ruhPX4qUXxEg2Dh5u2I+ADsW3ypGw6fiIISOp7lnUk+XpxupGcn80YbZee0vmIX8A5FwwWeBniMy2/TWLeQIDAQAB';//测试版

    //api版本
    public $apiVersion = '3.3.1';

    //商户生成签名字符串所使用的签名算法类型，目前支持RSA2和RSA，推荐使用RSA2
    public $signType = 'RSA2';

    //请求和签名使用的字符编码格式，支持GBK和UTF-8
    public $postCharset = 'utf-8';

    //参数返回格式，只支持json
    public $format = 'json';
    /**
     * 单笔转账到支付宝账户
     * @param $userid  用户id
     * @param $out_biz_no 编号 测试：3142321423409
     * @param $payee_account 提现的支付宝账号 测试：ypnhmv8520@sandbox.com
     * @param $amount 转账金额
     * @param $payee_real_name 账号的真实姓名 测试：沙箱环境
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function fundToAccount($out_biz_no, $payee_account, $amount, $payee_real_name, $payer_show_name = "提现", $remark = "")
    {
        // $payer_show_name = '用户红包提现';
        // $remark = '红包提现到支付宝';
        $aop = new \AopClient();
        $aop->gatewayUrl =  $this->gatewayUrl;//支付宝网关 https://openapi.alipay.com/gateway.do这个是不变的
        $aop->appId = $this->appId;//商户appid 在支付宝控制台找
        $aop->rsaPrivateKey = trim(file_get_contents($this->rsaPrivateKeyFilePath));
        $aop->alipayrsaPublicKey = $this->alipayrsaPublicKey;
        $aop->apiVersion = $this->apiVersion;
        $aop->signType = $this->signType;
        $aop->postCharset = $this->postCharset;
        $aop->format = $this->format;
        $request = new \AlipayFundTransToaccountTransferRequest();
        $request->setBizContent(
            "{" .
            "\"out_biz_no\":\"$out_biz_no\"," .
            "\"payee_type\":\"ALIPAY_LOGONID\"," .
            "\"payee_account\":\"$payee_account\"," .
            "\"amount\":\"$amount\"," .
            "\"payer_show_name\":\"$payer_show_name\"," .
            "\"payee_real_name\":\"$payee_real_name\"," .
            "\"remark\":\"$remark\"" .
            "}"
        );
        $result = $aop->execute ($request);

        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode = $result->$responseNode->code;

        if(!empty($resultCode)&&$resultCode == 10000){
            //提现成功以后 更新表状态
            //并且记录 流水等等
            $res['status']   = 'success';
            $res['code']     = $result->alipay_fund_trans_toaccount_transfer_response->code;
            $res['pay_no']   = $result->alipay_fund_trans_toaccount_transfer_response->order_id;
            $res['pay_time'] = $result->alipay_fund_trans_toaccount_transfer_response->pay_date;
            return $res;
        } else {
            //$result->$responseNode->sub_msg 这个参数 是返回的错误信息
            // throw new Exception($result->$responseNode->sub_msg);
            $res['status'] = 'fail';
            $res['msg'] = $result->$responseNode->sub_msg;
            return $res;
        }

    }

    /**
     * 查询转账订单
     * @return string
     */
    public function checkFundToAccount()
    {
        $aop = new \AopClient ();
        $aop->gatewayUrl =  'https://openapi.alipaydev.com/gateway.do';//支付宝网关 https://openapi.alipay.com/gateway.do这个是不变的
        $aop->appId = '2016092000553024';//商户appid 在支付宝控制台找
        $aop->rsaPrivateKey = 'MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQDpFhY1KyEBpvwMl/KHO8ZFjnOzKHpXf1Z/gZir7UU6ad1i8LqkLcNYqbrxHKDDgRAE1zJK1wUQeiezMnBDITxYQq2oFXcpRzu70Lzycri2B56TCea1fCzn31zVGxuetniI2mJcxzfLIGk0szTzATm0J/owyApA5eu+VGVms0CbYrHS2M+iwkqmSnNQNzxwRQfog74dzGnOYim5bL2ha/S9IHvzQDGlDwJGM3tG/j+/D7gQdNLlMhRTNGsSJHerutWGR6Zn4Z/WznAdo6Pr3xzj58yZRU8KdweKTC0K2BRToTjQAu/YIKA8x1gzG2cVM5WQkvAdsHkA6UDrf7+gZZ9/AgMBAAECggEAUkcHo9ELn6ewRUnFxNLm1FrKoK1jMkuy3uOCXv4sd8gy6+AKIjY9RKj1YnM2lIyUeQD1sdFg6kYUiX7+fdPXl1hCMJkTLTEF9vBiIjs41Jiv8zuFgv7IYzlHbbpZ75Z9yeE84383RK62NHIl+LmwAtYxzNJwPCRtKQxllvEjlzV9DSPKKIpcN6Dopn2Abfegvr/h1vgM+qcJroxM/l9bybSGRSJ1hGJCRe2b0DE4pWDvVjALlKYVRs8UJMZyyIIirehSJacgCZzEI7eJdP7MvQ5GlaFSBBqeo83HjSuCLVHvhq1dsZOtGhcdxN9rkwNd/pA8AdgRHWNu06tsRE8YMQKBgQD5haozAdSO5jLE8tpMcNxvJmDj/NOiA1qY98pTZIeM1Evzl2tiZC16e/FeUze5SidBL8+sMgGtbi94UVns5aaGQezGUwR2h/To6dnkRahRTZn2jOIsIT5q+NlUFd7gwezU4lBxFDAqoJTMc1XYtMni7dS+f29GFpXzm5bv5J3cowKBgQDvIzBB4lKf3T/68qQl2b1Jp5fQEkyu4wrhrN/4DUKp+9Dhk4d+3GeN2iZ5IkkM0B5NrXX37XGreLlQG2/fWfRnheu6acrLl+BNob46HxiFkDTgEz/LrF+ZjbQk0I4AacFZrLVB77kk63MP4HgzO4faOwIQ7a9zFPIHoO6ABGyjdQKBgQDJ09YxX9+gno6eQ5lTj06eETr57nz4ZSIY7QUYow4JWYb9x5KyiTlIA4zesQ03sMVDsvqV2/UDZUa8WYEVX0RtAFIWsPvBLINrCgyW2cklylEo3hfrfhBQ0mxn2TDoN7PrFgvfC2dSc4UoQnpDySgriBg25jkJRieQXl+eNxuBZQKBgCRG/E0iXlJuKASTxp8PVw3TZM5l2Eg3nTD7HsbsjA5paxitXsK6AOOeEnQR5W8SK2axR9IirTnMCKDEaMZDSTZjNI2E+wTDt31PaiI7EWdBpyPPB8CIF8CdpJRpJwGdCX3nG2AzU6zsIvpnnRR4lXVfukKDLDiTFtxdZ5D4Gtr5AoGAYCFMDupysvA8kTiw/Rl6VAH16k3oqkkFdtpMMGFnLe8t9ELJrhIvQPaTn7eddmXSFLp4/EQDt9BebQQVxwJI6CiiX0Lu8sRhVRNkAWAsvEsbYiTOQf279Sm0VMOiCUjiejqumBO0ZWtFUZCOjq6vXGq8DJMljX02WravAw0vYr8=';
        $aop->alipayrsaPublicKey='MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAwTjebvCmipw7QF0o6Pfizaa12WARj+fJhoYWJx9chwB0OPnacLpdNzUMjpAEswytZ45oAO63gTPZ1pgL3VTrXeKMdccyTQqh6DKQT5BfzCWd+reGT6LCFk+nbGmQmEBEQ+dgu0GBNEJSg0ZLyQto1/GN9Tvg8FQUBmtV+w9ZXleXL5BShS1j0DQ8Hm3TQJ5MTFjXYPhUA8UbmHbWE2pyTMcKoblS/KaHsVjW22vXh1Pskdi3AOqzoGwkc60CJSLD2s0ruhPX4qUXxEg2Dh5u2I+ADsW3ypGw6fiIISOp7lnUk+XpxupGcn80YbZee0vmIX8A5FwwWeBniMy2/TWLeQIDAQAB';
        $aop->apiVersion = '3.3.1';
        $aop->signType = 'RSA2';
        $aop->postCharset='utf-8';
        $aop->format='json';
        $request = new \AlipayFundTransOrderQueryRequest();
        $request->setBizContent(
            "{" .
            "\"out_biz_no\":\"3142321423407\"," .
            "\"order_id\":\"20181107110070001502770000424623\"" .
            "  }"
        );
        $result = $aop->execute ( $request);

        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode = $result->$responseNode->code;
        if(!empty($resultCode)&&$resultCode == 10000){
            var_dump($result);
            echo "成功";
        } else {
            echo "失败";
        }
    }
}