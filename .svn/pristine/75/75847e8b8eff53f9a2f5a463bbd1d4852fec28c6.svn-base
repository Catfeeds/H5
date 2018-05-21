<?php
namespace app\common\logic;

//zwx 数据处理
class DataProcessing
{

    public function __construct(){
    }

    //zwx转移 钻石列表页 搜索数据处理
    public function goods_diamond_attr_filter($goods_attr_filter=''){
        $params=[];
        if($goods_attr_filter){
            $arr=explode(";",$goods_attr_filter);
            foreach ($arr as $item){
                $kv=explode(":",$item);
                $params[$kv[0]]=$kv[1];
                $kvvarr=explode(",",$kv[1]);
                if(count($kvvarr)>1){
                    $params[$kv[0]]=$kvvarr;
                }               
            }
        }

        $param=[];
        $type = 0; //默认白钻
        $param['price'] = []; //默认价格筛选为空

        if(!empty($params)){
            $str = '';
            foreach ($params as $key=>$val){
                if(empty($key)) break;
                $key = strtolower($key);
                // $prefix = 'gd.';

                if($key=='type'){
                    $type = $val;
                    // $prefix = 'g.';
                }
                if($key=='supply_gtype'){
                    if(is_array($val)){
                        break;
                    }else{
                        // $prefix = 'g.';
                    }
                }
                if($key=='location'){
                    if(is_array($val)){
                        break;
                    }else{
                        if($val=='国内'){
                            $str.='location in("国内","深圳","香港") and ';
                            break;
                        }
                        if($val=='国外'){
                            $str.='location in("国外","印度") and ';
                            break;
                        }
                    }
                }

                if($val||$val==0){
                    if(is_array($val)){                               //区间
                        // $param[$key] =['in',$val];
                        $a_str='';
                        foreach ($val as $k => $v) {
                            $a_str.='"'.$v.'",';
                        }
                        $str.= $prefix.$key.' in('.trim($a_str,',').') and ';
                        // $str.= $key.' in('.implode(',', $val).') and ';
                    }else{  
                        if($key=='priceinterval'){
                            $arr=explode("-",$val);
                            $param['price'] = $arr;
                            // $param['price'] = [['>',$arr[0]],['<',$arr[1]],'and'];
                        }elseif($key=='weightinterval'){
                            $arr=explode("-",$val);
                            $str.= $prefix.'weight>='.$arr[0].' and '.$prefix.'weight<='.$arr[1].' and ';
                            // $param['weight']= [['>',$arr[0]],['<',$arr[1]],'and'];
                        }else{
                            // $param[$key] = $val;
                            $str.= $prefix.$key.'="'.$val.'" and ';
                        }                     
                    }
                }
            }
        }

        $param['where_str'] = trim($str,'and ');
        $param['type'] = $type;

        return $param;
    }

    //zwx 生成匹配钻石重量参数
    public function goods_Zpt($weight){
        $data['weight_egt'] = floor($weight*10)/10;
        $data['weight_lt'] = floor(($weight+0.1)*10)/10;
        return $data;
    }

    //zwx转移  成品定制 搜索字符串处理
    public function goods_attr_filter($goods_attr_filter){
        $arr=explode(";",$goods_attr_filter);
            foreach ($arr as $item){
                $kv=explode(":",$item);
                $params[$kv[0]]=$kv[1];                
        }

        $customWhere  = '';         //为传入自定义where条件段。
        $body         = '';
        $having       = '';
        if($params){
            foreach ($params as $key=>$val){
                if($key && $val){
                    $body .= ' (attr_id = '.$key.' and attr_value_id in('.rtrim($val,',').')) ';
                    $body .= 'or';
                    $having.= " cc REGEXP ',".$key.",' AND";
                }
            }
            if($body){
                $customWhere  = ' ( '.rtrim($body, 'or').' )';
                $having  = rtrim($having, 'AND');
                return ['where'=>$customWhere,'having'=>$having];
            }
        }
        return false;
    }

    //zwx 购物车 订单 返回显示数组
    public function spec_key_name_arr($v){
        $v['spec_key_name_arr'] = explode(',', $v['spec_key_name']);
        if($v['attr_main_json']){ //个性刻字
            $attr_main_json = json_decode($v['attr_main_json'],true);
            $attr_main_json['character_carving']?array_push($v['spec_key_name_arr'],'个性刻字：'.$attr_main_json['character_carving']):'';
        }
        return $v['spec_key_name_arr'];
    }

    //zwx 购物车获取总价
    public function cart_goods_price($cartList){
        if(!$cartList) return 0;
        foreach ($cartList as $k => $v) {
            $goods_price+=$v['member_goods_price']*$v['goods_num'];
        }
        return $goods_price;
    }

    //zwx 地址字符串处理
    public function user_address_area($list){
        if(!$list) return;

        foreach ($list as $k => $v) {
            $data = explode('-', $v['area']);
            $list[$k]['area'] = $data[0].$data[1].$data[2];
        }
        return $list;
    }

    //zwx 获取单个商品上架条件goods g goods_trader gt
    public function get_goods_product_status($agent_id,$prefix='g.'){
        $where = ' '.$prefix.'product_status = 1';
        //获取分销商信息
        $trader = get_agent_trader($agent_id);
        if($trader['t_agent_id']>0){ //二级分销商
            $where.= " and ".$prefix."agent_id in($agent_id,0,".$trader['t_agent_id'].")";
        }else{
            $where.= " and ".$prefix."agent_id in($agent_id,0)";
        }
        return $where;
    }

    //zwx 获取钻石商品上架条件
    public function get_goods_diamond_product_status($agent_id){
        $where = 'isdel = 0';
        //获取分销商信息
        $trader = get_agent_trader($agent_id);
        //获取是否开启分销
        $agent_config = get_agent_config();
        if($trader['t_agent_id']>0){ //二级分销商
            if($agent_config['istrader'] == 1){
                $where.= " and agent_id in($agent_id,0,".$trader['t_agent_id'].")";
            }else{
                $where.= " and agent_id in($agent_id,".$trader['t_agent_id'].")";
            }
        }else{
            if($agent_config['istrader'] == 1){
                $where.= " and agent_id in($agent_id,0)";
            }else{
                $where.= " and agent_id in($agent_id)";
            }
        }
        return $where;
    }

    //zwx 获取商品分销价格折扣加点
    public function get_trader_price($agent_id){
        $list = M('trader_price')->where(['agent_id'=>$agent_id])->select();
        return convert_arr_key($list,'goods_type');
    }

    //zwx 根据商品数组判断收藏
    public function get_check_goods_collection($list,$uid,$data=[]){
        if(!$list||!$uid) return $list;
        $isCollection = $data['isCollection']?$data['isCollection']:'isCollection';

        $list_k = convert_arr_key($list,'id');
          
        $where['uid'] = $uid;
        $where['goods_id'] = ['in',implode(',', array_keys($list_k))];

        $ugc_list = M("user_goods_collection")->where($where)->field("goods_id")->select();

        $ugc_list = convert_arr_key($ugc_list,'goods_id');
        foreach ($list as $k => $v) {
            $list[$k][$isCollection] = $ugc_list[$v['id']]?1:0;
            $list[$k]["isuid"] = 1;

        }
        return $list;
    }
}