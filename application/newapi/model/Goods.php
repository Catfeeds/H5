<?php
namespace app\newapi\model;
use think\Model;
use think\Db;

//商品数据层
class Goods extends Model
{
	/*
	*@diamondinfo 钻石信息 包含weight,shape
	*@data 		  goods_attr_filter属性值 type(4定制) agent_id
	*
	*/ 
	//根据主石获取定制类戒托列表
	public static function getGoodsList($diamondinfo,$data,$page,$pagesize)
	{
		if ($data['goods_attr_filter']) {
			//根据sku精确查找符合条件的商品id  (待优化)
			$map['agent_id'] = $data['agent_id'];
			$ids = [];
			foreach ($data['goods_attr_filter'] as $key => $val) {
				$map['attributes'] = ['like','%'.$val.'%'];
				$sonlist = Db::name('goods_sku')->where($map)->column('goods_id');
				foreach ($sonlist as $k => $v) {
					array_push($ids,$v);
				}
			}
			$arr['g.id']=['in',array_unique($ids)];
		} else {
			$arr = [];
		}
		//匹配钻石区间
		$weight = goods_Zpt($diamondinfo['weight']);
		$where = [
			'g.product_status'=>1, //上架
			'g.isdel'=>0, //未删除
			'g.supply_goods_id'=>0, //非下单通
			'g.type'=>$data['type'],
			'g.agent_id'=>$data['agent_id'],
			'zdm.weight'=>['BETWEEN',[$weight['weight_egt'],$weight['weight_lt']]],
			'zdm.shape'=>$diamondinfo['shape']
		];
		
		return self::alias('g')
			->field('g.*')
			->join('zm_goods_diamond_matching zdm','zdm.goods_id=g.id','LEFT')
			->where($where)
			->where($arr)
			->order($data['order'])
			->paginate($pagesize, true, ['page' => $page]);

	}

	/*
	*@diamondinfo 钻石信息 包含weight,shape
	*@data 		  type(定制4) agent_id
	*
	*/ 
	//根据主石获取定制类戒托的属性   ps 后期可以改为根据符合条件的商品来找属性  不用根据分类来找 不然会找不到
	public static function getAttrList($diamondinfo,$data)
	{
		$weight = goods_Zpt($diamondinfo['weight']);
		$where = [
			'g.product_status'=>1, //上架
			'g.isdel'=>0, //未删除
			'g.supply_goods_id'=>0, //非下单通
			'g.type'=>$data['type'],
			'g.agent_id'=>$data['agent_id'],
			'zdm.weight'=>['BETWEEN',[$weight['weight_egt'],$weight['weight_lt']]],
			'zdm.shape'=>$diamondinfo['shape']
		];
		//获取符合钻重的产品分类id
		$list = self::alias('g')
			->join('zm_goods_diamond_matching zdm','zdm.goods_id=g.id','LEFT')
			->where($where)
			->column('g.id,g.jewelry_id');
		//根据分类id获取属性列表
		$map = [
			'a.agent_id'=>$data['agent_id'],
			'a.category_id'=>['in',$list]
		];
		return Db::name('goods_category_attr a')
			->field('a.attr_id,b.name,c.id as value_id,c.name as value_name')
			->join('zm_goods_attr b','b.id=a.attr_id','LEFT')
			->join('zm_goods_attr_value c','c.attr_id=b.id','LEFT')
			->where($map)
			->select();
	}
}