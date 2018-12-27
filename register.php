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
	$head=$_FILES['head'];
	$destin="./images";
	
	
	
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
	
	#上传头像
	$fn=upload($destin,$head,["png","jpg","jpeg","gif","bmp"]);
	
	$sql="insert into user values (null,'{$name}','{$password}','{$fn}')";
	$res=mysqli_query($link,$sql);
	if($res){
		$return=[
			"errno"=>0,
			"data" => "ok"
		];
		echo json_encode($return);
	}
	
	function upload($destin,$file,$typearr=[])
	{
		if(!empty($file))
		{
			//目的路径
			$destin=rtrim($destin,'/').'/';
			
			//判断上传有没有什么错误！
			$error=$file['error'];
			if($error>0)
			{
				switch($error){
					case 1:$info="上传文件的大小超出了约定值。";break;
					case 2:$info="文件大小超过了隐藏域的MAX_FILE_SIZE规定的大小！";break;
					case 3:$info="文件只有部分上传！";break;
					case 4:$info="没有任何上传文件！";break;
					case 6:$info="找不到临时文件夹！";break;
					case 7:$info="写入文件失败！";break;
					default:$info='未知的上传文件错误！';
				}
				echo '上传失败！原因：'.$info;
				return false;
			}
			//判断文件类型是否被允许！
			//$typearr=array('jpg','png','gif');
			$filetype=explode('.',$file['name'])[1];
			if(count($typearr)>0)
			{
				if(!in_array($filetype,$typearr))
				{
					$info='您的文件格式不能上传！';
					return false;
				}
			}
			//判断文件大小
			$allowsize=1000000;
			$filesize=$file['size'];
			if($allowsize>0 && $filesize>$allowsize)
			{
				echo '您的文件大小超过了规定大小';
				return false;
			}
			//判断文件是否有重名！
			//写文件时千万别忘了加后缀哦！
			$ext=strstr($file['name'],'.');
			do{
				$filename=date("YmdHis").rand(100000,999999).$ext;
			}while(file_exists($destin.$filename));
			//判断是否是上传来的文件
			if(is_uploaded_file($file['tmp_name']))
			{
				if(move_uploaded_file($file['tmp_name'],$destin.$filename))
				{
					$info=$filename;
					return $filename;
				}
				else{
					echo "文件上传时失败！";
					return false;
				}
			}else{
				echo "您的文件不是一个上传文件";
				return false;
			}
		}else{
			$info="<script>alert('兄弟你是直接进入这个页面的吧...');history.go(-1);</script>";
			return false;
		}
	}
?>