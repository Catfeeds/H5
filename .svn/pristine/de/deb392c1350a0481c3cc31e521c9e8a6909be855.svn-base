<?php
namespace app\common\model;

use think\Model;

class AdminUser extends Model
{
	protected $insert = ['create_time'];   
    
    
    /**
     * 创建时间
     * @return bool|string
     */
    protected function setCreateTimeAttr()
    {
        return date('Y-m-d H:i:s');
    }
    
	/**
	 * 添加管理员	
	 * auth	：zengmm
	 * @param：$data:要添加的数据
	 * time	：2017-11-06
	**/
	public function addInfo($data){
	   $data_ag["group_id"] = $data["group_id"];	
	   unset($data["group_id"]);
       $data['password'] = pwdHash($data["password"],$data["username"],$data["agent_id"]);
       $data['last_login_time'] = date("Y-m-d H:i:s");
       $data['create_time'] = date("Y-m-d H:i:s");
       $data['update_time'] = date("Y-m-d H:i:s");  
       $admin_user_id = db('admin_user')->insertGetId($data);
       if(empty($admin_user_id)){ 
	   		return false; 
	   }else{ 
	   		$data_ag["admin_user_id"] = $admin_user_id;
	   		$data_ag["agent_id"] = $data['agent_id'];
			$rs = db('auth_group_access')->insert($data_ag);
	   		return true; 
	   }
	}
	
	/**
	 * 添加管理员	
	 * auth	：zengmm
	 * @param：$data:要添加的数据
	 * time	：2017-11-06
	**/
	public function saveInfo($data){ 
	   $data_ag["group_id"] = $data["group_id"];	
	   unset($data["group_id"]);
       $rs = db('admin_user')->update($data);
       if($rs === false){ 
	   		return false; 
	   }else{ 
			$rss = db('auth_group_access')->where("admin_user_id=".$data["id"])->update($data_ag);
	   		return true; 
	   }
	}
	
	/**
	 * 获取管理员信息	
	 * auth	：zengmm
	 * @param：$id  管理员ID
	 * time	：2017-11-08
	**/
	public function getInfoById($id){
		$data = db("admin_user")->alias("au")
		->join('auth_group_access aga','aga.admin_user_id=au.id')
		->field("au.*,aga.group_id")
		->where("au.id=".$id)
		->find();
		return $data;
	}

	/**
	 * 删除管理用户信息	
	 * auth	：zengmm
	 * @param：$id  管理员ID
	 * time	：2017-11-08
	**/
	public function del($id){
		$rs = db('admin_user')->delete($id);
        if(empty($rs)){ 
			return false; 
		}else{ 
			//删除auth_group_access表数据
			$where["admin_user_id"] = $id;
			db('auth_group_access')->where($where)->delete();
			return true; 
		}	
	}
}