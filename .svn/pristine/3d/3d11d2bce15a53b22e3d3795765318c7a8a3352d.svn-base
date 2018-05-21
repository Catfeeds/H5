<?php
namespace app\common\model;
use think\db;
use think\Model;

//积分商品
class ScoreGoods extends Model
{
  
    //设置积分商品 $data 要保存的数据 $act add,edit $where修改条件
    public function setScoreGoods($data,$act='',$where=''){
        isset($data['name'])?$save['name'] = $data['name']:''; 
        isset($data['price'])?$save['price'] = $data['price']:'';
        isset($data['stock_num'])?$save['stock_num'] = $data['stock_num']:'';
        isset($data['thumb'])?$save['thumb'] = $data['thumb']:'';
        isset($data['content'])?$save['content'] = $data['content']:'';
        isset($data['agent_id'])?$save['agent_id'] = $data['agent_id']:'';

        if($act == 'add'){
            $save['code'] = D('goods')->getGoodsCode();//code 速易宝标准编码
            $save['create_time'] = date('Y-m-d H:i:s',time());//create_time 创建时间
            $save['create_user'] = session('admin_id');//create_user 创建人
            $save['product_status'] = 1;
            $id = M('score_goods')->insertGetId($save);
            return $id;
        }else if($act == 'edit'){
            $bool = M('score_goods')->where($where)->update($save);
            return $bool;
        }
    }

    //zwx
    public function getScoreGoodsInfo($where=''){
        $info = M('score_goods')->where($where)->find();
        return $info;
    }


    //获取所有的列表
    public function getAllGoods($param,$page,$pagesize,$order,$iscount)
    {
        $where = [];
        isset($param['name'])?$where['name'] = ['like','%'.$param['name'].'%']:'';
        isset($param['agent_id'])?$where['agent_id'] = $param['agent_id']:'';
        $start=($page-1)*$pagesize;

        $list = M('score_goods')
                ->field('id,name,keyword,sn,code,type,thumb,agent_id,content,remark,price,price_seller,stock_unit,stock_num,product_status')
                ->where($where)
                ->order($order)
                ->limit($start,$pagesize)->select(); 
        if ($iscount == 1) {
            $total = M('score_goods')->where('product_status=1 and agent_id='.$param['agent_id'])->count();
        } else {
            $total = count($list);
        }
        $data = [];
        $data['list']  = $list;
        $data['page']  = $page;
        $data['pagesize']  = $pagesize;
        $data['total'] = $total;
        $data['totalpage'] = ceil($total/$pagesize);
        return $data;
    }
}