<?php
namespace app\common\model;

use think\Model;

class Menu extends Model
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
	 * 获取菜单列表	
	 * auth	：zengmm
	 * @param：$param 菜单的参数条件
	 * time	：2017-11-08
	**/ 
    public function getList($param=array())
    {
		$order	= 'sort DESC,id ASC';
		//$where['agent_id'] = $this->agent_id;
		$param['pid'] = 0;
		$menuList = db('menu')->where($param)->order($order)->select();			//获取父分类
		if($menuList){
			foreach($menuList as $key=>$val){
				$param['pid'] = $val['id'];
				$menuList[$key]['childMenu'] = db('menu')->where($param)->order($order)->select(); //获取子分类赋值到父分类
			}
		}
		if($menuList){
			return $menuList;
		}else{
			return false;
		}
    }
	
	/**
	 * 添加菜单列表	
	 * auth	：zengmm
	 * @param：$data 要添加的数据
	 * time	：2017-11-08
	**/ 
    public function addInfo($data)
    {
		$this->startTrans();
		$menuId = db("menu")->insertGetId($data);
		//添加数据到auth_rule表
		$arData["id"] = $menuId;
		$arData["name"] = $data["name"];
		$arData["title"] = $data["title"];
		$arData["type"] = $data["level"];
		$arData["status"] = $data["status"];
		$arData["pid"] = $data["pid"];
		$arData["icon"] = $data["icon"];
		$arData["sort"] = $data["sort"];
		$rs = db("auth_rule")->insert($arData);
		if($menuRs !== false && $rs !== false){
			$this->commit(); 
			return true;	
		}else{
			$this->rollback();
			return false;
		}
	}
	
	/**
	 * 获取菜单详情	
	 * auth	：zengmm
	 * @param：$id 菜单ID
	 * time	：2017-11-08
	**/ 
    public function getInfoById($id)
    {
		$data = db("menu")->where("id=".$id)->find();
		return $data;
	}
	
	/**
	 * 获取菜单详情	
	 * auth	：zengmm
	 * @param：$id 菜单ID
	 * time	：2017-11-08
	**/ 
    public function getInfo($param=array())
    {
		$data = db("menu")->where($param)->find();
		return $data;
	}

	/**
	 * 更新菜单	
	 * auth	：zengmm
	 * @param：$data 要更新的数据
	 * time	：2017-11-08
	**/ 
    public function saveInfo($data)
    {
		$this->startTrans();
		$oldTitle = $data["old_title"];
		unset($data["old_title"]);
		$rs = db("menu")->update($data);
		//修改auth_rule表数据
		$arData["name"] = $data["name"];
		$arData["title"] = $data["title"];
		$arData["type"] = $data["level"];
		$arData["status"] = $data["status"];
		$arData["pid"] = $data["pid"];
		$arData["icon"] = $data["icon"];
		$arData["sort"] = $data["sort"];
		$ar_rs = db("auth_rule")->where("title='".$oldTitle."'")->update($arData);
		if($rs !== false && $ar_rs !== false){
			$this->commit(); 
			return true;	
		}else{
			$this->rollback();
			return false;
		}
	}
	
	/**
	 * 删除菜单详情	
	 * auth	：zengmm
	 * @param：$id 菜单ID
	 * time	：2017-11-08
	**/ 
    public function del($id)
    {
		$this->startTrans();
		$menuInfo = db('menu')->where("id=".$id)->field("title")->find();
		$rs = db('menu')->delete($id);
        $arrs = db("auth_rule")->where("title='".$menuInfo["title"]."'")->delete();
		if($rs != false && $arrs != false){
			$this->commit();  
			return true; 
		}else{ 
			$this->rollback();
			return false; 
		}	
	}
}