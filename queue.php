<?php 
	//获取历史信息
	$r = new Redis();
	$r->connect("127.0.0.1",6379);
	
	//获取任务队列
	$rows=1000;
	$sql="insert into msg values ";
	
	if(!$r->lsize("queue:msg")){
		echo "暂无任务";
		die;
	}
	
	
	for($i=0;$i<$rows;$i++){
		#没有任务则终止循环
		if(!$r->lsize("queue:msg")){
			break;
		}
		
		$msg_id=$r->rpop("queue:msg");
		$msg=$r->hgetall("msg:id:".$msg_id);
		$r->hdel("msg:id:".$msg_id);
		$sql.="({$msg_id},{$msg['user_id']},'{$msg['content']}',{$msg['inputtime']}),";
	}
	
	$sql=rtrim($sql,",");
	
	#连接数据库
	$link=mysqli_connect("127.0.0.1","root","573234044","chatroom");
	mysqli_query($link,"set names utf8");
	$res=mysqli_query($link,$sql);
	if($res){
		echo "写入".$rows."条短信到MySQL";
	}
?>