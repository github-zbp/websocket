<?php 
header("content-type:text/html;charset=gbk");
	#����һ��socket����
	$socket = socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
	
	#���ӷ�������
	if(!socket_connect($socket,'127.0.0.1',8888)){
		echo "���ӷ�������ʧ�ܣ�".socket_strerror(socket_last_error());
		die;
	}
	
	$info="i love you";
	
	#������д����Ϣ
	if(!socket_write($socket,$info,strlen($info))){
		echo "������д����Ϣʧ��";
		die;
	}
	
	#��ȡ�ӷ��������ص�����
	if($cb = socket_read($socket,1024)){
		echo $cb."<br/>";
	}else{
		echo "û�з���";
	}
	
	socket_close($socket);  #�ر�socket
?>