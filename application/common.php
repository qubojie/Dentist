<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/6/19
 * Time: 下午4:00
 */

// 公共方法

/**
 * 公共返回
 * @param bool $result
 * @param string $message
 * @param null $data
 * @return string
 */
function comReturn($result = false , $message = '' , $data = null)
{
    $res = [
        'result'  => $result,
        'message' => $message,
        'data'    => $data
    ];

    return json($res);
}

/**
 * 加密token
 * @param $str
 * @param string $prefix
 * @return string
 */
function jmToken($str , $prefix = 'QBJ')
{
    return md5(sha1($prefix.$str).time());
}

/**
 * 加密密码
 * @param $password
 * @param string $prefix
 * @return string
 */
function jmPassword($password , $prefix = "QBJ")
{
    return sha1(md5($prefix.$password));
}

/**
 * 生成指定长度随机数
 * @param int $length
 * @param int $numeric
 * @return string
 */
function getRandCode($length = 6 , $numeric = 0)
{
    PHP_VERSION < '4.2.0' && mt_rand((double) microtime() * 1000000);
    if ($numeric){
        $hash = sprintf('%0'.$length.'d' , mt_rand(0 , pow(10 , $length) - 1));
    } else {
        $hash  = '';
        $chars = '0123456789';
        $max   = strlen($chars) - 1;
        for ($i = 0; $i < $length; $i ++) {
            $hash .= $chars[mt_rand(0 , $max)];
        }
    }
    return $hash;
}

/**
 * 获取某个时间戳为周几,以及未来某天为周几
 * @param $time
 * @param int $i
 * @return mixed
 */
function timeGetWeek($time , $i = 0)
{
    $weekArray = ["7" , "1" , "2" , "3" , "4" , "5" , "6"];
    $oneD = 24 * 60 * 60;
    return $weekArray[date("w" , $time + $oneD * $i)];
}

/**
 * 删除数组中指定的Key,
 * 多个以 , 逗号分割
 * @param $array
 * @param $keys
 * @return mixed
 */
function removeArrayKey($array , $keys)
{
    $keyArr  = explode("," , $keys);
    for ($i  = 0; $i < count($keyArr); $i ++){
        $key = $keyArr[$i];
        if (!array_key_exists($key , $array)) {
            return $array;
        }
        $keys  = array_keys($array);
        $index = array_search($key , $keys);
        if ($index !== false) {
            array_splice($array , $index , 1);
        }
    }
    return $array;
}

/**
 * 判断是否为空数组
 * @param $array
 * @return bool
 */
function isEmptyArray($array)
{
    if (empty($array) || !is_array($array) || count($array) == 0) {
        return true;
    }
    return false;
}

/**
 * 判断是否为空数据
 * @param $data
 * @return bool
 */
function isEmpty($data)
{
    if (empty($data)) {
        return true;
    }
    return false;
}

/**
 * 验证密码 : 长度6位及以上,至少包含1个数字,1个大写字母,1个小写字母,不包含空格
 * @param $password
 * @return bool
 */
function checkPassword($password)
{
    if (preg_match("/^((?=\S*?[A-Z])(?=\S*?[a-z])(?=\S*?[0-9]).{5,})\S$/" , $password)) {
        return true;
    }
    return false;
}

/**
 * 验证邮箱格式
 * @param $email
 * @return bool
 */
function checkEmail($email)
{
    if (preg_match("/^([a-zA-Z0-9])+([\w-.])*([a-zA-Z0-9])+@([a-zA-Z0-9_-])+(\.[a-zA-Z0-9_-]+)/" , $email)) {
        return true;
    }
    return false;
}

/**
 * 验证url格式
 * @param $url
 * @return bool
 */
function checkUrl($url)
{
    if (!preg_match('/http:\/\/[\w.]+[\w\/]*[\w.]*\??[\w=&\+\%]*/is' , $url)) {
        return false;
    }
    return true;
}

/**
 * 验证是否全是中文
 * @param $str
 * @return bool
 */
function checkCn($str)
{
    if (!eregi("[^\x80-\xff]" , "$str")) {
        return true;
    }
    return false;
}

/**
 * 检测微信id规则
 * @param $str
 * @return bool
 */
function checkWxId($str)
{
    //前2位字符为"wx" 及 长度为16~20位(微信 appid长度为18位, 检测时考虑点伸缩)
    if (substr($str, 0, 2) == 'wx' && preg_match("/^[a-zA-Z0-9]{16,20}$/", $str)) {
        return true;
    }
    return false;
}

/**
 * 验证手机号码格式
 * @param $mobile
 * @return bool
 */
function checkMobile($mobile)
{
    if (preg_match("/^(?=\d{11}$)^1(?:3\d|4[57]|5[^4\D]|7[^249\D]|8\d)\d{8}$/", $mobile)) {
        return true;
    }
    return false;
}

/**
 * 验证电话号码格式
 * @param $phone
 * @return bool
 */
function checkPhone($phone)
{
    if (preg_match("/^([0]\d{2,3})?[1-9]{1}\d{6,7}$/", $phone)) {
        return true;
    }
    return false;
}

/**
 * 验证特殊电话号码 如10000、95555、400号码(长度不小于5位的数字)
 * @param $str
 * @return bool
 */
function checkSpecialPhone($str)
{
    if (preg_match("/^\d{5,20}$/", "$str")) {
        return true;
    }
    return false;
}

/**
 * 简单验证身份证号码
 * @param $idCard
 * @return bool
 */
function checkIdCard($idCard)
{
    if (preg_match("/^[1-9][0-9]{17}$/", "$idCard")) {
        return true;
    }
    return false;
}

/**
 * 验证ip地址格式
 * @param $ip
 * @return bool
 */
function checkIp($ip)
{
    if (preg_match("/^[1-2]\d{1,2}\.\d{0,3}\.\d{0,3}\.\d{0,3}$/", $ip)) {
        return true;
    }
    return false;
}

/**
 * 转义字符替换
 * @param  string $subject
 * @return string
 */
function sReplace($subject)
{
    $search  = array('<' , '>' , '&' , '\'' , '"');
    $replace = array('&lt;' , '&gt;' , '&amp;' , '&apos;' , '&quot;');
    return str_replace($search , $replace , $subject);
}

/**
 * 检查是否是中文编码
 * @param $str
 * @return false|int
 */
function chkChinese($str)
{
    return preg_match('/[\x80-\xff]./', $str);
}

/**
 * 检测是否GB2312编码
 * @param $str
 * @return bool
 */
function isGb2312($str)
{
    for ($i = 0; $i < strlen($str); $i++) {
        $v = ord($str[$i]);
        if ($v > 127) {
            if (($v >= 228) && ($v <= 233)) {
                if (($i + 2) >= (strlen($str) - 1)) return true;  // not enough characters
                $v1 = ord($str[$i + 1]);
                $v2 = ord($str[$i + 2]);
                if (($v1 >= 128) && ($v1 <= 191) && ($v2 >= 128) && ($v2 <= 191))
                    return false;
                else
                    return true;
            }
        }
    }
    return false;
}

/**
 * 检测是否是BGK编码
 * @param $str
 * @param bool $gbk
 * @return bool
 */
function isGBK($str, $gbk = true)
{
    for ($i = 0; $i < strlen($str); $i++) {
        $v = ord($str[$i]);
        if ($v > 127) {
            if (($v >= 228) && ($v <= 233)) {
                if (($i + 2) >= (strlen($str) - 1)) return $gbk ? true : FALSE;  // not enough characters
                $v1 = ord($str[$i + 1]);
                $v2 = ord($str[$i + 2]);
                if ($gbk) {
                    return (($v1 >= 128) && ($v1 <= 191) && ($v2 >= 128) && ($v2 <= 191)) ? FALSE : TRUE;//GBK
                } else {
                    return (($v1 >= 128) && ($v1 <= 191) && ($v2 >= 128) && ($v2 <= 191)) ? TRUE : FALSE;
                }
            }
        }
    }
    return $gbk ? TRUE : FALSE;
}

/**
 * 数组转换为XML
 * @param $data '带转换的数组'
 * @param string $rootNodeName '根节点名称'
 * @param null $xml 'xml协议头'
 * @return string XML
 */
function arrToXml($data , $rootNodeName = 'data' , $xml = null)
{
    if (ini_get('zend.ze1_compatibility_mode') == 1) {
        ini_set('zend.ze1_compatibility_mode' , 0);
    }
    if ($xml == null) {
        $xml = simplexml_load_string("<?xml version='1.0' encoding='utf-8'?><$rootNodeName />");
    }
    foreach ($data as $key => $value) {
        if (is_numeric($key)) {
            $key = "unknownNode_" . (string)$key;
        }
        $key = preg_replace('/[^a-z]/i' , '' , $key);
        if (is_array($value)) {
            $node = $xml -> addChild($key);
            arrToXml($value , $rootNodeName , $node);
        } else {
            $value = htmlentities($value);
            $xml->addChild($key , $value);
        }
    }
    return $xml -> asXML();
}

/**
 * 将xml字符串转为数组
 * @param $xml 'xml字符串'
 * @param bool $flagCDATA '读取xml中<![CDATA[]]>数据'
 * @return array
 */
function xmlToArray($xml , $flagCDATA = false)
{
    if (!$flagCDATA) {
        $objTmp = simplexml_load_string($xml); //xml转化为对象
    } else {
        $objTmp = simplexml_load_string($xml , null , LIBXML_NOCDATA);//xml转化为对象
    }
    $strTmp = json_encode($objTmp);//对象转化为json
    $arrTmp = json_decode($strTmp , true);//json转换为数组
    return $arrTmp;
}

/**
 * 号码隐藏
 * @param $phone
 * @return null|string|string[]
 */
function hideTelephone($phone)
{
    if (!preg_match('/^\d{5,20}$/', $phone)) {
        return $phone;
    }
    $isWhat = preg_match('/(0[0-9]{2,3}[-]?[2-9][0-9]{6,7}[-]?[0-9]?)/i', $phone); //固定电话
    if ($isWhat) {
        return preg_replace('/(0[0-9]{2,3}[-]?[2-9])[0-9]{3,4}([0-9]{3}[-]?[0-9]?)/i', '$1****$2', $phone);
    } else {
        return preg_replace('/([1-9][0-9]{1}[0-9])[0-9]{1,4}([0-9]{1,4})/i', '$1****$2', $phone);
    }
}

/**
 * 电话号码加密
 * @param $phone
 * @return string
 */
function encryptPhone($phone)
{
    $head = 'QBJ:';
    return $head . base64_encode($phone);
}

/**
 * 电话号码解密
 * @param $phone
 * @return bool|string
 */
function decryptPhone($phone)
{
    $phone = ltrim($phone , 'QBJ:');
    return base64_decode($phone);
}

/**
 * 生成无限极分类树
 * @param $arr '数据数组结构'
 * @param $key_id '主键id的key'
 * @param $parent_id '区分层级关系的 Key名'
 * @return array
 */
function makeTree($arr , $key_id , $parent_id)
{
    $refer = array();
    $tree  = array();
    foreach ($arr as $k => $v) {
        $refer[$v[$key_id]] = & $arr[$k];//创建主键的数组以后用
    }
    foreach ($arr as $k => $v) {
        $pid = $v[$parent_id];//获取当前分类的父级id
        if ($pid == 0) {
            $arr[$k]['parent_id'] = (string)$arr[$k]['parent_id'];
            $arr[$k]['children'] = [];
            $tree[] = & $arr[$k];//顶级栏目
        } else {
            if (isset($refer[$pid])) {
                $arr[$k]['parent_id'] = (string)$arr[$k]['parent_id'];
                $refer[$pid]['children'][] = & $arr[$k];//如果存在父级栏目,则添加进父级栏目的子栏目数组中
            }
        }
    }
    return $tree;
}

/**
 * 生成唯一字符串 最长32位
 * @param int $length
 * @return bool|string
 */
function uniqueCode($length = 8)
{
    if ($length > 32) $length = 32;
    $charId = strtoupper(md5(uniqid(rand() , true)));
    $hyphen = chr(45);// "-"
    $uuid = chr(123)// "{"
        .substr($charId, 0 ,8).$hyphen
        .substr($charId, 8 ,4).$hyphen
        .substr($charId, 12 ,4).$hyphen
        .substr($charId, 16 ,4).$hyphen
        .substr($charId, 20 ,12)
        .chr(125);//"}"
    $code = $uuid;
    return (substr(str_replace("-","",$code) , 1 , $length));
}

/**
 * UUID不重复号,指定前缀
 * @param null $prefix
 * @return string
 */
function generateReadableUUID($prefix = null)
{
    mt_srand((double)microtime() * 10000);
    $charId = strtoupper(md5(uniqid(rand() , true)));
    $hyphen = chr(45);//"-"
    $uuid = chr(123)//"{"
        .substr($charId,0,8).$hyphen
        .substr($charId,8,4).$hyphen
        .substr($charId,12,4).$hyphen
        .substr($charId,16,4).$hyphen
        .substr($charId,20,12)
        .chr(125);//"}"

    $getUUID = strtoupper(str_replace("-","",$uuid));
    $generateReadableUUID = $prefix . date("ymdHis") . sprintf('%03D' , rand(0 , 999)) . substr($getUUID , 4 , 4);
    return $generateReadableUUID;
}

/**
 * 根据生日计算年龄,生肖和星座
 * @param $birth 920524
 * @return array
 */
function birthdayGetAgeAnimalConstellation($birth)
{
    $by = substr($birth,0,2);
    if ($by <100 && $by >= 40){
        $by = "19".$by;
    }else{
        $by = "20".$by;
    }

    $animal = getAnimal($by);
    $info['animal'] = $animal;//生肖

    $bm = substr($birth,2,2);
    $bd = substr($birth,4,2);
    $constellation = getConstellation($bm , $bd);
    $info['constellation'] = $constellation;//星座

    $cm  = date('n');
    $cd  = date('j');
    $age = date('Y') - $by - 1;
    if ($cm>$bm || $cm==$bm && $cd>$bd) $age++;
    $info['age'] = $age;//年龄

    return $info;
}

/**
 * 获取生肖
 * @param $year
 * @return mixed
 */
function getAnimal($year)
{
    if (strlen($year) != 4){
        return false;
    }
    $animals = array(
        '鼠', '牛', '虎', '兔', '龙', '蛇',
        '马', '羊', '猴', '鸡', '狗', '猪'
    );
    $key = ($year - 1900) % 12;
    return $animals[$key];
}

/**
 * 获取星座
 * @param $month
 * @param $day
 * @return bool
 */
function getConstellation($month , $day)
{
    // 检查参数有效性
    if ($month < 1 || $month > 12 || $day < 1 || $day > 31) return false;
    // 星座名称以及开始日期
    $constellations = array(
        array( "20" => "宝瓶座"),
        array( "19" => "双鱼座"),
        array( "21" => "白羊座"),
        array( "20" => "金牛座"),
        array( "21" => "双子座"),
        array( "22" => "巨蟹座"),
        array( "23" => "狮子座"),
        array( "23" => "处女座"),
        array( "23" => "天秤座"),
        array( "24" => "天蝎座"),
        array( "22" => "射手座"),
        array( "22" => "摩羯座")
    );
    $constellation_start = "";
    $constellation_name  = "";
    foreach ($constellations[$month - 1] as $key => $val) {
        $constellation_start = $key;
        $constellation_name = $val;
    }
    if ($day < $constellation_start){
        foreach ($constellations[($month -2 < 0) ? $month = 11 : $month -= 2] as $key=> $val){
//            $constellation_start = $key;
            $constellation_name = $val;
        }
    }
    return $constellation_name;
}


