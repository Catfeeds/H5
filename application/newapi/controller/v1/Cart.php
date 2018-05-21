<?php
namespace app\newapi\controller\v1;

use app\newapi\controller\Base;
use think\Exception;
use think\Db;

class Cart extends Base
{
	protected function _initialize()
    {
        parent::_initialize();       
    }  
	//购物车列表
	public function index()
	{
		// $uid = $this->checkUserLogin();
		$result = ['status'=>100,'msg'=>'success','data'=>''];
		return json($result);
	}
}