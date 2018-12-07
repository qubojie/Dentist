<?php
/**
 * 距离计算.
 * User: qubojie
 * Date: 2018/10/22
 * Time: 下午5:10
 */
namespace app\common\controller;

use think\Controller;

class Distance extends Controller
{
    /**
     * 对距离进行优化
     * @param $array
     * @return mixed
     */
    public static function disposeDistance($array)
    {
       foreach ($array as $key => $val) {
           $distance = $array[$key]['distance'];
           if ($distance <= 800){
               $array[$key]['distance'] = intval($distance) . "m";
           }else{
               $array[$key]['distance'] = round($distance / 1000 , 1) . "km";
           }
       }
       return $array;
    }
    /**
     * 计算两组坐标的直线距离
     * @param $origins
     * @param $destination
     * @return float|int
     */
    public function getDistanceLine($origins,$destination)
    {
        $qd_arr = explode(",",$origins);
        $lng1 = $qd_arr[0];
        $lat1 = $qd_arr[1];
        $zd_arr = explode(",",$destination);
        $lng2 = $zd_arr[0];
        $lat2 = $zd_arr[1];
        //将角度转化为弧度
        $radLat1 = deg2rad($lat1);//deg2rad()函数将角度转换为弧度
        $radLat2 = deg2rad($lat2);
        $radLng1 = deg2rad($lng1);
        $radLng2 = deg2rad($lng2);
        $a = $radLat1 - $radLat2;
        $b = $radLng1 - $radLng2;
        $s = 2 * asin(sqrt(pow(sin($a / 2),2) + cos($radLat2) * pow(sin($b / 2),2))) * 6378.137 * 1000;
        return $s;
    }

    /*
     * 高德地图测算距离
     * status 返回结果状态值，值为0或1，0表示请求失败；1表示请求成功
     * info 返回状态说明，status为0时，info返回错误原；否则返回“OK”。
     * results 距离信息列表
     *      origin_id   起点坐标，起点坐标序列号（从１开始）
     *      dest_id     终点坐标，终点坐标序列号（从１开始）
     *      distance    路径距离，单位：米
     *      duration    预计行驶时间，单位：秒
     * */
    public function gadMap($origins,$destination)
    {
//        $url = "https://restapi.amap.com/v3/distance?origins=116.481028,39.989643|114.481028,39.989643&destination=114.465302,40.004717&key=4efa02c21f11eec59d2af6ba956e7b35";
        $url = "https://restapi.amap.com/v3/distance?origins=".$origins."&destination=".$destination."&key=4efa02c21f11eec59d2af6ba956e7b35";
        $json = file_get_contents($url);
        return $json;
    }



    /**
     * 将数组重新排序
     * @param $array '要排序的数组'
     * @param $row  '排序依据列'
     * @param $type '排序类型[asc or desc]'
     * @return array    '排好序的数组'
     */
    public function arraySort($array,$row,$type)
    {
        $array_temp = array();
        foreach ($array as $v){
            $array_temp[$v[$row]] = $v;
        }
        if ($type == 'asc'){
            ksort($array_temp);
        }elseif ($type = 'desc'){
            ksort($array_temp);
        }
        return $array_temp;
    }

}