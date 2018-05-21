<?php
/**
 * 分销商逻辑层
 * zhy	find404@foxmail.com
 * 2017年11月17日 15:45:51
 */
namespace app\common\logic;

use app\common\logic\LogicBase;
use think\Commutator;

class Trader extends LogicBase
{

   //获取下级分销商的折扣率数据
   public function getTraderPricefx($agent_id,$list_rows=15){
       $param['agent_id']=$agent_id;
       $list=M('trader_price')
       ->alias('a')
       ->join('zm_trader b','a.trader_id = b.id')
       ->where("b.t_agent_id",$agent_id)
       ->paginate($list_rows,false,['query'=>$param]);
       
       return $list;
   }    

   
}