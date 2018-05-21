<?php
namespace app\mobile\controller;
use think\Db;
use function think\name;
use app\common\controller\MobileBase;

class Order extends MobileBase
{
    //订单
    public function index()
    {                       
        return $this->fetch();   
    }

    //确认订单
    public function confirm(){
        return $this->fetch();
    }
    
    //订单提交成功
    public function success(){
        return $this->fetch();
    }
    //支付成功后返回的页面
    public function wxpaysuccess(){
        return $this->fetch();
    }
}
