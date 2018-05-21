<?php

//二位数组去重
function er_array_unique($arr){
  $newarr = array();
  if(is_array($arr)){
    foreach($arr as $v){
      if(!in_array($v,$newarr,true)){
        $newarr[] = $v;
      }
    }
  }else{
     return false;
  }
  return $newarr;
}

//钻石匹配区间范围
function goods_Zpt($weight){
  $data['weight_egt'] = floor($weight*10)/10;
  $data['weight_lt'] = floor(($weight+0.1)*10)/10;
  return $data;
}