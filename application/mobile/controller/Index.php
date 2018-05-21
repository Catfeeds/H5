<?php
namespace app\mobile\controller;
use think\Db;
use function think\name;
use app\common\controller\MobileBase;

class Index extends MobileBase
{
   
    public function index()
    {   
        $agent_id=get_agent_id();         
        
        //首页模板配置项
        $config_id=$this->agent_template['config_id'];//模板编号        
        
        $templateitem=model("template")->getComListByPage($agent_id,$config_id,'index');
        $this->assign("templateitem",$templateitem);  
        
        //前台导航菜单
        // $nav=model("nav")->where("status=1 and type=1")->where("agent_id",$agent_id)->select();
        // $navtree=array2treesingle($nav);
        // $this->assign("nav",$navtree);
        
        return $this->fetch();
    }
    
    public function test(){
        return $this->fetch();
    }
}
