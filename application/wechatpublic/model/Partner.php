<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/11/2
 * Time: 上午11:02
 */
namespace app\wechatpublic\model;

use think\Model;

class Partner extends Model
{
    protected $table = 'dts_partner';

    protected $primaryKey = 'pid';

    public $timestamps = false;

    public $column = [
        "pid",
        "phone",
        "name",
        "wxid",
        "mp_openid",
        "nickname",
        "avatar",
        "sex",
        "register_way",
        "register_time",
        "lastlogin_time",
        "status",
        "remember_token",
        "token_lastime",
        "review_time",
        "review_desc",
        "review_user",
        "created_at",
        "updated_at",
    ];

    public $column_info = [
        "pid",
        "phone",
        "name",
        "qr_code",
        "wxid",
        "mp_openid",
        "is_attention_wx",
        "nickname",
        "avatar",
        "sex",
        "truncate(account_balance,2) account_balance",
        "truncate(account_freeze,2) account_freeze",
        "truncate(account_cash,2) account_cash",
        "register_way",
        "register_time",
        "lastlogin_time",
        "status",
        "remember_token",
        "token_lastime",
        "review_time",
        "review_desc",
        "review_user",
        "created_at",
        "updated_at",
    ];
}