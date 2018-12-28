<?php 
	#获取更多历史记录
	if($_GET["page"] == -1){
		#获取redis中的1000条历史消息
		$r=new Redis();
		$r->connect("127.0.0.1",6379);
		
		$msg_ids=$r->lrange("new_msg",0,-1);
		$msgs=[];
		foreach($msg_ids as $id){
			$msgs[]=$r->hgetall("msg:id:".$id);
		}
		
	}else{
		$page=$_GET["page"];
		$rows=$_GET["rows"];
		
		$link=mysqli_connect("127.0.0.1","root","573234044","chatroom");
		mysqli_query($link,"set names utf8");
		$sql="select msg.*,user.name from user join msg on msg.user_id = user.id order by id desc limit ".($page-1)*$rows.",".$rows;
		$res=mysqli_query($link,$sql);
		$msgs=[];
		if($res && mysqli_num_rows($res)){
			while($msg=mysqli_fetch_assoc($res)){
				$msgs[]=$msg;
			}
			mysqli_free_result($res);
		}else{
			$data=[
				"errno"=>201,
				"errmsg"=>"没有更多历史记录了"
			];
			echo json_encode($data);
			die;			
		}
		
		mysqli_close($link);
	}
	
	$data=[
		"errno"=>0,
		"data"=>array_reverse($msgs)
	];
	
	echo json_encode($data);
?>