<?php
namespace app\api\controller;

use think\Controller;
use think\Session;
use app\common\controller\ApiBase;

/**
 * 用户api
 * Class User
 * @package app\api\controller
 */
class User extends ApiBase
{
    protected function _initialize()
    {
        parent::_initialize();       
    }  
    
    /**
     *读取用户详细信息
     *return:100,用户信息
     */
    public function getuserinfo(){
        $data=session("user");//H5可以存储session
        if(empty($data['id'])){
            $uid= $this->checkUserLogin();//检查是否已经登录;
            $data=model("User")->getUserInfoById($uid);
        }
        $this->ajaxFormat($data);
    }
    
    
    /**
     * 用户订单列表    
     * @return array
     * @author guihongbing
     * @date 20171213
     */
    public function getOrderList($page=1,$pagesize=20,$orderby="id desc")
    {
        $uid=$this->checkUserLogin();//检查是否已经登录    
        $where['user_id'] = $uid;
        $where['agent_id'] = get_agent_id();
        //订单过期处理 同PC端保持一直
        logic('Order')->BeOverdue($uid);
        //订单状态显示
        $order_status_a = ConditionShow('','order','order_status_a');
        $order_status = I('order_status')?I('order_status'):'0';
        if($order_status_a[$order_status] != NULL){
            $where['order_status'] = ['in',$order_status_a[$order_status]];
            $where_url['order_status'] = $order_status;
        }
        //查询
        $list = model('order')->getUserOrderList($where,$page,$pagesize);
        if($order_status_a[$order_status] == 5 && $list) {
            $list['goods'] = [];
            foreach($list['data'] as $val) {
                foreach ($val['sub_order_goods'] as $k => $v) {
                    $list['goods'][] = $v;
                }
            }
        }
        //订单状态
        $list['order_status_arr'] = $order_status_a;
        //判断订单数据
        $result = empty($list['total']) ? array('status'=>1014,'msg'=>'无订单数据','data'=>'') : array('status'=>100,'msg'=>'','data'=>$list);
        //返回JSON数据
        $this->ajaxReturn($result);
    }
    
    /**
     * 用户商品浏览记录
     * @return array
     * @author fire
     * @date 20171213
     */
    public function getGoodsViewList($page=1,$pagesize=20){
        $uid=$this->checkUserLogin();//检查是否已经登录
        $where['uid'] = $uid;        
        $list=model("User")->getUserGoodsViewList(null,$page,$pagesize);
        if(empty($list)){
            $this->ajaxReturn(array('status'=>1009,'msg'=>'无浏览记录'));
        }
        $this->ajaxReturn(array('status'=>100,'msg'=>'成功','data'=>$list));
    }
    
    /**
     * 用户商品收藏记录
     * @return array
     * @author fire
     * @date 20171213
     */
    public function getUserGoodsCollectionList($page=1,$pagesize=50){
        $uid=$this->checkUserLogin();//检查是否已经登录
        $where['uid'] = $uid;
        $list=model("User")->getUserGoodsCollectionList($where,$page,$pagesize);
        if(empty($list)){
            $this->ajaxReturn(array('status'=>1009,'msg'=>'无浏览记录'));
        }
        $this->ajaxReturn(array('status'=>100,'msg'=>'成功','data'=>$list));
    }
    
    
    /**
     * 查询用户收货地址
     * @return array
     * @author guihongbing
     * @date 20171211
     */
    public function getAddress(){
        //用户ID
        $param['uid'] = $this->checkUserLogin();//检查是否已经登录;
        //分销商ID
        $param['agent_id'] = get_agent_id();
        //查询用户收货地址
        $list = D('User')->getAddressList($param);
        if(empty($list)){
            $this->ajaxReturn(array('status'=>1009,'msg'=>'无收货地址'));
        }
        $this->ajaxReturn(array('status'=>100,'msg'=>'','data'=>$list));
    }

    //zwx 三级联动获取地址 ajax
    public function getRegion($type=0,$pid=0){
        if($type){
            $map['type']=$type;
        }
        if($pid){
            $map['pid']=$pid;
        }        

        $data = D('user')->getRegionList($map);
        $this->ajaxReturn($data);
    }

    /**
     * 添加用户收货地址   
     * @param $title int 标注
     * @param $country int 国家id
     * @param $city int 城市id
     * @param $district int 区县id
     * @param $town int 城镇
     * @param $address string 详细地址信息
     * @param $zipcode string 邮政编码
     * @param $recname string 收货人姓名
     * @param $phone string 手机
     * @param $is_default 是否默认  1是   0否
     * @return array
     * @author guihongbing
     * @date 20171211
     */
    public function addAddress(){
        $uid= $this->checkUserLogin();//检查是否已经登录;
        
        $post = I('post.');
        $param['uid'] = $uid;
        $param['title'] = $post['title'];
        $param['country'] = $post['country'] ? $post['country'] : 0;
        $param['province'] = $post['province'];
        $param['city'] = $post['city'];
        $param['district'] = $post['district'];
        $param['town'] = $post['town'];
        $param['area'] = $post['area'];
        $param['address'] = $post['address'];
        $param['zipcode'] = $post['zipcode'] ? $post['zipcode'] : '';
        $param['recname'] = $post['recname'];
        $param['phone'] = $post['phone'];
        $param['is_default'] = $post['is_default'] ? $post['is_default'] : 0;
        $param['agent_id'] = get_agent_id();
        $result = D('User')->addAddress($param);        
        
        if(empty($result)){
            $this->ajaxReturn(array('status'=>1011,'msg'=>'添加收货地址失败'));
        }
        $this->ajaxReturn(array('status'=>100,'msg'=>'','data'=>$result));
    }

    /**
     * 更新用户收货地址
     * @param $uid int 用户ID
     * @param $title int 标注
     * @param $country int 国家id
     * @param $city int 城市id
     * @param $district int 区县id
     * @param $town int 城镇
     * @param $address string 详细地址信息
     * @param $zipcode string 邮政编码
     * @param $recname string 收货人姓名
     * @param $phone string 手机
     * @param $is_default 是否默认  1是   0否
     * @return array
     * @author guihongbing
     * @date 20171211
     */
    public function updateAddress($id){
        $uid= $this->checkUserLogin();//检查是否已经登录;
        $param['uid'] =$uid;
        $post = I('post.');
        $param['id'] = $post['id'];
        if($post['title']){
            $param['title'] = $post['title'];
        }
       
        if($post['country']){
            $param['country'] = $post['country'];
        }
        if($post['province']){
            $param['province'] = $post['province'];
        }
        if($post['city']){
            $param['city'] = $post['city'];
        }
        if($post['district']){
            $param['district'] = $post['district'];
        }
        if($post['town']){
            $param['town'] = $post['town'];
        }
        if($post['area']){
            $param['area'] = $post['area'];
        }
        if($post['address']){
            $param['address'] = $post['address'];
        }
        if($post['zipcode']){
            $param['zipcode'] = $post['zipcode'];
        }
        if($post['recname']){
            $param['recname'] = $post['recname'];
        }
        if($post['phone']){
            $param['phone'] = $post['phone'];
        }
        if($post['is_default']===0||$post['is_default']>0){
           $param['is_default'] = $post['is_default'];
        }
        
        $param['agent_id'] = get_agent_id();
        
        $result = D('User')->saveAddress($param);
        if(empty($result)){
            $this->ajaxReturn(array('status'=>1011,'msg'=>'更新收货地址失败'));
        }
        $this->ajaxReturn(array('status'=>100,'msg'=>'','data'=>$result));
    }

    /**
     * 删除用户收货地址
     * @param $id int 收货地址ID
     * @return array
     * @author guihongbing
     * @date 20171211
     */
    public function delAddress($id){
        $uid= $this->checkUserLogin();//检查是否已经登录;        
     
        if(empty($id)){
            $this->ajaxReturn(array('status'=>1013,'msg'=>'删除收货地址操作错误'));
        }
        $result = D('User')->delAddress($id);
        if(empty($result)){
            $this->ajaxReturn(array('status'=>1012,'msg'=>'删除收货地址失败'));
        }
        $this->ajaxReturn(array('status'=>100,'msg'=>'','data'=>$result));
    }

    /**
     * 用户收藏商品
     * @date 20180301
     */

    public function addusergoodscollection()
    {
        $uid= $this->checkUserLogin();//检查是否已经登录;    
        $post = I('post.');
        $data['goods_id'] = $post['goods_id']; //[1,2,3] or 1
        $data['agent_id'] = get_agent_id();

        $result = logic('User')->userGoodsCollectionAdd($uid,$data);
        
        if($result['status'] == 100){
            echo json_encode(['status'=>100,'msg'=>'收藏成功','data'=>'']);
        }else{
            echo json_encode(['status'=>$result['status'],'msg'=>$result['msg'],'data'=>'']);
        }
    }
    /**
     * 用户取消收藏商品
     * @date 20180301
     */

    public function delusergoodscollection(){
        $uid= $this->checkUserLogin();//检查是否已经登录; 
        $post = I('post.');
        $result = logic('User')->userGoodsCollectionDel($uid,$post['goods_id']);
        if($result['status'] == 100){
            echo json_encode(['status'=>100,'msg'=>'取消收藏成功','data'=>'']);
        }else{
            echo json_encode(['status'=>$result['status'],'msg'=>$result['msg'],'data'=>'']);
        }
    }
}