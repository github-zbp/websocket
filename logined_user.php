<?php 
	//获取历史信息
	$r = new Redis();
	$r->connect("127.0.0.1",6379);
	
	//获取所有登陆了的用户
	$user_ids=$r->lrange("login_user",0,-1);
	$users=[];
	foreach($user_ids as $k => $user_id){
		$users[]=$r->hgetAll("login_user:id:".$user_id);
	}
	
	$users=array_reverse($users);
	
	$data=[
		"errno"=>0,
		"data" =>$users
	];
	
	echo json_encode($data);
?>