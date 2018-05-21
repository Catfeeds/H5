<?php
/**
 * 积分
 */
namespace app\common\logic;

class Score
{
    public function __construct(){
    }

    //zwx 获取积分抵扣计算数据
    public function getScoreCalculation($user_id,$agent_config,$goods_price){
      if(!$user_id||!$agent_config||!$goods_price) return false;
      if($agent_config['isscore'] == 0|| $agent_config['score_realize'] == 0 || $agent_config['score_rate'] == 0) return false; //返回false 表示分销商未开启积分抵扣

      $user = M('user')->where(['id'=>$user_id])->find();

      $score['user_id'] = $user_id; //用户ID
      $score['score'] = $user['score']?$user['score']:0; //当前可用积分
      $score['isscore'] = $agent_config['isscore']; //1 表示开启 0 关闭
      $score['score_realize'] = $agent_config['score_realize']; // 抵扣积分率
      $score['score_realize_money'] = intval($goods_price/100*$agent_config['score_realize']); //最大抵扣金额
      $score['score_realize_score'] = intval($goods_price/100*$agent_config['score_realize'])*$agent_config['score_rate']; //最大抵扣积分

      $score['score_rate'] = $agent_config['score_rate']?$agent_config['score_rate']:0; // 多少积分抵扣一元

      if($score['score_realize_money']<1) return false; //抵扣金额小于1 不显示

      return $score;
    }

    /*
      会员折扣率为空 或NULL 不参与计算 例子：商品价格100 折扣率90  计算后商品价格为（100*90）/100 = 90

      折扣后的商品价格*(会员等级积分率 或 分组积分率，取高积分率)*商品分类积分率 例子：折扣后的商品价格为100 积分率为10 （100*10）/100 *100/100 = 10

      如果 会员等级积分率，分组积分率 为空 公式为
      折扣后的商品价格*商品分类积分率 例子：折扣后的商品价格为100 积分率为10 （100*10）/100 = 10

      如果 商品分类积分率为空 公式为
      折扣后的商品价格*(会员等级积分率 或 分组积分率，取高积分率) 例子：折扣后的商品价格为100 积分率为10 （100*10）/100 = 10
      
      如果 会员等级积分率，分组积分率，商品分类积分率 为空
      不增加积分
    */

    //zwx 根据原价计算会员折扣后的价格 折扣后的赠送积分 默认购物车数据
    public function getGoodsScore($user_id,$list=[],$data=[]){
      if(!$user_id) return $list;
      $g_agent_id = isset($data['agent_id'])?$data['agent_id']:'agent_id';//商品所属分销商
      $price = isset($data['price'])?$data['price']:'member_goods_price';//要计算的单价字段
      $goord_price = isset($data['goods_price'])?$data['goods_price']:'goods_price';//原价
      $goods_num = isset($data['goods_num'])?$data['goods_num']:'goods_num';//商品个数
      $type = isset($data['type'])?$data['type']:'g_type';//商品类型
      $score_rate = isset($data['score_rate'])?$data['score_rate']:'gc_score_rate';//商品分类积分率
      
      $agent_id = get_agent_id();
      
      $rate_list = M('user')->alias('u')
                  ->join('zm_score_type st','st.id = u.type_id','left')
                  ->join('zm_score_rank sr','sr.id = u.rank_id','left')
                  ->where(['u.id'=>$user_id])
                  ->field('u.id,u.first_leader,st.rate st_rate,st.discount st_discount,sr.rate sr_rate,sr.discount sr_discount')->find();

      //空和NULL 不进行计算   
      if(isset($rate_list['st_rate'])&&$rate_list['st_rate']>0||$rate_list['st_rate']===0){
        $st_rate = $rate_list['st_rate']; //分组 积分率
      }

      if(isset($rate_list['sr_rate'])&&$rate_list['sr_rate']>0||$rate_list['sr_rate']===0){
        $sr_rate = $rate_list['sr_rate']; //会员 积分率
      }

      if(isset($st_rate)&&isset($sr_rate)){ // 都有积分率 取高积分率
        $num_1 = $st_rate>$sr_rate?$st_rate:$sr_rate;
      }elseif(isset($st_rate)&&!isset($sr_rate)){ // 分组有积分率 会员没有积分率
        $num_1 = $st_rate;
      }elseif(!isset($st_rate)&&isset($sr_rate)){ // 分组没有积分率 会员有积分率
        $num_1 = $sr_rate;
      }

      //空和NULL 不进行计算 
      if(isset($rate_list['st_discount'])&&$rate_list['st_discount']>0||$rate_list['st_discount']===0){
        $st_discount = $rate_list['st_discount'];
      }

      if(isset($rate_list['sr_discount'])&&$rate_list['sr_discount']>0||$rate_list['sr_discount']===0){
        $sr_discount = $rate_list['sr_discount'];
      }

      if(isset($st_discount)&&isset($sr_discount)){ // 都有折扣率 取高折扣率
        $num_2 = $st_discount<$sr_discount?$st_discount:$sr_discount;
      }elseif(isset($st_discount)&&!isset($sr_discount)){ // 分组有折扣率 会员没有折扣率
        $num_2 = $st_discount;
      }elseif(!isset($st_discount)&&isset($sr_discount)){ // 分组没有折扣率 会员有折扣率
        $num_2 = $sr_discount;
      }

      $total_score = 0; //购买商品获取的总积分
      $order_amount = 0; //会员折扣后 总价
      $total_amount = 0; // 订单总价

      foreach ($list as $k => $v) {
          $total_amount += $list[$k][$price]*$v[$goods_num];
          $list[$k][$goord_price] = $v[$price];

          switch ($v[$type]) {
            case 0:
            case 1:
            case 2:
              if(isset($num_2)){ //如果有折扣，计算获取折扣价
                $list[$k][$price] = formatRound($v[$price]*$num_2/100);
              }
              break;
            case 3:
            case 4:
              if(isset($num_2)){ //如果有折扣，计算获取折扣价
                $list[$k][$price] = formatRound($v[$price]*$num_2/100);
              }
              $v_score = 0;
              if($v['g_isscore'] == 1&&$v[$g_agent_id]==$agent_id){ //如果有积分奖励 并且是自己的商品
                if(isset($num_1)){ //积分分组 等级有设置
                  if(isset($v[$score_rate])&&$v[$score_rate]>0||$v[$score_rate]===0){ //判断分类积分奖励设置
                    $v_score = formatRound($list[$k][$price]*$v[$goods_num]*$num_1/100*$v[$score_rate]/100);
                  }else{
                    $v_score = formatRound($list[$k][$price]*$v[$goods_num]*$num_1/100);
                  }
                }else{
                  if(isset($v[$score_rate])&&$v[$score_rate]>0||$v[$score_rate]===0){
                    $v_score = formatRound($list[$k][$price]*$v[$goods_num]*$v[$score_rate]/100);
                  }
                }
              }
              
              $total_score += floor($v_score);
              break;
          }

          $order_amount += $list[$k][$price]*$v[$goods_num];
          $list[$k]['total_'.$price] =  $list[$k][$price]*$v[$goods_num];
      }

      //获取商品价格比例
      foreach ($list as $k => $v) {
        //$order_amount如果为0 除0会报错
        if($order_amount > 0){
          $list[$k]['price_ratio'] = round($v['total_'.$price]/$order_amount,2);
        }else{
          $list[$k]['price_ratio'] = 0;
        }
      }
      $result['list'] = $list;
      $result['user_id'] = $user_id; 
      $result['total_score'] = floor($total_score); //购物获得积分
      $result['total_amount'] = $total_amount; //商品总价
      $result['order_amount'] = $order_amount; //会员折扣后总价

      return $result;
    }

    //zwx 根据折扣,抵扣后的价格 计算赠送积分 默认购物车数据
    public function getGoodsDeductibleScore($user_id,$list=[],$data=[]){
      if(!$user_id||!$list) return $list;

      $price = isset($data['price'])?$data['price']:'member_goods_price';//要计算的价格字段
      $goods_num = isset($data['goods_num'])?$data['goods_num']:'goods_num';//商品个数
      $type = isset($data['type'])?$data['type']:'g_type';//商品类型
      $score_rate = isset($data['score_rate'])?$data['score_rate']:'gc_score_rate';//商品分类积分率


      $rate_list = M('user')->alias('u')
                  ->join('zm_score_type st','st.id = u.type_id','left')
                  ->join('zm_score_rank sr','sr.id = u.rank_id','left')
                  ->where(['u.id'=>$user_id])
                  ->field('u.id,u.first_leader,st.rate st_rate,st.discount st_discount,sr.rate sr_rate,sr.discount sr_discount')->find();

      //空和NULL 不进行计算   
      if(isset($rate_list['st_rate'])&&$rate_list['st_rate']>0||$rate_list['st_rate']===0){
        $st_rate = $rate_list['st_rate']; //分组 积分率
      }

      if(isset($rate_list['sr_rate'])&&$rate_list['sr_rate']>0||$rate_list['sr_rate']===0){
        $sr_rate = $rate_list['sr_rate']; //会员 积分率
      }

      if(isset($st_rate)&&isset($sr_rate)){ // 都有积分率 取高积分率
        $num_1 = $st_rate>$sr_rate?$st_rate:$sr_rate;
      }elseif(isset($st_rate)&&!isset($sr_rate)){ // 分组有积分率 会员没有积分率
        $num_1 = $st_rate;
      }elseif(!isset($st_rate)&&isset($sr_rate)){ // 分组没有积分率 会员有积分率
        $num_1 = $sr_rate;
      }

      $first_leader_id = 0;

      if($rate_list['first_leader']>0){ //有推荐人
        $agent_config = get_agent_config();
        if($agent_config['score_first_leader_rate'] > 0){ // 推荐人积分率大于0
          $first_leader_id = $rate_list['first_leader'];
        }
      }

      foreach ($list as $k => $v) {
          $list[$k]['give_integral'] = 0; //购买者送积分初始化
          $list[$k]['give_leader_integral'] = 0; //推荐人送积分初始化

          $v_score = 0; //购买者送积分
          $v_leader_score = 0; //推荐人送积分

          switch ($v[$type]) {
            case 0:
            case 1:
            case 2:
              break;
            case 3:
            case 4:
              if($v['g_isscore'] == 1){ //如果有积分奖励
                if(isset($num_1)){ //积分分组 等级有设置
                  if(isset($v[$score_rate])&&$v[$score_rate]>0||$v[$score_rate]===0){ //判断分类积分奖励设置
                    $v_score = formatRound($list[$k][$price]*$v[$goods_num]*$num_1/100*$v[$score_rate]/100);
                  }else{
                    $v_score = formatRound($list[$k][$price]*$v[$goods_num]*$num_1/100);
                  }
                }else{
                  if(isset($v[$score_rate])&&$v[$score_rate]>0||$v[$score_rate]===0){
                    $v_score = formatRound($list[$k][$price]*$v[$goods_num]*$v[$score_rate]/100);
                  }
                }
              }
              break;
          }

          $v_score = floor($v_score);
          if($v_score>0){ //购买者送积分
            $list[$k]['give_integral'] = $v_score;
          }

          if($first_leader_id>0){ //推荐人送积分
            $v_leader_score = floor(formatRound($list[$k][$price]*$v[$goods_num]*$agent_config['score_first_leader_rate']/100));
            if($v_leader_score>0){
              $list[$k]['give_leader_integral'] = $v_leader_score;
            }
          }
      }

      return $list;
    }

    //zwx 计算赠送订单积分 
    public function setOrderGoodsScore($order_id){
      $where['og.order_id'] = $order_id;
      $list = M('order_goods')->alias('og')
      ->join('zm_order o','o.id = og.order_id','left')
      ->field('
          o.user_id,o.order_sn,o.agent_id,
          og.id,og.goods_id,og.member_goods_price,og.goods_num,og.give_integral,og.give_leader_integral
          ')
      ->where($where)
      ->select();

      if(!$list) return false;

      $give_integral_total = 0;
      $give_leader_integral_total = 0;
      foreach ($list as $k => $v) {
        $give_integral_total += $v['give_integral'];
        $give_leader_integral_total += $v['give_leader_integral'];
      }

      $datajson = json_encode(['order_sn'=>$list[0]['order_sn']]);

      $where = [];
      if($give_integral_total>0){ //订单用户送积分
        $where['uid'] = $list[0]['user_id'];
        $where['remark'] = '购物获得积分';
        $where['datajson'] = $datajson;

        if(!M('user_score')->where($where)->find()){
          D('score')->setUserScore($list[0]['user_id'],$list[0]['agent_id'],$give_integral_total,'购物获得积分','',$datajson);
        }
      }

      if($give_leader_integral_total>0){  //推荐用户送积分
        $user = M('user')->where(['id'=>$list[0]['user_id']])->find();
        if($user['first_leader']>0){
          $where['uid'] = $user['first_leader'];
          $where['remark'] = '推荐人购物获得积分';
          $where['datajson'] = $datajson;

          if(!M('user_score')->where($where)->find()){
            D('score')->setUserScore($user['first_leader'],$list[0]['agent_id'],$give_leader_integral_total,'推荐人购物获得积分','',$datajson);
          }
        }
      }

    }

    //zwx 计算赠送订单积分 
    // public function setOrderGoodsScore($order_id,$agent_id){
    //   if(!$order_id||!$agent_id) return false;
    //   $price = isset($data['price'])?$data['price']:'member_goods_price';//要计算的价格字段
    //   $goods_num = isset($data['goods_num'])?$data['goods_num']:'goods_num';//商品个数
    //   $type = isset($data['type'])?$data['type']:'g_type';//商品类型
    //   $score_rate = isset($data['score_rate'])?$data['score_rate']:'gc_score_rate';//商品分类积分率

    //   $where['og.order_id'] = $order_id;
    //   $where['o.agent_id'] = $agent_id;
    //   $list = M('order_goods')->alias('og')
    //       ->join('zm_order o','o.id = og.order_id','left')
    //       ->join('zm_goods g','g.id = og.goods_id','left')
    //       ->join('zm_goods_category gc','g.jewelry_id = gc.id')
    //       ->field('
    //           o.user_id,o.order_sn,
    //           og.id,og.goods_id,og.member_goods_price,og.goods_num,og.give_integral,
    //           g.type g_type,
    //           gc.score_rate gc_score_rate
    //           ')
    //       ->where($where)
    //       ->select();

    //   if(!$list) return false;

    //   $user_id = $list[0]['user_id'];
    //   $order_sn = $list[0]['order_sn'];

    //   $rate_list = M('user')->alias('u')
    //               ->join('zm_score_type st','st.id = u.type_id','left')
    //               ->join('zm_score_rank sr','sr.id = u.rank_id','left')
    //               ->where(['u.id'=>$user_id])
    //               ->field('u.id,u.first_leader,st.rate st_rate,st.discount st_discount,sr.rate sr_rate,sr.discount sr_discount')->find();

    //   //空和NULL 不进行计算   
    //   if(isset($rate_list['st_rate'])&&$rate_list['st_rate']>0||$rate_list['st_rate']===0){
    //     $st_rate = $rate_list['st_rate']; //分组 积分率
    //   }

    //   if(isset($rate_list['sr_rate'])&&$rate_list['sr_rate']>0||$rate_list['sr_rate']===0){
    //     $sr_rate = $rate_list['sr_rate']; //会员 积分率
    //   }

    //   if(isset($st_rate)&&isset($sr_rate)){ // 都有积分率 取高积分率
    //     $num_1 = $st_rate>$sr_rate?$st_rate:$sr_rate;
    //   }elseif(isset($st_rate)&&!isset($sr_rate)){ // 分组有积分率 会员没有积分率
    //     $num_1 = $st_rate;
    //   }elseif(!isset($st_rate)&&isset($sr_rate)){ // 分组没有积分率 会员有积分率
    //     $num_1 = $sr_rate;
    //   }

    //   $first_leader_id = 0;

    //   if($rate_list['first_leader']>0){
    //     $agent_config = get_agent_config();
    //     if($agent_config['score_first_leader_rate'] > 0){ // 推荐人积分率大于0
    //       $first_leader_id = $rate_list['first_leader'];
    //     }
    //   }
      
    //   $total_score = 0; //购买商品获取的总积分
    //   $total_first_leader_score = 0;//推荐人获取积分总和

    //   foreach ($list as $k => $v) {
    //       if($v['give_integral'] > 0) break;//表示已经获取过积分
    //       switch ($v[$type]) {
    //         case 0:
    //         case 1:
    //         case 2:
    //           break;
    //         case 3:
    //         case 4:
    //           if(isset($num_1)){ //如果有积分奖励
    //             $v_score = 0;
    //             if(isset($v[$score_rate])&&$v[$score_rate]>0||$v[$score_rate]===0){ //判断分类积分奖励设置
    //               $v_score = formatRound($list[$k][$price]*$v[$goods_num]*$num_1/100*$v[$score_rate]/100);
    //             }else{
    //               $v_score = formatRound($list[$k][$price]*$v[$goods_num]*$num_1/100);
    //             }
    //           }
    //           $v_score = floor($v_score);
    //           $total_score += $v_score;
    //           if($v_score>0){ //当前商品有积分奖励
    //             M('order_goods')->where(['id'=>$v['id']])->update(['give_integral'=>$v_score]);
    //           }
    //           if($first_leader_id>0){
    //             $total_first_leader_score += formatRound($list[$k][$price]*$v[$goods_num]*$agent_config['score_first_leader_rate']/100);
    //           }
    //           break;
    //       }
    //   }

    //   $total_score = floor($total_score);
    //   $total_first_leader_score = floor($total_first_leader_score);

    //   if($total_score>0){ //购买获得积分大于0
    //     D('score')->setUserScore($user_id,$agent_id,$total_score,'购物获得积分，订单号：'.$order_sn);
    //   }

    //   if($total_first_leader_score>0){ //推荐人获得积分
    //     D('score')->setUserScore($user_id,$agent_id,$total_first_leader_score,'推荐人购物获得积分');
    //   }
    // }

    //zwx 计算非消费行为积分
    public function setScore($uid,$actioncode,$agent_id){
      $score = M('score')->where(['code'=>$actioncode,'agent_id'=>$agent_id])->find();
      if($score['score']<=0) return ; //积分大于0

      switch ($actioncode) {
        case 'login':   //登录送积分

            $time_start = date('Y-m-d H:i:s',strtotime(date('Y-m-d',time())));
            $time_end = date('Y-m-d H:i:s',strtotime(date('Y-m-d',time()))+24*60*60);

            $where = "actioncode = 'login' and uid = ".$uid." and create_time>='".$time_start."' and create_time<'".$time_end."'";
          break;
        case 'recommend':   //推荐送积分
            $where['actioncode'] = 'recommend';
            $where['uid'] = $uid;
          break;
        default:
          return ;
      }

      $user_score = M('user_score')->where($where)->find();
      if($user_score) return ; //已添加过积分

      D('score')->setUserScore($uid,$agent_id,$score['score'],$score['name'],$actioncode);
    }
}