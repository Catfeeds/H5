<?php
namespace app\common\model;
use think\db;
use think\Model;

//代理商
class Agent extends Model
{  
	public  $field             = '';       //字段
	public  $agent_id          = '';       //字段
    /**
     * 获取客户信息简单
     * @return arr
     */
   public function getAgentInfoSingle($agent_id){
       $result= M('agent_info')
       ->where("agent_id",$agent_id)
       ->field("id,name,code,singlename,type,country,province,city,district,lng,lat,address,url,linkname,tel,phone,email,remark,wximg,wbimg,logo")
       ->find();
       return $result;
   }
   
   /**
    * 获取客户信息详情
    * @return arr
    */
   public function getAgentInfoDetail($agent_id){
       $result= M('agent_info')
       ->where("agent_id",$agent_id)
       ->field("id,name,code,singlename,type,country,province,city,district,lng,lat,address,url,linkname,tel,phone,email,remark,wximg,wbimg,logo,info")
       ->find();
       return $result;
   }
   

	/**
    * 获取客户指定字段信息
    * @return arr
    */
	public function getAgentField(){
	   $result= M('agent_info')->where("agent_id",$this->agent_id)->value($this->field);
	   return $result;
	}	
	   
	/**
	 * 获取客户第三方登录配置
	 * @return arr
	 */
   public function getAgentOpen($agent_id){
       $result=M('agent_open')->where("agent_id",$agent_id)->find();
       return $result;
   }    
}