<?php
namespace app\newapi\validate;

use think\Request;
use think\Validate;

/**
 * Class BaseValidate
 * 验证类的基类
 */
class BaseValidate extends Validate
{
	public function goCheck()
    {
        //必须设置contetn-type:application/json
        $request = Request::instance();
        $params = $request->param();

        if (!$this->check($params)) {
            return false;
        }
        
        return true;
    }

}