<?php
namespace app\common\logic;

//zwx 调用官网钻明接口 http://btbzb_local.com/doc.html 内网api地址
class Openzm
{
    public $url;
    public $msg;

    public function __construct(){
        $this->url = C('OPENZM_URL');
        $this->msg = [
            '100200'=>'请求成功',
            '100400'=>'请求失败',
            '100401'=>'无效的签名',
            '100403'=>'请求数据不可用',
            '100405'=>'请求的方法不存在',
            '100406'=>'请求参数错误',
            '100504'=>'请求超时'
        ];   
    }
    
    //zwx 获取下单通价格
    public function get_customize_calculatePrice($goods_id,$material_id,$luozuan_ids,$matched_ids=0){
        $data['goods_id'] = $goods_id;
        $data['material_id'] = $material_id;
        $data['luozuan_ids'] = $luozuan_ids;
        $matched_ids!=0?$data['matched_ids'] = $matched_ids:'';
        return $this->get_data($this->url.'/customize/calculatePrice',$data);
    }

    //zwx 获取下单通在线配石
    public function get_customize_matchOnline($weight){
        return $this->get_data($this->url.'/customize/matchOnline',['weight'=>$weight]);
    }

    //zwx 获取下单通产品详情
    public function get_customize_detail($id){
        return $this->get_data($this->url.'/customize/detail',['id'=>$id]);
    }

    //zwx 获取订单列表
    public function get_order($agent_id){
        $supply_user = $this->get_supply_user($agent_id);
        return $this->get_data($this->url.'/order',['uid'=>$supply_user[2]['user_id'],'type'=>2]);
    }

    //zwx 获取订单详情
    public function get_order_detail($order_id,$agent_id){
        $supply_user = $this->get_supply_user($agent_id);
        return $this->get_data($this->url.'/order/detail',['uid'=>$supply_user[2]['user_id'],'type'=>2,'order_id'=>$order_id]);
    }

    //zwx 获取裸钻列表
    public function get_diamond(){
        return $this->get_data($this->url.'/diamond');
    }

    //zwx 查询单个钻石信息
    public function get_diamond_detail($id){
        return $this->get_data($this->url.'/diamond/detail',['id'=>$id]);
    }

    //zwx 获取分销商对应官网uid supply_id 1 珠宝 2 钻石
    public function get_supply_user($agent_id){
        $list = M('supply_user')->where(['agent_id'=>$agent_id])->select();
        return convert_arr_key($list,'supply_id');
    }

    //zwx 获取系统配置信息 dollar_huilv
    public function config_getConfig(){
        return $this->get_data($this->url.'/config/getConfig');
    }

    //zwx 添加钻石订单 分销商ID,tid 用户ID,uid 裸钻ID,gid 订单类型(1: 普通钻石 2: 速订购),type
    public function order_createDiamondOrder($data,$agent_id){
        $supply_user = $this->get_supply_user($agent_id);
        $data['uid'] = $supply_user[2]['user_id'];
        return $this->get_data($this->url.'/order/createDiamondOrder',$data);
    }

    //zwx 添加下单通订单 $data['data'=>json,'type'=>1]  $data['uid']
    public function order_createCustomizeOrder($data,$agent_id){
        $supply_user = $this->get_supply_user($agent_id);
        $data['uid'] = $supply_user[$data['type']]['user_id'];
        return $this->get_data($this->url.'/order/createCustomizeOrder',$data);
    }

    //zwx 更新订单超时时间 分销商ID,tid 用户ID,uid 订单ID,order_id delay,支付延长时间(单位:秒)
    public function order_modifyDiamondOrder($data,$agent_id){
        $supply_user = $this->get_supply_user($agent_id);
        $data['uid'] = $supply_user[$data['type']]['user_id'];
        return $this->get_data($this->url.'/order/modifyDiamondOrder',$data);
    }

    //zwx 获取数据 $url http://openzm.btbzb_local.com/diamond/?
    public function get_data($url,$data=[]){
        $data["timestamp"] = time();
        $data["sign"] = signature($data);
        $data["client_id"] = config('ZMFXWEB.client_id');
        $param['data'] = $data;
        $param['url'] = $url;
        $return = $this->_httpsRequest($param);
        // $return = json_decode(httpRequest($url.'?'.http_build_query($data)),true);
        return $return;
    }

    public function _httpsRequest($param){
        $url = $param['url'];
        $data = $param['data'] ? $param['data'] : '';
        $not_json = $param['not_json'] ? 1 : 0;
        $is_get = $param['is_get'] ? 1 : 0;
        $data = http_build_query($data);
        if($is_get){
            $url .= '?'.$data;
        }
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($data) && !$is_get){
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        $output = curl_exec($curl);
        curl_close($curl);
        if($not_json==1){
            return $output;
        }

        return json_decode($output,true);

    }
}