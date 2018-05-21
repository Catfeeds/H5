<?php
namespace app\common\model;

use think\Model;

/**
* 新闻分类
* time: 2017/11/01
**/
class Category extends Model
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
	 * 获取分类列表,以二位数组显示	
	 * auth	：zengmm
	 * @param：$param 分类参数
	 * time	：2017-11-01
	**/
	public function getList($param=array())
	{
		$catModel = db('article_cat');
		$order	= 'sort DESC';
		//$where['agent_id'] = $this->agent_id;
		$param['pid'] = 0;
		$categoryList = db('article_cat')->where($param)->order($order)->select();			//获取父分类
		if($categoryList){
			foreach($categoryList as $key=>$val){
				$param['pid'] = $val['id'];
				$categoryList[$key]['childCategory'] = $catModel->where($param)->order($order)->select(); //获取子分类赋值到父分类
			}
		}
		if($categoryList){
			return $categoryList;
		}else{
			return false;
		}
	}

	/**
	 * 根据上级分类，获取子分类列表（以一维数组显示）
	 * auth	：zengmm
	 * @param：$pid 上级分类   $is_show 是否显示
	 * time	：2017-11-01
	**/
	public function getSonList($pid=0,$agent_id)
	{
		$order	= 'sort DESC';
		$where["pid"] = $pid;
		$where["agent_id"] = $agent_id;
		$categoryList = db('article_cat')->where($where)->order($order)->select();			//获取子分类
		if($categoryList){
			return $categoryList;
		}else{
			return false;
		}
	}
	
	/**
	 * 获取分类详情
	 * auth	：zengmm
	 * @param：$param  分类参数
	 * time	：2017-11-01
	**/
	public function getInfo($param=array()){
		if(!empty($param)){
			$categoryInfo = db('article_cat')->where($param)->find();
			if($categoryInfo){ return $categoryInfo; }else{ return false; }
		}else{ return false; }
	}
	
	/**
	 * 获取分类详情
	 * auth	：zengmm
	 * @param：$id 分类ID
	 * time	：2017-11-01
	**/
	public function getInfoById($id){
		$categoryInfo = db('article_cat')->where('id='.$id)->find();
		if($categoryInfo){ return $categoryInfo; }else{ return false; }
	}
	
	/**
	 * 添加分类数据	
	 * auth	：zengmm
	 * @param：$data  要保存的数据
	 * time	：2017-11-01
	**/
	public function addInfo($data){
		$action	= db('article_cat')->insert($data);
		if($action){ return true; }else{ return false; }
	}
	
	/**	
	 * 更新分类数据 
	 * auth	：zengmm
	 * @param：$data  要保存的数据
	 * time	：2017-11-01
	**/
	public function saveInfo($data){
		if($data['pid'] == $data["id"]){ $data['pid'] = 0; }
		$action	= db('article_cat')->update($data);
		if($action){ return true; }else{ return false; }
	}
	
	/**
	 * 删除分类数据
	 * auth	：zengmm
	 * @param：$id 分类ID
	 * time	：2017-11-01
	**/
	public function del($id){
		$catModel = db('article_cat');
		$where['id']		= $id;
		$getCategoryInfo = $this->getInfoById($id);
		if($getCategoryInfo){
			$action	= $catModel->where($where)->delete();
			if($action){
				return true;
			}else{
				return false;
			}
		}else{
			return false;
		}
	}	
}	