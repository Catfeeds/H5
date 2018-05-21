<?php
namespace app\common\model;
use think\db;
use think\Model;

//前台导航菜单
class Nav extends Model
{  
   /**
	 * 获取导航列表	
	 * auth	：zengmm
	 * @param：$param 前台导航条件
	 * time	：2017-11-09
	**/ 
    public function getList($agent_id,$type="")
    {
		$order	= 'type asc,sort DESC,id ASC';
		if($type !== ''){ $where['type'] = $type; }
		$where['agent_id'] = $agent_id;
		$where['pid'] = 0;
		$navList = db('nav')->where($where)->order($order)->select();			//获取父分类
		if($navList){
			foreach($navList as $key=>$val){
				$where['pid'] = $val['id'];
				$navList[$key]['childNav'] = db('nav')->where($where)->order($order)->select(); //获取子分类赋值到父分类
			}
		}
		if($navList){
			return $navList;
		}else{
			return false;
		}
    }
	
	/**
	 * 添加前台导航	
	 * auth	：zengmm
	 * @param：$data 添加的导航数据
	 * time	：2017-11-09
	**/ 
    public function addInfo($data)
    {
		if($data["pid"]==0){
			$data['level'] = 1;
		}else{
			$navInfo = $this->getInfoById($data["pid"]);
			$data['level'] = $navInfo["pid"]+1;
		}  
		$rs	= db('nav')->insert($data);
		if($rs){ return true; }else{ return false; }
	}
	
	/**
	 * 获取前台导航详情
	 * auth	：zengmm
	 * @param：$id 前台导航ID
	 * time	：2017-11-09
	**/ 
    public function getInfoById($id)
    {
		$data = db("nav")->where("id=".$id)->find();
		return $data;	
	}
	
	/**
	 * 获取前台导航详情
	 * auth	：zengmm
	 * @param：$id 前台导航ID
	 * time	：2017-11-09
	**/ 
    public function getInfo($param=array())
    {
		$data = db("nav")->where($param)->find();
		return $data;	
	}
	
	/**
	 * 更新前台菜单导航	
	 * auth	：zengmm
	 * @param：$data 更新的导航数据
	 * time	：2017-11-09
	**/ 
    public function saveInfo($data)
    {
		return $rs = db("nav")->update($data);
	}
	
	/**
	 * 修改前台菜单列表	
	 * auth	：zengmm
	 * @param：$id 导航ID
	 * time	：2017-11-09
	**/ 
    public function del($id)
    {
		if($id){
			$rs = db('nav')->delete($id);
			if(empty($rs)){ return false; }else{ return true; }		
		}else{
			return false;
		}
	}
}