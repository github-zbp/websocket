$(function(){
	var userInfo={};
	var login_url="/login.php";
	var register_url="/register.php";
	var logout_url="/logout.php";
	var login_btn=$("#login");
	var register_btn=$("#register");
	var reg_btn=$("#reg_btn");
	var log_btn=$("#login_btn");
	var ui=$(".user-info");
	var rl=$(".rl");
	var timer=null;
	
	// $(".show-talk").scrollTop(100);
	
	//初始化获取用户信息
	$.get(login_url,{"init":1},function(d){
		if(!d.errno){
			userInfo=d.data;
			delete(userInfo.password);
			rl.hide()
			ui.find("span").html(userInfo.name);
			ui.find("img").attr("src","/images/"+userInfo.img);
			ui.show();
		}
		console.log(userInfo);
	},"json");
	
	//登陆弹框
	login_btn.click(function(){
		$("#tab_login").trigger("click");
		$("#modal").modal();
	});
	register_btn.click(function(){
		$("#tab_reg").trigger("click");
		$("#modal").modal();
	});
	
	//登陆 
	log_btn.click(function(){
		var data=$("#f-login").serialize();
		$.post(login_url,data,function(d){
			if(d.errno){
				alert(d.errmsg);
			}else{
				userInfo=d.data;
				delete(userInfo.password);
				$("#modal").modal("hide");
				rl.hide()
				ui.find("span").html(userInfo.name);
				ui.find("img").attr("src","/images/"+userInfo.img);
				ui.show();
				
				//将本人登陆的消息发送给服务端并通知所有的其他用户
				var data={
					"action":"login",
					"user_id":userInfo.id,
					"head":userInfo.img,
					"name":userInfo.name,
					"logintime":Date.parse(new Date())/1000
				};
				
				sendMsg(data);
			}
		},"json");
	});
	
	//退出
	$("#logout").click(function(){
		$.post(logout_url,{},function(d){
			if(!d.errno){
				userInfo={};
				rl.show();
				ui.hide();
			}
			
		},"json");
	});
	
	//注册
	reg_btn.click(function(){
		if($("#password2").val() != $("#password3").val()){
			alert("两次密码不相同");
			return false;
		}
		var data=new FormData($("#f-register").get(0));
		$.ajax({
			url:register_url,
			data:data,
			type:"post",
			processData:false,
			contentType:false,
			success:function(d){
				
				if(d.errno){
					alert(d.errmsg);
				}else{
					alert("注册成功,请登录");
					$("#tab_login").trigger("click");
				}
			},
			
			dataType:"json"
		});
		
	});
	
	//初始化websocket
	var socket_ip="ws://www.talk.cn:8888/";
	var websocket= window.WebSocket || window.MozWebSocket;
	var ws=new websocket(socket_ip);
	listenWS();
	
	
	var send_btn=$("#send");
	var txtarea=$("#t1");
	send_btn.click(function(){
		var data={
			"action":"talk",
			"user_id":userInfo.id,
			"name":userInfo.name,
			"content":txtarea.val(),
			"inputtime":Date.parse(new Date())/1000
		};
		
		txtarea.val("");
		sendMsg(data);
	});
	
	txtarea.keypress(function(ev){
		var e=ev || event;
		if(e.keyCode == 13){
			$("#send").trigger("click");
			return false;
		}
	});
	
	$("#close").click(function(){
		ws.close();
		console.log(ws);
	});
	
	
	function fmtDate(timeStamp,type=1) { 
		var date = new Date();
		date.setTime(timeStamp * 1000);
		var y = date.getFullYear();    
		var m = date.getMonth() + 1;    
		m = m < 10 ? ('0' + m) : m;    
		var d = date.getDate();    
		d = d < 10 ? ('0' + d) : d;    
		var h = date.getHours();  
		h = h < 10 ? ('0' + h) : h;  
		var minute = date.getMinutes();  
		var second = date.getSeconds();  
		minute = minute < 10 ? ('0' + minute) : minute;    
		second = second < 10 ? ('0' + second) : second;   
		if(type != 1){
			return h+':'+minute+':'+second;
		}
		return y + '-' + m + '-' + d+' '+h+':'+minute+':'+second;    
	};

	function listenWS(){
		//监听是否连到服务端
		ws.addEventListener("open",function(){
			console.log("连接成功,状态:"+this.readyState);
		});
		
		//监听服务端传来的数据
		ws.addEventListener("message",function(evt){
			var data=$.parseJSON(evt.data);
			if(data.action == "login"){
				showUser(data);
			}else if(data.action == "talk"){
				showMsg(data);
			}
			
		});
		
		//监听服务端关闭该客户端的socket
		ws.addEventListener("close",function(){
			console.log(ws);
			clearInterval(timer);   //这里添加这句是怕如果一直连接不到服务端,那么计时器会递归执行,生出无数个计时器最后卡死,所以在onclose事件开头先把上一次开启的计时器干掉先
			//现在考虑一种情况,如果用户网络不稳定,或者时断时连,那么断网的那一刻,客户端的socket就和服务端断开了,此时我们要自动再连上服务端
			timer=setInterval(function(){
				if(ws.readyState == 1){
					clearInterval(timer);
				}else{
					ws=new websocket(socket_ip);
					listenWS();
				}
				
			},1000);
			console.log("客户端关闭");
		});
	}
	
	//msg是json
	function sendMsg(msg){
		var msg_str=JSON.stringify(msg);
		ws.send(msg_str);
	}
	
	//显示消息
	function showMsg(msg){
		var html=""
		if(msg.user_id == userInfo.id){
			html+='<li class="talk-li talk-li-right">';
		}else{
			html+='<li class="talk-li">';
		}
		
		html+='<div class="time"><span >'+ fmtDate(msg.inputtime) + '</span>&nbsp;&nbsp;&nbsp;&nbsp;<span>'+ msg.name +'</span></div>';
		html+='<div class="content"><div>'+ msg.content +'</div></div>';
		html+='</li>';
		
		$(".talk-ul").append(html);
		
		var ul_height=0;
		$(".talk-li").each(function(){
			ul_height+=$(this).outerHeight(true);
		});
		console.log(ul_height);
		$(".show-talk").scrollTop(ul_height);
	}
	
	//显示用户
	function showUser(user){
		// <div class="col-md-12 user" >
			// <div class="user-head">
				// <img src="/images/default.png" >
			// </div>
			// <div class="user-name">
				// <div class="name" >用&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;户 : <span>jqsf</span></div>
				// <div class="login-time" >登陆时间 : <span>11:54:32</span></div>
			// </div>
		// </div>
		var html=""
		html+='<div class="col-md-12 user" uid="'+ user.user_id +'"><div class="user-head">';
		html+='<img src="/images/'+ user.head +'" >';
		html+='</div><div class="user-name">';
		html+='<div class="name" >用&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;户 : <span>'+ user.name +'</span></div>';
		html+='<div class="login-time" >登陆时间 : <span>'+ fmtDate(user.logintime) +'</span></div>';
		html+='</div></div>';
		
		$(".users").prepend(html);
		
		var height=0;
		$(".user").each(function(){
			height+=$(this).outerHeight(true);
		});
		// console.log(ul_height);
		$(".users").scrollTop(height);
	}

});
	
