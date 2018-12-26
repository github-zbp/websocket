<?php
	class WS
	{
		protected $sockets=[];  #存放所有客户端和服务端的sockets
		protected $w_sockets=[]; #存放所有可写入的sockets,即全部客户端的sockets
		protected $clients=[];  #存放所有客户端的socket和相关信息,每个socket有一个独立的key和两个信息,一个是socket自己,一个是是否握手
		protected $s_socket=null;
		
		public function __construct($ip="127.0.0.1",$port=8888){
			#创建服务端socket
			if(!$s_socket = socket_create(AF_INET,SOCK_STREAM,SOL_TCP)){
				echo "Create socket service fail : ".$this->getErr($s_socket);
				die;
			}
			
			#绑定ip和端口
			if(!socket_bind($s_socket,$ip,$port)){
				echo "Bind fail : ".getErr($s_socket);
				die;
			}
			
			#监听
			if(!socket_listen($s_socket,128)){
				echo "Listen fail : ".getErr($s_socket);
				die;
			}
			
			$this->s_socket=$s_socket;
			$this->sockets[]=$s_socket;
			
		}
		
		public function run(){
			$expire=null;
			while(true){
				$tmp_reads=$this->sockets;
				$tmp_writes=[];
				echo date("Y-m-d H:i:s").PHP_EOL;
				
				#使用socket_select()观察所有sockets的变化,如果没有sockets有变化,那么就会阻塞,不往下执行循环
				socket_select($tmp_reads,$tmp_writes,$expire,null);
				
				foreach($tmp_reads as $rs){
					if($rs == $this->s_socket){
						#接受客户端的socket
						$cs=socket_accept($this->s_socket);
						
						#将这个客户端socket存入对象属性中
						if($cs){
							$k=uniqid();  #起一个唯一的key
							$this->sockets[]=$cs;
							$this->clients[$k]=[
								"socket"=>$cs,
								"handshake"=>false
							];
							
							echo "Connect ".$this->getSockInfo($cs);
						}
						
					}else{
						#获取客户端的传过来的信息
						$msg=socket_read($rs,2048);
						// echo "Reading Message :".PHP_EOL;
						if($msg == ''){	#如果信息为空,那么说明客户端socket关闭了
							$this->disconnect($rs);
						}else{
							#判断是否握手
							$client_info=$this->getClientInfo($rs);
							if(!$client_info["handshake"]){
								#握手
								$this->handshake($rs,$msg);
							}else{
								#已经握手就通信
								$this->communicate($rs,$msg);
								
							}
						}
					}
				}
			}
			
		}
		
		protected function getClientInfo($socket){
			$k=$this->getClientKey($socket);
			return $this->clients[$k];
		}
		
		protected function getClientKey($socket){
			foreach($this->clients as $k => $v){
				if($socket == $v["socket"]){
					return $k;
				}
			}
		}
		
		/*
		* 握手发生在客户端连接服务端的时候,具体的过程就是客户端会发送一段请求报文,形如
		*GET / HTTP/1.1 
		*Host: localhost:1234 
		*User-Agent: Mozilla/5.0 (WindowsNT 10.0; WOW64; rv:59.0) Gecko/20100101 Firefox/59.0 
		*Accept:text/html,application/xhtml+xml,application/xml;q=0.9,/;q=0.8 
		*Accept-Language:zh-CN,zh;q=0.8,zh-TW;q=0.7,zh-HK;q=0.5,en-US;q=0.3,en;q=0.2 
		*Accept-Encoding: gzip, deflate 
		*Sec-WebSocket-Version: 13 
		*Origin:http://xxx.xxx.xxx 
		*Sec-WebSocket-Extensions: permessage-deflate 
		*Sec-WebSocket-Key: 3hfEc+Te7n7FSrLBsN59ig== 
		*Connection: keep-alive,Upgrade 
		*Pragma: no-cache 
		*Cache-Control: no-cache 
		*Upgrade: websocket
		*
		*这段报文会写入客户端的socket中,服务端要用socket_read()读取到上面的内容,并返回形如
		*HTTP/1.1 101 Switching Protocols 
		*Upgrade: websocket 
		*Sec-WebSocket-Version: 13 
		*Connection: Upgrade 
		*Sec-WebSocket-Accept:new-key
		*
		*的响应给客户端,通过socket_write()来写入客户端socket的方式传送
		*其中new-key的值通过先获取客户端请求头里面的Sec-WebSocket-Key值与258EAFA5-E914-47DA-95CA-C5AB0DC85B11进行字符串连接后进行sha1加密，再base64编码得到。 
		*这就是握手,客户端和服务端要握手之后才能进行正式通信
		*/
		protected function handshake($socket,$msg){
			$msg_arr=preg_split("/\r\n/",$msg);
			foreach($msg_arr as $k => $v){
				if(strpos($v,"Sec-WebSocket-Key:") !== false){
					$sw_key = trim(explode(":",$v)[1]);
				}
			}
			
			#对Sec-WebSocket-Key加密
			$new_key=base64_encode(sha1($sw_key."258EAFA5-E914-47DA-95CA-C5AB0DC85B11",true));
			$return=<<<EOF
				HTTP/1.1 101 Switching Protocols\r\nUpgrade: websocket\r\nSec-WebSocket-Version: 13\r\nConnection: Upgrade\r\nSec-WebSocket-Accept:{$new_key}\r\n\r\n
EOF;
			socket_write($socket,$return,strlen($return));
			
			#做握手标记
			$k=$this->getClientKey($socket);
			$this->clients[$k]["handshake"]=true;
			
			echo "Handshake ".$this->getSockInfo($socket).PHP_EOL;
		}
		
		#通信，该方法可用于重写
		protected function communicate($socket,$msg){
			$msg=$this->msg_decode($msg);
			echo "Return message '{$msg}' to ".$this->getSockInfo($socket);
			//编码后发送回去
			$res_msg=$this->msg_encode($msg);
			socket_write($socket,$res_msg,strlen($res_msg));

		}
		
		protected function getErr($socket){
			return socket_strerror(socket_last_error($socket)).PHP_EOL;
		}
		
		public function getSockInfo($socket){
			socket_getpeername($socket,$addr,$port);
			return " client IP ".$addr." port ".$port.PHP_EOL;
		}
		
		protected function disconnect($socket){
			#获取这个socket的key
			$k=$this->getClientKey($socket);
			unset($this->clients[$k]);
			foreach($this->sockets as $key => $val){
				if($val == $socket){
					unset($this->sockets[$key]);
				}
			}
			
			echo "Close ".$this->getSockInfo($socket);
			socket_close($socket);
		}
		
		 //编码 把消息打包成websocket协议支持的格式
			protected function msg_encode( $buffer ){
				$len = strlen($buffer);
				if ($len <= 125) {
					return "\x81" . chr($len) . $buffer;
				} else if ($len <= 65535) {
					return "\x81" . chr(126) . pack("n", $len) . $buffer;
				} else {
					return "\x81" . char(127) . pack("xxxxN", $len) . $buffer;
				}
			}

			//解码 解析websocket数据帧
			protected function msg_decode( $buffer )
			{
				$len = $masks = $data = $decoded = null;
				$len = ord($buffer[1]) & 127;
				if ($len === 126) {
					$masks = substr($buffer, 4, 4);
					$data = substr($buffer, 8);
				}
				else if ($len === 127) {
					$masks = substr($buffer, 10, 4);
					$data = substr($buffer, 14);
				}
				else {
					$masks = substr($buffer, 2, 4);
					$data = substr($buffer, 6);
				}
				for ($index = 0; $index < strlen($data); $index++) {
					$decoded .= $data[$index] ^ $masks[$index % 4];
				}
				return $decoded;
			}
	}
	
	// $ws=new WS();
	// $ws->run();
	