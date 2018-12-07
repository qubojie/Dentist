<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/10/15
 * Time: 下午6:45
 */
namespace app\admin\model;

use think\Model;

class SysAdminUser extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'el_sys_admin_user';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['user_name', 'password'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['password', 'remember_token'];

    /**
     * 列表
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function lists()
    {
        $user = new SysAdminUser();

        $res = $user->sysRole()->select();

        return $res;

    }

    public function sysRole()
    {
        return $this->hasMany('sys_role','role_id')->field('role_name,action_list');
    }
}