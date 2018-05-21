<?php
namespace app\common\model;

use think\Model;

class GoodsImages extends Model
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
        isset($param['agent_id'])?$where['agent_id'] = $param['agent_id']:'';

        $vo = M('goods_images')
                ->field('id,host,small,big,goods_id,agent_id')
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
        isset($param['goods_id'])?$where['goods_id'] = $param['goods_id']:'';

        $list = M('goods_images')
                ->field('id,host,small,big,goods_id,agent_id')
                ->where($where)
                ->order($order)
                ->select();
        return $list;
    }

    /** 
     * zwx
     * @param array     $data 保存数据 $act add,edit $param修改条件
     * @return array
     */
    public function setGoodsImages($data,$act,$param=''){
        $save = [];
        isset($data['goods_id'])?$save['goods_id'] = $data['goods_id']:'';
        isset($data['host'])?$save['host'] = $data['host']:'';
        isset($data['small'])?$save['small'] = $data['small']:'';
        isset($data['big'])?$save['big'] = $data['big']:'';
        isset($data['agent_id'])?$save['agent_id'] = $data['agent_id']:'';

        $where = [];
        if(isset($param['id'])){
            $where['id'] = is_array($param['id'])?['in',implode(',',$param['id'])]:$param['id'];
        }

        if($act == 'edit' && $where){
            return M('goods_images')->where($where)->update($save);
        }elseif($act == 'add'){
            return M('goods_images')->insertGetId($save);
        }
        
    }

    /** 
     * zwx
     * @param array     $param 搜索条件
     * @return array
     */
    public function delGoodsImages($param){
        $where = [];
        if(isset($param['id'])){
            $where['id'] = is_array($param['id'])?['in',implode(',',$param['id'])]:$param['id'];
        }
        isset($param['agent_id'])?$where['agent_id'] = $param['agent_id']:'';
        isset($param['goods_id'])?$where['goods_id'] = $param['goods_id']:'';
        if($where){
            $data = M('goods_images')->where($where)->select();
            $this->unlikeGoodsImages($data);
            return M('goods_images')->where($where)->delete();
        }
    }

    //zwx
    public function unlikeGoodsImages($data){
        foreach ($data as $k => $v) {
            //$big = substr($v['big'],1);
            $bigArr = explode("/public",$v['big']);
			$big = "public".$bigArr[1];
			if(is_file($big)){
                unlink($big);
            }
			
            $smallArr = explode("/public",$v['small']);
            $small = "public".$smallArr[1];
            if(is_file($small)){
                unlink($small);
            }
        }
    }
}