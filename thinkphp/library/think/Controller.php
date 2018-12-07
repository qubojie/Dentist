<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2017 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

namespace think;

\think\Loader::import('controller/Jump', TRAIT_PATH, EXT);

use app\admin\model\User;
use app\common\model\SysSetting;
use app\wechat\model\DtsGoods;
use app\wechatpublic\model\ShopEnterprise;
use think\exception\ValidateException;
header("Access-Control-Allow-Origin:*");
header("Access-Control-Allow-Headers:x-token,x-uid,x-token-check,x-requested-with,content-type,Host,version,token,authorization,timeStamp,randomStr,signature");
header("Access-Control-Allow-Credentials:true");

class Controller
{
    use \traits\controller\Jump;

    /**
     * @var \think\View 视图类实例
     */
    protected $view;
    /**
     * @var \think\Request Request实例
     */
    protected $request;
    // 验证失败是否抛出异常
    protected $failException = false;
    // 是否批量验证
    protected $batchValidate = false;

    /**
     * 前置操作方法列表
     * @var array $beforeActionList
     * @access protected
     */
    protected $beforeActionList = [];

    /**
     * 架构函数
     * @param Request $request Request对象
     * @access public
     */
    public function __construct(Request $request = null)
    {
        if (is_null($request)) {
            $request = Request::instance();
        }
        $this->view    = View::instance(Config::get('template'), Config::get('view_replace_str'));
        $this->request = $request;

        // 控制器初始化
        $this->_initialize();

        // 前置操作方法
        if ($this->beforeActionList) {
            foreach ($this->beforeActionList as $method => $options) {
                is_numeric($method) ?
                $this->beforeAction($options) :
                $this->beforeAction($method, $options);
            }
        }
    }

    // 初始化
    protected function _initialize()
    {
    }

    /**
     * 前置操作
     * @access protected
     * @param string $method  前置操作方法名
     * @param array  $options 调用参数 ['only'=>[...]] 或者['except'=>[...]]
     */
    protected function beforeAction($method, $options = [])
    {
        if (isset($options['only'])) {
            if (is_string($options['only'])) {
                $options['only'] = explode(',', $options['only']);
            }
            if (!in_array($this->request->action(), $options['only'])) {
                return;
            }
        } elseif (isset($options['except'])) {
            if (is_string($options['except'])) {
                $options['except'] = explode(',', $options['except']);
            }
            if (in_array($this->request->action(), $options['except'])) {
                return;
            }
        }

        call_user_func([$this, $method]);
    }

    /**
     * 加载模板输出
     * @access protected
     * @param string $template 模板文件名
     * @param array  $vars     模板输出变量
     * @param array  $replace  模板替换
     * @param array  $config   模板参数
     * @return mixed
     */
    protected function fetch($template = '', $vars = [], $replace = [], $config = [])
    {
        return $this->view->fetch($template, $vars, $replace, $config);
    }

    /**
     * 渲染内容输出
     * @access protected
     * @param string $content 模板内容
     * @param array  $vars    模板输出变量
     * @param array  $replace 替换内容
     * @param array  $config  模板参数
     * @return mixed
     */
    protected function display($content = '', $vars = [], $replace = [], $config = [])
    {
        return $this->view->display($content, $vars, $replace, $config);
    }

    /**
     * 模板变量赋值
     * @access protected
     * @param mixed $name  要显示的模板变量
     * @param mixed $value 变量的值
     * @return void
     */
    protected function assign($name, $value = '')
    {
        $this->view->assign($name, $value);
    }

    /**
     * 初始化模板引擎
     * @access protected
     * @param array|string $engine 引擎参数
     * @return void
     */
    protected function engine($engine)
    {
        $this->view->engine($engine);
    }

    /**
     * 设置验证失败后是否抛出异常
     * @access protected
     * @param bool $fail 是否抛出异常
     * @return $this
     */
    protected function validateFailException($fail = true)
    {
        $this->failException = $fail;
        return $this;
    }

    /**
     * 验证数据
     * @access protected
     * @param array        $data     数据
     * @param string|array $validate 验证器名或者验证规则数组
     * @param array        $message  提示信息
     * @param bool         $batch    是否批量验证
     * @param mixed        $callback 回调方法（闭包）
     * @return array|string|true
     * @throws ValidateException
     */
    protected function validate($data, $validate, $message = [], $batch = false, $callback = null)
    {
        if (is_array($validate)) {
            $v = Loader::validate();
            $v->rule($validate);
        } else {
            if (strpos($validate, '.')) {
                // 支持场景
                list($validate, $scene) = explode('.', $validate);
            }
            $v = Loader::validate($validate);
            if (!empty($scene)) {
                $v->scene($scene);
            }
        }
        // 是否批量验证
        if ($batch || $this->batchValidate) {
            $v->batch(true);
        }

        if (is_array($message)) {
            $v->message($message);
        }

        if ($callback && is_callable($callback)) {
            call_user_func_array($callback, [$v, &$data]);
        }

        if (!$v->check($data)) {
            if ($this->failException) {
                throw new ValidateException($v->getError());
            } else {
                return $v->getError();
            }
        } else {
            return true;
        }
    }

    /********************************************My Function On***************************************************/
    /**
     * 更新之前
     * @param $dbName '表名,不带前缀'
     * @param $id   '主键字段'
     * @param $idValue '主键值'
     * @param $field '被更新的字段'
     * @return array|false|mixed|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @return  array '更新之前的数据'
     */
    public function updateBefore($dbName,$id,$idValue,$field)
    {
        $res = Db::name("$dbName")
            ->where("$id","$idValue")
            ->field($field)
            ->find();

        $res = json_decode(json_encode($res),true);
        return $res;
    }

    /**
     * 日志记录数组数据不一致的对比
     * @param $oldRes '更新前数据'
     * @param $newRes '更新后数据'
     * @return string
     */
    public function checkDifAfter($oldRes,$newRes)
    {
        $res = "";
        foreach ($oldRes as $key => $val){
            if ($oldRes[$key] !== $newRes[$key]){
                $res .= $key . "('" .$oldRes[$key]. "'=>'" . $newRes[$key] ."'),";
            }
        }

        return $res;
    }

    /**
     * 路由翻译
     * @param $route
     * @param $dbName
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * $this->request->routeInfo()
     */
    public function routeTranslation($route,$dbName)
    {
        $rule = $route['rule'];
        $rule_zero  = $rule[0];
        $rule_one   = $rule[1];
        $rule_two   = $rule[2];

        if (isset($rule[3])){
            $rule_three = $rule[3];
            $ruleStr = $rule_zero . "," . $rule_one . "," .  $rule_two . "," . $rule_three;
        }else{
            $ruleStr = $rule_zero . "," . $rule_one . "," .  $rule_two;
        }

        $res = $this->getMenuName($ruleStr,$dbName);

        return $res;
    }

    /**
     * 将路由翻译成中文
     * @param $ruleName
     * @param $dbName
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getMenuName($ruleName,$dbName)
    {
        $urlArr = explode(",",$ruleName);
        $res = "";
        foreach ($urlArr as $key => $val){
            $url = $urlArr[$key];
            $nameRes = Db::name($dbName)
                ->where('url',$url)
                ->field('title')
                ->find();
            $nameRes = json_decode(json_encode($nameRes),true);
            if (!empty($nameRes)){
                $res .= $nameRes['title'] . "->";
            }
        }
        return $res;
    }

    /**
     * 记录系统操作日志
     * @param $log_time     '记录时间'
     * @param $action_user  '操作管理员名'
     * @param $log_info     '操作描述'
     * @param $ip_address   '操作登录的地址'
     * @param $sid   '店铺id'
     * @return bool
     */
    public function addSysLog($log_time,$action_user='0',$log_info='0',$ip_address='0.0.0.0',$sid = '')
    {
        if (empty($log_time)){
            $log_time = time();
        }

        if (!empty($sid)){
            $params = [
                'sid'        => $sid,
                'log_time'   => $log_time,
                'action_user'=> $action_user,
                'log_info'   => $log_info,
                'ip_address' => $ip_address
            ];
            $dbName = 'shop_log';
        }else{
            $params = [
                'log_time'   => $log_time,
                'action_user'=> $action_user,
                'log_info'   => $log_info,
                'ip_address' => $ip_address
            ];
            $dbName = 'sys_log';
        }

        $res = Db::name("$dbName")
            ->insert($params);

        if ($res !== false){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 给数据添加标签
     * @param $info '描述信息'
     * @param $patternElement '标签name'
     * @param $patternClass 'name的class属性'
     * @return string
     */
    public function infoAddClass($info,$patternClass,$patternElement = "span")
    {
        if (empty($patternElement)){
            $patternElement = "span";
        }

        $res = '<' . $patternElement. ' class="' . $patternClass . '" >'
            . $info
            . '</' . $patternElement . '>';

        return $res;
    }


    /**
     * 记录操作日志
     * @param $sid '店铺id'
     * @param $gid '商品id'
     * @param $oid '订单id'
     * @param $action ''
     * @param $reason '操作原因'
     * @param $name '操作人名字'
     * @return string
     */
    public function addAdminLog($gid,$oid,$action,$name,$sid = "", $uid = "",$reason = "")
    {

        if (!empty($sid)){
            $params = [
                'sid'         => $sid,
                'gid'         => $gid,
                'oid'         => $oid,
                'action'      => $action,
                'action_user' => $name,
                'action_time' => time()
            ];
            $dbName = 'shop_admin_log';
        }else{
            $params = [
                'uid'         => $uid,
                'gid'         => $gid,
                'oid'         => $oid,
                'action'      => $action,
                'reason'      => $reason,
                'action_user' => $name,
                'action_time' => time()
            ];
            $dbName = 'sys_admin_log';
        }

        $res = Db::name("$dbName")
            ->insert($params);

        if ($res !== false){
            return true;
        }else{
            return false;
        }
    }

    public function addSysAdminLog($id, $action, $name, $reason = "", $sid = "")
    {

        $params = [
            'id'          => $id,
            'action'      => $action,
            'reason'      => $reason,
            'action_user' => $name,
            'action_time' => time()
        ];

        $res = Db::name("sys_admin_log")
            ->insert($params);

        if ($res !== false){
            return true;
        }else{
            return false;
        }
    }


    /**
     * 根据 wxid(unid)判断是否加盟
     * @param $unionid
     * @return array|false|mixed|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function checkIsJoin($unionid)
    {
        $shopEnterpriseModel = new ShopEnterprise();

        $res = $shopEnterpriseModel
            ->where("wxid",$unionid)
            ->find();

        $res = json_decode(json_encode($res),true);
        if (!empty($res)) {
            $review_desc = $res['review_desc'];
            $res['review_desc'] = htmlspecialchars_decode($review_desc);
        }
        return $res;
    }

    /**
     * 根据eid获取加盟申请信息
     * @param $eid
     * @return array|false|mixed|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function eidGetJoinInfo($eid)
    {
        $shopEnterpriseModel = new ShopEnterprise();
        $res = $shopEnterpriseModel
            ->where("eid",$eid)
            ->find();

        $res = json_decode(json_encode($res),true);
        return $res;
    }

    /**
     * 根据gid获取商品信息
     * @param $gid
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function gidGetInfo($gid)
    {
        $goodsModel = new DtsGoods();

        $res = $goodsModel
            ->where('gid',$gid)
            ->find();

        return $res;
    }

    /**
     * 判断此手机号码是否已绑定
     * @param $phone
     * @return bool
     */
    public function checkPhoneIsOnly($phone,$uid)
    {
        $userModel = new User();

        $is_exist = $userModel
            ->where('phone',$phone)
            ->where('uid','neq',$uid)
            ->count();

        if ($is_exist){
            return false;
        }else{
            return true;
        }
    }

    /**
     * 获取需要的后台设置的系统信息
     * @param $keys
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getSettingInfo($keys)
    {
        $key_array = explode(",",$keys);

        $sysSettingModel = new SysSetting();
        $res = array();

        foreach ($key_array as $key => $val) {
            $info = $sysSettingModel
                ->where('key',$val)
                ->field('value')
                ->find();

            $info = json_decode($info,true);
            $res[$val] = $info['value'];
        }

        return $res;
    }

    /********************************************My Function Off***************************************************/
}
