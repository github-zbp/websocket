<?php 
	session_start();
	
	#判断是否已登录
	if(!empty($_SESSION["user"])){
		$return=[
			"errno"=>101,
			"errmsg"=>"您已登录"
		];
		echo json_encode($return);
		die;
	}
	
	$link=mysqli_connect("127.0.0.1","root","573234044","chatroom");
	
	mysqli_query($link,"set names utf8");
	
	#执行注册逻辑
	$name=$_POST["name"];
	$password=md5($_POST["password"]);
	
	$sql="select * from user where name = '{$name}'";
	$res=mysqli_query($link,$sql);
	if($res && mysqli_num_rows($res)){
		$return=[
			"errno"=>102,
			"errmsg"=>"用户已存在"
		];
		echo json_encode($return);
		die;
	}
	
	$sql="insert into user values (null,'{$name}','{$password}')";
	$res=mysqli_query($link,$sql);
	if($res){
		$return=[
			"errno"=>0,
			"data" => "ok"
		];
		echo json_encode($return);
	}
	
	
?>