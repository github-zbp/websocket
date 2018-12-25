$(function(){
	//初始化websocket
	websocket= window.WebSocket || window.MozWebSocket;
	var ws=new websocket("ws://www.talk.cn:8888/echo");
	
	//监听是否连到服务端
	ws.onopen=function(){
		console.log("连接成功");
	}
	
	//监听服务端传来的数据
	ws.onmessage=function(evt){
		console.log(evt);
	}
	
	//监听服务端关闭该客户端的socket
	ws.onclose=function(){
		console.log("客户端关闭");
	}
	
	var send_btn=$("#send");
	var txtarea=$("#t1");
	send_btn.click(function(){
		var data=txtarea.val();
		txtarea.html("");
		ws.send(data);
	});
});
	
