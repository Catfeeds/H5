<?php
namespace app\common\taglib;
use think\template\TagLib;
class Web extends TagLib{
    /**
     * 定义标签列表
     */
    protected $tags   =  [   
        'goods' => ['attr' => 'cat,num,key,id,cache'],
    ];
    
    //商品数据
    public function tagGoods($tag,$content){        
        $cat   = $tag['cat'];//分类或类型，如hot,new       
        $id     = isset($tag['id'])?$tag['id']:'vo';
        $num    = isset($tag['num'])?(int)$tag['num']:5;
        $cache  = isset($tag['cache'])?$tag['cache']:0;
        $key    = isset($tag['key'])?$tag['key']:'key'; 
        
        $agent_id=get_agent_id(); 
        $parse = '<?php ';
        $parse .= '$__GOOD'.$agent_id.'LIST__ = logic("Goods")->getGoodslistByCat("'.$cat.'",'.$num.','.$agent_id.');';
        $parse .= '?>';
        $parse .= '{volist name="__GOOD'.$agent_id.'LIST__" id="' . $id . '"}';
        $parse .= $content;
        $parse .= '{/volist}';
        return $parse;
    }    
}