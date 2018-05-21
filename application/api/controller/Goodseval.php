<?php
namespace app\api\controller;
/**
 * 订单商品评价
 * Created by PhpStorm.
 * User: guihongbing
 * Date: 2017/12/15
 */

use think\Controller;
use think\Session;
use think\Request;
use app\common\controller\ApiBase;


class Goodseval extends ApiBase
{
    protected function _initialize()
    {
        parent::_initialize();
    }

    public function index(){
        $this->evalList();
    }

    /**
     * 订单商品评价列表
     * @param $agent_id int 分销商ID
     * @param $goods_id int 商品编号
     * @param $status int 是否显示 1显示   2不显示
     * @return array
     * @author guihongbing
     * @date 20171215
     */
    public function evalList()
    {
        $where['agent_id'] = get_agent_id();
        $where['goods_id'] = I('goods_id',0);
        $where['status'] = I('status',0);
        $page = I('status',0);
        $list_rows = I('list_rows',0);
        $res = D('OrderGoodsEval')->getEvalList($where,$page ,$list_rows);
        $result = ($res['total'] == 0) ? array('ret'=>1018,'msg'=>'无订单商品评价','data'=>'') :array('ret'=>0,'msg'=>'','data'=>$res);
        $this->ajaxReturn($result);

    }
    /**
     * 查询订单商品评价内容
     * @param $agent_id int 分销商ID
     * @param $goods_id int 商品编号
     * @param $order_id int 订单ID
     * @return $result array
     * @author guihongbing
     * @date 20171215
     */
    public function evalInfo()
    {
        $where['agent_id'] = get_agent_id();
        $where['order_goods_id'] = I('order_goods_id',0);
        $where['order_id'] = I('order_id',0);
        $res = D('OrderGoodsEval')->getEvalInfo($where);
        $result = empty($res) ? array('status'=>1019,'msg'=>'无订单商品评价内容','data'=>'') : array('status'=>100,'msg'=>'','data'=>$res);
        $this->ajaxReturn($result);
    }

    /**
     * 发布订单商品评价
     * @param $order_id int 订单编号
     * @param $goods_id int 商品编号
     * @param $goods_sku string 商品规格名称
     * @param score int 商品得分，5分制
     * @param $uid int 用户编号
     * @param $uname string 用户姓名
     * @param $images string 上传图片
     * @param $content string 内容
     * @param $create_ip int 用户IP地址
     * @return $result array
     * @author guihongbing
     * @date 20171215
     */
    public function addEval(){
        $result = array('ret'=>1020,'msg'=>'发布订单商品评价请求错误','data'=>'');
        //判断POST请求类型
        if(Request::instance()->isPost()){
            $param['order_id'] = Request::instance()->post('order_id');
            $param['goods_id'] = Request::instance()->post('goods_id');
            $param['goods_sku'] = Request::instance()->post('goods_sku');
            $param['score'] = Request::instance()->post('score');
            $param['uid'] = Request::instance()->post('uid');
            $param['uname'] = Request::instance()->post('uname');
            $param['images'] = Request::instance()->post('images');
            $param['content'] = Request::instance()->post('content');
            $param['create_ip'] = Request::instance()->ip();
            $res = D('OrderGoodsEval')->insertEval($param);
            $result = empty($res) ? array('ret'=>1021,'msg'=>'发布订单商品评价失败','data'=>'') : array('ret'=>0,'msg'=>'','data'=>$res);
        }
        $this->ajaxReturn($result);
    }
}