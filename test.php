<?php

// date_default_timezone_set('PRC');
class Test
{
	// static private $options = "hdrmp:s:l:c:";
    // static private $longopts = array("help", "http", "daemon", "checktime", "reload", "monitor", "pid:", "log:", "config:", "host:", "port:");
    
	static private $turntable = array();
	static private $current_task = ''; 
	
	static function run()
	{
		swoole_set_process_name('lzm_Crontab');
		self::load_config();
		$run = true;
        echo ("正在启动...");
        while ($run) {
            $s = date("s");
            if ( $s == 0) {
                self::register_timer();
                $run = false;
            }else{
                echo ("启动倒计时 ".(60-$s)." 秒\n");
                sleep(1);
            }
        }
	}
	
	/**
     *  注册定时任务
     */
    static protected function register_timer()
    {
        swoole_timer_tick(60000, function ($interval) {
            Test::load_config();
        });
        swoole_timer_tick(1000, function ($interval) {
            Test::do_something($interval);
        });
    }
	
	static function load_config()
	{	
		$time = time();
        $config = include(dirname(__FILE__).'/config.php');
        foreach ($config as $id => $task) {
			// 先支持秒（, *） 这两种
            $ret = self::_parse_cron_number($task["time"], 0, 59);
			// 设置每秒的任务
			foreach ($ret as $sec) {
				self::$turntable[$sec][$id] = $task;
			}
        }
	}
	
	static protected function _parse_cron_number($s, $min, $max)
    {
        $result = array();
        $v1 = explode(",", $s);
        foreach ($v1 as $v2) {
			// 增加-,/方式
			$v3 = explode('/', $v2);
			$step = empty($v3[1]) ? 1 : $v3[1];
			$v4 = explode("-", $v3[0]);
			
			$_min = count($v4)==2 ? $v4[0] : ($v3[0] == "*" ? $min : $v3[0]);
			$_max = count($v4)==2 ? $v4[1] : ($v3[0] == "*" ? $max : $v3[0]);
            for ($i = $_min; $i <= $_max; $i += $step) {
                $result[$i] = intval($i);
            }
        }
        ksort($result);
        return $result;
    }
	
	static function do_something($interval)
	{
		// 在执行获取任务的时候，只获取当前秒的任务
		$tasks_arr = self::$turntable;
		$current_sec = intval(date('s'));
		if( !isset($tasks_arr[$current_sec])) {
			return false;
		}
		$tasks = $tasks_arr[$current_sec];
		foreach ($tasks as $id => $task) {
			self::$current_task = $task;
			$process = new swoole_process(function($worker){ Test::run_process($worker); }, true);
			$pid = $process->start();
		}

        return true;
	}
	
	static function run_process(swoole_process $worker)
	{
		$task = self::$current_task;
		$worker->exec($task['task']['parse'], $task['task']['ext']);
	}
}



Test::run();