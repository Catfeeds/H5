<?php
/**
 * 获取模板信息
 * @param
 * @return int
 */
function get_agent_template($id){
    $agent_template=db('template')
    ->find($id);
    return $agent_template;
}

/**
 * 获取代理商的支付方式
 * @param
 * @return int
 */
function get_agent_payment_old($total_fee){
    $agent=get_agent_config();

    $result=[];

    //线下转帐
    if($agent['isunderline']){
        $result['underline']['code']="1000";
        $result['underline']['name']="线下转帐";
        $result['underline']['action']="";
        $result['underline']['ziliao']=get_agent_base();
    }

    if($total_fee>3000
        ||empty($agent['isips'])){
            if($agent['isalipay']){
                $result['alipay']['code']="1001";
                $result['alipay']['name']="支付宝";
                $result['alipay']['action']="alipay";
                $result['alipay']['url']=url('pay/alipay');
            }
            if($agent['iswxpay']){
                $result['wxscan']['code']="1002";
                $result['wxscan']['name']="微信";
                $result['wxscan']['action']="wxscan";
                $result['wxscan']['url']=url('pay/wxscan');
            }
    }

    if($agent['isips']){
        $result['ipspay']['code']="1003";
        $result['ipspay']['name']="网银支付";
        $result['ipspay']['action']="ipspay";
        if(empty($result['wxscan'])){
            $result['ipsscanwx']['code']="1004";
            $result['ipsscanwx']['name']="微信扫码";
            $result['ipsscanwx']['action']="ipsscanwx";
        }
        if(empty($result['alipay'])){
            $result['ipsscanali']['code']="1005";
            $result['ipsscanali']['name']="支付宝扫码";
            $result['ipsscanali']['action']="ipsscanali";
        }
    }

    return $result;
}

function get_agent_payment($total_fee){
    $agent=get_agent_config();

    $result=[];

    //线下转帐
    if($agent['isunderline']){
        $result['underline']['code']="1000";
        $result['underline']['name']="线下转帐";
        $result['underline']['action']="";
        $result['underline']['ziliao']=get_agent_base();
    }

    if($total_fee>3000
        ||empty($agent['isips'])){
            if($agent['isalipay']){
                $result['alipay']['code']="1001";
                $result['alipay']['name']="支付宝";
                $result['alipay']['action']="alipay";
                $result['alipay']['url']=url('pay/alipay');
            }
            if($agent['iswxpay']){
                $result['wxscan']['code']="1002";
                $result['wxscan']['name']="微信";
                $result['wxscan']['action']="wxscan";
                $result['wxscan']['url']=url('pay/wxscan');
            }
    }

    if($agent['isips']){
        $result['ipspay']['code']="1003";
        $result['ipspay']['name']="网银支付";
        $result['ipspay']['action']="ipspay";
        if(empty($result['wxscan'])){
            $result['ipsscanwx']['code']="1004";
            $result['ipsscanwx']['name']="微信扫码";
            $result['ipsscanwx']['action']="ipsscanwx";
        }
        if(empty($result['alipay'])){
            $result['ipsscanali']['code']="1005";
            $result['ipsscanali']['name']="支付宝扫码";
            $result['ipsscanali']['action']="ipsscanali";
        }
    }
    //整理arr
    $res = [];
    foreach ($result as $key => $val) {
        $val['payname'] = $key;
        $res[] = $val;
    }
    return $res;
}