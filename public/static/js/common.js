/***模拟百度贴吧的分页**/
var page = {
	data:{},             //运行时的中间数据
	options:{
		PageID:1,        //当前第几页
		TotalNum:0,      //数据总数
		PageSize:50,     //每页的行数
		//下面是常用的静态设置
		PageSum:1,       //总页数
		DisplayMark:10,  //页面数字最多显示多少列
		Prev:'上一个',
		Next:'下一个',
		First:'首页',
		Last:'尾页',
		//下面的设置用于post提交的场景
		Url:'',          //地址
		Format:'',
		PageArgsStr:'page_id',  //href连接的分页name，href会增加?p=
		PageSizeArgsStr:'page_size',//页面数据的pagesize参数
		SetPageSize:true,
	},
	create:function(options){
		for(var key in this.options){
			this.options[key] = options[key] || this.options[key];
		}
		options = this.options;
		var arr = new Array();
		if(options.TotalNum < 1 ){
			arr[1] = 1;
		}
		options.PageSum = Math.ceil(options.TotalNum/options.PageSize); //总页数
		if(parseInt(options.PageSize) >= parseInt(options.TotalNum) ){
			if (parseInt(options.PageSum) < parseInt(options.PageID) ){ //无数据
				arr = null;
			}else{
				arr[1] = 1;
			}
		}else{
			if (parseInt(options.PageSum) < parseInt(options.PageID) ){ //无数据
				arr = null;
			}else {
				if ( parseInt(options.PageSum) > parseInt(options.DisplayMark) ){
					if (parseInt(options.PageID) <= Math.ceil(options.DisplayMark/2)){
						var begin = 1;
						var total = options.DisplayMark;
					} else if (parseInt(options.PageID)>Math.ceil(options.DisplayMark/2) && options.PageID < options.PageSum - Math.floor(options.DisplayMark/2)){
						var begin = options.PageID - Math.ceil(options.DisplayMark/2)+1;
						var total = options.DisplayMark;
					} else if (parseInt(options.PageID) >= options.PageSum - Math.floor(options.DisplayMark/2)) {
						var begin = options.PageSum - options.DisplayMark+1;
						var total = options.DisplayMark;
					}
				}else {
					var begin = 1;
					var total = options.PageSum;
				}
				for( var i = 0;i < total; i++ ){
					arr[begin+i] = begin+i;
				}
			}
		}
		this.data = arr;
		return this;
	},
	getHtml:function(){
				
		var _PageArr = this.data;
		var html     = '<nav>';
		if(_PageArr){
			html +=  '<ul class="pagination">';
			html +=  '<strong class="pagination pull-left" style="margin-top: 8px; margin-right: 5px;">'+this.options.TotalNum+'条记录 '+this.options.PageID+'/'+this.options.PageSum+'页</strong>';
			html += (this.options.PageID==1?'':'<li><a data-page-id="1" data-page-size="'+this.options.PageSize+'" href="javascript:void(0);">'+this.options.First+'</a></li><li><a data-page-id="'+(Number(this.options.PageID)-1)+'" data-page-size="'+this.options.PageSize+'"  href="javascript:void(0);">'+this.options.Prev+'</a></li>');
			for(var value in _PageArr){
				if ( value == this.options.PageID){
					html += '<li class="active"><span>'+value+'</span></li>';
				}else {
					html += '<li><a data-page-id="'+value+'" data-page-size = "'+this.options.PageSize+'"  href="javascript:void(0);">'+value+'</a></li>';
				}
			}
			html += (this.options.PageID==this.options.PageSum?'':('<li><a data-page-id='+(Number(this.options.PageID)+1)+' data-page-size="'+this.options.PageSize+'" href="javascript:void(0);">'+this.options.Next+'</a></li><li><a data-page-id="'+(this.options.PageSum)+'" data-page-size="'+this.options.PageSize+'"  href="javascript:void(0);">'+this.options.Last+'</a></li>'));
			html += '</ul>';
		}else{
			html += '';
		}
		html     += '</nav>';
		return html;
	},
}

function setCookie(name,value) 
{ 
    var Days = 30; 
    var exp = new Date(); 
    exp.setTime(exp.getTime() + Days*24*60*60*1000); 
    document.cookie = name + "="+ escape (value) + ";expires=" + exp.toGMTString(); 
} 

//读取cookies 
function getCookie(name) 
{ 
    var arr,reg=new RegExp("(^| )"+name+"=([^;]*)(;|$)");
 
    if(arr=document.cookie.match(reg))
 
        return unescape(arr[2]); 
    else 
        return null; 
} 

//删除cookies 
function delCookie(name) 
{ 
    var exp = new Date(); 
    exp.setTime(exp.getTime() - 1); 
    var cval=getCookie(name); 
    if(cval!=null) 
        document.cookie= name + "="+cval+";expires="+exp.toGMTString(); 
} 
