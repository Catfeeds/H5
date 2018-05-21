<?php


namespace app\common\logic;

use app\common\logic\LogicBase;
use think\Commutator;



class GoodsAttr extends LogicBase{


 

	public function GetAttrs() {
		return  Commutator::Subject( [Commutator::MGOODSATTR		,['Where'=>['zga.agent_id'  => $this->agent_id,'zga.is_filter' 		=> 1]
																	,'Join'=>['zm_goods_attr_value zgav','zga.attr_id=zgav.id','LEFT']
																	,'Field'=>' * ']
																	,'GetGoodsAttrList']);
	}	
	
	
	public function GetGoodsAttrList($goods_type){
		$GoodsAttrModel 					= new \app\common\model\GoodsAttr();
		$GoodsAttr 							= $GoodsAttrModel->GetGoodsAttrList($this->agent_id,$goods_type);

		if($GoodsAttr){
			$GoodsAttrSata 					= [];
			foreach ($GoodsAttr as $v){
				if(isset($GoodsAttrSata[$v['zga_id']])){
					$GoodsAttrSata[$v['zga_id']]['sub'][] = ['id'=>$v['zgav_id'],'name'=>$v['zgav_name']];
				}else{
					$GoodsAttrSata[$v['zga_id']]['id']    = $v['zga_id'];
					$GoodsAttrSata[$v['zga_id']]['name']  = $v['zga_name'];
				}
			}
			return $GoodsAttrSata;
		}
	}
	
	
	/**
	 * 获取分类
	 * zhy	find404@foxmail.com
	 * 2017年11月11日 14:09:42
	 * $agent_id分销ID，$pid那一级别ID，$is_see，是否会员查看
	 */
	public function GetAttrsListForCategoryid($category_id){
		$GoodsAttrModel 					= new \app\common\model\GoodsAttr();
		$GoodsAttr 							= $GoodsAttrModel->GetAttrsListForCategoryid($this->agent_id,$category_id);
		if($GoodsAttr){
			$GoodsAttrSata 					= [];
			foreach ($GoodsAttr as $v){
				if(isset($GoodsAttrSata[$v['zga_id']])){
					$GoodsAttrSata[$v['zga_id']]['sub'][] = ['id'=>$v['zgav_id'],'name'=>$v['zgav_name']];
				}else{
					$GoodsAttrSata[$v['zga_id']]['id']    = $v['zga_id'];
					$GoodsAttrSata[$v['zga_id']]['name']  = $v['zga_name'];
				}
			}
			return $GoodsAttrSata;
		}
	}
	
	
	/**
	 * 获取分类属性
	 * wxh	
	 */
	public function GetAttrsByCat($category_id,$type=3){
	    $GoodsAttrModel 					= new \app\common\model\GoodsAttr();
	    $GoodsAttr 							= $GoodsAttrModel->GetAttrsListForCategoryid($this->agent_id,$category_id,$type);

	    if($GoodsAttr){
	        $GoodsAttrSata 					= [];
	        foreach ($GoodsAttr as $key=>$v){
	            if(isset($GoodsAttrSata[$v['zga_id']])){	                
	                $GoodsAttrSata[$v['zga_id']]['sub'][] = ['id'=>$v['zgav_id'],'name'=>$v['zgav_name']];	                
	            }else{
	                $GoodsAttrSata[$v['zga_id']]['id']    = $v['zga_id'];
	                $GoodsAttrSata[$v['zga_id']]['name']  = $v['zga_name'];
	                $GoodsAttrSata[$v['zga_id']]['sub'][] = ['id'=>$v['zgav_id'],'name'=>$v['zgav_name']];
	            }
	        }
	        $result=[];
	        foreach ($GoodsAttrSata as $vv){
	            array_push($result, $vv);
	        }	        
	        return $result;
	    }
	}
	
	
	
	
	/**
	 * 通过裸钻ID获取属性值
	 * zhy	find404@foxmail.com
	 * 2017年12月2日 11:36:34
	 * $agent_id分销ID，$pid那一级别ID，$is_see，是否会员查看
	 */
	public function GetDiamondAttrIds($diamond_id){
		$AttrIds = [];
		$AttrId 					= model('GoodsAttr')->GetDiamondAttrIds($this->agent_id,$diamond_id);
		if($AttrId){
			foreach ($AttrId as $val){
				$AttrIds[$val['attr_id']] .= $val['attr_value_id'].',';
			}
		}
		return $AttrIds;
	}	

	 
	
	
	
}