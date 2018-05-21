<?php
namespace app\common\model;

use think\Model;

class GoodsVideos extends Model
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
    public function getInfo($param,$order='id desc'){
        $where = [];
        isset($param['id'])?$where['id'] = $param['id']:'';
        isset($param['agent_id'])?$where['agent_id'] = $param['agent_id']:'';
        isset($param['goods_id'])?$where['goods_id'] = $param['goods_id']:'';
        
        $vo = M('goods_videos')
                ->field('id,url,goods_id,create_time,agent_id')
                ->where($where)
                ->order($order)
                ->find();
        return $vo;
    }

    /** 
     * zwx
     * @param array     $param 搜索条件
     * @return array
     */
    public function getList($param,$list_rows=15,$order='id desc'){
        $where = [];
        if(isset($param['id'])){
            $where['id'] = is_array($param['id'])?['in',implode(',',$param['id'])]:$param['id'];
        }
        isset($param['agent_id'])?$where['agent_id'] = $param['agent_id']:'';
        isset($param['goods_id'])?$where['goods_id'] = $param['goods_id']:'';

        $list = M('goods_videos')
                ->field('id,url,goods_id,create_time,agent_id')
                ->where($where)
                ->order($order)
                ->paginate($list_rows,false,['query'=>$param]);
        return $list;
    }

    /** 
     * zwx
     * @param array     $data 保存数据 $act add,edit $param修改条件
     * @return array
     */
    public function setGoodsVideos($data,$act,$param=''){
        $save = [];
        isset($data['goods_id'])?$save['goods_id'] = $data['goods_id']:'';
        isset($data['url'])?$save['url'] = $data['url']:'';
        isset($data['agent_id'])?$save['agent_id'] = $data['agent_id']:'';
        $save['create_time'] = date('Y-m-d H:i:s',time());

        $where = [];
        if(isset($param['id'])){
            $where['id'] = is_array($param['id'])?['in',implode(',',$param['id'])]:$param['id'];
        }

        if($act == 'edit' && $where){
            return M('goods_videos')->where($where)->update($save);
        }elseif($act == 'add'){
            return M('goods_videos')->insertGetId($save);
        }
        
    }

    /** 
     * zwx
     * @param array     $param 搜索条件
     * @return array
     */
    public function delGoodsVideos($param){
        $where = [];
        if(isset($param['id'])){
            $where['id'] = is_array($param['id'])?['in',implode(',',$param['id'])]:$param['id'];
        }
        isset($param['agent_id'])?$where['agent_id'] = $param['agent_id']:'';
        isset($param['goods_id'])?$where['goods_id'] = $param['goods_id']:'';
        if($where){
            $data = M('goods_videos')->where($where)->select();
            $this->unlikeGoodsVideos($data);
            return M('goods_videos')->where($where)->delete();
        }
    }

    //zwx
    public function unlikeGoodsVideos($data){
        foreach ($data as $k => $v) {
            $d = substr($v['url'],1);
            if(is_file($d)){
                unlink($d);
            }
        }
    }
}