<?php 
	include("server.php");
	
	class Chat extends WS
	{
		public $user=[];  #记录某个登陆了的用户信息，包括用户id，用户名，他的客户端socket，凡是登陆的用户才能发送消息，但是没登录也可以看到消息
		
		public function __construct($ip="192.168.1.100",$port=8888){
			parent::__construct($ip,$port);
			
			#判断是否登陆
			if(isset($_SESSION["user"])){
				$this->user=$_SESSION['user'];
			}
			
		}
		
		/*
		*  $msg是一个json字符串，包含用户id(user_id)，名称(name)，消息内容(content)
		*
		*/
		public function communicate($socket,$msg){
			#解码
			$msg=$this->msg_decode($msg);
			$msg_arr=json_decode($msg,true);
			
			#判断$msg中是否含有user_id,如果没有,则该消息不发送会客户端
			if(!isset($msg_arr["user_id"]) || !$msg_arr["user_id"]){
				return false;
			}
			
			echo "Return message '{$msg_arr['content']}' to ".$this->getSockInfo($socket);
			
			#编码后发送给所有在线上的客户端socket
			$res_msg=$this->msg_encode($msg);
			// var_dump($res_msg);
			foreach($this->clients as $client){
				socket_write($client["socket"],$res_msg,strlen($res_msg));
			}
			
		}
	}
	
	$chat=new Chat();
	$chat->run();
?>