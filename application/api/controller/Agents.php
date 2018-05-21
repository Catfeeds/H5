<?php
namespace app\api\controller;

use think\Db;
use function think\name;
use app\common\controller\ApiBase;

//代理商信息
class Agents extends ApiBase
{
   
   
   //获取代理商配置信息
   public function getconfig(){
       $data=get_agent_config();
       $this->ajaxFormat($data);
   }
   
   //获取代理商模板信息
   public function gettemplate($id){        
       $data=get_agent_template($id);
       $this->ajaxFormat($data);
   }
   
   //获取一个页的模板控制数据信息
   public function gettemplateitembypage($config_id,$page='index'){
       $agent_id=get_agent_id();          
       $data=model("template")->getComListByPage($agent_id,$config_id,$page);
       $this->ajaxFormat($data);
   }
   
   //获取代理商配置和模板主表信息
   public function gettemplatedata($type=''){
       $data['config']=get_agent_config(); 
       if(empty($type)){
           $type='template_id';
       }else{
           $type=$type.'_template_id';
       }
       $config_id=$data['config'][$type];   
       $data['template']=get_agent_template($config_id);
       $agent_id=get_agent_id();
       $data['templateindex']=model("template")->getComListByPage($agent_id,$data['template']['config_id'],'index'); 
       $newitem = [];      
       foreach ($data['templateindex'] as $key => $val) {
          $newitem[$val['code']][] = $val;
       }
       return json(['status'=>100,'msg'=>'success','data'=>$newitem]);
       // if($type==2){
       //     $nav=model("nav")
       //     ->where("status=1 and type=2")
       //     ->where("agent_id",$this->agent_id)->select();
       //     $data['nav']=array2treesingle($nav); 
       // }
   }
   
   //获取代理商基本信息
   public function getagentbase(){
       $data=get_agent_base();
       $this->ajaxFormat($data);
   }
   
   //获取代理商详细信息
   public function getagentinfo(){
       $data=get_agent_info();
       $this->ajaxFormat($data);
   }
   
   //获取地区
   public function getregion($pid=0){
       if($pid){
           $list=model('region')->getListByPid($pid);
       }else{
           $list=model('region')->getList();
       }
       $this->ajaxFormat($list);
   }
   
   //获取前端导航配置
   public function getnav($type=0){
       //前台导航菜单
       $nav=model("nav")
       ->where("status=1")
       ->where("type",$type)
       ->where("agent_id",$this->agent_id)
       ->select();
       $navtree=array2treesingle($nav);
       $this->ajaxFormat($navtree);
   }
}
