<?php
namespace app\newapi\model;
use think\Model;
use think\Db;

//钻石模型
class GoodsDiamond extends Model
{
	//获取一条钻石数据
	public static function getdiamondinfo($goods_id,$agent_id)
	{
		$where = 'g.product_status = 1';
        //获取分销商信息
        $trader = get_agent_trader($agent_id);
        if($trader['t_agent_id']>0){ //二级分销商
            $where.= " and g.agent_id in($agent_id,0,".$trader['t_agent_id'].")";
        }else{
            $where.= " and g.agent_id in($agent_id,0)";
        }
		$where.= ' and g.id = '.$goods_id;

		return  self::alias('gd')
			->field('gd.*,g.product_status,g.thumb,g.price,g.supply_goods_id,g.supply_goods_type')
			->join('zm_goods g','g.id=gd.goods_id','left')
			->where($where)->find();
	}

	//获取一条钻石4C数据 (原始数据)
	public static function defaultinfo($goods_id)
	{
		return  self::alias('gd')
			->field('gd.*,g.thumb')
			->join('zm_goods g','g.id=gd.goods_id','LEFT')
			->where([
				'gd.goods_id'=>(int)$goods_id
				])
			->find();
	}
}