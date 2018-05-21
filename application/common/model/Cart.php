<?php
namespace app\common\model;

use think\Model;
use think\db;
class Cart extends Model
{

    protected function _initialize()
    {
        parent::_initialize();
    }


    /** 
     * zwx
     * @param array     $param 搜索条件
     * @return array
     */
    public function getInfo($param){
        $where = [];
        isset($param['id'])?$where['id'] = $param['id']:'';
        isset($param['goods_id'])?$where['goods_id'] = $param['goods_id']:'';
        isset($param['user_id'])?$where['user_id'] = $param['user_id']:'';
        isset($param['spec_key'])?$where['spec_key'] = $param['spec_key']:'';
        isset($param['attr_main_json'])?$where['attr_main_json'] = $param['attr_main_json']:'';
        
        $vo = M('cart')
                ->field('id,user_id,session_id,goods_id,goods_sn,goods_name,market_price,goods_price,member_goods_price,goods_num,spec_key,spec_key_name,goods_group,selected,create_time,prom_type,prom_id')
                ->where($where)
                ->find();
        return $vo;
    }

    /** 
     * zwx
     * @param array     $param 搜索条件
     * @return array
     */
    public function getList($param,$order='id desc'){
        $where = [];
        if(isset($param['id'])){
            $where['id'] = is_array($param['id'])?['in',implode(',',$param['id'])]:$param['id'];
        }
        isset($param['agent_id'])?$where['agent_id'] = $param['agent_id']:'';
        isset($param['user_id'])?$where['user_id'] = $param['user_id']:'';
        isset($param['selected'])?$where['selected'] = $param['selected']:'';

        $list = M('cart a')
            // ->field('id,user_id,cart_type,goods_id,goods_sn,goods_name,market_price,goods_price,member_goods_price,goods_num,spec_key,spec_key_name,goods_group,selected,create_time,prom_type,prom_id,attr_main_json')
            ->field('a.*,b.thumb')
            ->join('zm_goods b','b.id=a.goods_id','LEFT')
            ->where($where)
            ->order($order)
            ->select();
        return $list;
    }

    /** 
     * zwx
     * @param array     $param 搜索条件
     * @return array
     */
    public function getListJoinG($param,$order='c.id desc'){
        $where = [];
        if(isset($param['id'])){
            $where['c.id'] = is_array($param['id'])?['in',implode(',',$param['id'])]:$param['id'];
        }

        isset($param['user_id'])?$where['c.user_id'] = $param['user_id']:'';
        isset($param['selected'])?$where['c.selected'] = $param['selected']:'';
        isset($param['product_status'])?$where['g.product_status'] = $param['product_status']:'';
        
        $agent_id = get_agent_id();
        $list = [];
        $list = M('cart')->alias('c')
            ->join('zm_goods g','g.id = c.goods_id','left')
            ->join('zm_goods_trader gt','gt.goods_id = c.goods_id and gt.agent_id ='.$agent_id,'left')
            ->join('zm_goods_category gc','g.jewelry_id = gc.id','left')
            ->join('zm_goods_diamond gd','g.id = gd.goods_id','left')
            ->join('zm_goods_sku gs','gs.attributes = c.spec_key and g.id = gs.goods_id','left')
            ->field('
                c.id,c.user_id,c.cart_type,c.goods_id,c.goods_sn,c.goods_name,c.market_price,c.goods_price,c.member_goods_price,c.goods_num,c.spec_key,c.spec_key_name,c.goods_group,c.selected,c.create_time,c.prom_type,c.prom_id,c.attr_main_json,
                g.agent_id g_agent_id,g.supply_goods_id,g.supply_goods_type,g.supply_id,g.thumb g_thumb,g.type g_type,g.stock_status g_stock_status,g.stock_num g_stock_num,g.price g_price,g.id g_goods_id,g.isscore g_isscore,
                gc.score_rate gc_score_rate,
                gs.goods_number gs_goods_number,gs.id gs_sku_id,gs.goods_price gs_goods_price,gs.attributes gs_attributes,gs.name gs_name,
                gd.weight,gd.global_price,gd.dia_discount,
                gt.status gt_status
                ')
            ->where($where)
            ->order($order)
            ->select();
        
        foreach ($list as $k => $v) {  //获取戒托价格
            //下单通商品不进行价格赋值
            if($v['g_type']==3||$v['g_type']==4){
                if($v['supply_goods_id']>0){ //下单通定制
                    $attr_main_json = json_decode($v['attr_main_json'],true);
                    $price = 0;
                    foreach ($attr_main_json as $k1 => $v1) {
                        $Openzm = logic('Openzm')->get_customize_calculatePrice($v1['goods_id'],$v1['customize']['material_id'],$v1['customize']['stone_id'],$v1['matched_diamond_id']);
                        if($Openzm['code']==100200){
                            $price += $Openzm['data']['price'];
                        }
                    }
                    $list[$k]['member_goods_price'] = $price;
                }else{ //普通定制
                    if($v['gs_sku_id']>0){//有规格
                        $list[$k]['member_goods_price'] = $v['gs_goods_price'];
                    }else{
                        $list[$k]['member_goods_price'] = $v['g_price'];
                    }
                }
            }
        }
        
        $list = logic('PriceCalculation')->goods_price($list,['type'=>'g_type','price'=>'member_goods_price','agent_id'=>'g_agent_id']);
        // dump($list);
        return $list;
    }

    /** 
     * zwx 添加商品到购物车
     * @param array     $data 保存条件 $act add,edit $param修改条件
     * @return array
     */
    public function setCart($save,$act='',$param=''){

        if(isset($param['id'])){
            $where['id'] = is_array($param['id'])?['in',implode(',',$param['id'])]:$param['id'];
        }
        // isset($data['user_id'])?$save['user_id'] = $data['user_id']:''; //用户id
        // isset($data['session_id'])?$save['session_id'] = $data['session_id']:'';
        // isset($data['cart_type'])?$save['cart_type'] = $data['cart_type']:'';
        // isset($data['goods_id'])?$save['goods_id'] = $data['goods_id']:''; //商品id
        // isset($data['goods_sn'])?$save['goods_sn'] = $data['goods_sn']:''; //商品货号
        // isset($data['goods_name'])?$save['goods_name'] = $data['goods_name']:''; //商品名称
        // isset($data['market_price'])?$save['market_price'] = $data['market_price']:'';//市场价
        // isset($data['goods_name'])?$save['goods_name'] = $data['goods_name']:''; //本店价
        // isset($data['member_goods_price'])?$save['member_goods_price'] = $data['member_goods_price']:''; //会员折扣价
        // isset($data['goods_num'])?$save['goods_num'] = $data['goods_num']:''; //购买数量
        // isset($data['spec_key'])?$save['spec_key'] = $data['spec_key']:''; //商品规格key 对应zm_goods_sku attributes
        // isset($data['spec_key_name'])?$save['spec_key_name'] = $data['spec_key_name']:''; //商品规格name 对应zm_goods_sku name
        // isset($data['goods_group'])?$save['goods_group'] = $data['goods_group']:''; //商品组合
        // isset($data['selected'])?$save['selected'] = $data['selected']:''; //购物车选中状态 1选中
        // isset($data['create_time'])?$save['create_time'] = $data['create_time']:''; //加入购物车的时间
        // isset($data['prom_type'])?$save['prom_type'] = $data['prom_type']:''; //0 普通订单,1 限时抢购, 2 团购 , 3 促销优惠
        // isset($data['prom_id'])?$save['prom_id'] = $data['prom_id']:''; //活动id

        if($act == 'edit' && $where){
            return M('cart')->where($where)->update($save);
        }elseif($act == 'add'){
            return M('cart')->insertGetId($save);
        }
    }

    /** 
     * zwx
     * @param array     $param 搜索条件
     * @return array
     */
    public function delCart($param){
        $where = [];
        if(isset($param['id'])){
            $where['id'] = is_array($param['id'])?['in',implode(',',$param['id'])]:$param['id'];
        }
        isset($param['agent_id'])?$where['agent_id'] = $param['agent_id']:'';
        isset($param['goods_id'])?$where['goods_id'] = $param['goods_id']:'';
        if($where){
            return M('cart')->where($where)->delete();
        }
    }

    /** 
     * zwx 更新用户购物车数据
     * @param array     $user_id 购物车用户ID
     * @return array
     */
    public function refreshCart($data){
        if(!$data) return;

        $list = $this->getListJoinG($data);
        if(!$list) return;
        foreach ($list as $k => $v) {
            $save['id'] = $v['id'];
            $save['member_goods_price'] = $v['member_goods_price'];

            $saveAll[] = $save;
        }
        $this->saveAll($saveAll);

        return $list;
    }

    //zwx 生成购物车存储字段spec_key_name数据  goods_diamond 一维数组 
    public function setGoodsDiamondSkN($GoodsDiamond){
        $data[] = '证书类型：'.$GoodsDiamond['certificate_type'];
        $data[] = '证书号：'.$GoodsDiamond['certificate_number'];
        $data[] = '名称：'.$GoodsDiamond['goods_name'];
        $data[] = '类型：'.ConditionShow($GoodsDiamond['type'],'goods_diamond','type');
        $data[] = '形状：'.$GoodsDiamond['shape'];
        $data[] = '荧光：'.$GoodsDiamond['fluor'];
        $data[] = '重量：'.$GoodsDiamond['weight'];
        $data[] = '颜色：'.$GoodsDiamond['color'];
        $data[] = '净度：'.$GoodsDiamond['clarity'];
        $data[] = '切工：'.$GoodsDiamond['cut'];
        $data[] = '抛光：'.$GoodsDiamond['polish'];
        $data[] = '对称：'.$GoodsDiamond['symmetry'];
        $data[] = '库存：'.ConditionShow($GoodsDiamond['inventory_type'],'goods_diamond','inventory_type');
        
        return  implode(',', $data);
    }
}