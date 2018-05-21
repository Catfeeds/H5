<?php
/**
 * 门店
 */
namespace app\common\logic;

class Store
{
    public function __construct(){

    }
    
    //门店预约
    public function booked($data){
      if(!is_numeric($data['agent_id'])||$data['agent_id']<=0) return ['status'=>0,'msg'=>'分销商错误','data'=>''];
      if(!is_numeric($data['id'])||$data['id']<=0) return ['status'=>0,'msg'=>'请选择门店','data'=>''];
      if(!trim($data['name'])) return ['status'=>0,'msg'=>'预约人不能为空','data'=>''];
      if(!check_mobile($data['phone'])) return ['status'=>0,'msg'=>'电话号码错误','data'=>''];
      if(!in_array($data['type'], [0,1])) return ['status'=>0,'msg'=>'请设置预约类型','data'=>''];
      if(time()>strtotime($data['time'])) return ['status'=>0,'msg'=>'预约时间应大于当前时间','data'=>''];

      $where = [];
      $where['id'] = $data['id'];
      $where['agent_id'] = $data['agent_id'];
      $store = M('store')->where($where)->find();
      if(!$store) return ['status'=>0,'msg'=>'找不到门店','data'=>''];

      $save['agent_id'] = $data['agent_id'];
      $save['store_id'] = $data['id'];
      $save['name'] = $data['name'];
      $save['sex'] = in_array($data['sex'], [1,2])?$data['sex']:0; // 1男 2女 0未知
      $save['phone'] = $data['phone'];
      $save['create_time'] = date('Y-m-d H:i:s',time());
      $save['time'] = $data['time'];
      $save['type'] = $data['type'];
      $save['content'] = $data['content'];
      
      $id=M('store_booked')->insertGetId($save);
      if($id){
        M('store')->where($where)->setInc('booked_num');
        return ['status'=>100,'msg'=>'预约成功','data'=>''];
      }else{
        return ['status'=>0,'msg'=>'预约失败','data'=>''];
      }
    }
}