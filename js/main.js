$(function(){
	var userInfo={};
	var login_url="/login.php";
	var register_url="/register.php";
	var logout_url="/logout.php";
	var history_url="/history.php";
	var more_history_url="/more_history.php";
	var logined_user_url="/logined_user.php";
	var login_btn=$("#login");
	var register_btn=$("#register");
	var reg_btn=$("#reg_btn");
	var log_btn=$("#login_btn");
	var history_btn=$(".more-history");
	var ui=$(".user-info");
	var rl=$(".rl");
	var timer=null;
	var beginTime=0;
	var diffTime=0;
	
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
	
	//初始化获取最新的100条消息记录
	$.get(history_url,{},function(d){
		if(!d.errno){
			var msgs=d.data;
			$.each(msgs,function(i,v){
				showMsg(v);
			});
		}
	},"json");
	
	//初始化获取所有在线用户
	$.get(logined_user_url,{},function(d){
		if(!d.errno){
			var users=d.data;
			$.each(users,function(i,v){
				console.log(v);
				showUser(v);
			});
		}
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
	
	//点击获取更多历史记录
	history_btn.click(function(){
		var rows=50;   //一次获取50条
		var page=$(this).attr("page");
		var _this=$(this);
		$.get(more_history_url,{"page":page,"rows":rows},function(d){
			if( page < 0 ){
				//直接在对话框中显示历史记录
				html="";
				$.each(d.data,function(i,v){
					html+=createTalkHtml(v);
				});
				$(".talk-ul").html(html);
				_this.attr("page",1);
			}else{
				if(d.errno){
					alert(d.errmsg);
				}else{
					$("#more-history-modal").html(d.data).modal();
					
				}
			}
		},"json");
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
				location.href=location.href;
			}
		},"json");
	});
	
	//退出
	$("#logout").click(function(){
		$.post(logout_url,{},function(d){
			if(!d.errno){
				var logout_data={
					"action":"logout",
					"user_id":userInfo.id
				};
				
				//发送消息说自己退出了
				sendMsg(logout_data);
				
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
			}else if(data.action == "logout"){
				dropUser(data);
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
		var html=createTalkHtml(msg);
		
		$(".talk-ul").append(html);
		
		var ul_height=0;
		$(".talk-li").each(function(){
			ul_height+=$(this).outerHeight(true);
		});
		
		$(".show-talk").scrollTop(ul_height);
	}
	
	//显示用户
	function showUser(user){
		//如果用户列表中已经存在了该用户,那么就不再添加
		$(".user").each(function(){
			if($(this).attr("uid") == user.user_id){
				$(this).remove();
			}
		});
		
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
	
	//右侧用户栏删掉退出的用户
	function dropUser(user){
		$(".user").each(function(){
			if($(this).attr("uid") == user.user_id){
				$(this).remove();
			}
		});
	}
	
	//构建聊天内容的html
	function createTalkHtml(msg){
		var html=""
		if(msg.user_id == userInfo.id){
			html+='<li class="talk-li talk-li-right">';
		}else{
			html+='<li class="talk-li">';
		}
		
		html+='<div class="time"><span >'+ fmtDate(msg.inputtime) + '</span>&nbsp;&nbsp;&nbsp;&nbsp;<span>'+ msg.name +'</span></div>';
		html+='<div class="content"><div>'+ msg.content +'</div></div>';
		html+='</li>';
		
		return html;
	}
});
	
