<?php
namespace app\common\model;

use think\Model;

class Region extends Model
{    
    //获取一个级别的地区
    public function getListByPid($pid){        
        return $this->where("pid",$pid)->select();    
    }
}