<?php

namespace app\mobile\controller;

use think\Db;
use function think\name;
use app\common\controller\MobileBase;

class Goods extends MobileBase
{


    //分类
    public function index()
    {
        return $this->fetch();
    }

    //珠宝列表
    public function goodslist()
    {
//        $this->assign("id", $_GET['goodsid']);
        return $this->fetch();
    }

    //商品详情
    public function details()
    {
        // return $this->fetch();
        return $this->fetch('detail_test');
    }

    //商品详情(下单通)
    public function xdtdetails()
    {
        // return $this->fetch();
        return $this->fetch('xdtdetails');
    }
    //商品详情内的详情页
    public function details_page()
    {
        return $this->fetch();
    }

    //商品详情内的评价
    public function evaluation()
    {
        return $this->fetch();
    }


    //钻石分类
    public function diamond()
    {
        return $this->fetch('diamond');
        // return $this->fetch('diamond_test');
    }


    //首页搜索后列表
    public function search()
    {
        return $this->fetch();
    }
    public function searchgoodslist()
    {
        return $this->fetch();
    }
    //配戒托页面
    public function custom()
    {
        return $this->fetch();
    }
    //钻石详情页面
    public function diamondinfo()
    {
        return $this->fetch();
    }
    

}
