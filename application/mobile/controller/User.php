<?php
namespace app\mobile\controller;
use think\Db;
use function think\name;
use app\common\controller\MobileBase;

class User extends MobileBase
{     
    public function index()
    {                       
        return $this->fetch();   
    }
    /*订单详情*/
    public function orderDetail(){
        return $this->fetch();
    }
    
    //我的订单
    public function order(){
        return $this->fetch();
    }
    
    //我的收藏
    public function collection(){
        return $this->fetch();
    }
    
    //浏览记录
    public function goodsview(){
        return $this->fetch();
    }
    
    //重置密码
    public function passwordset(){
        return $this->fetch();
    }
    
    //地址管理
    public function address(){
        return $this->fetch();
    }
    
    
    //地址详情
    public function addressdetail(){
        return $this->fetch();
    }

    //新增地址
    public function addAddress(){
        return $this->fetch();
    }
    //编辑地址
    public function editAddress(){
        return $this->fetch();
    }
    //商品评价
    public function evaluate(){
        return $this->fetch();
    }

    //设置
    public function seting(){
        return $this->fetch();
    }

    //个人信息
    public function personal(){
        return $this->fetch();
    }

}
