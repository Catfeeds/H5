<?php
namespace app\common\model;
use think\db;
use think\Model;

//门店
class Store extends Model
{  
    /**
     * 门店预约
     **/
    public function booked($agent_id,$data){
        $data['agent_id']=$agent_id;
        $id=M('store_booked')->insertGetId($data);
        
        if($id){
            $this->where("id",$data['store_id'])->setInc('booked_num');
        }
        
        return $id;
    }

    /**
     * 查询门店
     * @param $agent_id int 分销商id
     * @param $is_show int 0：不显示 1：显示
     * @return array 分类  门店信息数组
     * @author guihongbing
     * @date 20171219
     */
    public function storeList($agent_id = 0,$is_show = 0)
    {
        $where['agent_id'] = $agent_id;
        $where['is_show'] = $is_show;
        $result = array();
        $list = Db::name('store')->where($where)->select();
        return $list;
    }
}