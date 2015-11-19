<?php

class Process
{
	private $task;
	
	/**
	 * 创建子进程
	 *
	 */
	public function create_process($task)
	{
		$this->task = $task;
		$process = new swoole_process(array($this,'run'), true);
		$pid = $process->start();
	}
	
	/**
     * 子进程执行的入口
     * @param $worker
     */
	public function run(swoole_process $worker)
	{
		$worker->exec($this->task['task']['parse'], $this->task['task']['ext']);
	}
	
}