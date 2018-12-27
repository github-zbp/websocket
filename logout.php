<?php 
	session_start();
	$_SESSION["user"]=null;
	$return=[
		"errno"=>0,
		"data" =>"ok"
	];
	echo json_encode($return);
	
?>