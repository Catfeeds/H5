<?php
/**
 * 订单商品评价
 * Created by PhpStorm.
 * User: guihongbing
 * Date: 2017/12/15
 */

namespace app\common\model;
use think\Model;

class OrderGoodsEval extends Model
{
    protected $insert = ['create_time'];

    /**
     * 查询订单商品评价
     * @param $where array 分销商ID,商品编号,是否显示
     * @param $page int  当前页数
     * @param $pagesize  每月显示页数
     * @param $order   排序
     * return $array   数组
     * @author guihongbing
     * @date 20171215
     */
    public function getEvalList($where = '',$page = 1,$pagesize = 20, $order='create_time desc')
    {
        if(is_array($where)){
            return '';
        }
        $list = $this->field('*')->where($where)->order($order)->page($page,$pagesize)->select();
        $list['page'] = $page;
        $list['pagesize'] = $pagesize;
        $list['total'] = $this->getEvalNum($where);
        return $list;
    }

    /**
     * 查询订单商品个数
     * @param $where array 分销商ID,商品编号,是否显示
     * @return $num int 订单个数
     * @author guihongbing
     * @date 20171215
     */
    public function getEvalNum($where)
    {
        if(is_array($where)){
            return '';
        }
        $num = $this->where($where)->count();
        return $num;
    }

    /**
     * 查询订单商品评价内容
     * @param $where array 分销商ID,商品编号,是否显示
     * @return $result array
     * @author guihongbing
     * @date 20171215
     */
    public function getEvalInfo($where)
    {
        if(!is_array($where)){
            return '';
        }
        $info = $this->where($where)->find();
        return $info;
    }

    /**
     * 新增订单商品评价内容
     * @param $param array 评价内容
     * @return $result int 评价自增主键
     * @author guihongbing
     * @date 20171215
     */
    public function insertEval($param)
    {
        if(is_array($param)){
            return '';
        }
        //添加评价
        $result = $this->insertGetId($param);
        return $result;
    }

}