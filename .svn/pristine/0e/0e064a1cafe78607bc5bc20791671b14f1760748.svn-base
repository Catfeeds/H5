<?php
namespace app\common\logic;

//zwx 价格计算
class PriceCalculation
{
    public $agent_info;
    public function __construct(){
        $this->agent_info = get_agent_info();
        //dollar_huilv 美元汇率

    }
    
    // 珠宝价格计算
    public Static function getGoodsPrice($data)
    {
        if ($data['agent_id']) {
            return number_format($data['price'] * $data['point_my'] / 10000, 2);
        } else {
            return number_format($data['price'] * $data['point'] / 10000, 2);
        }
    }
    // 定制价格计算
    public Static function getCustomPrice($data)
    {
        if ($data['agent_id']) {
            return number_format($data['price'] * $data['point_my'] / 10000, 2);
        } else {
            return number_format($data['price'] * $data['point'] / 10000, 2);
        }
    }
    // 定制价格计算
    public Static function getXdtPrice($data)
    {
        if ($data['agent_id']) {
            return number_format($data['price'] * $data['point_my'] / 10000, 2);
        } else {
            return number_format($data['price'] * $data['point'] / 10000, 2);
        }
    }
    
    // 钻石价格计算,非钻明要求二级分销已经读取了上级的汇率,$zb_doller_huilv本级汇率，$zb_t_doller_huilv上级汇率
    public Static function getDiamondPrice($v,$agent_id,$zm_doller_huilv=null,$zb_doller_huilv=null,$zb_t_doller_huilv=null)
    {
        if ($v['agent_id'] == $agent_id) { // 1自己的钻石
            if(empty($zb_doller_huilv)){
                $zb_doller_huilv=$v['dollar_huilv'];
            }
            return number_format($v['global_price'] * $v['weight'] * $zb_doller_huilv * ($v['dia_discount']) / 100, 2);
        } elseif ($v['agent_id'] > 0) { // 2非钻明分销,在sql中处理上级汇率
            if(empty($zb_t_doller_huilv)){
                $zb_t_doller_huilv=$v['dollar_huilv'];
            }
            return number_format($v['global_price'] * $v['weight'] *$zb_t_doller_huilv* ($v['dia_discount'] + $v['point_my']) / 100, 2);
        } else { // 分销钻明官网钻石
            // 检查官网汇率，正常是要有值,如果没值取自己的汇率
            if (empty($zm_doller_huilv)) {
                if (empty($v['usd_exchange_rate'])) {
                    $zm_doller_huilv = $v['dollar_huilv'];
                } else {
                    $zm_doller_huilv = $v['usd_exchange_rate'];
                }
            }
    
            if (empty($v['type']) && $v['supply_gtype'] == 1) { // 3速订购
                return number_format($v['global_price'] * $v['weight'] * $zm_doller_huilv * ($v['dia_discount'] + $v['point_sdg']) / 100, 2);
            } else { // 4其他的钻明分销商品
                return number_format($v['global_price'] * $v['weight'] * $zm_doller_huilv * ($v['dia_discount'] + $v['point']) / 100, 2);
            }
        }
    }
    
    

    //商品销售价格计算
    public function goods_price($list,$data=[]){
        if(!$list) return $list;
        $g_agent_id = isset($data['agent_id'])?$data['agent_id']:'agent_id';//商品所属分销商
        $price = isset($data['price'])?$data['price']:'price';//要计算的价格字段
        $type = isset($data['type'])?$data['type']:'type';//商品类型
        $global_price = isset($data['global_price'])?$data['global_price']:'global_price';//国际报价
        $weight = isset($data['weight'])?$data['weight']:'weight';//重量
        $dia_discount = isset($data['dia_discount'])?$data['dia_discount']:'dia_discount';//dia_discount 折扣 
        $supply_goods_id = isset($data['supply_goods_id'])?$data['supply_goods_id']:'supply_goods_id';//supply_goods_id 条件 
        $supply_goods_type = isset($data['supply_goods_type'])?$data['supply_goods_type']:'supply_goods_type';//supply_goods_type 0普通 1速订购 

        // 1.1 白钻计算公式： 白钻价格 = 国际报价*钻重*汇率*折扣；
        // 例如：国际报价为2000，钻重为0.5，汇率为7，折扣为72
        // 销售价 = 2000*0.5*7*[（72 /100）]

        // 1.2 彩钻价格 = 国际报价*钻重*汇率*折扣
        // 例如：国际报价为2000，钻重为0.5，汇率为7，折扣为72
        // 销售价 = 2000*0.5*7*[（72 /100）]

        //钻明汇率
        $zm_dollar_huilv = 0; 
        //上级分销商汇率
        $t_dollar_huilv = 0;
        $agent_id = get_agent_id();

        $trader = []; //分销商信息
        $trader_price_list = []; //自己的加点信息
        $t_trader_price_list = []; //上级的加点信息

        //分销商信息
        $trader = M('trader')->where(['agent_id'=>$agent_id])->find();
        //自己的加点信息
        $trader_price_list = logic('DataProcessing')->get_trader_price($agent_id);
        //速订购销售加点，只有白钻，一级分销商
        $jiadian_zm_sdg = $trader_price_list[0]['point_config'];
        //上级加点信息
        if($trader['t_agent_id']>0){
            $t_trader_price_list = logic('DataProcessing')->get_trader_price($trader['t_agent_id']);
            //二级分销商速订购加点
            $jiadian_zm_sdg = $trader_price_list[0]['point_config']+$trader_price_list[0]['rate_config']+$t_trader_price_list['point_config'];
        }
        //上级分销商汇率
        if($t_dollar_huilv == 0){
            $t_dollar_huilv = get_agent_info($trader['t_agent_id'])['dollar_huilv'];
        }

        foreach ($list as $k => $v) {
            //暂时只做一级分销商加点 
            if(!$v) return ;

            //戒托计算价格 销售加点进行
            if($v[$type] == 3 ||$v[$type] == 4){
                
                if($v[$supply_goods_id]>0){ //官网戒托
                    $list[$k][$price] = formatRound($v[$price]*$trader_price_list[$v[$type]]['point']/10000);
                }elseif($v[$g_agent_id]>0&&$v[$g_agent_id] != $agent_id){ //上级分销商的商品
                    $list[$k][$price] = formatRound($v[$price]*$trader_price_list[$v[$type]]['point_my']/10000);
                }
            }

            //白钻彩砖计算价格 销售加折扣计算
            if($v[$type] == 0 ||$v[$type] == 1){ 
                if($v[$supply_goods_id]>0){ //官网钻石
                    if($zm_dollar_huilv == 0){ //获取汇率
                        $Openzm = logic('Openzm')->config_getConfig();
                        if($Openzm['code']==100200){
                            $zm_dollar_huilv = $Openzm['data']['dollar_huilv'];
                        }else{
                            return false;
                        }
                    }
                    //如果是速订购钻石
                    if($v[$supply_goods_type] == 1){
                        $list[$k][$price] = formatRound($v[$global_price]*$v[$weight]*$zm_dollar_huilv*($v[$dia_discount]+$jiadian_zm_sdg)/100);
                    }else{
                        $list[$k][$price] = formatRound($v[$global_price]*$v[$weight]*$zm_dollar_huilv*($v[$dia_discount]+$trader_price_list[$v[$type]]['point'])/100);
                    }
                }else if($v[$g_agent_id] == $agent_id){ //自己的钻石商品
                    $list[$k][$price] = formatRound($v[$global_price]*$v[$weight]*$this->agent_info['dollar_huilv']*($v[$dia_discount])/100);
                }else if($v[$g_agent_id]>0&&$v[$g_agent_id] != $agent_id){ //上级分销商钻石商品
                    $list[$k][$price] = formatRound($v[$global_price]*$v[$weight]*$t_dollar_huilv*($v[$dia_discount]+$trader_price_list[$v[$type]]['point_my'])/100);
                }
            }
        }
        return $list;
    }

    //商品采购价格计算
    public function goods_purchase_price($list,$data=[]){
        if(!$list) return $list;
        $g_agent_id = isset($data['agent_id'])?$data['agent_id']:'agent_id';//商品所属分销商
        $price = isset($data['price'])?$data['price']:'price';//要计算的价格字段
        $type = isset($data['type'])?$data['type']:'type';//商品类型
        $global_price = isset($data['global_price'])?$data['global_price']:'global_price';//国际报价
        $weight = isset($data['weight'])?$data['weight']:'weight';//重量
        $dia_discount = isset($data['dia_discount'])?$data['dia_discount']:'dia_discount';//dia_discount 折扣 
        $supply_goods_id = isset($data['supply_goods_id'])?$data['supply_goods_id']:'supply_goods_id';//supply_goods_id 条件 
        $supply_goods_type = isset($data['supply_goods_type'])?$data['supply_goods_type']:'supply_goods_type';//supply_goods_type 0普通 1速订购 

        // 1.1 白钻计算公式： 白钻价格 = 国际报价*钻重*汇率*折扣；
        // 例如：国际报价为2000，钻重为0.5，汇率为7，折扣为72
        // 销售价 = 2000*0.5*7*[（72 /100）]

        // 1.2 彩钻价格 = 国际报价*钻重*汇率*折扣
        // 例如：国际报价为2000，钻重为0.5，汇率为7，折扣为72
        // 销售价 = 2000*0.5*7*[（72 /100）]

        //钻明汇率
        $zm_dollar_huilv = 0; 
        //上级分销商汇率
        $t_dollar_huilv = 0;
        $agent_id = get_agent_id();

        $trader = []; //分销商信息
        $trader_price_list = []; //自己的加点信息
        $t_trader_price_list = []; //上级的加点信息
        
        //分销商信息
        $trader = M('trader')->where(['agent_id'=>$agent_id])->find();
        //自己的加点信息
        $trader_price_list = logic('DataProcessing')->get_trader_price($agent_id);
        //速订购采购加点，只有白钻，一级分销商
        $jiadian_zm_sdg = 0;
        //上级加点信息
        if($trader['t_agent_id']>0){ 
            $t_trader_price_list = logic('DataProcessing')->get_trader_price($trader['t_agent_id']);
            //二级分销商速订购采购加点，只有白钻
            $jiadian_zm_sdg = $trader_price_list[0]['rate_config']+$t_trader_price_list[0]['point_config'];
        }
        //上级分销商汇率
        if($t_dollar_huilv == 0){
            $t_dollar_huilv = get_agent_info($trader['t_agent_id'])['dollar_huilv'];
        }

        foreach ($list as $k => $v) {
            //暂时只做一级分销商加点 
            if(!$v) return ;

            //戒托计算价格 销售加点进行
            if($v[$type] == 3 ||$v[$type] == 4){
                
                if($v[$supply_goods_id]>0){ //官网戒托
                    $list[$k][$price] = formatRound($v[$price]*$trader_price_list[$v[$type]]['rate']/10000);
                }elseif($v[$g_agent_id]>0&&$v[$g_agent_id] != $agent_id){ //上级分销商的商品
                    $list[$k][$price] = formatRound($v[$price]*$trader_price_list[$v[$type]]['rate_my']/10000);
                }
            }

            //白钻彩砖计算价格 销售加折扣计算
            if($v[$type] == 0 ||$v[$type] == 1){ 
                if($v[$supply_goods_id]>0){ //官网钻石
                    if($zm_dollar_huilv == 0){ //获取汇率
                        $Openzm = logic('Openzm')->config_getConfig();
                        if($Openzm['code']==100200){
                            $zm_dollar_huilv = $Openzm['data']['dollar_huilv'];
                        }else{
                            return false;
                        }
                    }
                    //如果是速订购钻石
                    if($v[$supply_goods_type] == 1){
                        $list[$k][$price] = formatRound($v[$global_price]*$v[$weight]*$zm_dollar_huilv*($v[$dia_discount]+$jiadian_zm_sdg)/100);
                    }else{
                        $list[$k][$price] = formatRound($v[$global_price]*$v[$weight]*$zm_dollar_huilv*($v[$dia_discount]+$trader_price_list[$v[$type]]['rate'])/100);
                    } 
                }else if($v[$g_agent_id] == $agent_id){ //自己的钻石商品
                    $list[$k][$price] = formatRound($v[$global_price]*$v[$weight]*$this->agent_info['dollar_huilv']*($v[$dia_discount])/100);
                }else if($v[$g_agent_id]>0&&$v[$g_agent_id] != $agent_id){ //上级分销商钻石商品
                    $list[$k][$price] = formatRound($v[$global_price]*$v[$weight]*$t_dollar_huilv*($v[$dia_discount]+$trader_price_list[$v[$type]]['rate_my'])/100);
                }
            }
        }
        return $list;
    }

    //商品采购价格计算
    public function admin_goods_price($list,$data=[]){
        if(!$list) return $list;
        $g_agent_id = isset($data['agent_id'])?$data['agent_id']:'agent_id';//商品所属分销商
        $price = isset($data['price'])?$data['price']:'price';//销售价
        $purchase_price = isset($data['purchase_price'])?$data['purchase_price']:'purchase_price';//销售价
        $type = isset($data['type'])?$data['type']:'type';//商品类型
        $global_price = isset($data['global_price'])?$data['global_price']:'global_price';//国际报价
        $weight = isset($data['weight'])?$data['weight']:'weight';//重量
        $dia_discount = isset($data['dia_discount'])?$data['dia_discount']:'dia_discount';//dia_discount 折扣 
        $supply_goods_id = isset($data['supply_goods_id'])?$data['supply_goods_id']:'supply_goods_id';//supply_goods_id 条件 
        $supply_goods_type = isset($data['supply_goods_type'])?$data['supply_goods_type']:'supply_goods_type';//supply_goods_type 0普通 1速订购 
        $point = isset($data['point'])?$data['point']:'point';//销售加点或折扣
        $rate = isset($data['rate'])?$data['rate']:'rate';//采购加点或折扣

        // 1.1 白钻计算公式： 白钻价格 = 国际报价*钻重*汇率*折扣；
        // 例如：国际报价为2000，钻重为0.5，汇率为7，折扣为72
        // 销售价 = 2000*0.5*7*[（72 /100）]

        // 1.2 彩钻价格 = 国际报价*钻重*汇率*折扣
        // 例如：国际报价为2000，钻重为0.5，汇率为7，折扣为72
        // 销售价 = 2000*0.5*7*[（72 /100）]

        //钻明汇率
        $zm_dollar_huilv = 0; 
        //上级分销商汇率
        $t_dollar_huilv = 0;
        $agent_id = get_agent_id();

        $trader = []; //分销商信息
        $trader_price_list = []; //自己的加点信息
        $t_trader_price_list = []; //上级的加点信息
        
        //分销商信息
        $trader = M('trader')->where(['agent_id'=>$agent_id])->find();
        //自己的加点信息
        $trader_price_list = logic('DataProcessing')->get_trader_price($agent_id);
        //速订购采购加点，只有白钻，一级分销商
        $jiadian_zm_sdg = 0;
        //上级加点信息
        if($trader['t_agent_id']>0){ 
            $t_trader_price_list = logic('DataProcessing')->get_trader_price($trader['t_agent_id']);
            //二级分销商速订购采购加点，只有白钻
            $jiadian_zm_sdg = $trader_price_list[0]['rate_config']+$t_trader_price_list[0]['point_config'];
        }
        //上级分销商汇率
        if($t_dollar_huilv == 0){
            $t_dollar_huilv = get_agent_info($trader['t_agent_id'])['dollar_huilv'];
        }

        foreach ($list as $k => $v) {
            //暂时只做一级分销商加点 
            if(!$v) return ;

            //戒托计算价格 销售加点进行
            if($v[$type] == 3 ||$v[$type] == 4){
                
                if($v[$supply_goods_id]>0){ //官网戒托
                    $list[$k][$price] = formatRound($v[$price]*$trader_price_list[$v[$type]]['point']/10000);
                    $list[$k][$purchase_price] = formatRound($v[$purchase_price]*$trader_price_list[$v[$type]]['rate']/10000);
                    
                }elseif($v[$g_agent_id]>0&&$v[$g_agent_id] != $agent_id){ //上级分销商的商品
                    $list[$k][$price] = formatRound($v[$price]*$trader_price_list[$v[$type]]['point_my']/10000);
                    $list[$k][$purchase_price] = formatRound($v[$purchase_price]*$trader_price_list[$v[$type]]['rate_my']/10000);
                }
            }

            //白钻彩砖计算价格 销售加折扣计算
            if($v[$type] == 0 ||$v[$type] == 1){ 
                if($v[$supply_goods_id]>0){ //官网钻石
                    if($zm_dollar_huilv == 0){ //获取汇率
                        $Openzm = logic('Openzm')->config_getConfig();
                        if($Openzm['code']==100200){
                            $zm_dollar_huilv = $Openzm['data']['dollar_huilv'];
                        }else{
                            return false;
                        }
                    }
                    //如果是速订购钻石
                    if($v[$supply_goods_type] == 1){
                        $list[$k][$point] = $jiadian_zm_sdg;
                        $list[$k][$rate] = $jiadian_zm_sdg;
                        $list[$k][$price] = formatRound($v[$global_price]*$v[$weight]*$zm_dollar_huilv*($v[$dia_discount]+$jiadian_zm_sdg)/100);
                        $list[$k][$purchase_price] = formatRound($v[$global_price]*$v[$weight]*$zm_dollar_huilv*($v[$dia_discount]+$jiadian_zm_sdg)/100);
                    }else{
                        $list[$k][$point] = $trader_price_list[$v[$type]]['point'];
                        $list[$k][$rate] = $trader_price_list[$v[$type]]['rate'];
                        $list[$k][$price] = formatRound($v[$global_price]*$v[$weight]*$zm_dollar_huilv*($v[$dia_discount]+$trader_price_list[$v[$type]]['point'])/100);
                        $list[$k][$purchase_price] = formatRound($v[$global_price]*$v[$weight]*$zm_dollar_huilv*($v[$dia_discount]+$trader_price_list[$v[$type]]['rate'])/100);
                    }
                }else if($v[$g_agent_id] == $agent_id){ //自己的钻石商品
                    $list[$k][$point] = 0;
                    $list[$k][$rate] = 0;
                    $list[$k][$price] = formatRound($v[$global_price]*$v[$weight]*$this->agent_info['dollar_huilv']*($v[$dia_discount])/100);
                    $list[$k][$purchase_price] = formatRound($v[$global_price]*$v[$weight]*$this->agent_info['dollar_huilv']*($v[$dia_discount])/100);
                }else if($v[$g_agent_id]>0&&$v[$g_agent_id] != $agent_id){ //上级分销商钻石商品
                    $list[$k][$point] = $trader_price_list[$v[$type]]['point_my'];
                    $list[$k][$rate] = $trader_price_list[$v[$type]]['rate_my'];
                    $list[$k][$price] = formatRound($v[$global_price]*$v[$weight]*$t_dollar_huilv*($v[$dia_discount]+$trader_price_list[$v[$type]]['point_my'])/100);
                    $list[$k][$purchase_price] = formatRound($v[$global_price]*$v[$weight]*$t_dollar_huilv*($v[$dia_discount]+$trader_price_list[$v[$type]]['rate_my'])/100);
                }
            }
        }
        return $list;
    }
}