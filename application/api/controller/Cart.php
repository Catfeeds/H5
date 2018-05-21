<?php
namespace app\api\controller;

use think\Controller;
use think\Session;
use app\common\controller\ApiBase;

/**
 * 购物车api
 * Class Cart
 * @package app\api\controller
 */
class Cart extends ApiBase
{
    protected function _initialize()
    {
        parent::_initialize();       
    }  
    
    /**
     * 用户购物车列表
     * @author fire
     * @date 20171212
     */
    public function index($uid=0){
        $uid=$this->checkUserLogin(); 
        $where['user_id'] = $uid;
        $list = D('cart')->getListJoinG($where);  
        $data=logic('Cart')->CartShow(convert_arr_key($list,'id'));
        $result = $list ? array('status'=>100,'msg'=>'成功','data'=>$data) : array('status'=>1016,'msg'=>'失败');
        //返回JSON数据
        $this->ajaxReturn($result);
    }

    /**
     * 添加商品到购物车
     * @author fire
     * @date 20171212
     */
    public function addToCart()
    {
        $uid=$this->checkUserLogin(); 
        //接收参数
        $post = I('post.');
        //购物类型 0表示加入购物  1表示立即购买
        $data['type']   = $post['type']?$post['type']:0;
        $data['goods_id'] = $post['goods_id'];
        $data['goods_num'] = $post['goods_num'];
        $data['spec_key'] = $post['spec_key']?$post['spec_key']:''; //规格
        $data['character_carving'] = $post['character_carving']?$post['character_carving']:''; //个性刻字
        $data['diamond_id'] = $post['diamond_id']?$post['diamond_id']:''; //钻配托提交ID

        $data['agent_id'] = get_agent_id();
        $data['user_id'] = $uid;
            
        $result = logic('Cart')->addCart($uid,$data);
        
        if($result['status'] == 100){
            echo json_encode(['status'=>100,'msg'=>'添加购物车成功','data'=>'']);
        }else{
            echo json_encode(['status'=>$result['status'],'msg'=>$result['msg'],'data'=>'']);
        }
    }
    
    /**
     * 添加商品到购物车,批量添加购物车
     * 临时处理，性能要调整，不常用
     * cart:商品数据 [{'goods_id':12,'goods_num':1}]  goods_id,num
     * @author fire
     * @date 20171212
     */
    public function addToCartMul()
    {               
        $uid=$this->checkUserLogin();
        $agent_id=get_agent_id();
        
        //接收参数
        $post = I('post.');
        $type=$post['type']?$post['type']:0;
        $cart=$post['cart'];
        
        foreach ($cart as $v){
            //购物类型 0表示加入购物  1表示立即购买
            $data['type']   = $type;
            $data['goods_id'] = $v['goods_id'];
            $data['goods_num'] = $v['goods_num'];
            $data['agent_id'] = $agent_id;
            $data['user_id'] = $uid;
            $result = logic('Cart')->addCart($uid,$data);
        }
        if($result['status'] == 100){
            echo json_encode(['status'=>100,'msg'=>'添加购物车成功','data'=>'']);
        }else{
            echo json_encode(['status'=>$result['status'],'msg'=>$result['msg'],'data'=>'']);
        }
    }
    
    
    /**
     * 修改购物车商品
     * @author fire
     * @date 20171212
     */
    public function saveCart(){
        $uid=$this->checkUserLogin();
        $post = I('post.');
        $datapost=json_decode(htmlspecialchars_decode($post['cart']),true);
        $result=[];
        foreach ($datapost as $v){
            $result[$v['id']]=$v;
        }
        $data['cart'] =$result; //要修改购物车二维数组 键为购物车ID 数据结构 [6=>['a'=>1,'b'=>2],7=>['a'=>1,'b'=>2]]
        $result = logic('Cart')->editCart($uid,$data);
        if($result['status'] == 100){
            $returndata = '';
            if (count($data['cart']) == 1) { //单个修改返回价格及总价 sxm
                $returndata = logic('Cart')->getoneprice($data['cart'],$uid); 
            }
            echo json_encode(['status'=>100,'msg'=>'提交成功','data'=>$returndata]);
        }else{
            echo json_encode(['status'=>$result['status'],'msg'=>$result['msg'],'data'=>'']);
        }
    }
    
    //全选购物车商品
    public function selectAll()
    {
        $uid=$this->checkUserLogin();
        $isselected = input('isselected');
        $result = logic('Cart')->changeAll($uid,$isselected);
        return json($result);
    }

    /**
     * 删除购物车商品
     * @author fire
     * @date 20171212
     */
    public function delCart(){
        $uid=$this->checkUserLogin();
        $post = I('post.');
        $id = explode(',',rtrim($post['id'],','));
        $result = logic('Cart')->delCart($uid,$id);
        if($result['status'] == 100){
            echo json_encode(['status'=>100,'msg'=>'提交成功','data'=>'']);
        }else{
            echo json_encode(['status'=>$result['status'],'msg'=>$result['msg'],'data'=>'']);
        }
    }

    /**
     * 下单通加入购物车
     * @author sxm
     * @date 20180417
     */
    public function xdtaddcart()
    {
        $uid=$this->checkUserLogin();
        $post = I('post.');
        $data = $post;
        $data['agent_id'] = get_agent_id();
        $result = logic('Cart')->addCartXdt($uid,$data);
        
        if($result['status'] == 100){
            return json(['status'=>100,'msg'=>'添加购物车成功','data'=>'']);
        }else{
            return json(['status'=>$result['status'],'msg'=>$result['msg'],'data'=>'']);
        }
    }
}