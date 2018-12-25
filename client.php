<?php 
header("content-type:text/html;charset=gbk");
	#创建一个socket服务
	$socket = socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
	
	#连接服务器端
	if(!socket_connect($socket,'127.0.0.1',8888)){
		echo "连接服务器端失败：".socket_strerror(socket_last_error());
		die;
	}
	
	$info="i love you";
	
	#向服务端写入信息
	if(!socket_write($socket,$info,strlen($info))){
		echo "向服务端写入信息失败";
		die;
	}
	
	#读取从服务器返回的数据
	if($cb = socket_read($socket,1024)){
		echo $cb."<br/>";
	}else{
		echo "没有返回";
	}
	
	socket_close($socket);  #关闭socket
?>