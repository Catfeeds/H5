<?php
/**
 * 订单管理逻辑层
 */
namespace app\common\logic;

use app\common\logic\LogicBase;
use think\Commutator;

class Order
{
    public function __construct(){

    }
    
    
    //支付完成回调
    public function afterPay($order_sn,$fee,$type,$typename='在线支付'){
        $order=model("Order");
        $orderInfo=$order->getInfoBySn($order_sn);   
        //插入payment
        $data['uid']=$orderInfo['user_id'];
        $data['order_id']=$orderInfo['id'];
        $data['agent_id']=$orderInfo['agent_id'];
        $data['price']=$fee;       
        $data['payment_status']=1;
        
        if($order->addOrderPayment($data)){
            //更改订单状态      
            $dataOrder['order_status']=3;//已支付
            $dataOrder['pay_time']=date('Y-m-d H:i:s', time());          
            $dataOrder['pay_status']=1;//已支付              
            $order->where("id",$orderInfo['id'])->update($dataOrder);
            D('order')->goDelivery($orderInfo['id']); //订单配货
            logic('PurchaseOrder')->set_index_zm_modifyDiamondOrder($orderInfo['id']); //订单转采购单
            return true;
        }   
        return false;
    }

    //zwx 订单过期
    public function BeOverdue($user_id){
        if(!is_numeric($user_id)&&$user_id<=0) return ;
        
        $where['user_id'] = $user_id;
        $where['order_status'] = 0;
        $where['pay_type'] = 2; //在线支付
        $where['add_time'] = ['lt',date('Y-m-d H:i:s',time()-900)];
        M('order')->where($where)->update(['order_status'=>7]);//订单过期
    }

    //zwx 添加订单评价
    public function AddUserOrderGoodsEval($user_id,$order_id,$order_goods_id,$score,$content,$agent_id){
        if(!is_numeric($user_id)||$user_id<=0) return ['status'=>99,'msg'=>'请先登录','data'=>''];
        if(!is_numeric($order_id)||$order_id<=0) return ['status'=>0,'msg'=>'订单错误','data'=>''];
        if(!is_numeric($order_goods_id)||$order_goods_id<=0) return ['status'=>0,'msg'=>'订单错误','data'=>''];
        if(!in_array($score,[1,2,3,4,5])) return ['status'=>0,'msg'=>'请选择评分','data'=>''];
        if(!trim($content)) return ['status'=>0,'msg'=>'请输入评价','data'=>''];
        
        $where['id'] = $order_id;
        $where['user_id'] = $user_id;
        $D_order = D('order');
        $result = $D_order->getOrderShowList($where);

        if(!$result) return ['status'=>0,'msg'=>'订单错误','data'=>''];

        $order_goods = [];
        // $is_comment = [];

        foreach ($result['data'] as $k => $v) {
            if($v['order_status'] != 5) return ['status'=>0,'msg'=>'评价状态错误','data'=>''];
            foreach ($v['sub_order_goods'] as $k1 => $v1) {
                if($v1['id'] == $order_goods_id){
                    if($v1['is_comment'] == 1) return ['status'=>0,'msg'=>'已评价','data'=>''];
                    $order_goods = $v1;
                }else{
                    // $is_comment[] = $v1['is_comment'];
                }
            }
        }

        if(!$order_goods) return ['status'=>0,'msg'=>'订单错误','data'=>''];

        $save['order_id'] = $order_goods['order_id'];
        $save['order_goods_id'] = $order_goods['id'];
        $save['goods_id'] = $order_goods['goods_id'];
        $save['score'] = $score;
        $save['uid'] = $user_id;
        $save['content'] = $content;
        $save['create_time'] = date('Y-m-d H:i:s',time());
        $save['create_ip'] =  $_SERVER['REMOTE_ADDR'];
        $save['agent_id'] = $agent_id;
        $D_order->startTrans();
        $b1 = M('order_goods_eval')->insertGetId($save);
        $b2 = M('order_goods')->where(['id'=>$order_goods['id']])->update(['is_comment'=>1]);
        $b3 = 1;
        // if(!in_array(0,$is_comment)){ //找不到0 表示全都评价
        //     $b3 = M('order')->where(['id'=>$order_goods['order_id']])->update(['order_status'=>6]);
        // }

        if($b1&&$b2&&$b3){
            $D_order->commit();
            return ['status'=>100,'msg'=>'评价成功','data'=>''];
        }else{
            $D_order->rollback();
            return ['status'=>0,'msg'=>'评价失败','data'=>''];
        }

    }

    //zwx 添加订单
    //$data [agent_id,shipping_type,store_id,shipping_time,consignee,mobile,user_address_id,pay_type,pay_code,user_note]
    public function addOrder($user_id,$data=[]){
        if(!is_numeric($user_id)||$user_id<=0) return ['status'=>99,'msg'=>'请先登录','data'=>''];

        if($data['shipping_type'] == 1){ //门店自取
            $save['store_id'] = $data['store_id']; //门店ID
            $save['shipping_time'] = $data['shipping_time'];
            $save['consignee'] = $data['consignee'];
            $save['mobile'] = $data['mobile'];
        }

        if($data['shipping_type'] == 2){ //第三方物流
            $save['user_address_id'] = $data['user_address_id']; //地址ID
        }
        
        $save['shipping_type'] = $data['shipping_type']; //1 门店自取 2 第三方物流
        $save['pay_type'] = $data['pay_type']; //1 线下转账 2 在线支付
        $save['pay_code'] = $data['pay_type'] == 2?$data['pay_code']:'1000';
        $save['user_note'] = $data['user_note'];
        $save['agent_id'] = $data['agent_id'];
        $save['user_id'] = $user_id;

        $cartList = D('cart')->refreshCart(['user_id'=>$user_id,'selected'=>1]); //更新购物车数据，并返回最新数据
        if(!$cartList) return ['status'=>0,'msg'=>'购物车商品选中为空','data'=>''];  
        
        foreach ($cartList as $k => $v) {
            if($v['g_stock_status'] == 1){ //现货判断库存
                if($v['gs_sku_id']>0){ //有sku
                    if($v['goods_num']>$v['gs_goods_number']){ //购物车数量大于库存数量
                        return ['status'=>0,'msg'=>$v['goods_name'].' 库存不足，剩余库存'.$v['gs_goods_number'],'data'=>''];
                    }
                }else{ //没有sku
                    if($v['goods_num']>$v['g_stock_num']){ //购物车数量大于库存数量
                        return ['status'=>0,'msg'=>$v['goods_name'].' 库存不足，剩余库存'.$v['g_stock_num'],'data'=>''];
                    }
                }
            }
        }

        $pay_type_arr = ConditionShow('','order','pay_type');
        $pay_code_arr = ConditionShow('','order','pay_code');

        if(!array_key_exists($save['pay_type'], $pay_type_arr)) return ['status'=>0,'msg'=>'支付方式错误','data'=>''];  
        if(!array_key_exists($save['pay_code'], $pay_code_arr)) return ['status'=>0,'msg'=>'支付方式错误','data'=>''];  

        $shipping_type_arr = ConditionShow('','order','shipping_type');
        if(!array_key_exists($save['shipping_type'], $shipping_type_arr)) return ['status'=>0,'msg'=>'取货方式错误','data'=>''];

        if($save['shipping_type'] == 1){ //门店自取
            if(!check_mobile($save['mobile'])) return ['status'=>0,'msg'=>'电话号码错误','data'=>''];
            if(!trim($save['consignee'])) return ['status'=>0,'msg'=>'请填写收货人','data'=>''];
            if(time()>strtotime($save['shipping_time'])) return ['status'=>0,'msg'=>'预约时间应大于当前时间','data'=>''];
            
            $where = [];
            $where['id'] = $save['store_id']; //门店ID
            $where['agent_id'] = $save['agent_id'];
            $store_info = M('store')->where($where)->find();
            if(!$store_info) return ['status'=>0,'msg'=>'门店选择错误','data'=>''];

            $return_data['mobile'] = $save['mobile'];
            $return_data['address'] = $store_info['province_name'].$store_info['city_name'].$store_info['district_name'].$store_info['address'];
            $return_data['shipping_time'] = $save['shipping_time']; // 上门预约时间
            $return_data['consignee'] = $save['consignee']; //收货人
        }

        if($save['shipping_type'] == 2){ //第三方物流
            $where = [];
            $where['uid'] = $user_id;
            $where['id'] = $save['user_address_id'];
            $user_address_info = M('user_address')->where($where)->find();
            if(!$user_address_info) return ['status'=>0,'msg'=>'地址选择错误','data'=>''];

            $return_data['consignee'] = $user_address_info['recname']; //收货人
            $return_data['province'] = $user_address_info['province'];
            $return_data['city'] = $user_address_info['city']; 
            $return_data['district'] = $user_address_info['district'];
            
            $area = explode('-', $user_address_info['area']);
            $return_data['address'] = $area[0].$area[1].$area[2].$user_address_info['address'];
            $return_data['zipcode'] = $user_address_info['zipcode'];
            $return_data['mobile'] = $user_address_info['phone'];
        }

        //根据原价计算会员折扣后的价格 折扣后的赠送积分
        $result = logic('Score')->getGoodsScore($user_id,$cartList);

        $save['cartList'] = $result['list'];

        $save['integral'] = 0; //初始化 
        $save['integral_money'] = 0; //初始化 
        $integral = is_numeric($data['integral'])&&$data['integral']>0?$data['integral']:0;

        //输入积分大于0进行计算
        if($integral>0){
            //zwx 获取积分抵扣计算数据
            $score = logic('Score')->getScoreCalculation($user_id,get_agent_config(),$result['order_amount']);

            if($score){ //返回数据表示开启抵扣 抵扣金额大于1
                if($save['integral']>$score['score_realize_score']) return ['status'=>0,'msg'=>'抵扣积分大于最大抵扣积分','data'=>''];  
                if($save['integral']>$score['score']) return ['status'=>0,'msg'=>'积分不足','data'=>''];  
                
                $integral_money = floor($integral/$score['score_rate']);//积分抵扣多少元
                
                if($integral_money>0){
                    $save['integral'] = $integral_money*$score['score_rate']; //抵扣积分
                    $save['integral_money'] = $integral_money; //抵扣金额
                }
            }
        }
        
        if($save['integral_money']>0){ //抵扣价格大于0 , 分摊到每个商品价格
            foreach ($save['cartList'] as $k => $v) {
                $integral_money_ratio = round($v['price_ratio']*$save['integral_money'],2);
                if($result['order_amount'] == $save['integral_money']){
                    $save['cartList'][$k]['member_goods_price'] = 0;
                }else{
                    $save['cartList'][$k]['member_goods_price'] -= round($integral_money_ratio/$v['goods_num'],2);
                }
            }
        }

        //根据折扣,抵扣后的价格 计算赠送积分
        $save['cartList'] = logic('Score')->getGoodsDeductibleScore($user_id,$save['cartList']);

        $return_data['integral'] = $save['integral']; //抵扣积分 
        $return_data['integral_money'] = $save['integral_money']; //抵扣金额

        $return_data['goods_price'] = $result['total_amount']; //商品总价
        $return_data['total_amount'] = $result['total_amount']; //订单总价
        $return_data['order_amount'] = $result['order_amount']-$save['integral_money']; //应付款金额

        $return_data['pay_type'] = $save['pay_type'];
        $return_data['shipping_type'] = $save['shipping_type'];
        $return_data['cartList'] = $save['cartList'];
        $return_data['agent_id'] = $save['agent_id'];
        $return_data['user_id'] = $user_id;
        $return_data['pay_name'] = $pay_code_arr[$save['pay_code']];
        $return_data['pay_code'] = $save['pay_code'];
        $return_data['user_note'] = $save['user_note'];

        $b = D('order')->addOrder($return_data);

        if(is_numeric($b)&&$b>0){
            return ['status'=>100,'msg'=>'订单生成成功','data'=>['id'=>$b]];
        }else{
            return ['status'=>0,'msg'=>$b,'data'=>''];
        }
    }

    //zwx 根据商品订货 现货 计算定金  数据结构['list'=>[],'order_amount'=>'']
    public function getOrderPaidMoney($result=[]){
        if(!$result) return ;
        $deposit_proportion = get_agent_info()['deposit_proportion'];

        //如果设置定金比例不正确，默认为1
        if($deposit_proportion<=0||$deposit_proportion>1){
            $deposit_proportion = 1;
        }
        //定金
        $paid_money = 0;
        foreach ($result['list'] as $k => $v) {
            if($v['g_stock_status'] == 2){ //订货
                $prepay_price = $v['member_goods_price']*$v['goods_num']*$deposit_proportion;
            }else{ //现货
                $prepay_price = $v['member_goods_price']*$v['goods_num'];
            }
            $result['list'][$k]['prepay_price'] = $prepay_price;   
            $paid_money += $prepay_price;
        }

        $result['paid_money'] = $paid_money; //要支付的定金
        return $result;
    }
}