<?php
	// header("content-type:text/html;charset=gbk");
	#不限时脚本
	set_time_limit(0);
	
	ob_implicit_flush(true);
	
	#创建socket
	if(!$sock=socket_create(AF_INET,SOCK_STREAM,SOL_TCP)){
	    echo "Create socket service fail:".getErr($sock);
	}
	
	#绑定ip和端口
	if(!socket_bind($sock,"127.0.0.1",8888)){
		echo "Bind fail:".getErr($sock);
	}
	
	#监听
	if(!socket_listen($sock,128)){
		echo "Listen fail:".getErr($sock);
	}
	
	#定义3个被监听的sockets组
	$read_sockets=[$sock];	#用于被读取信息的socket
	$write_sockets=[];		#用于被写入信息的socket
	$except_sockets=null;
	
	while(true){
		$tmp_reads=$read_sockets;
		$tmp_writes=$write_sockets;
		
		#监听多个socket
		socket_select($tmp_reads,$tmp_writes,$except_sockets,null);
		
		#接受$tmp_reads中的信息
		foreach($tmp_reads as $key => $read_sock){
			if($read_sock == $sock){
				#接受客户端连过来的socket
				$client=socket_accept($read_sock);
				
				#将这个socket存入到$read_sockets
				if($client){
					#先获取他的IP 端口 信息
					echo "Connect ".getSockInfo($client);
					
					#再存入
					$read_sockets[]=$client;
					$write_sockets[]=$client;
				}
			}else{
				#遍历到客户端的socket时就获取里面的数据
				$data=socket_read($read_sock,1024);
				echo $data.PHP_EOL;
				#如果$data为空，说明客户端的socket要关闭
				if($data == ''){
					#移除对该客户端socket的监听
					foreach($write_sockets as $k => $v){
						if($v == $read_sock){
							unset($write_sockets[$k]);
							break;
						}
					}
					foreach($read_sockets as $k => $v){
						if($v == $read_sock){
							unset($read_sockets[$k]);
							break;
						}
					}
					
					echo "Close ".getSockInfo($read_sock);
					socket_close($read_sock);
				}else{
					#将该用户的信息发送给所有的客户端
					foreach($write_sockets as $k => $v){
						socket_write($v,$data);
					}
					
				}
			}
		}
	}
	
	#关闭服务端socket
	socket_close($sock);
	
	#返回socket错误
	function getErr($socket){
		return socket_strerror(socket_last_error($socket)).PHP_EOL;
	}
	
	#输出客户端socket的IP和端口
	function getSockInfo($socket){
		socket_getpeername($socket,$ip,$port);
		return "client  IP:".$ip." port:".$port.PHP_EOL;
	}
	
	function handshake($k,$buffer){
        //截取Sec-WebSocket-Key的值并加密
        $buf  = substr($buffer,strpos($buffer,'Sec-WebSocket-Key:')+18);
        $key  = trim(substr($buf,0,strpos($buf,"\r\n")));
        $new_key = base64_encode(sha1($key."258EAFA5-E914-47DA-95CA-C5AB0DC85B11",true));

        //按照协议组合信息进行返回
        $new_message = "HTTP/1.1 101 Switching Protocols\r\n";
        $new_message .= "Upgrade: websocket\r\n";
        $new_message .= "Sec-WebSocket-Version: 13\r\n";
        $new_message .= "Connection: Upgrade\r\n";
        $new_message .= "Sec-WebSocket-Accept: " . $new_key . "\r\n\r\n";
        socket_write($this->users[$k]['socket'],$new_message,strlen($new_message));

        //对已经握手的client做标志
        $this->users[$k]['handshake']=true;
        return true;
    }
