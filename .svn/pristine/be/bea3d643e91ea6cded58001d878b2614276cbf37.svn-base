<?php


namespace app\common\logic;

use app\common\logic\LogicBase;
use think\Commutator;



class GoodsCategory extends LogicBase{


	//获取分类
	public function GetClassify($data) {
		return Commutator::Subject( [Commutator::MGOODSCATEGORY		,['Where'=>[ 'agent_id'  		=> $this->agent_id,'pid' 		=> 0]]
																	,'OthenGetList']);
	}


}