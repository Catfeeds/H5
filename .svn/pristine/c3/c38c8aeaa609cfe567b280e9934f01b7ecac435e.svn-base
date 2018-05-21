<?php
namespace app\newapi\controller\v1;

use app\newapi\controller\Base;
use app\newapi\model\GoodsDiamond as DiamondModel;
use app\newapi\service\PriceCalculation;
use think\Db;

class Diamond extends Base
{
	protected function _initialize()
    {
        parent::_initialize();       
    }  
    
	/*
    * @diamond_id  钻石id 必传
    * 获取一条钻石列表 by sxm
    */
	public function getdiamondinfo()
	{
		if (!is_numeric(I('diamond_id')) || I('diamond_id') < 0) {return json(['status'=>101,'msg'=>'钻石参数不正确','data'=>'']);}
		$info = DiamondModel::getdiamondinfo(I('diamond_id'),$this->agent_id);
		//动态计算价格
		$info = (new PriceCalculation())->goods_price([$info]);
		return json(['status'=>100,'msg'=>'获取成功','data'=>$info[0]]);
	}

}