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
		*  $msg是一个json字符串，包含用户id(user_id)，名称(name)，消息内容(content),发送时间(inputtime)
		*  要将所有消息都存入到redis中
		*/
		public function communicate($socket,$msg){
			#解码
			$msg=$this->msg_decode($msg);
			$msg_arr=json_decode($msg,true);
			
			if($msg_arr["action"] == "talk"){
				#判断$msg中是否含有user_id,如果没有,说明发消息的用户是游客,则该消息不发送会客户端
				if(!isset($msg_arr["user_id"]) || !$msg_arr["user_id"]){
					return false;
				}
				
				$this->storeMsg($msg_arr);
			}
			
			echo "Return message '{$msg_arr['content']}' to ".$this->getSockInfo($socket);
			
			#编码后发送给所有在线上的客户端socket
			$res_msg=$this->msg_encode($msg);
			// var_dump($res_msg);
			foreach($this->clients as $client){
				socket_write($client["socket"],$res_msg,strlen($res_msg));
			}
			
		}
		
		/*
		* $msgInfo中要包含user_id,content,inputtime
		*
		*
		*/
		public function storeMsg($msgInfo){
			// echo "redis";
			$r = new \Redis();
			$r->connect("127.0.0.1",6379);
			
			#自增
			$msg_id=$r->incr("global:msg");
			
			#写入聊天内容到hash
			$msg=[
				"id"=>$msg_id,
				"user_id"=>$msgInfo["user_id"],
				"name"=>$msgInfo['name'],
				"content"=>$msgInfo["content"],
				"inputtime"=>$msgInfo["inputtime"]
			];
			$r->hmset("msg:id:".$msg_id,$msg);
			
			#记录最新的1000条消息,其他的放到任务队列中
			$r->lpush("new_msg",$msg_id);
			if($r->lsize("new_msg")>1000){
				$r->rpoplpush("new_msg","queue:msg");
			}
			
		}
		
	}
	
	$chat=new Chat();
	$chat->run();
?>