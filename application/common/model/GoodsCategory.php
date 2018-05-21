<?php
namespace app\common\model;
use app\common\model\GoodsAttr as GoodsAttrModel;
use think\Model;
use think\db;

class GoodsCategory extends Model
{
	protected $insert = ['create_time'];
	public $Where 	  = '';
	public $limit 	  = '';
	public $Field 	  = '';
	public $Data 	  = '';
    /**
     * 创建时间
     * @return bool|string
     */
    protected function setCreateTimeAttr()
    {
        return date('Y-m-d H:i:s');
    }
	
	/**
	 * 获取分类列表	
	 * auth	：zengmm
	 * @param：$param 查询参数
	 * time	：2017-11-09
	**/ 
    public function getList($param=array())
    {
		$goodsCatModel = db('goods_category');
		if(empty($param['pid'])){
			$param['pid'] = 0;
		}
		$goodsCatList = $goodsCatModel->where($param)->order('id asc')->select();			//获取父分类
		$goodsCatList = convert_arr_key($goodsCatList,'id');
		if($goodsCatList){
			foreach($goodsCatList as $key=>$val){
				$param['pid'] = $val['id'];
				$childCategory = $goodsCatModel->where($param)->order('id asc')->select(); //获取子分类赋值到父分类
				$childCategory = convert_arr_key($childCategory,'id');
				$goodsCatList[$key]['childCategory'] = $childCategory;
			}
		}
		if($goodsCatList){
			return $goodsCatList;
		}else{
			return false;
		}
	}
	
	/**
	 * 获取分类详情（通过参数获取）
	 * auth	：zengmm
	 * @param：$param  分类参数
	 * time	：2017-11-12
	**/
	public function getInfo($param=array()){
		if(!empty($param)){
			$goodsCatInfo = db('goods_category')->where($param)->find();
			if($goodsCatInfo){ return $goodsCatInfo; }else{ return false; }
		}else{ return false; }
	}
	
	/**
	 * 获取分类详情
	 * auth	：zengmm
	 * @param：$id 分类ID
	 * time	：2017-11-12
	**/
	public function getInfoById($id){
		$agent_id	= get_agent_id();
		$goodsCatInfo = db('goods_category')->where('id='.$id.' and agent_id='.$agent_id)->find();
		if($goodsCatInfo["pid"]){
			//获取上级分类
			$upperCat = db('goods_category')->where('id='.$goodsCatInfo["pid"])->field("name")->find();
			$goodsCatInfo["pname"] = $upperCat["name"];
			//获取分类属性值
			$goods_attr_model = new GoodsAttrModel();
			//成品属性
			$gcaAList = db('goods_category_attr')->where("category_id=".$id." and type=1 and agent_id=".$agent_id)->order("id asc")->select();
			$attrIdArrA = array();
			foreach($gcaAList as $key=>$val){
				$attrIdArrA[] = $val["attr_id"];
			}
			$attrIdsA = implode(",",$attrIdArrA);
			$whereA["id"] = array("in",$attrIdsA);
			$whereA["agent_id"] = $agent_id;
			$goodsCatInfo["attrA"] = $goods_attr_model->getOtherList($whereA);
			$goodsCatInfo["attrIdsA"] = $attrIdsA;
			//成品规格
			$gcaBList = db('goods_category_attr')->where("category_id=".$id." and type=2 and agent_id=".$agent_id)->order("id asc")->select();
			$attrIdArrB = array();
			foreach($gcaBList as $key=>$val){
				$attrIdArrB[] = $val["attr_id"];
			}
			$attrIdsB = implode(",",$attrIdArrB);
			$whereB["id"] = array("in",$attrIdsB);
			$whereB["agent_id"] = $agent_id;
			$goodsCatInfo["attrB"] = $goods_attr_model->getOtherList($whereB);
			$goodsCatInfo["attrIdsB"] = $attrIdsB;
			//定制属性
			$gcaCList = db('goods_category_attr')->where("category_id=".$id." and type=3 and agent_id=".$agent_id)->order("id asc")->select();
			$attrIdArrC = array();
			foreach($gcaCList as $key=>$val){
				$attrIdArrC[] = $val["attr_id"];
			}
			$attrIdsC = implode(",",$attrIdArrC);
			$whereC["id"] = array("in",$attrIdsC);
			$whereC["agent_id"] = $agent_id;
			$goodsCatInfo["attrC"] = $goods_attr_model->getOtherList($whereC);
			$goodsCatInfo["attrIdsC"] = $attrIdsC;
			//定制规格
			$gcaDList = db('goods_category_attr')->where("category_id=".$id." and type=4 and agent_id=".$agent_id)->order("id asc")->select();
			$attrIdArrD = array();
			foreach($gcaDList as $key=>$val){
				$attrIdArrD[] = $val["attr_id"];
			}
			$attrIdsD = implode(",",$attrIdArrD);
			$whereD["id"] = array("in",$attrIdsD);
			$whereD["agent_id"] = $agent_id;
			$goodsCatInfo["attrD"] = $goods_attr_model->getOtherList($whereD);
			$goodsCatInfo["attrIdsD"] = $attrIdsD;
		}
		if($goodsCatInfo){ return $goodsCatInfo; }else{ return false; }
	}

	/**
	 * 添加分类	
	 * auth	：zengmm
	 * @param：$data  要保存的数据
	 * time	：2017-11-12
	**/
	public function addInfo($data){
		//处理所选属性值
		$this->startTrans();
		$attrVals1 = explode(",",$data["attrVals1"]);
		$attrVals2 = explode(",",$data["attrVals2"]);
		$attrVals3 = explode(",",$data["attrVals3"]);
		$attrVals4 = explode(",",$data["attrVals4"]);
		array_pop($attrVals1); 
		array_pop($attrVals2); 
		array_pop($attrVals3); 
		array_pop($attrVals4); 
		unset($data["attrVals1"]);
		unset($data["attrVals2"]);
		unset($data["attrVals3"]);
		unset($data["attrVals4"]);
		unset($data["file"]);
		//个性刻字图标
		/*if($data["letteringLcon"]){ 
			$data["lettering_lcon"] = implode(",",$data["letteringLcon"]); 
			unset($data["letteringLcon"]); 
		}*/
		$category_id = db('goods_category')->insertGetId($data);
		if($category_id){
			if($data["pid"]!=0){ 
				//添加分类属性
				$data_attr["category_id"] = $category_id;
				$returnStr = 1;
				foreach($attrVals1 as $key=>$val){
					$data_attr["type"] = 1; 
					$data_attr["attr_id"] = $val; 
					$data_attr["agent_id"] = $data["agent_id"]; 
					$rs = db('goods_category_attr')->insert($data_attr);
					if($rs==false){ $returnStr = 2; }
				}
				foreach($attrVals2 as $key=>$val){
					$data_attr["type"] = 2; 
					$data_attr["attr_id"] = $val; 
					$data_attr["agent_id"] = $data["agent_id"]; 
					$rs = db('goods_category_attr')->insert($data_attr);
					if($rs==false){ $returnStr = 2; }	
				}
				if($attrVals3){
				foreach($attrVals3 as $key=>$val){
					$data_attr["type"] = 3; 
					$data_attr["attr_id"] = $val; 
					$data_attr["agent_id"] = $data["agent_id"]; 
					$rs = db('goods_category_attr')->insert($data_attr);
					if($rs==false){ $returnStr = 2; }
				}
				}
				if($attrVals4){
				foreach($attrVals4 as $key=>$val){
					$data_attr["type"] = 4; 
					$data_attr["attr_id"] = $val; 
					$data_attr["agent_id"] = $data["agent_id"]; 
					$rs = db('goods_category_attr')->insert($data_attr);
					if($rs==false){ $returnStr = 2; }
				}
				}
				if($returnStr==2){
					$this->rollback();
					return false;
				}else{
					$this->commit(); 
					return true; 
				}
			}else{
				$this->commit(); 
				return true; 	
			}
		}else{ 
			return false; 
		}
	}
	
	/**
	 * 添加分类	
	 * auth	：zengmm
	 * @param：$data  要保存的数据
	 * time	：2017-11-12
	**/
	public function addGoodsCatAttrInfo($data){
		
	}

	/**	
	 * 更新分类数据 
	 * auth	：zengmm
	 * @param：$data  要保存的数据
	 * time	：2017-11-01
	**/
	public function saveInfo($data){
		//处理所选属性值
		$this->startTrans();
		if($data["attrVals1"]){
			$attrVals1 = explode(",",$data["attrVals1"]);
			array_pop($attrVals1); 
		}
		if($data["attrVals2"]){
			$attrVals2 = explode(",",$data["attrVals2"]);
			array_pop($attrVals2); 
		}
		if($data["attrVals3"]){
			$attrVals3 = explode(",",$data["attrVals3"]);
			array_pop($attrVals3); 
		}
		if($data["attrVals4"]){
			$attrVals4 = explode(",",$data["attrVals4"]);
			array_pop($attrVals4); 
		}
		unset($data["attrVals1"]);
		unset($data["attrVals2"]);
		unset($data["attrVals3"]);
		unset($data["attrVals4"]);
		unset($data["file"]);
		//个性刻字图标
		/*if($data["letteringLcon"]){ 
			$data["lettering_lcon"] = implode(",",$data["letteringLcon"]); 
			unset($data["letteringLcon"]); 
		}*/
		$action	= db('goods_category')->update($data);
		if($action !== false){ 
			if($data["pid"]!=0){ 
				//添加分类属性
				$data_attr["category_id"] = $data["id"];
				$returnStr = 1;
				if($attrVals1){
					foreach($attrVals1 as $key=>$val){
						$data_attr["type"] = 1; 
						$data_attr["attr_id"] = $val; 
						$data_attr["agent_id"] = $data["agent_id"]; 
						$rs = db('goods_category_attr')->insert($data_attr);
						if($rs==false){ $returnStr = 2; }
					}
				}
				if($attrVals2){
					foreach($attrVals2 as $key=>$val){
						$data_attr["type"] = 2; 
						$data_attr["attr_id"] = $val; 
						$data_attr["agent_id"] = $data["agent_id"]; 
						$rs = db('goods_category_attr')->insert($data_attr);
						if($rs==false){ $returnStr = 2; }	
					}
				}
				if($attrVals3){
					foreach($attrVals3 as $key=>$val){
						$data_attr["type"] = 3; 
						$data_attr["attr_id"] = $val; 
						$data_attr["agent_id"] = $data["agent_id"]; 
						$rs = db('goods_category_attr')->insert($data_attr);
						if($rs==false){ $returnStr = 2; }
					}
				}
				if($attrVals4){
					foreach($attrVals4 as $key=>$val){
						$data_attr["type"] = 4; 
						$data_attr["attr_id"] = $val; 
						$data_attr["agent_id"] = $data["agent_id"]; 
						$rs = db('goods_category_attr')->insert($data_attr);
						if($rs==false){ $returnStr = 2; }
					}
				}
				if($returnStr==2){
					$this->rollback();
					return false;
				}else{
					$this->commit(); 
					return true; 
				}
			}else{
				$this->commit(); 
				return true; 	
			}
			return true; 
		}else{ 
			return false; 
		}
	}
	
	/**
	 * 删除分类数据
	 * auth	：zengmm
	 * @param：$id 分类ID
	 * time	：2017-11-01
	**/
	public function del($id){
		$this->startTrans();
		//$where['agent_id']	= $this->agent_id;
		$getGoodsCatInfo = $this->getInfoById($id);
		if($getGoodsCatInfo){
			$returnStr = 1;
			if($getGoodsCatInfo["pid"]!=0){
				//删除相应的分类属性
				$attrIdsA = $getGoodsCatInfo["attrIdsA"];
				$attrIdsB = $getGoodsCatInfo["attrIdsB"];
				$attrIdsC = $getGoodsCatInfo["attrIdsC"];
				$attrIdsD = $getGoodsCatInfo["attrIdsD"];
				$whereA["category_id"] = $id; 
				$whereA["type"] = 1; 
				$whereA["agent_id"] = $getGoodsCatInfo["agent_id"]; 
				$whereB["category_id"] = $id; 
				$whereB["type"] = 2; 
				$whereB["agent_id"] = $getGoodsCatInfo["agent_id"]; 
				$whereC["category_id"] = $id; 
				$whereC["type"] = 3; 
				$whereC["agent_id"] = $getGoodsCatInfo["agent_id"]; 
				$whereD["category_id"] = $id; 
				$whereD["type"] = 4; 
				$whereD["agent_id"] = $getGoodsCatInfo["agent_id"]; 
				
				if(count($getGoodsCatInfo["attrA"])>1){ 
					$whereA["attr_id"] = array("in",$attrIdsA); 
				}else{
					$whereA["attr_id"] = $attrIdsA; 
				}
				if(count($getGoodsCatInfo["attrB"])>1){ 
					$whereB["attr_id"] = array("in",$attrIdsB); 
				}else{
					$whereB["attr_id"] = $attrIdsB; 
				}
				if(count($getGoodsCatInfo["attrC"])>1){ 
					$whereC["attr_id"] = array("in",$attrIdsC); 
				}else{
					$whereC["attr_id"] = $attrIdsC; 
				}
				if(count($getGoodsCatInfo["attrD"])>1){ 
					$whereD["attr_id"] = array("in",$attrIdsC); 
				}else{
					$whereD["attr_id"] = $attrIdsC; 
				}
				$rsA = db('goods_category_attr')->where($whereA)->delete();
				$rsB = db('goods_category_attr')->where($whereB)->delete();
				/*$rsC = db('goods_category_attr')->where($whereC)->delete();
				$rsD = db('goods_category_attr')->where($whereD)->delete();*/
				if($rsA==false){ $returnStr = 2; }
				if($rsB==false){ $returnStr = 2; }
				/*if($rsC==false){ $returnStr = 2; }
				if($rsD==false){ $returnStr = 2; }*/
			}
			if($returnStr==1){
				$where['id'] = $id;
				$action	= db('goods_category')->where($where)->delete();
				if($action){ 
					$this->commit(); 
					return true;
				}else{
					$this->rollback(); 
					return false; 
				}
			}elseif($returnStr==2){
				$this->rollback(); 
				return false; 
			}
		}else{
			$this->rollback(); 
			return false;
		}
	}



	/**
	 * 获取分类
	 * zhy	find404@foxmail.com
	 * 2017年11月11日 14:09:42
	 * $agent_id分销ID，$pid那一级别ID，$is_see，是否会员查看
	 */
    public function OthenGetList($agent_id,$pid,$is_see){
		return Db::name('goods_category')
		->alias('zgc')
		->join('zm_goods_category_agent zgca','zgca.category_id=zgc.id','Left')
		->where('zgc.pid','=',$pid)
		->where('zgc.agent_id','=',$agent_id)
		->where('zgc.is_see','in',$is_see)	
		->where('( ISNULL(zgca.is_use) or zgca.is_use=0)')
		->field('zgc.id,zgc.name')
		->select();	
    }
    /**
     * 获取分类
     * wxh
     * $agent_id分销ID，$is_see，是否会员查看 $goods_type 商品类型 $type 0 非官网商品 1官网商品
     */
    public function GetListByAgent($agent_id,$is_see,$goods_type=3,$type=0){
        return Db::name('goods_category')
        ->alias('zgc')
        ->join('zm_goods_category_agent zgca','zgca.category_id=zgc.id','Left')     
        ->where('zgc.agent_id','=',$agent_id)
        ->where('zgc.is_see','in',$is_see)
        ->where('zgc.goods_type','=',$goods_type)
        ->where('zgc.type','=',$type)
        ->where('( ISNULL(zgca.is_use) or zgca.is_use=0)')
        ->field('zgc.id,zgc.pid,zgc.name')
        ->select();
    }

	/**
	 * 查询分类
	 * @param $pid int 分类id
	 * @param $agent_id int 分销商id
	 * @param $is_see int 是否限定会员查看 0不限定  1限定
	 * @return array 分类
	 * @author guihongbing
	 * @date 20171215
	 */
	public function categoryList($pid = 0,$agent_id = 0,$is_see = 0)
	{
		$where['pid'] = $pid;
		$where['agent_id'] = $agent_id;
		$where['is_see'] = $is_see;
		$result = array();
		$list = Db::name('goods_category')->where($where)->select();
		foreach($list as $key => $val){
			//查询下级分类
			$val['sub'] = $this->categoryList($val['id'],$val['agent_id']);
			$result[] = $val;
		}
		return $result;
	}
	
}