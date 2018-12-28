<?php 
	session_start();
	
	#判断是否已登录
	if(!empty($_SESSION["user"])){
		$return=[
			"errno"=>0,
			"data" =>$_SESSION['user']
		];
		echo json_encode($return);
		die;
	}
	
	#初始化获取登陆信息则不执行登陆逻辑
	if(isset($_GET["init"])){
		$return=[
			"errno"=>104,
			"errmsg" =>""
		];
		echo json_encode($return);
		die;
	}
	
	$link=mysqli_connect("127.0.0.1","root","573234044","chatroom");
	
	mysqli_query($link,"set names utf8");
	
	#执行登陆逻辑
	$name=$_POST["name"];
	$password=md5($_POST["password"]);
	
	$sql="select * from user where name='{$name}' and password='{$password}'";
	$res=mysqli_query($link,$sql);
	if($res && mysqli_num_rows($res)){
		$user=mysqli_fetch_assoc($res);
	}else{
		$return=[
			"errno"=>103,
			"errmsg" =>"登陆失败"
		];
		echo json_encode($return);
		die;
	}
	
	
	#用户信息存入到redis中
	$r = new \Redis();
	$r->connect("127.0.0.1",6379);	
	
	#写入聊天内容到hash
	$userInfo=[
		"user_id"=>$user["id"],
		"name"=>$user['name'],
		"head"=>$user["img"],
		"logintime"=>time()
	];
	
	$r->hmset("login_user:id:".$userInfo["user_id"],$userInfo);
	
	#用户登陆顺序写入列表
	$r->lrem("login_user",$userInfo['user_id']);
	$r->lpush("login_user",$userInfo["user_id"]);
	
	$_SESSION["user"]=$user;
	
	$return=[
		"errno"=>0,
		"data" =>$_SESSION['user']
	];
	echo json_encode($return);
?>