<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/10/15
 * Time: 下午6:29
 */
namespace app\common\controller;

use think\Controller;
use think\Db;

class DateBaseAction extends Controller
{
    /**
     * 数据列表不分页
     * @param $dbName '表名'
     * @param $where '条件'
     * @param $field '查询字段'
     * @param $order '排序'
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function lists($dbName,$where,$field,$order)
    {
        $res = Db::name("$dbName")
            ->where($where)
            ->field($field)
            ->order($order)
            ->select();
        return $res;
    }

    /**
     * 数据列表 分页
     * @param $dbName
     * @param $where
     * @param $field
     * @param $order
     * @param $paginate
     * @return \think\Paginator
     * @throws \think\exception\DbException
     */
    public function listsPaginate($dbName,$where,$field,$order,$paginate)
    {
        $res = Db::name("$dbName")
            ->where($where)
            ->field($field)
            ->order($order)
            ->paginate($paginate);
        return $res;
    }

    /**
     * 数据添加
     * @param $dbName
     * @param $params
     * @return int|string
     */
    public function add($dbName,$params)
    {
        $res = Db::name("$dbName")
            ->insert($params);
        return $res;
    }

    /**
     * 数据添加 获取id
     * @param $dbName
     * @param $params
     * @return int|string
     */
    public function addGetId($dbName,$params)
    {
        $res = Db::name("$dbName")
            ->insertGetId($params);
        return $res;
    }

    /**
     * 数据更新
     * @param $dbName
     * @param $where
     * @param $params
     * @return int|string
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function update($dbName,$where,$params)
    {
        $res = Db::name("$dbName")
            ->where($where)
            ->update($params);
        return $res;
    }

    /**
     * 数据删除
     * @param $dbName
     * @param $where
     * @return int
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function delete($dbName,$where)
    {
        $res = Db::name("$dbName")
            ->where($where)
            ->delete();
        return $res;
    }
}