<?php
namespace app\mobile\controller;
use think\Db;
use function think\name;
use app\common\controller\MobileBase;

class Cart extends MobileBase
{    

    public function index()
    {                       
        return $this->fetch();
    }

}
