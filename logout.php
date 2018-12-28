<?php 
	session_start();

	$r = new \Redis();
	$r->connect("127.0.0.1",6379);
	
	#干掉缓存中该用户的hash和列表元素
	$user_id=$_SESSION['user']['id'];
	$r->del("login_user:id:".$user_id);
	$r->lrem("login_user",$user_id);
	$_SESSION["user"]=null;
	
	$return=[
		"errno"=>0,
		"data" =>"ok"
	];
	echo json_encode($return);
	
?>