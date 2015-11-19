<?php
return array(
    'taskid1' =>
        array(
            'time' => '5-20/5',//定时规则
            'task' =>
                array(
                    'parse'   => '/usr/local/php5.5/bin/php',//命令
					'ext' => array('/home/swoole_async/test2.php'),
                ),
        ),
);