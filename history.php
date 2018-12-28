<?php 
	//获取历史信息
	$r = new Redis();
	$r->connect("127.0.0.1",6379);
	
	//获取最新的100条
	$msg_ids=$r->sort("new_msg",["sort"=>"desc","limit"=>[0,100]]);
	$msgs=[];
	foreach($msg_ids as $k => $msg_id){
		$msgs[]=$r->hgetAll("msg:id:".$msg_id);
	}
	$msgs=array_reverse($msgs);
	$data=[
		"errno"=>0,
		"data" =>$msgs
	];
	
	echo json_encode($data);
?>