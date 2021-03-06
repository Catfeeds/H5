<?php
namespace app\common\controller;
use think\Controller;
use think\Config;

//移动版后台管理
class AdminBase extends Controller
{
    protected $agent_id;
    protected $agent_base;//代理商基本信息，需要缓存
    protected $agent_config;//代理商配置信息，需要缓存
	
    public function __construct(){  
        $this->agent_base	 = get_agent_base();        
        $this->agent_config  = get_agent_config();
               
        parent::__construct();//上面代码一定要放在前面
        header('Access-Control-Allow-Origin:*');       
    }    
   
    
    /**
     *旧版ajax返回函数
     */
    public function ajaxReturn($data)
    {
        echo json_encode($data);
    }
 
}