<?php
namespace app\common\model;

use think\Model;

class System extends Model
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
	 * 系统设置 
	 * auth	：zengmm
	 * @param：$data 
	 * time	：2017-12-5
	**/
	public function updateAgentConfigInfo($data)
	{
		//设置三个表的数据   zm_agent、zm_agent_config、zm_agent_info
		$this->startTrans();
		//设置zm_agent表数据
		$aData["id"] = $data["agent_id"];
		$aData["name"] = $data["agentName"];
		$aData["telkefu"] = $data["telkefu"];
		$aData["bank"] = $data["bank"];
		$aData["bankaccount"] = $data["bankaccount"];
		$aData["bankcard"] = $data["bankcard"];
		$aData["alipayid"] = $data["alipayid"];
		$aData["alipaykey"] = $data["alipaykey"];
		$aData["aliappid"] = $data["aliappid"];
		$aData["alipublickey"] = $data["alipublickey"];
		$aData["aliprivatekey"] = $data["aliprivatekey"];
		$aData["wxappid"] = $data["wxappid"];
		$aData["wxmchid"] = $data["wxmchid"];
		$aData["wxkey"] = $data["wxkey"];
		$aData["wxappsecret"] = $data["wxappsecret"];
		$aData["weblogoimg"] = $data["logo"];
		$aData["icon"] = $data["icon"];
		$aData["seo_describe"] = $data["seo_describe"];
		$rs_a = db("agent")->update($aData);
		//设置zm_agent_info表数据
		$aiData["name"] = $data["name"];
		$aiData["code"] = $data["singlename"];
		$aiData["singlename"] = $data["singlename"];
		$aiData["province"] = $data["province"];
		$aiData["city"] = $data["city"];
		$aiData["district"] = $data["district"];
		$aiData["address"] = $data["address"];
		$aiData["url"] = $data["url"];
		$aiData["linkname"] = $data["linkname"];
		$aiData["tel"] = $data["tel"];
		$aiData["phone"] = $data["phone"];
		$aiData["email"] = $data["email"];
		$aiData["logo"] = $data["logo"];
		$aiData["remark"] = $data["remark"];
		$aiData["info"] = $data["info"];
		$aiData["expected_delivery_time"] = $data["expected_delivery_time"];
		$aiData["deposit_proportion"] = $data["deposit_proportion"];
		$aiData["dollar_huilv"] = $data["dollar_huilv"];
		$aiData["wximg"] = $data["wximg"];
		$aiData["wbimg"] = $data["wbimg"];
		$aiData["appimg"] = $data["appimg"];
		$aiData["copyright"] = $data["copyright"];
		$rs_ai = db("agent_info")->where("agent_id=".$data["agent_id"])->update($aiData);
		//设置zm_agent_config表数据
		$acData["istrader"] = $data["istrader"];
		$acData["isim"] = $data["isim"];
		$acData["isqqlogin"] = $data["isqqlogin"];
		$acData["iswxlogin"] = $data["iswxlogin"];
		$acData["isips"] = $data["isips"];
		$acData["isalipay"] = $data["isalipay"];
		$acData["iswxpay"] = $data["iswxpay"];
		$acData["isunderline"] = $data["isunderline"];
		$acData["isselfget"] = $data["isselfget"];
		$acData["isscore"] = $data["isscore"];
		$acData["score_realize"] = $data["score_realize"];
		$acData["score_rate"] = $data["score_rate"];
		$acData["score_first_leader_rate"] = $data["score_first_leader_rate"];
		$rs_ac = db("agent_config")->where("agent_id=".$data["agent_id"])->update($acData);
		if($rs_a !== false && $rs_ai !== false && $rs_ac !== false){
			//设置zm_trader_price表的加点算法
			$tpDataA["point"] = $data["white_drill_point"];
			$tpDataB["point"] = $data["color_drill_point"];
			$tpDataD["point"] = $data["product_drill_point"];
			$tpDataE["point"] = $data["custom_add_point"];
			$this->updTraderPrice($tpDataA["point"],0,$data["agent_id"]);
			$this->updTraderPrice($tpDataB["point"],1,$data["agent_id"]);
			$this->updTraderPrice($tpDataD["point"],3,$data["agent_id"]);
			$this->updTraderPrice($tpDataE["point"],4,$data["agent_id"]);
			$this->commit(); 
			return true; 
		}else{
			$this->rollback();
			return false;		
		}
	}
	
	/**
	 * 设置各种分类加点 
	 * auth	：zengmm
	**/
	public function updTraderPrice($point,$num,$agent_id){
		$data["point"] 		= $point;
		$data["goods_type"] = $num;	
		$data["agent_id"] 	= $agent_id;
		$traderInfo = M("trader")->where("agent_id=".$data["agent_id"])->field("id")->find();		
		$data["trader_id"] 	= $traderInfo["id"];
		$trader_price_info  = M("trader_price")->where("agent_id=".$data["agent_id"]." and goods_type=".$data["goods_type"])->field("id,point")->find();
		if($trader_price_info["id"]){
			$xData["point"] = $data["point"];
			M("trader_price")->where("id=".$trader_price_info["id"])->update($xData);	
		}else{
			M("trader_price")->insert($data);		
		}	
		return true;
	}
}	