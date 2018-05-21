<?php
/**
 * 门店信息
 * Created by PhpStorm.
 * User: guihongbing
 * Date: 2017/12/19
 */

namespace app\api\controller;
use think\Controller;

use app\common\controller\ApiBase;

class Store extends ApiBase
{
    protected function _initialize()
    {
        parent::_initialize();
    }
    
    /**
     * 门店列表   
     * @param $is_show int 0：不显示 1：显示
     * @return $result array  门店信息内容
     * @author guihongbing
     * @date 20171219
     */
    public function index()
    {
        $agent_id = get_agent_id();
        $is_show = I('is_show',1);
        $res =model('Store')->storeList($agent_id,$is_show);
        $result = $res ? array('status'=>100,'msg'=>'','data'=>$res) : array('ret'=>1023,'msg'=>'无门店信息');
        $this->ajaxReturn($result);
    }
    
     /**
     * 门店详情
     * @param $id 门店编号
     * @return $result array  门店信息内容
     * @author wxh
     * @date 20180104
     */
    public function detail($id)
    { 
        $agent_id=get_agent_id(); 
        $res=model('Store')->find($id);      
        $result = $res ? array('status'=>100,'msg'=>'','data'=>$res) : array('ret'=>1023,'msg'=>'无门店信息');
        $this->ajaxReturn($result);
    }     

    /**
     * 根据省份查询店铺
     * @param $pid 省份编号
     * @return $result array  门店信息内容
     * @author wxh
     * @date 20180104
     */
    public function getstorebyprovince($pid){
        $agent_id=get_agent_id();
        $list=model("Store")
        ->where("agent_id",$agent_id)
        ->where("province",$pid)
        ->field("id,name")
        ->select();        
        $this->ajaxReturn(["status"=>100,"msg"=>"成功","data"=>$list]);
    }    

    /**
     * 门店预约
     * @param $id 门店编号
     * @return $result bool 状态
     * @author wxh
     * @date 20180104
     */    
    public function booked(){
        $data['store_id']=I('id');
        if (!I('id')) {return json(['status'=>101,$result['msg']='id必填']);}
        $data['name']=I('name');
        $data['phone']=I('phone');
        $data['sex']=I('sex') ? I('sex') : 1 ;
        $data['time']=time();
        $data['content']=I('content') ? I('content') : '';
        $agent_id=get_agent_id();
        $bookedid=model('Store')->booked($agent_id,$data);
        if($bookedid){
            $result['status']=100;
            $result['msg']='预约成功';
        }else{
            $result['status']=101;
            $result['msg']='网络繁忙，请稍后重试';
        }
        $this->ajaxReturn($result);
    }

}