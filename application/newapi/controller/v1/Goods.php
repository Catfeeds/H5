<?php
namespace app\newapi\controller\v1;

use app\newapi\controller\Base;
use app\newapi\service\Goods as GoodsService;
use think\Db;

class Goods extends Base
{
	protected function _initialize()
    {
        parent::_initialize();       
    }  

    /*
    * @diamond_id  钻石id 必传
    * @goods_attr_filter  属性组合
    * @order  排序 
    * 钻配托 获取定制商品列表 by sxm
    */
	public function getcustomgoods()
	{
		$data['agent_id'] = $this->agent_id; //分销商
        $data['type'] = 4; //成品 定制
        $data['diamond_id'] = I('diamond_id') ? I('diamond_id') : ''; // 定制钻配托 goods表 ID
        $data['goods_attr_filter'] = I('goods_attr_filter') ? I('goods_attr_filter') : ''; //属性搜索 2:12;6:18;
        $data['order'] = I('order') ? I('order') : 'id desc';

        $page = I('page')>0?I('page'):1;
        $pagesize = I('pagesize')?I('pagesize'):20;
        if (!is_numeric($data['diamond_id']) || $data['diamond_id'] <0) {
            return json(['status'=>101,'msg'=>'钻石参数不正确','data'=>'']);}

        $result = GoodsService::zptGoodslist($data,$page,$pagesize);
        return json(['status'=>100,'msg'=>'请求成功','data'=>$result]);
	}

    /*
    * @diamond_id  钻石id 必传
    * 钻配托获取戒托属性 by sxm
    */
    public function getcustomattr()
    {
        $data['agent_id'] = $this->agent_id; //分销商
        $data['type'] = 4; //成品 定制
        $data['diamond_id'] = I('diamond_id') ? I('diamond_id') : ''; // 定制钻配托 goods表 ID
        if (!is_numeric($data['diamond_id']) || $data['diamond_id'] <0) {
            return json(['status'=>101,'msg'=>'钻石参数不正确','data'=>'']);}

        $result = GoodsService::zptAttrList($data);
        return json(['status'=>100,'msg'=>'请求成功','data'=>$result]);
    }

}