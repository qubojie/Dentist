<?php
namespace app\wechat\model;

use think\Model;

class User extends Model
{
    protected $table = 'dts_user';

    protected $primaryKey = 'uid';
}