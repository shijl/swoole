<?php

class Server
{
	private $serv;
	
	public function __construct()
	{
		$this->serv = new swoole_server("0.0.0.0", 9501);
		$this->serv->set(array(
			'worker_num' => 4,
			'daemonize' => false,
			'max_request' => 2000,
			'dispatch_mode' => 2,
			'debug_mode' => 1,
			'task_worker_num' => 4
		));
		
		$this->serv->on('Start', array($this, 'onStart'));
		$this->serv->on('WorkerStart', array($this, 'onWorkerStart'));
        $this->serv->on('Connect', array($this, 'onConnect'));
        $this->serv->on('Receive', array($this, 'onReceive'));
        $this->serv->on('Close', array($this, 'onClose'));
        // bind callback
        $this->serv->on('Task', array($this, 'onTask'));
        $this->serv->on('Finish', array($this, 'onFinish'));
        $this->serv->start();
	}
	
	public function onStart($serv)
	{
		echo "start\n";
	}
	
	public function onWorkerStart($serv, $worker_id)
	{
		echo "onWorkerStart\n";
		if($worker_id == 0) {
			$serv->tick(10000, array($this, 'putContents'));
		}
	}
	
	public function onConnect($serv, $fd, $from_id)
	{
		echo "Client {$fd} connect\n";
	}
	
	
	
	public function onClose($serv, $fd, $from_id)
	{
		echo "Client {$fd} close connection\n";
	}
	
	public function onReceive(swoole_server $serv, $fd, $from_id, $data)
	{
		echo "Get Message from client {$fd}:{$data}:{$from_id}\n";
		$params = array('fd'=>$fd,'file'=>$data);
		$id = $serv->task(json_encode($params));
		echo "Continue Handle Worker {$id}\n";
	}
	
	public function onTask($serv, $task_id, $from_id, $data)
	{
		echo "this task {$task_id} from worker {$from_id}\n";
		echo "Data: {$data}\n";
		// sleep(10);
		$file = json_decode($data,true)['file'];
		// $url = 'http://test.tv/'.$file.'.php';
		// $url = 'http://test.tv/test.php';
		// $a = file_get_contents($url);
		$a = $this->$file();
		return $a.'---'.$data;
	}
	
	public function onFinish($serv, $task_id, $data)
	{
		echo "Task {$task_id} finish\n";
		$name = "log".$task_id.".txt";
		file_put_contents($name,$data);
	}
	
	public function test()
	{
		sleep(15);
		return 'test';
	}
	public function test1()
	{
		sleep(5);
		return 'test1';
	}
	
	public function putContents($id)
	{
		$name = dirname(__FILE__)."/log".time().".txt";
		$data = time();
		file_put_contents($name,$data);
	}
}
$server = new Server();