<?php
namespace app\common\logic;
use think\db;

//zwx 采购单
class PurchaseOrder
{

    public function __construct(){
   
    }

    //zwx 二级分销商生成采购单 $result['purchase_og_list'] $result['order_amount']
    public function set_purchase_order_2($result=[],$trader,$p_type){
        if(empty($result['purchase_og_list'])) return true;

        //订单编号
        $save_order['order_sn'] = D('order')->getOrderSn(); 
        //上级分销商id
        $save_order['agent_id'] = $trader['t_agent_id']; 
        //所在上级分销商的uid
        $save_order['user_id'] = $trader['purchase_uid']; 
        //默认第三方物流 1 门店自取 2 第三方物流
        $save_order['shipping_type'] = 2;
        //默认线下转账 1 线下转账 2 在线支付
        $save_order['pay_type'] = 1; 
        //根据二级分销商 在一级分销商的用户ID 获得收货地址
        $user_address = M('user_address')->where(['uid'=>$trader['purchase_uid'],'is_default'=>1])->find();
        if(!$user_address){
            return '错误1001，请联系商家';
        }
        //收货人
        $save_order['consignee'] = $user_address['recname']; 
        $area = explode('-', $user_address['area']);
        //收货地址
        $save_order['address'] = $area[0].$area[1].$area[2].$user_address['address'];
        //收货人电话
        $save_order['mobile'] = $user_address['phone'];
        //省份ID
        $save_order['province'] = $user_address['province'];
        //城市ID
        $save_order['city'] = $user_address['city'];
        //区县ID
        $save_order['district'] = $user_address['district'];
        //邮政编码
        $save_order['zipcode'] = $user_address['zipcode'];
        
        //默认线下支付 支付方式 
        $save_order['pay_code'] = 1000; 
        $save_order['pay_name'] = '线下转账'; //支付方式名称
        //抵扣积分
        $save_order['integral'] = 0; 
        //抵扣价格
        $save_order['integral_money'] = 0;
        //商品总价 
        $save_order['goods_price'] = $result['order_amount']; 
        //应付款金额
        $save_order['order_amount'] = $result['order_amount']; 
        //订单总价
        $save_order['total_amount'] = $result['order_amount']; 
        //默认定金总额
        $save_order['paid_money'] = $result['order_amount'];

        if($save_order['paid_money'] == 0){ //如果定金金额为0
            $save_order['order_status'] = 3; //订单状态
            $save_order['pay_status'] = 1; //支付状态
        }else{
            $save_order['order_status'] = 0; //订单状态
            $save_order['pay_status'] = 0; //支付状态
        }

        //添加订单时间
        $save_order['add_time'] = date('Y-m-d H:i:s',time()); 

        Db::startTrans();
        //生成二级分销商在一级分销商的order订单
        $id = M('order')->insertGetId($save_order);

        foreach ($result['purchase_og_list'] as $k => $v) {
            //二级分销商订单存储 在一级分销商生成的订单ID
            M('order_goods')->where(['id'=>$v['id']])->update(['zm_orderid'=>$id]);
            
            $save_order_goods['order_id'] = $id;
            //提交类型 1裸钻 2成品 3定制 钻配托 托配钻 一起提交属于定制
            $save_order_goods['order_type'] = $v['order_type'];
            $save_order_goods['goods_id'] = $v['goods_id'];
            $save_order_goods['goods_name'] = $v['goods_name'];
            $save_order_goods['stock_status'] = $v['stock_status']; //1为现货 2订货
            $save_order_goods['goods_sn'] = $v['goods_sn'];
            $save_order_goods['goods_num'] = $v['goods_num'];
            $save_order_goods['goods_price'] = $v['member_goods_price'];
            //预付金
            $save_order_goods['prepay_price'] = $v['member_goods_price']; 
            $save_order_goods['member_goods_price'] = $v['member_goods_price'];
            $save_order_goods['spec_key'] = $v['spec_key'];
            $save_order_goods['spec_key_name'] = $v['spec_key_name'];
            $save_order_goods['attr_main_json'] = $v['attr_main_json'];
            //组合编码
            $save_order_goods['goods_group_code'] = $v['goods_group'];
            $saveAll_order_goods[] = $save_order_goods;
        }

        //生成二级分销商在一级分销商的order_goods订单
        $bool_num = M('order_goods')->insertAll($saveAll_order_goods);
        
        if($id&&$bool_num){
            //一级向钻明转采购单
            $b = logic('PurchaseOrder')->set_index_zm_order($id,$trader['t_agent_id'],$p_type);
            if($b !== true){
                Db::rollback();
                return $b;
            }
            Db::commit();
            return true;
        }else{
            Db::rollback();
            return false;
        }
    }

    //zwx 前台根据 表[order] 字段[id] 订单生成采购单 $p_type = 1,提交自动转采购单 2 手动全转采购单
    public function set_index_zm_order($idStr,$agent_id,$p_type=1){
        $og_list = M('order_goods')->alias('og')
                    ->field('og.*,o.agent_id o_agent_id,g.agent_id g_agent_id,g.thumb,g.supply_goods_id,g.supply_goods_type,g.type')
                    ->join('zm_order o','o.id=og.order_id','left')
                    ->join('zm_goods g','g.id=og.goods_id','left')
                    ->where(['og.order_id'=>['in',$idStr]])
                    ->select();
        if(!$og_list) return false;

        return $this->set_purchase_order_1_or_2($og_list,$agent_id,$p_type);
    }

    //zwx 后台根据 表[order_goods] 字段[id] 生成采购单 $p_type = 2,手动全转采购单 1 提交自动转采购单
    public function set_admin_zm_order($idStr,$agent_id,$p_type=2){
        $og_list = M('order_goods')->alias('og')
                    ->field('og.*,o.agent_id o_agent_id,g.agent_id g_agent_id,g.thumb,g.supply_goods_id,g.supply_goods_type,g.type')
                    ->join('zm_order o','o.id=og.order_id','left')
                    ->join('zm_goods g','g.id=og.goods_id','left')
                    ->where(['og.id'=>['in',$idStr],'o.agent_id'=>$agent_id])
                    ->select();
        if(!$og_list) return false;

        return $this->set_purchase_order_1_or_2($og_list,$agent_id,$p_type);
    }

    //zwx 一级二级分销商调用此方法
    public function set_purchase_order_1_or_2($og_list,$agent_id,$p_type){
        //分销商信息
        $trader = get_agent_trader($agent_id);

        //二级分销商 暂时只有速订购生成采购单
        if($trader['t_agent_id']>0){
            //要生成采购单的数组
            $result['purchase_og_list'] = [];
            //生成订单总额
            $result['order_amount'] = 0;

            foreach ($og_list as $k => $v){
                //速订购钻石直接转采购单 或普通钻石手动转采购单
                if(($v['supply_goods_type'] == 1||($v['supply_goods_id']>0&&$p_type == 2))&&($v['type']==0||$v['type']==1||$v['type']==2)){
                    //官网钻石 普通和速订购
                    $goods_diamond = M('goods_diamond')->where(['goods_id'=>$v['goods_id']])->find();
                    //计算采购价 price 
                    $goods_diamond = logic('PriceCalculation')->goods_purchase_price([$goods_diamond],['supply_goods_id'=>'supply_gid'])[0];
                    //采购价赋值
                    $v['goods_price'] = $goods_diamond['price'];
                    $v['prepay_price'] = $goods_diamond['price'];
                    $v['member_goods_price'] = $goods_diamond['price'];
                    $result['purchase_og_list'][] = $v;
                    $result['order_amount'] += $goods_diamond['price']*$v['goods_num'];
                }else if($v['supply_goods_id']>0&&$p_type == 2&&$v['type']==4){
                    //下单通商品
                    $attr_main_json = json_decode($v['attr_main_json'],true);
                    $price = 0;
                    foreach ($attr_main_json as $k1 => $v1) {
                        //调用接口获取下单通价格
                        $Openzm = logic('Openzm')->get_customize_calculatePrice($v['supply_goods_id'],$v1['customize']['material_id'],$v1['customize']['stone_id'],$v1['matched_diamond_id']);
                        if($Openzm['code']==100200){
                            $price += $Openzm['data']['price']*$v1['goods_number'];
                        }else{
                            return false;
                        }
                    }
                    //计算价格设置值
                    $goods_purchase = [];
                    $goods_purchase['agent_id'] = 0;
                    $goods_purchase['type'] = 4;
                    $goods_purchase['price'] = $price;
                    $goods_purchase['supply_goods_id'] = 1;
                    //计算下单通 销售价格
                    $goods_purchase = logic('PriceCalculation')->goods_purchase_price([$goods_purchase])[0];
                    //采购价赋值
                    $v['goods_price'] = $goods_purchase['price'];
                    $v['prepay_price'] = $goods_purchase['price'];
                    $v['member_goods_price'] = $goods_purchase['price'];
                    $result['purchase_og_list'][] = $v;
                    $result['order_amount'] += $goods_purchase['price'];
                }else if(($v['supply_goods_id']==0&&$p_type == 2)&&($v['type']==0||$v['type']==1||$v['type']==2)){
                    //上级分销商自营钻石
                    $goods_diamond = M('goods_diamond')->where(['goods_id'=>$v['goods_id']])->find();
                    $goods_diamond = logic('PriceCalculation')->goods_purchase_price([$goods_diamond],['supply_goods_id'=>'supply_gid'])[0];

                    //采购价赋值
                    $v['goods_price'] = $goods_diamond['price'];
                    $v['prepay_price'] = $goods_diamond['price'];
                    $v['member_goods_price'] = $goods_diamond['price'];
                    $result['purchase_og_list'][] = $v;
                    $result['order_amount'] += $goods_diamond['price']*$v['goods_num'];
                }else if(($v['supply_goods_id']==0&&$p_type == 2)&&($v['type']==3||$v['type']==4)){
                    //上级分销商自营成品定制
                    //计算价格设置值
                    $goods_purchase = [];
                    $goods_purchase['agent_id'] = $trader['t_agent_id'];
                    $goods_purchase['type'] = $v['type'];
                    $goods_purchase['supply_goods_id'] = 0;

                    if($v['spec_key']){ //有sku查询sku价格
                        $goods_sku = M('goods_sku')->where(['attributes'=>$v['spec_key'],'goods_id'=>$v['goods_id']])->find();
                        $goods_purchase['price'] = $goods_sku['goods_price'];
                    }else{
                        $goods = M('goods')->where(['goods_id'=>$v['goods_id']])->find();
                        $goods_purchase['price'] = $goods['price'];
                    }
                    
                    $goods_purchase = logic('PriceCalculation')->goods_purchase_price([$goods_purchase])[0];

                    //采购价赋值
                    $v['goods_price'] = $goods_purchase['price'];
                    $v['prepay_price'] = $goods_purchase['price'];
                    $v['member_goods_price'] = $goods_purchase['price'];
                    $result['purchase_og_list'][] = $v;
                    $result['order_amount'] += $goods_purchase['price']*$v['goods_num'];
                }
            }
            //二级分销商向一级分销商发起采购单调用此方法
            return $this->set_purchase_order_2($result,$trader,$p_type);
        }

        //一级分销商 暂时只有速订购生成采购单
        if($trader['t_agent_id']==0){
            foreach ($og_list as $k => $v){
                if($v['supply_goods_id']>0){
                    $Openzm = [];
                    if($v['type']==0||$v['type']==1||$v['type']==2){ //钻石
                        if($v['supply_goods_type'] == 1||$p_type == 2){ //速订购直接转采购单,或手动转采购单
                            $Openzm = logic('Openzm')->get_diamond_detail($v['supply_goods_id']);
                            //订单类型(1: 普通钻石 2: 速订购)
                            $o_type = $v['supply_goods_type'] == 1?2:1;

                            if($Openzm['data']['goods_number'] != 1){
                                return $Openzm['data']['goods_name'].' 库存不足';
                            }
                            if($Openzm['data']['is_sale'] != 1){
                                return $Openzm['data']['goods_name'].' 商品已下架';
                            }
                            $Openzm = logic('Openzm')->order_createDiamondOrder(['gid'=>$v['supply_goods_id'],'type'=>$o_type],$v['o_agent_id']);                       
                        }
                    }else{ //戒托
                        if($p_type == 2){ //手动转采购单
                            $Openzm = logic('Openzm')->order_createCustomizeOrder(['data'=>$v['attr_main_json'],'type'=>1],$v['o_agent_id']);
                        }
                    }

                    if($Openzm['code'] == 100200){ //提交订单成功
                        M('order_goods')->where(['id'=>$v['id']])->update(['zm_orderid'=>$Openzm['data']['order_id']]);
                    }

                    if(!empty($Openzm)&&$Openzm['code']!=100200) return false;
                }
            }
        }

        return true;
    }

    //zwx 前台根据 表[order] 字段[id] 延长官网订单时间3天
    public function set_index_zm_modifyDiamondOrder($idStr){
        $og_list = M('order_goods')->alias('og')
                    ->field('og.*,o.agent_id o_agent_id,g.agent_id g_agent_id,g.thumb,g.supply_goods_id,g.supply_goods_type,g.type')
                    ->join('zm_order o','o.id=og.order_id','left')
                    ->join('zm_goods g','g.id=og.goods_id','left')
                    ->where(['og.order_id'=>['in',$idStr]])
                    ->select();
        if(!$og_list) return false;

        return $this->set_zm_modifyDiamondOrder($og_list);
        
    }

    //zwx 后台表[order_goods] 字段[id] 延长官网订单时间3天
    public function set_admin_zm_modifyDiamondOrder($idStr,$agent_id){
        $og_list = M('order_goods')->alias('og')
                    ->field('og.*,o.agent_id o_agent_id,g.agent_id g_agent_id,g.thumb,g.supply_goods_id,g.supply_goods_type,g.type')
                    ->join('zm_order o','o.id=og.order_id','left')
                    ->join('zm_goods g','g.id=og.goods_id','left')
                    ->where(['og.id'=>['in',$idStr],'o.agent_id'=>$agent_id])
                    ->select();
        if(!$og_list) return false;

        return $this->set_zm_modifyDiamondOrder($og_list);
    }

    public function set_zm_modifyDiamondOrder($og_list){
        foreach ($og_list as $k => $v){
            $Openzm = [];
            if($v['type']==0||$v['type']==1||$v['type']==2){ //钻石
                $type = 2;
            }else{ //珠宝
                $type = 1;
            }
            
            if($v['zm_orderid']>0){
                $Openzm = logic('Openzm')->order_modifyDiamondOrder(['type'=>$type,'order_id'=>$v['zm_orderid'],'delay'=>3600*24*3],$v['o_agent_id']);
            }

            if(!empty($Openzm)&&$Openzm['code']!=100200) return false;
        }
        
        return true;
    }
}