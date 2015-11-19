<?php

define('DS', DIRECTORY_SEPARATOR);
define('ROOT_PATH', realpath(dirname(__FILE__)) . DS);

class Test
{
	// static private $options = "hdrmp:s:l:c:";
    // static private $longopts = array("help", "http", "daemon", "checktime", "reload", "monitor", "pid:", "log:", "config:", "host:", "port:");
    
	static private $turntable = array();
	static private $current_task = ''; 
	
	static function run()
	{
		self::spl_autoload_register();
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
     * 注册类库载入路径
     */
    static public function spl_autoload_register()
    {
        spl_autoload_register(function ($name) {
            $file_path = ROOT_PATH . DS . $name . ".class.php";
            include $file_path;
        });
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
        $config = include(ROOT_PATH.DS.'config.php');
        foreach ($config as $id => $task) {
			// 先支持秒（, *） 这两种
            $ret = ParseCrontab::parse($task["time"], $time);
			// 设置每秒的任务
			foreach ($ret as $sec) {
				self::$turntable[$sec][$id] = $task;
			}
        }
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
			(new Process)->create_process($task);
		}

        return true;
	}
}

Test::run();