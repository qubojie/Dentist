<?php
/**
 * 计划任务.
 * User: qubojie
 * Date: 2018/10/12
 * Time: 下午2:22
 */
namespace app\common\lib;

use think\Config;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;
use think\Exception;

class Crond extends Command
{
    protected function configure()
    {
        $this->setName('Cron')
            ->setDescription('计划任务');
    }

    protected function execute(Input $input, Output $output)
    {
        $this->doCron();
        $output->writeln("已经执行计划任务");
    }

    public function doCron()
    {
        $GLOBALS['_beginTime'] = microtime(TRUE);

        /*永不超时*/
        ini_set('max_execution_time' , 0);
        $exe_method = [];
        $crond_list      = Config::get('crond');
        $sys_crond_timer = Config::get('sys_crond_timer');

        foreach ($sys_crond_timer as $format) {
            $key = date($format , ceil(time()));
            if (is_array(@$crond_list[$key])) {
                $exe_method = array_merge($exe_method , $crond_list[$key]);
            }
        }

        if (!empty($exe_method)) {
            Db::startTrans();
            try{
                foreach ($exe_method as $method) {
                    if (!is_callable($method)) {
                        //方法不存在跳过不执行即可
                        continue;
                    }
                    echo "执行crond --- {$method}()\n";
                    $runtime_start = microtime(true);
                    $res = call_user_func($method);
                    $runtime = microtime(true) - $runtime_start;
                    echo "{$method}(), 执行时间: {$runtime}\n\n";

                    //TODO 暂未定义日志记录位置
                    $dateTimeFile = APP_PATH."定义日志记录位置".date("Ym")."/";
                    if (!is_dir($dateTimeFile)) @mkdir($dateTimeFile);
                    if ($res === 1) {
                        Db::commit();
                        error_log(date('Y-m-d H:i:s')." {$method}(), 执行时间: {$runtime} , 执行成功 \n",3,$dateTimeFile.date("d").".log");
                    } elseif ($res === 2) {
                        //什么都没有做,不用记录任何信息
                    } else {
                        //记录执行失败日志
                        error_log(date('Y-m-d H:i:s')." {$method}(), 执行时间: {$runtime} , 记录执行失败日志 \n",3,$dateTimeFile.date("d").".log");
                    }
                }
                $time_total = microtime(true) - $GLOBALS['_beginTime'];
                echo "total:{$time_total}\n";

            }catch (Exception $e){
                Db::rollback();
                error_log(date('Y-m-d H:i:s')." {$method}(), 执行时间: {$runtime} , 记录执行失败日志 \n".$e->getMessage(),3,$dateTimeFile.date("d").".log");
            }
        }

    }
}