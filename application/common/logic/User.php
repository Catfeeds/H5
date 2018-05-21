<?php

namespace app\common\logic;
use think\Db;
class User{

  //zwx  获取用户数据统计
  public function getUserTotal($id){
    if($id<=0) return ['status'=>99,'msg'=>'请先登录','data'=>''];
    $user = M('user')->where(['id'=>$id])->find();

    //上级推荐人
    $user['first_leader_name'] = '';
    if($user['first_leader'] > 0){
      $first_leader = M('user')->where(['id'=>$user['first_leader']])->find();
      $user['first_leader_name'] = substr($first_leader['username'],0,3).'*****'.substr($first_leader['username'],-3);
      
    }
    //我的推荐人数量
    $user['underling_number'] = M('user')->where(['first_leader'=>$id])->count();
    
    //我的推荐总奖励
    $total_recommend_score = M('user_score')->field('sum(score) total_recommend_score')->where(['actioncode'=>'recommend','uid'=>$id])->find();
    $user['total_recommend_score'] = $total_recommend_score['total_recommend_score']>0?$total_recommend_score['total_recommend_score']:0;
    
    //消费次数，消费金额
    $order_where['user_id'] = $id;
    $order_where['order_status'] = ['in','2,3,4,5,6'];
    $order = M('order')->field('count(*) total_order_count,sum(order_amount) total_order_amount')->where($order_where)->find();
    
    $user['total_order_amount'] = $order['total_order_amount'];//消费金额
    $user['total_order_count'] = $order['total_order_count'];//消费次数

    return ['status'=>100,'msg'=>'','data'=>$user];
  }

  //zwx  设置用户基本信息
  //$data = [birthday,sex,realname]
	public function userSet($id='',$data=[]){
		if($id<=0) return ['status'=>99,'msg'=>'请先登录','data'=>''];
		
		isset($data['birthday'])? $data['birthday'] = strtotime($data['birthday']):'';

		$user = M('user')->where(['id'=>$id])->find();
		foreach ($user as $k => $v) {
			if(array_key_exists($k,$data)){
				if($v == $data[$k]){
					unset($data[$k]);
				}
			}
		}

		if(!$data) return ['status'=>100,'msg'=>'修改成功','data'=>''];

		isset($data['birthday'])?$save['birthday'] = $data['birthday']:'';
		isset($data['sex'])&&in_array($data['sex'], [1,2])?$save['sex'] = $data['sex']:'';
		isset($data['realname'])?$save['realname'] = $data['realname']:'';
		$bool = M('user')->where(['id'=>$id])->update($save);
		if($bool){
			return ['status'=>100,'msg'=>'修改成功','data'=>''];
		}else{
			return ['status'=>0,'msg'=>'修改失败','data'=>''];
		}
	}

	//zwx 修改密码 $old_password 旧密码 $new_password 新密码 $new_password_c 确认新密码
	public function userSetPassword($id='',$agent_id,$old_password='',$new_password='',$new_password_c=''){
		if($id<=0) return ['status'=>99,'msg'=>'请先登录','data'=>''];
    if($new_password!=$new_password_c) return ['status'=>0,'msg'=>'确认密码不一致','data'=>''];
		if(strlen($new_password)<6) return ['status'=>0,'msg'=>'密码大于6位数','data'=>''];
		
		$user = M('user')->where(['id'=>$id])->find();
		$old_password = pwdHash($old_password,$user['username'],$agent_id);
		if($user['password']!=$old_password) return ['status'=>0,'msg'=>'原密码错误','data'=>''];
		
		$new_password = pwdHash($new_password,$user['username'],$agent_id);
		if($user['password']==$new_password) return ['status'=>0,'msg'=>'新密码不能与原密码相同','data'=>''];

		$bool = M('user')->where(['id'=>$id])->update(['password'=>$new_password]);

		if($bool){
			return ['status'=>100,'msg'=>'修改成功','data'=>''];
		}else{
			return ['status'=>0,'msg'=>'修改失败','data'=>''];
		}
	}

	//zwx  设置用户地址信息
	//$data = [id,agent_id,title,province,city,district,address,zipcode,recname,phone,is_default]
	public function userAddressSet($uid='',$data=[]){
		if(!is_numeric($uid)||$uid<=0) return ['status'=>99,'msg'=>'请先登录','data'=>''];
    if(!is_numeric($data['province'])||$data['province']<=0) return ['status'=>0,'msg'=>'请选择省','data'=>''];
    if(!is_numeric($data['city'])||$data['city']<=0) return ['status'=>0,'msg'=>'请选择市','data'=>''];
    if(!is_numeric($data['district'])||$data['district']<=0) return ['status'=>0,'msg'=>'请选择区','data'=>''];
		if(!check_mobile($data['phone'])) return ['status'=>0,'msg'=>'电话号码错误','data'=>''];
		if(!trim($data['recname'])) return ['status'=>0,'msg'=>'收货人不能为空','data'=>''];
		if(!trim($data['address'])) return ['status'=>0,'msg'=>'详细地址不能为空','data'=>''];
    if(mb_strlen($data['title'])>8) return ['status'=>0,'msg'=>'地址标注不能超过8个字符','data'=>''];

		$data['id']>0?$save['id'] = $data['id']:'';
		$save['uid'] = $uid;
		$save['agent_id'] = $data['agent_id'];

		$save['title'] = $data['title']; //例如家里、公司、最多8个字符
    $save['province'] = $data['province']; //省份id
    $save['city'] = $data['city']; //城市id
    $save['district'] = $data['district']; //区县id
    $save['area'] = $data['area']; //详细地址
    $save['address'] = $data['address']; //详细地址
    $save['zipcode'] = $data['zipcode']; //邮政编码
    $save['recname'] = $data['recname']; //收货人姓名
    $save['phone'] = $data['phone']; //手机
    $save['is_default'] = $data['is_default'] == 1?1:0; //1选中 0不选中

    if($save['is_default'] == 1){ //如果选中 , 先设置用户所有地址不选中
        M('user_address')->where(['uid'=>$uid])->update(['is_default'=>0]);
    }

    if($save['id']>0){ //编辑
			$bool = D('user')->saveAddress($save);
			if($bool){
				return ['status'=>100,'msg'=>'保存成功','data'=>['id'=>$save['id']]];
			}
    }else{ //新增
    	$id = D('user')->addAddress($save);
    	if($id){
    		return ['status'=>100,'msg'=>'保存成功','data'=>['id'=>$id]];
    	}
    }
    return ['status'=>0,'msg'=>'保存失败','data'=>''];
	}

	//zwx 删除收货地址 $id = 1 or [1,2,3]
	public function userAddressDel($uid='',$id){
		if(!is_numeric($uid)||$uid<=0) return ['status'=>99,'msg'=>'请先登录','data'=>''];

		if(is_array($id)){
        $id_in = implode(',',$id);
        $id_count = count($id);
    }else{
        $id_in = $id;
        $id_count = 1;
        $id = [$id]; //字符串转数组
    }
    if(!$id) return ['status'=>0,'msg'=>'地址错误','data'=>''];

    $where = [];
    $where['id'] = ['in',$id_in];
    $where['uid'] = $uid;
    $count = M('user_address')->where($where)->count();

    if($count != $id_count) return ['status'=>0,'msg'=>'删除地址错误','data'=>''];

    if(M('user_address')->where($where)->delete()){
    	return ['status'=>100,'msg'=>'删除地址成功','data'=>''];
    }else{
    	return ['status'=>0,'msg'=>'删除地址失败','data'=>''];
    }
	}

	//zwx 加入收藏
	//$data = [goods_id,agent_id]
	public function userGoodsCollectionAdd($uid='',$data=[]){
		if(!is_numeric($uid)||$uid<=0) return ['status'=>99,'msg'=>'请先登录','data'=>''];
		
		//判断数组 组装所要使用数据
    if(is_array($data['goods_id'])){
        $goods_id_in = implode(',',$data['goods_id']);
        $goods_id_count = count($data['goods_id']);
    }else{
        $goods_id_in = $data['goods_id'];
        $goods_id_count = 1;
        $data['goods_id'] = [$data['goods_id']]; //字符串转数组
    }
    if(!$data['goods_id']) return ['status'=>0,'msg'=>'商品错误','data'=>''];

    $D_goods = D('goods');

    //要收藏商品个数 效验
    $where = [];
    $where['id'] = ['in',$goods_id_in];
    // $where['agent_id'] = $data['agent_id'];
    $count = $D_goods->field('id')->where($where)->count();

    if(!$count||$count!=$goods_id_count) return ['status'=>0,'msg'=>'商品所属分销商错误','data'=>''];

    //要收藏商品是否已收藏查询
    $where = [];
    $where['goods_id'] = ['in',$goods_id_in];
    $where['uid'] = $uid;
    $user_goods_collection = M('user_goods_collection')->field('goods_id')->where($where)->select();

    if(count($user_goods_collection) == $goods_id_count) return ['status'=>0,'msg'=>'商品已收藏','data'=>''];
    
    if($user_goods_collection){ //要添加的收藏 数据库有部分收藏
        foreach ($user_goods_collection as $k => $v) {
            if(!in_array($v['goods_id'], $data['goods_id'])){ //要收藏的商品过滤掉 已收藏商品
                $goods_id_arr[] = $v['goods_id'];
            }
        }
        $goods_id_in = implode(',',$goods_id_arr); //过滤后
    }

    //组装要添加的收藏数据
    $where = [];
    $where['id'] = ['in',$goods_id_in];
    // $where['agent_id'] = $data['agent_id'];
    $goods_list = $D_goods->field('id,type,agent_id,name,code,price')->where($where)->select();

    foreach ($goods_list as $k => $v) {
            $save['uid'] = $uid;
            $save['goods_id'] = $v['id'];
            $save['goods_type'] = $v['type'];
            $save['agent_id'] = $v['agent_id'];
            $save['goods_name'] = $v['name'];
            $save['goods_sn'] = $v['code'];
            $save['goods_price'] = $v['price'];
            $save['create_time'] = date('Y-m-d H:i:s');
            $saveAll[] =  $save;
    }

    $bool = M('user_goods_collection')->insertAll($saveAll);
    if($bool){
        cookie('collection_count'.$uid,null); //新增购物车清空cookie
        return ['status'=>100,'msg'=>'收藏成功','data'=>''];
    }else{
       return ['status'=>0,'msg'=>'收藏失败','data'=>''];
    }
	}

	//zwx 取消收藏 $id goods_id = 1 or [1,2,3] 
	public function userGoodsCollectionDel($uid='',$id){
		if(!is_numeric($uid)||$uid<=0) return ['status'=>99,'msg'=>'请先登录','data'=>''];

		if(is_array($id)){
          $id_in = implode(',',$id);
          $id_count = count($id);
      }else{
          $id_in = $id;
          $id_count = 1;
          $id = [$id]; //字符串转数组
      }
      if(!$id) return ['status'=>0,'msg'=>'取消收藏错误','data'=>''];

      $where = [];
      $where['goods_id'] = ['in',$id_in];
      $where['uid'] = $uid;
      $count = M('user_goods_collection')->where($where)->count();

      if($count != $id_count) return ['status'=>0,'msg'=>'取消收藏错误','data'=>''];

      if(M('user_goods_collection')->where($where)->delete()){
        cookie('collection_count'.$uid,null); //新增购物车清空cookie
      	return ['status'=>100,'msg'=>'取消收藏成功','data'=>''];
      }else{
      	return ['status'=>0,'msg'=>'取消收藏失败','data'=>''];
      }
	}

	//zwx 删除浏览记录 $id goods_id = 1 or [1,2,3] 
	public function userGoodsViewDel($uid='',$id){
		if(!is_numeric($uid)||$uid<=0) return ['status'=>99,'msg'=>'请先登录','data'=>''];

		if(is_array($id)){
          $id_in = implode(',',$id);
          $id_count = count($id);
      }else{
          $id_in = $id;
          $id_count = 1;
          $id = [$id]; //字符串转数组
      }
      if(!$id) return ['status'=>0,'msg'=>'删除错误','data'=>''];

      $where = [];
      $where['goods_id'] = ['in',$id_in];
      $where['uid'] = $uid;
      $count = M('user_goods_view')->where($where)->count();

      if($count != $id_count) return ['status'=>0,'msg'=>'删除错误','data'=>''];

      if(M('user_goods_view')->where($where)->delete()){
      	return ['status'=>100,'msg'=>'删除成功','data'=>''];
      }else{
      	return ['status'=>0,'msg'=>'删除失败','data'=>''];
      }
	}

  //获取礼品订单
  public function getScoreOrder($where,$page,$pagesize)
  {
    if(!is_numeric($where['user_id'])||$where['user_id']<=0) return ['status'=>101,'msg'=>'请先登录','data'=>''];
    $start=($page-1)*$pagesize;
    if ($where['a.order_status']) {  //如果有条件 
      $total = Db::name('score_order a')->where($where)->count();
    } else {
      $total = Db::name('score_order a')->count();
    }
    $list = Db::name('score_order a')
          ->field('a.*,b.thumb')
          ->join('score_goods b','b.id=a.goods_id',"LEFT")
          ->where($where)
          ->limit($start,$pagesize)
          ->order('id desc')
          ->select();
    $data = [];
    $data['list'] = $list;
    $data['total'] = $total;
    $data['page'] = $page;
    $data['pagesize'] = $pagesize;
    $data['totalpage'] = ceil($total / $pagesize);
    return $data;
  }

  //礼品订单状态修改
  public function editScoreOrder($user_id,$params,$agent_id)
  {
    if(!is_numeric($user_id)||$user_id<=0) return ['status'=>101,'msg'=>'请先登录','data'=>''];
    if($params['tmp'] != 1) return ['status'=>101,'msg'=>'慢点点','data'=>''];
    $res = Db::name('score_order')->field('order_status,total_amount,order_sn,goods_num,goods_id')->where('id',$params['id'])->find();
    if ($params['type'] == -1 && $res['order_status'] == 0) { //取消订单
        $res1 = Db::name('score_order')->where('id',$params['id'])->update(['order_status'=>7]);
        $res1 = Db::name('score_goods')->where('id',$res['goods_id'])->setInc('stock_num',$res['goods_num']);
        $res2 = Db::name('user')->where('id',$user_id)->setInc('score',$res['total_amount']);
        $res3 = Db::name('user_score')->insert([
            'uid'=>$user_id,
            'score'=>$res['total_amount'],
            'datajson'=>json_encode(['score_order_sn'=>$res['order_sn']]),
            'remark'=>"礼品订单取消退回积分",
            'agent_id'=>$agent_id
          ]);
        if ($res1 && $res2 && $res3) {
          return ['status'=>100,'msg'=>'订单取消成功','data'=>''];
        } else {
          return ['status'=>101,'msg'=>'订单取消失败','data'=>''];
        }
    } elseif ($params['type'] == 1 && $res['order_status'] == 4) {  //确认收货
        $res1 = Db::name('score_order')->where('id',$params['id'])->update(['order_status'=>5,'confirm_time'=>date('Y-m-d H:i:s',time())]);
        if ($res1) {
          return ['status'=>100,'msg'=>'订单收货成功','data'=>''];
        } else {
          return ['status'=>101,'msg'=>'订单收货失败','data'=>''];
        }
    } else {
       return ['status'=>101,'msg'=>'订单状态错误','data'=>''];
    }

  }

}