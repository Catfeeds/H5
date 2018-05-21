<?php
/**
 * cookie存储
 */
namespace app\common\logic;

class Cookie
{
    public function __construct(){

    }

    //zwx 设置购物车 收藏cookie
    public function cartCollectionCount($user_id=''){
        $data['cart_count'] = 0;
        $data['collection_count'] = 0;

        if(!$user_id) return $data;

        if(!cookie('cart_count'.$user_id)&&$user_id){
            $cart_count = M('cart')->where(['user_id'=>$user_id])->count();
            cookie('cart_count'.$user_id,$cart_count);
            $data['cart_count'] = $cart_count;
        }

        if(!cookie('collection_count'.$user_id)&&$user_id){
            $collection_count = M('user_goods_collection')->where(['uid'=>$user_id])->count();
            cookie('collection_count'.$user_id,$collection_count);
        }
        
        $data['cart_count'] = cookie('cart_count'.$user_id);
        $data['collection_count'] = cookie('collection_count'.$user_id);
        return $data; 
    }
}