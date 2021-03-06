/**
 * 
 */

// 登录后设置用户信息
function setUserInfo(user) {
	localStorage.setItem("user", JSON.stringify(user));
	localStorage.setItem("token", user.token);
}

// 获取用户信息
function getUserInfo() {
	var str = localStorage.getItem("user");
	return JSON.parse(str);
}

// 清除用户信息
function clearUserInfo() {
	localStorage.removeItem("user");
	localStorage.removeItem("token");
}

// 设置求青值token
function setHeaderToken(xhr) {
	var tokenVal = localStorage.getItem("token");
	if (tokenVal) {
		xhr.beforeSend = function() {
			xhr.headers['Token'] = tokenVal
		}
	}
	return xhr;
}

// 获取设置header对象
function getHeaderToken() {
	var tokenVal = localStorage.getItem("token");
	return {
		"Accept" : "application/json; charset=utf-8",
		"Token" : tokenVal
	};
}

// 设置请求头
function setHeader(xhr) {
	var tokenVal = localStorage.getItem("token");
	if (tokenVal) {
		xhr.beforeSend = function() {
			xhr.headers['Token'] = tokenVal
		}
	}
	return xhr;
}

// 获取设置header对象
function getHeader() {
	var tokenVal = localStorage.getItem("token");
	var aidVal = localStorage.getItem("aid");
	return {
		"Accept" : "application/json; charset=utf-8",
		"Token" : tokenVal
	};
}

//获取token
function getToken() {
	return localStorage.getItem("token");
}

//设置openid
function setOpenId(openid) {
	if (openid != "") {
		//微信浏览器存在sessionstorage  每次重新登录可保不失效
		// localStorage.setItem("openid", openid);
		sessionStorage.setItem("openid", openid);
	}	
}

//获取openid
function getOpenId() {
	// return localStorage.getItem("openid");
	return sessionStorage.getItem("openid");
}

//设置agent_id
function setAId(aid) {
	if (aid != "") {
		localStorage.setItem("aid", aid);
	}	
}

//获取agent_id
function getAId() {
	return localStorage.getItem("aid");
}

//调用微信JS api 支付  
function jsApiCall(jsApiParameters,jsApiUrl)  
{ 	
    WeixinJSBridge.invoke('getBrandWCPayRequest', jsApiParameters,  
        function(res){  
            //alert(res.err_msg); 
            if(res.err_msg == "get_brand_wcpay_request:ok" ) {  
            	alert('支付成功');
            	window.location.href= jsApiUrl;               
            }else{  
                if(res.err_msg == "get_brand_wcpay_request:cancel"){  
                    alert('支付取消!');  
                    return false;  
                }else{  
                    alert('支付失败!');  
                    return false;  
                }  
            }  
        }  
	);  
}  

function jsApiMpPay(jsApiParameters,jsApiUrl)  
{  	
    if (typeof WeixinJSBridge == "undefined"){
        if( document.addEventListener ){  
            document.addEventListener('WeixinJSBridgeReady', jsApiCall, false);  
        }else if (document.attachEvent){  
            document.attachEvent('WeixinJSBridgeReady', jsApiCall);  
            document.attachEvent('onWeixinJSBridgeReady', jsApiCall);  
        }  
    }else{  
        jsApiCall(jsApiParameters,jsApiUrl);  
    }  
}

//简单封装请求 当notoken为false时不带token callback为回调 必填
function request(params,callback,notoken)
{
    var headers = {
    	Accept: "application/json; charset=utf-8",
    	sign:'abx',
    	Aid:getAId()
    }
    if(notoken == true) {
    	headers.Token = getToken();
    }
	var obj = {
		method: params.type,
		url: params.url,
		headers: headers
	}
	if (!params.type) {
		callback && callback({status:101,msg:'请设置请求方式'})
		return
	}

	if(params.type == 'get'){
    	obj.params = params.data;
    } else if (params.type == 'post'){
		obj.data = params.data;
    }

    axios(obj).then(function(res){
    	//失效重新登录
    	if (res.status == -1) {
    		mui.alert('账号已失效,请重新登录', function () {
                window.location.href = "{:url('com/login')}"
            });
            return false;
    	} 
    	callback && callback(res.data);
	}).catch(function(error){
		console.log(error);
    	var code = error.response.status.toString();
        var startChar = code.charAt(0);
        if (startChar != '2') {
         	callback && callback({status:101,msg:'请求错误,错误代码:'+error.response.status});
        }
    })
}