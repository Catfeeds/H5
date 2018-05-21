<?php
namespace app\common\model;

use think\Model;

class UserGoodsView extends Model
{

    protected function _initialize(){
        parent::_initialize();
    }

    //zwx
    public function getInfo($where){
        $vo = M('user_goods_view')->field('*')->where($where)->find();
        return $vo;
    }

    //zwx
    public function getList($where,$page=0,$pagesize=15,$order='id desc'){
        $list = M('user_goods_view')->field('*')->where($where)->order($order)->limit($page*$pagesize,$pagesize)->select();
        return $list;
    }

    //zwx  用户浏览商品记录操作
    public function setUserHistory($uid,$goods_id){
        if(!is_numeric($uid)||$uid<=0) return;
        if(!is_numeric($goods_id)||$goods_id<=0) return;

        $vo = M('user_goods_view')->field('id')->where(['uid'=>$uid,'goods_id'=>$goods_id])->find();
        $goods = M('goods')->where(['id'=>$goods_id])->find();

        $save['uid'] = $uid;
        $save['goods_id'] = $goods_id;
        $save['goods_type'] = $goods['type'];
        $save['agent_id'] = $goods['agent_id'];
        $save['goods_name'] = $goods['name'];
        $save['goods_sn'] = $goods['code'];
        $save['goods_price'] = $goods['price'];
        $save['create_time'] = date('Y-m-d H:i:s');
        if(!$vo){ //未记录
            M('user_goods_view')->insertGetId($save);
        }else{
            M('user_goods_view')->where(['id'=>$vo['id']])->update($save);
        }
    }
}