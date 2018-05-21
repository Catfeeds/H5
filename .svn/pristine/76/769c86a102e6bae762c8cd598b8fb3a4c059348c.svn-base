<?php
namespace app\common\model;

use think\Model;
use think\db;

class GoodsAttr extends Model
{
	public $Where 	  = '';
 	public $Join 	  = '';
	public $Limit 	  = '';
	public $Field 	  = '';
	public $Data 	  = '';
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
	 * 获取产品属性列表	
	 * auth	：zengmm
	 * @param：$param 筛选参数
	 * time	：2017-11-13
	**/
	public function getList($param=array())
	{	
		$goodsAttrList = db('goods_attr')->where($param)->order("id asc")->select();
		foreach($goodsAttrList as $key=>$val){
			//查询对应的属性值
			$goodsAttrValueList = db('goods_attr_value')->where('attr_id='.$val["id"]." and agent_id=".$param["agent_id"])->field("id,name")->select();
			if(!empty($goodsAttrValueList)){
				$attr_value_list = array();
				foreach($goodsAttrValueList as $k=>$v){
					$attr_value_list[] = $v["name"];
				}
				$goodsAttrList[$key]["attr_value"] = implode(",",$attr_value_list);
			}
		}
		if($goodsAttrList){
			return $goodsAttrList;
		}else{
			return false;
		}
	}
	
	/**
	 * 获取产品属性列表（不带属性值的）	
	 * auth	：zengmm
	 * @param：$param 筛选参数
	 * time	：2017-11-14
	**/
	public function getOtherList($param=array())
	{	
		$goodsAttrList = db('goods_attr')->where($param)->order("id asc")->select();
		if($goodsAttrList){ return $goodsAttrList; }else{ return false; }
	}
	
	/**
	 * 添加产品属性	
	 * auth	：zengmm
	 * @param：$data  添加数据 
	 * time	：2017-11-13
	**/
	public function addInfo($data)
	{	
		$attrValue = $data["attrValue"];	
		unset($data["attrValue"]);
		$attr_id = db('goods_attr')->insertGetId($data);
		if($attr_id){ 
			//循环添加属性值
			if(!empty($attrValue)){
				$attrData["attr_id"] = $attr_id;
				foreach($attrValue as $key=>$val){
					$attrData["name"] = $val;
					$attrData["agent_id"] = $data["agent_id"];
					if(!empty($attrData["name"])){
						db('goods_attr_value')->insert($attrData);
					}
				}
			}
			return true; 
		}else{ 
			return false; 
		}
	}
	
	/**
	 * 获取产品属性	
	 * auth	：zengmm
	 * @param：$data  添加数据 
	 * time	：2017-11-13
	**/
	public function getInfoById($id)
	{	
		$agent_id = get_agent_id();
		$goodsAttrInfo = db('goods_attr')->where('id='.$id.' and agent_id='.$agent_id)->find();
		if($goodsAttrInfo){ 
			//获取属性值
			$where["attr_id"] = $goodsAttrInfo["id"];
			$where["agent_id"] = $agent_id;
			$goodsAttrInfo["attrValueList"] = $this->getGoodsAttrValueList($where);	
			return $goodsAttrInfo;
		}else{ 
			return false; 
		}
	}
	
	/**
	 * 获取产品属性值	
	 * auth	：zengmm
	 * @param：$param  筛选参数 
	 * time	：2017-11-13
	**/
	public function getGoodsAttrValueList($param)
	{	
		$goodsAttrValueList = db('goods_attr_value')->where($param)->select();
		if($goodsAttrValueList){ return $goodsAttrValueList; }else{ return false; }
	}
	
	
	/**
	 * 编辑产品属性值	
	 * auth	：zengmm
	 * @param：$data  要编辑数据 
	 * time	：2017-11-13
	**/
	public function saveAttrValue($data)
	{	
		$action	= db('goods_attr_value')->update($data);
		if($action){ return true; }else{ return false; }
	}
	
	/**
	 * 删除产品属性值	
	 * auth	：zengmm
	 * @param：$id   
	 * time	：2017-11-13
	**/
	public function delAttrValue($id)
	{	
		$action	= db('goods_attr_value')->delete($id);
		if($action){ return true; }else{ return false; }
	}
	
	/**
	 * 编辑产品属性	
	 * auth	：zengmm
	 * @param：$data  要编辑数据 
	 * time	：2017-11-13
	**/
	public function saveInfo($data)
	{	
		$attrValue = $data["attrValue"];	
		unset($data["attrValue"]);
		$rs = db('goods_attr')->update($data);
		if($rs !== false){ 
			//重新编辑属性值信息
			/*$where["attr_id"] = $data["id"];
			$attrValueList = $this->getGoodsAttrValueList($where);	
			$attrValue = $data["attrValue"];
			foreach($attrValueList as $key=>$val){
				if($attrValue[$key]==""){
					//删除对应的属性值
					$this->delAttrValue($val["id"]);
				}elseif($attrValue[$key]!=$val["name"]){
					//修改相应的属性值
					$data_attr_value["id"] = $val["id"];
					$data_attr_value["attr_id"] = $val["attr_id"];
					$data_attr_value["name"] = $attrValue[$key];
					$this->delAttrValue($data_attr_value);
				}
				unset($attrValue[$key]);
			}
			foreach($attrValue as $key=>$val){
				if($val==""){
					unset($attrValue[$key]);
				}
			}*/
			//循环添加属性值
			if(!empty($attrValue)){
				$attrData["attr_id"] = $data["id"];
				foreach($attrValue as $key=>$val){
					$attrData["name"] = $val;
					$attrData["agent_id"] = get_agent_id();
					if(!empty($attrData["name"])){
						db('goods_attr_value')->insert($attrData);
					}
				}
			}
			return true; 
		}else{ 
			return false; 
		}
	}
	
	
 
 
	/**
	 * 获取裸钻的属性ID。
	 * zhy	find404@foxmail.com
	 * 2017年12月1日 14:45:09
	 */
	public function GetDiamondAttrIds($agent_id,$goods_id){
		return $AttrId = Db::name('goods_associate_attr')
				->alias('zgaa')
				->join('zm_goods_attr zga','zgaa.attr_id=zga.id','LEFT')
				->where(['zgaa.agent_id'  => $agent_id
						,'zgaa.goods_id'  => $goods_id
						,'zga.system_code'  => ['in','2,3']
						])
				->field('zgaa.attr_id
						,zgaa.attr_value_id')		
				->select();
	}	
	
	/**
	 * 获取列表
	 * zhy	find404@foxmail.com
	 * 2017年11月22日 10:40:58
	 */
	public function GetGoodsAttrList($agent_id,$type){
		return Db::name('goods_attr')
				->alias('zga')
				->join('zm_goods_attr_value zgav','zgav.attr_id=zga.id','LEFT')
				->where(['zgav.agent_id'  => $agent_id
						,'zga.is_filter'  => 1
						,'zga.goods_type' => $type])
				->field('zga.id     as zga_id
						,zga.name   as zga_name
						,zgav.id  	as zgav_id
						,zgav.name  as zgav_name')		
				->select();
	}
	

	/**
	 * 通过分类ID，获取该分类下的sku
	 * zhy	find404@foxmail.com
	 * 2017年11月11日 14:09:42
	 * $agent_id分销ID，$category_id 分类ID
	 */
    public function GetAttrsListForCategoryid($agent_id,$category_id,$type=3){
		return Db::name('goods_category_attr')
		->alias('zgca')
		->join('zm_goods_attr zga','zga.id=zgca.attr_id','Left')
		->join('zm_goods_attr_agent zgaa','zgaa.attr_id=zga.id','Left')
		->join('zm_goods_attr_value zgav','zgav.attr_id=zgca.attr_id','Left')
		->join('zm_goods_attr_value_agent zgava','zgava.attr_value_id=zgav.attr_id','Left')
		->where('zga.is_filter','=',1) //是否前台搜索
		// ->where('zga.goods_type','=',$type) //废弃，属性不分定制成品，以分类来分
		->where('zgca.category_id','=',$category_id)
		->where('zgca.agent_id','=',$agent_id)
		->where('ISNULL(zgaa.is_use)  or zgaa.is_use=0')
		->where('ISNULL(zgava.is_use) or zgava.is_use=0')
		->field('zga.id as zga_id,zga.name as zga_name,zgav.id as zgav_id,zgav.name as zgav_name')
		->group('zgav.id')
		->select();
		
		 
	}
	
	//zwx 获取属性，属性值
	public function getGoodsAttr($param=array()){
		//获取属性	
		$ga_list = M('goods_attr')->where($param)->order("id asc")->select();
		//ID替换键值
		$ga_list = convert_arr_key($ga_list,'id');
		//获取键生成数组
		$gav_list_id = array_keys($ga_list);
		//获取属性值
		$gav_list = M('goods_attr_value')->where(['attr_id'=>['in',$gav_list_id]])->select();
		//生成循环数据
		foreach ($gav_list as $k => $v) {
			$ga_list[$v['attr_id']]['sub'][] = $v;
		}
		return $ga_list;
	}

}