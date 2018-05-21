<?php
/**
 * 商品类型
 * Created by PhpStorm.
 * User: guihongbing
 * Date: 2017/12/15
 */

namespace app\api\controller;
use think\Controller;
use think\Session;
use app\common\controller\ApiBase;

class GoodsCategory extends ApiBase
{
    protected function _initialize()
    {
        parent::_initialize();
    }

    public function index(){

    }

    /**
     * 商品分类
     * @param $pid int 上级分类  默认为0
     * @param $agent_id int 分销商ID
     * @param $is_see int 是否限定会员查看 0不限定  1限定
     * @return array
     * @author guihongbing
     * @date 20171215
     */
    public function categoryList(){
        $pid = I('pid',0);
        $agent_id = get_agent_id();
        $is_see = I('is_see',0);
        //查询分类
        $res = D('GoodsCategory')->categoryList($pid,$agent_id,$is_see);
        $result = $res ? array('ret'=>0,'msg'=>'','data'=>$res) : array('ret'=>1017,'msg'=>'无分类数据');
        $this->ajaxReturn($result);
    }
}