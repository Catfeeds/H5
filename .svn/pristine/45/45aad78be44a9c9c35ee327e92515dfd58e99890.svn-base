<?php
namespace app\common\model;

use think\Model;

class PaymentMode extends Model
{
	protected $insert = ['create_time'];   
    
    
    /**
     * 创建时间
     * @return bool|string
     */
    protected function setCreateTimeAttr()
    {
        return date('Y-m-d H:i:s');
    }
 
 	/**
	 * 获取支付方式列表	
	 * auth	：zengmm
	 * @param：$param 筛选参数
	 * time	：2017-11-22
	**/
	public function getList($param=array())
	{
		$list = db('payment_mode')->where($param)->select();
		if($list){
			return $list;
		}else{
			return false;
		}
	}
	
    
	
	
	
}	