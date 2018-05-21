<?php
namespace app\common\model;
use think\db;
use think\Model;

//代理商
class AgentConfig extends Model
{  
 
	public  $field             = '';       //字段
	public  $agent_id          = '';       //字段
	
 
	
	
   /**
    * 获取客户指定字段信息
    * @return arr
    */
	public function getField(){
	   $result= M('agent_config')->where("agent_id",$this->agent_id)->field($this->field)->find();
	   return $result;
	}	
	
	
}