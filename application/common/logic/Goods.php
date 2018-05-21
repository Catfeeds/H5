<?php

namespace app\common\logic;

use think\Db;

class Goods{

	public $agent_id;

     /**
	 * 实时分销商品价格计算，用于产品详情
	 * $agent_id 当前代理商编号
	 * $goods_id 要查询的商品编号
	 * add fire 
	 */
    public function getGoodsPriceCurrent($agent_id,$goods_id){ 
        if($agent_id&&$goods_id){
            $data=Db::table('zm_goods')
            ->field("price,type,agent_id,supply_goods_id,supply_goods_type")
            ->find($goods_id);
            if($data){
                if($data['agent_id']===0){
                    //如果是官网数据，先去取最新的基础价格
                    $basePrice=$this->getGoodsPriceZM($agent_id, $goods_id);                    
                    //更新基础价格
                    Db::table('zm_goods')
                    ->where("id",$goods_id)
                    ->update([
                        'price'  => $basePrice['price'],                        
                        'update_time' => time()
                    ]);                     
                }
                
                //自己创建的商品
                if($data['agent_id']==$agent_id){
                    $priceobj= ["price"=>$data['price'],"ratepoint"=>$data['ratepoint']];
                }else{
                    //不相等，说明是分销的商品，去找分销价格
                    $priceobj= $this->getGoodsPriceTrader($agent_id,$data['agent_id'],$goods_id);
                }                
                echo json_encode($priceobj);
            }
        }
    }
    
    //通过接口获取最新价格数据，未完成，需要调用钻明官网接口
    private function getGoodsPriceZM($agent_id,$goods_id){        
        if($goods_id==22199)
            return ["price"=>100,"ratepoint"=>1];
        if($goods_id==22200)
            return ["price"=>1000,"ratepoint"=>1];        
        return 0;
    }
    
    //计算分销价格
    private function getGoodsPriceTrader($agent_id,$goods_agent_id,$goods_id){
        $data=Db::table('zm_goods_trader')
        ->alias('a')
        ->join('zm_goods g','g.id = a.goods_id')
        ->join('zm_trader b','b.id = a.trader_id')
        ->join('zm_trader_price c','b.id = c.trader_id and c.goods_type=g.type')
        ->where("g.id",$goods_id)
        ->where("b.agent_id",$agent_id)
        ->field("b.t_agent_id,IFNULL(a.price,g.price) price,c.rate,c.point")
        ->find();        
        if($data){            
            $rate=$data['rate'];
            $point=$data['point'];            
            $ratepoint=$rate/100*(1+$point/100);//计算公式            
            $t_agent_id=$data['t_agent_id']; 
            if($t_agent_id==$goods_agent_id||$t_agent_id===0){   
                $priceobj= ["price"=>$data['price'],"ratepoint"=>$ratepoint];    
            }else{
                //递归调用上级
                $priceobj=  $this->getGoodsPriceTrader($data['t_agent_id'],$goods_agent_id,$goods_id);                
            }  
            return ["price"=>$priceobj['price']*$ratepoint,"ratepoint"=>$ratepoint];
        }
    }    
	
	
     /**
	 * 珠宝成品查询
	 * $category_id 分类或标签
	 * $keyword 名称或标签模糊查询
	 * $iscount =0用于移动查询
	 * add fire 
	 */
	public function getGoodsList($attrparams,$category_id,$keyword,$page=1,$pagesize=20,$orderby="id desc",$iscount=1){
	    $agent_id=get_agent_id();
	    if(empty($attrparams)&&empty($keyword)){
	        $list=model("Goods")->getListTraderByCat($agent_id,$category_id,$page,$pagesize,$iscount,$orderby);
	    }else{	
	        $customWhere=null;
	        if($attrparams){
	            $customWhere  = 'SELECT		a.goods_id,a.attr_id,count(b.goods_id) c FROM	zm_goods_associate_attr a JOIN (SELECT id, goods_id, attr_id FROM zm_goods_associate_attr WHERE 1=2 ';	            
	            foreach ($attrparams as $key=>$val){
	                if($key && $val){
	                	if (is_array($val)) {
							$customWhere .= 'or attr_id = '.$key.' and attr_value_id in('.implode($val,',').')';
	                	} else {
	                		$customWhere .= 'or attr_id = '.$key.' and attr_value_id in('.rtrim($val,',').')';
	                	}
	                    	                   
	                }
	            }	 
	            $customWhere.=" GROUP BY goods_id, attr_id ) b ON a.id = b.id GROUP BY b.goods_id HAVING  c>".(count($attrparams)-1);
	            //防注入
	            $customWhere=mysql_escape_string($customWhere);
	        }	        
	        $list= model("Goods")->getGoodsTrader($agent_id,$category_id,$keyword,$customWhere,$page,$pagesize,$orderby,$iscount);	
	    }
	    //$list['debug']=model("Goods")->getlastsql();
	    return $list;
	}
	
	/**
	 * 珠宝成品查询
	 * $category_id 分类或标签
	 * $keyword 名称或标签模糊查询
	 * $iscount =0用于移动查询
	 * add fire
	 */
	public function getGoodsListFast($attrparams=null,$category_id=null,$keyword=null,$page=1,$pagesize=20,$orderby="id desc",$iscount=1){
	    $agent_id=get_agent_id();
	    if(empty($attrparams)&&empty($keyword)){
	        $list=model("Goods")->getListTraderByCat($agent_id,$category_id,$page,$pagesize,$iscount,$orderby);
	    }else{
	        $customWhere=null;
	        if($attrparams){
	            $customWhere  = 'SELECT	a.goods_id,a.attr_id,count(b.goods_id) c FROM	zm_goods_associate_attr a JOIN (SELECT id, goods_id, attr_id FROM zm_goods_associate_attr WHERE 1=2 ';
	            foreach ($attrparams as $key=>$val){
	                if($key && $val){
	                    if (is_array($val)) {
	                        $customWhere .= 'or attr_id = '.$key.' and attr_value_id in('.implode($val,',').')';
	                    } else {
	                        $customWhere .= 'or attr_id = '.$key.' and attr_value_id in('.rtrim($val,',').')';
	                    }
	                     
	                }
	            }
	            $customWhere.=" GROUP BY goods_id, attr_id ) b ON a.id = b.id GROUP BY b.goods_id HAVING  c>".(count($attrparams)-1);
	            //防注入
	            $customWhere=mysql_escape_string($customWhere);
	        }
	        $list= model("Goods")->getGoodsTraderFast($agent_id,$category_id,$keyword,$customWhere,$page,$pagesize,$orderby,$iscount);
	    }	    
	    return $list;
	}
	
	
     /**
	 * 钻石查询
	 * $keyword 名称或标签模糊查询
	 * $iscount =0用于移动查询
	 * add fire 
	 */
	public function getDiamondList($Condition,$keyword=null,$page=1,$pagesize=20,$orderby="id desc",$iscount=1){	    
	    $param=[];
	    if(!empty($Condition)){
	        foreach ($Condition as $key=>$val){
	            if($val||$val==0){
	                if(is_array($val)){																//区间
	                    if($key!='supply_goods_type'){
	                    	$param[$key] =['in',$val];
	                    }
	                }else{	
	                    if($key=='Priceinterval'){
	                        $arr=explode("-",$val);
	                        $param['goods_price'] = [['>',$arr[0]],['<',$arr[1]],'and'];
	                    }elseif($key=='Weightinterval'){
	                        $arr=explode("-",$val);
	                        $param['weight']= [['>',$arr[0]],['<',$arr[1]],'and'];
	                    }else{
	                    	$param[$key] = $val;
	                    }	                    
	                }
	            }
	        }
	    }	

	    $agent_id=get_agent_id();	 
	    $list= model("Goods")->getDiamondTrader($agent_id,$param,$keyword,$page,$pagesize,$orderby,$iscount);
	    
	    //$list['debug']=model("Goods")->getlastsql();
	    return $list;
	}	


	/**
	 * 钻石查询
	 * $keyword 名称或标签模糊查询
	 * $iscount =0用于移动查询
	 * add fire
	 */
	public function getDiamondListFast($params,$keyword=null,$page=1,$pagesize=20,$orderby="id desc",$iscount=1){
	    $where="where 1=1";
	    if(!empty($params)){
	        foreach ($params as $key=>$val){
	            if($val||$val==0){
	                if(is_array($val)){																//区间
	                    $where=$where." and a.".$key." in(".$val.")";
	                }else{
	                    if($key=='Priceinterval'){
	                        $arr=explode("-",$val);
	                        $where=$where." and b.price>".$arr[0]." and b.price<=".$arr[1];	                       
	                    }elseif($key=='Weightinterval'){
	                        $arr=explode("-",$val);	                       
	                        $where=$where." and a.weight>".$arr[0]." and a.weight<=".$arr[1];
	                    }else{	                     
	                        $where=$where." and a.".$key."='".$val."'";
	                    }
	                }
	            }
	        }
	    }
	     
	    $agent_id=get_agent_id();
	    $list= model("Goods")->getDiamondTraderFast($agent_id,$where,$keyword,$page,$pagesize,$orderby,$iscount);	   
	    return $list;
	}
	
	
	/**
	 * 获取指定商品,用于标签
	 * $catId 分类或标签
	 * $num 读取的数量
	 */
	public function getGoodslistByCat($catId = 0,$num=5)
	{
	    $agent_id=get_agent_id();
	    if($catId=='new'){
	        $list= model('goods')->getListTraderByTag($agent_id,'新品',1,$num);
	    }elseif ($catId=='hot'){
	        $list= model('goods')->getListTraderByTag($agent_id,'热卖',1,$num);
	    }
	    if(empty($list)){
	        $list= model('goods')->getListTraderByCat($agent_id,0,1,$num);
	    }	
	    
	    foreach ($list as $k=>$v){
	        if($v['type']==3){//珠宝
	            $list[$k]['price_seller']=PriceCalculation::getGoodsPrice($v);
	        }else{//定制
	            $list[$k]['price_seller']=PriceCalculation::getCustomPrice($v);
	        }
	    }
	    
	    return $list;
	}

	//zwx
	public function getDiamondListzwx2($data,$page=1,$size=15,$order='id desc'){
		//排序
		$data['orderby']?$order = $data['orderby']:'';
		//搜索拼接
		$whereArr = logic('DataProcessing')->goods_diamond_attr_filter($data['goods_attr_filter']);
		
		//官网汇率  ,对外一种强依赖应该使用缓存，5分钟，upt by fire
        $zm_dollar_huilv = cache("zm_dollar_huilv"); 
        if(empty($zm_dollar_huilv)){
            $Openzm = logic('Openzm')->config_getConfig();
            if($Openzm['code']==100200){
                $zm_dollar_huilv = $Openzm['data']['dollar_huilv'];
                cache("zm_dollar_huilv",$zm_dollar_huilv,300);
            }else{
                return false;
            }
        }
        
        
        //分销商ID
        $agent_id = get_agent_id(); 
        //分销商汇率
        $dollar_huilv = get_agent_info()['dollar_huilv'];
        //分销商加点
        $trader_price_list = logic('DataProcessing')->get_trader_price($agent_id);
        //获取分销商信息判断是 一级还是二级分销商
        $trader = M('trader')->where(['agent_id'=>$agent_id])->find();
        //上级分销商 agent_id  如果为0 表示是一级分销商
        $t_agent_id = $trader['t_agent_id'];
        //获取是否开启分销钻石
        $agent_config = get_agent_config();
        //过滤下架钻石商品
        $where = 'isdel = 0 and product_status=1';
        //获取钻明钻石加点 通用一级和二级分销商
        $jiadian_zm = $trader_price_list[$whereArr['type']]['point']; //钻明
        
        //根据分销商拼接价格计算 goods_price
        if($trader['t_agent_id']>0){ //二级分销商
        	//上级分销商汇率
        	$t_dollar_huilv = get_agent_info($trader['t_agent_id'])['dollar_huilv'];
        	//分销上级分销商自营钻石
        	$jiadian_zy = $trader_price_list[$whereArr['type']]['point_my']?$trader_price_list[$whereArr['type']]['point_my']:0; //自营

        	$case = "global_price*weight*(case 
						when(agent_id = 0) then ($jiadian_zm+dia_discount)*$zm_dollar_huilv 
						when(agent_id = $agent_id) then dia_discount*$dollar_huilv  
						when(agent_id > 0 and agent_id!=$agent_id) then ($jiadian_zy+dia_discount)*$t_dollar_huilv 
        			end)/100";
			//开启钻石分销
			if($agent_config['istrader'] == 1){
				$where .= " and agent_id in($agent_id,$t_agent_id,0)";
			}else{
				$where .= " and agent_id in($agent_id,$t_agent_id)";
			}
        }else{ //一级分销商
        	$case = "global_price*weight*(case 
						when(agent_id = 0) then ($jiadian_zm+dia_discount)*$zm_dollar_huilv 
						when(agent_id=$agent_id) then dia_discount*$dollar_huilv  
        			end)/100";
			//开启钻石分销
			if($agent_config['istrader'] == 1){
				$where .= " and agent_id in($agent_id,0)";
			}else{
				$where .= " and agent_id in($agent_id)";
			}
        }
        //条件搜索
    	if($whereArr['where_str']){
			$where.= ' and '.$whereArr['where_str'];
		}
		//价格筛选
		if($whereArr['price']){
			$where.= ' and '.$case.'>='.$whereArr['price'][0].' and '.$case.'<='.$whereArr['price'][1];
		}
		
        $sql = "SELECT count(*) count FROM zm_goods_diamond 
                WHERE $where";
        $total = Db::query($sql);

        $sql = "SELECT *,$case goods_price FROM zm_goods_diamond 
                WHERE $where  order by $order limit ".($page-1)*$size.",$size";
        
        $list = Db::query($sql);

        //证书数字
        $certificate_num = ConditionShow('','goods_diamond','certificate_type');
        foreach ($list as $k => $v) {
          $list[$k]['goods_price'] = formatRound($v['goods_price']);
          $list[$k]['certificate_num'] = $certificate_num[strtoupper($v['certificate_type'])];
        }

		$result['page']=$page;
        $result['size']=$size;
        $result['total']=$total[0]['count'];
        $result['data']=$list;

        return $result;
	}

	//zwx  拼接列表页 定制成品查询条件
	public function getDiamondListzwx($data,$page=1,$size=15,$order='id desc'){
		$data['orderby']?$order = $data['orderby']:'';
		$whereArr = logic('DataProcessing')->goods_diamond_attr_filter($data['goods_attr_filter']);
		
		$dollar_huilv = 0; //官网汇率
        $Openzm = logic('Openzm')->config_getConfig();
        if($Openzm['code']==100200){
            $dollar_huilv = $Openzm['data']['dollar_huilv'];
        }else{
            return false;
        }

        $agent_id = get_agent_id(); //分销商ID
        $agent_info = get_agent_info(); //获取分销商汇率

        $trader_price_list = logic('DataProcessing')->get_trader_price($agent_id);//分销商加点

        $jiadian = 0;
        //分销商加点
        $jiadian = $trader_price_list[$whereArr['type']]['rate']+$trader_price_list[$whereArr['type']]['point'];

        //$where = "(g.agent_id =".$agent_id." and g.product_status = 1 and g.isdel=0) or (g.agent_id = 0 and gt.status = 1 and gt.agent_id =".$agent_id.")";
        //二级分销商暂时没做，这里只做了一级分销商
        $where_1 = "g.agent_id =".$agent_id." and g.product_status = 1 and g.isdel=0"; //查询自己商品条件
		$where_2 = 'g.agent_id = 0 and gt.status = 1 and gt.agent_id ='.$agent_id; //查询官网商品条件
		
        if($whereArr){
        	if($whereArr['where_str']){
        		$where_1.= ' and '.$whereArr['where_str'];
        		$where_2.= ' and '.$whereArr['where_str'];

        		$sql = "SELECT sum(count) count FROM (SELECT count(*) count FROM zm_goods g 
                LEFT JOIN zm_goods_diamond gd ON g.id = gd.goods_id 
                WHERE ".$where_1." UNION ".
                "SELECT count(*) count FROM zm_goods g 
                LEFT JOIN zm_goods_diamond gd ON g.id = gd.goods_id 
                LEFT JOIN zm_goods_trader gt ON gt.goods_id = g.id 
                WHERE ".$where_2.' ) a';
        	}
        	
        	if(!empty($whereArr['price'])){
        		$where_1.= " and gd.global_price*gd.weight*(gd.dia_discount/100)*".$agent_info['dollar_huilv'].">=".$whereArr['price'][0]." and gd.global_price*gd.weight*(gd.dia_discount/100)*".$agent_info['dollar_huilv']."<=".$whereArr['price'][1];
        		$where_2.= " and gd.global_price*gd.weight*(gd.dia_discount+".$jiadian.")/100*".$dollar_huilv.">=".$whereArr['price'][0]." and gd.global_price*gd.weight*(gd.dia_discount+".$jiadian.")/100*".$dollar_huilv."<=".$whereArr['price'][1];
        		
        		$sql = "SELECT sum(count) count FROM (SELECT count('id') count,gd.global_price*gd.weight*(gd.dia_discount/100)*".$agent_info['dollar_huilv']." goods_price FROM zm_goods g 
                LEFT JOIN zm_goods_diamond gd ON g.id = gd.goods_id 
                WHERE ".$where_1." UNION ".
                "SELECT count('id') count,gd.global_price*gd.weight*(gd.dia_discount+".$jiadian.")/100*".$dollar_huilv." goods_price FROM zm_goods g 
                LEFT JOIN zm_goods_diamond gd ON g.id = gd.goods_id 
                LEFT JOIN zm_goods_trader gt ON gt.goods_id = g.id 
                WHERE ".$where_2.' ) a';
        	}
        }

        // $sql = "SELECT count('id') count FROM zm_goods g 
        //         LEFT JOIN zm_goods_diamond gd ON g.id = gd.goods_id 
        //         LEFT JOIN zm_goods_trader gt ON gt.goods_id = g.id 
        //         WHERE ".$where." limit 1";
        // p($sql);
		$total = Db::query($sql);
		// p($total);
		$sql = "SELECT 
				gd.id,gd.supply_id,gd.supply_gid,gd.goods_id,gd.goods_name,gd.certificate_type,gd.certificate_number,gd.intensity,gd.location,gd.quxiang,gd.shape,gd.weight,gd.color,gd.clarity,gd.cut,gd.polish,gd.symmetry,gd.fluor,gd.milk,gd.coffee,gd.belongs,gd.dia_table,gd.dia_depth,gd.dia_size,gd.global_price,gd.dia_discount,gd.inventory_type,gd.address,
				g.type,g.thumb,g.agent_id,g.supply_goods_id,g.supply_goods_type,
				gd.global_price*gd.weight*(gd.dia_discount/100)*".$agent_info['dollar_huilv']." goods_price FROM zm_goods g 
                LEFT JOIN zm_goods_diamond gd ON g.id = gd.goods_id 
                WHERE ".$where_1." UNION ".
                "SELECT 
                gd.id,gd.supply_id,gd.supply_gid,gd.goods_id,gd.goods_name,gd.certificate_type,gd.certificate_number,gd.intensity,gd.location,gd.quxiang,gd.shape,gd.weight,gd.color,gd.clarity,gd.cut,gd.polish,gd.symmetry,gd.fluor,gd.milk,gd.coffee,gd.belongs,gd.dia_table,gd.dia_depth,gd.dia_size,gd.global_price,gd.dia_discount,gd.inventory_type,gd.address,
				g.type,g.thumb,g.agent_id,g.supply_goods_id,g.supply_goods_type,
                gd.global_price*gd.weight*(gd.dia_discount+".$jiadian.")/100*".$dollar_huilv." goods_price FROM zm_goods g 
                LEFT JOIN zm_goods_diamond gd ON g.id = gd.goods_id 
                LEFT JOIN zm_goods_trader gt ON gt.goods_id = g.id 
                WHERE ".$where_2.' order by '.$order.' limit '.($page-1)*$size.','.$size;

		$list = Db::query($sql);

        foreach ($list as $k => $v) {
          $list[$k]['goods_price'] = formatRound($v['goods_price']);
        }
		$result['page']=$page;
        $result['size']=$size;
        $result['total']=$total[0]['count'];
        $result['data']=$list;

        return $result;
	}
	//zwx 下单通列表
	public function getGoodsListXdt($data,$page=1,$size=15,$order='g.id desc'){
		$where = ' product_status = 1'; //上架
		$where.= ' and g.agent_id =0'; //必须条件
		$where.= ' and gt.agent_id ='.$data['agent_id']; //必须条件
		$where.= ' and gt.status =1'; //必须条件
		$where.= ' and g.type ='.$data['type']; //必须条件
		$where.= ' and g.isdel =0'; //过滤删除的商品

		if($data['category_id']){ //分类
			$where.= ' and (category_id = '.$data['category_id'].' OR jewelry_id = '.$data['category_id'].' )';
		}
		if($data['keyword']){ //搜索
			$where.= ' and (code like "%'.$data['keyword'].'%" or name like "%'.$data['keyword'].'%" or keyword like "%'.$data['keyword'].'%" or tagStr like "%'.$data['keyword'].'%")';
		}
		if($data['goods_attr_filter']){ //属性查询
			//1:2,5;2:12,13,14 条件组合
			$where_having = logic('DataProcessing')->goods_attr_filter($data['goods_attr_filter']);
			if($where_having){
				$where.= ' and '.$where_having['where'];
			}
		}
		if(!$where_having){ //非属性查询
			$sql_count = "SELECT count(g.id) count FROM zm_goods g
				   LEFT JOIN zm_goods_trader gt ON g.id = gt.goods_id and gt.agent_id=".$data['agent_id']."
				   WHERE ".$where.' limit 1';
			$sql_list = "SELECT g.* FROM zm_goods g
				   LEFT JOIN zm_goods_trader gt ON g.id = gt.goods_id
				   WHERE  ".$where." order by ".$order.' limit '.($page-1)*$size.','.$size;
		}else{

			$sql_count = "SELECT count(g.id) count FROM zm_goods g
					   LEFT JOIN zm_goods_associate_attr gaa ON g.id = gaa.goods_id 
					   LEFT JOIN zm_goods_trader gt ON g.id = gt.goods_id
					   WHERE ".$where.' limit 1';

			$sql_list = "SELECT g.* FROM zm_goods g
					   LEFT JOIN zm_goods_associate_attr gaa ON g.id = gaa.goods_id 
					   LEFT JOIN zm_goods_trader gt ON g.id = gt.goods_id
					   WHERE ".$where.' order by '.$order.' limit '.($page-1)*$size.','.$size;

		}
		
		$total = Db::query($sql_count);	
		if(!$total) return ;
		
		$list = Db::query($sql_list);

		$result['page']=$page;
        $result['size']=$size;
        $result['total']=$total[0]['count'];
        $result['data']=$list;
		//销售加点
		$result['data'] = logic('PriceCalculation')->goods_price($result['data']);
		return $result;
	}

	//zwx  拼接列表页 定制成品查询条件
	public function getGoodsListCustomized($data,$page=1,$size=15,$order='g.id desc'){
		$orderArr = [
			"price desc" => "price desc",
			"price asc" => "price asc",
			"create_time asc" => "create_time asc",
			"create_time desc" => "create_time desc",
			"tagStr like '%新品%' desc" => "tagStr like '%新品%' desc",
			"tagStr like '%新品%' asc" => "tagStr like '%新品%' asc",
			"tagStr like '%热卖%' desc" => "tagStr like '%热卖%' desc",
			"tagStr like '%热卖%' asc" => "tagStr like '%热卖%' asc",
		];
		
		$orderArr[$data['orderby']]?$order = $orderArr[$data['orderby']]:'';
		
		$data['type'] = $data['type']==4?4:3; //成品 定制
		$data['category_id'] = is_numeric($data['category_id'])&&$data['category_id']>0?$data['category_id']:'';
		$data['keyword'] = trim($data['keyword']);

		$data['shape'] = trim($data['shape']);
		$data['weight'] = is_numeric($data['weight'])&&$data['weight']>0?$data['weight']:'';

		if($data['shape']&&$data['weight']){ // 钻配托
			return $this->getGoodsListZpt($data,$page,$size,$order);
		}else{ // 普通商品列表
			if($data['type'] == 3){
				//成品
				return $this->getGoodsListCp($data,$page,$size,$order);
			}else{
				//定制
				return $this->getGoodsListPt($data,$page,$size,$order);
			}
		}


		// SELECT
		// 	g.*,
		// 	CONCAT(
		// 		',',
		// 		GROUP_CONCAT(gaa.attr_id),
		// 		','
		// 	) cc
		// FROM
		// 	vgoods g
		// LEFT JOIN zm_goods_associate_attr gaa ON g.id = gaa.goods_id
		// WHERE
		// 	(
		// 		gaa.attr_id = 1
		// 		AND gaa.attr_value_id IN (2, 3)
		// 	)
		// OR (
		// 	gaa.attr_id = 2
		// 	AND gaa.attr_value_id IN (12, 13)
		// )
		// GROUP BY
		// 	gaa.goods_id
		// HAVING
		// 	cc REGEXP ',1,'
		// AND cc REGEXP ',2,'

	        // echo model("Goods")->getlastsql();
	        // echo '<br>';

		// SELECT
		// 	g.*
		// FROM
		// 	vgoods g
		// JOIN (
		// 	SELECT
		// 		a.id,
		// 		a.goods_id,
		// 		a.attr_id,
		// 		count(b.goods_id)
		// 	FROM
		// 		zm_goods_associate_attr a
		// 	JOIN (
		// 		SELECT
		// 			id,
		// 			goods_id,
		// 			attr_id
		// 		FROM
		// 			zm_goods_associate_attr
		// 		WHERE
		// 			agent_id = 1002
		// 		AND ((
		// 			attr_id = 1
		// 			AND attr_value_id IN (2, 3)
		// 		)
		// 		OR (
		// 			attr_id = 2
		// 			AND attr_value_id IN (12, 13)
		// 		))
		// 		GROUP BY
		// 			goods_id,
		// 			attr_id
		// 	) b ON a.id = b.id
		// 	GROUP BY
		// 		b.goods_id
		// 	HAVING
		// 		count(b.goods_id) = 2
		// ) c ON g.id = c.goods_id
	}

	//zwx 成品列表 跟定制普通列表 getGoodsListPt 代码一样 
	public function getGoodsListCp($data,$page=1,$size=15,$order='g.id desc'){
		$where = ' g.product_status = 1'; //上架
		$where.= ' and g.isdel = 0'; //过滤删除商品
		$where.= ' and g.type ='.$data['type']; //必须条件
		$where.= ' and g.supply_goods_id = 0';//非下单通商品

		if($data['category_id']){ //分类
			$where.= ' and (g.category_id = '.$data['category_id'].' OR g.jewelry_id = '.$data['category_id'].' )';
		}
		if($data['keyword']){ //搜索
			$where.= ' and (g.code like "%'.$data['keyword'].'%" or g.name like "%'.$data['keyword'].'%" or g.keyword like "%'.$data['keyword'].'%" or g.tagStr like "%'.$data['keyword'].'%")';
		}

		if($data['goods_attr_filter']){ //属性查询
			//1:2,5;2:12,13,14 条件组合
			$where_having = logic('DataProcessing')->goods_attr_filter($data['goods_attr_filter']);
			if($where_having){
				$where.= ' and '.$where_having['where'];
			}
		}
		// halt($where);
		//分销商ID
        $agent_id = get_agent_id(); 
		//获取分销商信息判断是 一级还是二级分销商
        $trader = get_agent_trader();
        //二级分销商获取加点
        if($trader['t_agent_id']>0){
        	//分销商加点
        	$trader_price_list = logic('DataProcessing')->get_trader_price($agent_id);
        	//二级分销商条件
        	//获取是否开启分销
	        $agent_config = get_agent_config();
			if($agent_config['istrader'] == 1){
				$where.=' and (g.agent_id = '.$data['agent_id'].' or (gt.status = 1 and gt.agent_id = '.$data['agent_id'].'))';
			}else{
				$where.= ' and g.agent_id ='.$data['agent_id'];
			}
        }else{
        	//一级分销商条件
        	$where.= ' and g.agent_id ='.$data['agent_id']; //必须条件
        }

        //暂时未做价格排序
		if(!$where_having){ //非属性查询
			$sql = "SELECT count(*) count FROM (SELECT g.id FROM zm_goods g
				   LEFT JOIN zm_goods_trader gt ON g.id = gt.goods_id 
				   WHERE ".$where." ) a limit 1";
		
			$total = Db::query($sql);

			if(!$total) return ;

			$sql = "SELECT g.* FROM zm_goods g
					   LEFT JOIN zm_goods_trader gt ON g.id = gt.goods_id 
					   WHERE ".$where."  order by ".$order.' limit '.($page-1)*$size.','.$size;
			
	        $list = Db::query($sql);

	        $result['page']=$page;
	        $result['size']=$size;
	        $result['total']=$total[0]['count'];
	        $result['data']=$list;

		}else{ //属性查询
			$sql = "SELECT count(*) count FROM (SELECT CONCAT(',',GROUP_CONCAT(gaa.attr_id),',') cc FROM zm_goods g
					   LEFT JOIN zm_goods_associate_attr gaa ON g.id = gaa.goods_id 
					   LEFT JOIN zm_goods_trader gt ON g.id = gt.goods_id 
					   WHERE ".$where." GROUP BY gaa.goods_id HAVING ".$where_having['having'].') a limit 1';

			$total = Db::query($sql);

			if(!$total) return ;

			$sql = "SELECT g.*, CONCAT(',',GROUP_CONCAT(gaa.attr_id),',') cc FROM zm_goods g
					   LEFT JOIN zm_goods_associate_attr gaa ON g.id = gaa.goods_id 
					   LEFT JOIN zm_goods_trader gt ON g.id = gt.goods_id 
					   WHERE ".$where." GROUP BY gaa.goods_id HAVING ".$where_having['having'].' order by '.$order.' limit '.($page-1)*$size.','.$size;
			$list = Db::query($sql);

			$result['page']=$page;
	        $result['size']=$size;
	        $result['total']=$total[0]['count'];
	        $result['data']=$list;
		}
		
		$result['data'] = logic('PriceCalculation')->goods_price($result['data']);
		return $result;
	}

	//zwx 定制 普通列表
	public function getGoodsListPt($data,$page=1,$size=15,$order='g.id desc'){
		$where = ' g.product_status = 1'; //上架
		$where.= ' and g.isdel = 0'; //过滤删除商品
		$where.= ' and g.type ='.$data['type']; //必须条件
		$where.= ' and g.supply_goods_id = 0';//非下单通商品

		if($data['category_id']){ //分类
			$where.= ' and (g.category_id = '.$data['category_id'].' OR g.jewelry_id = '.$data['category_id'].' )';
		}
		if($data['keyword']){ //搜索
			$where.= ' and (g.code like "%'.$data['keyword'].'%" or g.name like "%'.$data['keyword'].'%" or g.keyword like "%'.$data['keyword'].'%" or g.tagStr like "%'.$data['keyword'].'%")';
		}

		if($data['goods_attr_filter']){ //属性查询
			//1:2,5;2:12,13,14 条件组合
			$where_having = logic('DataProcessing')->goods_attr_filter($data['goods_attr_filter']);
			if($where_having){
				$where.= ' and '.$where_having['where'];
			}
		}
		//分销商ID
        $agent_id = get_agent_id(); 
		//获取分销商信息判断是 一级还是二级分销商
        $trader = get_agent_trader();
        //二级分销商获取加点
        if($trader['t_agent_id']>0){
        	//分销商加点
        	$trader_price_list = logic('DataProcessing')->get_trader_price($agent_id);
        	//二级分销商条件
        	//获取是否开启分销
	        $agent_config = get_agent_config();
			if($agent_config['istrader'] == 1){
				$where.=' and (g.agent_id = '.$data['agent_id'].' or (gt.status = 1 and gt.agent_id = '.$data['agent_id'].'))';
			}else{
				$where.= ' and g.agent_id ='.$data['agent_id'];
			}
        }else{
        	//一级分销商条件
        	$where.= ' and g.agent_id ='.$data['agent_id']; //必须条件
        }

        //暂时未做价格排序
		if(!$where_having){ //非属性查询
			$sql = "SELECT count(*) count FROM (SELECT g.id FROM zm_goods g
				   LEFT JOIN zm_goods_trader gt ON g.id = gt.goods_id 
				   WHERE ".$where." ) a limit 1";
		
			$total = Db::query($sql);

			if(!$total) return ;

			$sql = "SELECT g.* FROM zm_goods g
					   LEFT JOIN zm_goods_trader gt ON g.id = gt.goods_id 
					   WHERE ".$where."  order by ".$order.' limit '.($page-1)*$size.','.$size;

	        $list = Db::query($sql);

	        $result['page']=$page;
	        $result['size']=$size;
	        $result['total']=$total[0]['count'];
	        $result['data']=$list;

		}else{ //属性查询
			$sql = "SELECT count(*) count FROM (SELECT CONCAT(',',GROUP_CONCAT(gaa.attr_id),',') cc FROM zm_goods g
					   LEFT JOIN zm_goods_associate_attr gaa ON g.id = gaa.goods_id 
					   LEFT JOIN zm_goods_trader gt ON g.id = gt.goods_id 
					   WHERE ".$where." GROUP BY gaa.goods_id HAVING ".$where_having['having'].') a limit 1';
			$total = Db::query($sql);

			if(!$total) return ;

			$sql = "SELECT g.*, CONCAT(',',GROUP_CONCAT(gaa.attr_id),',') cc FROM zm_goods g
					   LEFT JOIN zm_goods_associate_attr gaa ON g.id = gaa.goods_id 
					   LEFT JOIN zm_goods_trader gt ON g.id = gt.goods_id 
					   WHERE ".$where." GROUP BY gaa.goods_id HAVING ".$where_having['having'].' order by '.$order.' limit '.($page-1)*$size.','.$size;
			$list = Db::query($sql);

			$result['page']=$page;
	        $result['size']=$size;
	        $result['total']=$total[0]['count'];
	        $result['data']=$list;
		}

		$result['data'] = logic('PriceCalculation')->goods_price($result['data']);
		return $result;
	}
	//zwx 定制 钻配托列表
	public function getGoodsListZpt($data,$page=1,$size=15,$order='g.id desc'){
		$where = ' product_status = 1'; //上架
		$where.= ' and g.agent_id ='.$data['agent_id']; //必须条件
		$where.= ' and g.type ='.$data['type']; //必须条件

		$weight = logic('DataProcessing')->goods_Zpt($data['weight']);

		$where.= $data['shape']?' and gdm.shape = "'.$data['shape'].'"':'';
		$where.= $data['weight']?' and gdm.weight >='.$weight['weight_egt'].' and gdm.weight<'.$weight['weight_lt']:'';
		
		if($data['category_id']){ //分类
			$where.= ' and (category_id = '.$data['category_id'].' OR jewelry_id = '.$data['category_id'].' )';
		}
		if($data['keyword']){ //搜索
			$where.= ' and (code like "%'.$data['keyword'].'%" or name like "%'.$data['keyword'].'%" or keyword like "%'.$data['keyword'].'%" or tagStr like "%'.$data['keyword'].'%")';
		}
		if($data['goods_attr_filter']){ //属性查询
			//1:2,5;2:12,13,14 条件组合
			$where_having = logic('DataProcessing')->goods_attr_filter($data['goods_attr_filter']);
			if($where_having){
				$where.= ' and '.$where_having['where'];
			}
		}

		if(!$where_having){ //非属性查询
			$total = Db::table('vgoods')->alias('g')->join('zm_goods_diamond_matching gdm','gdm.goods_id=g.id')->where($where)->count();
			if(!$total) return ;

			$list = Db::table('vgoods')->alias('g')->join('zm_goods_diamond_matching gdm','gdm.goods_id=g.id')->field('g.*')->where($where)->order($order)->page($page,$size)->select();
	        $result['page']=$page;
	        $result['size']=$size;
	        $result['total']=$total;
	        $result['data']=$list;
	        return $result;

		}else{ //属性查询
			$sql = "SELECT count(g.id) count, CONCAT(',',GROUP_CONCAT(gaa.attr_id),',') cc FROM vgoods g
					   LEFT JOIN zm_goods_associate_attr gaa ON g.id = gaa.goods_id 
					   LEFT JOIN zm_goods_diamond_matching gdm ON g.id = gdm.goods_id 
					   WHERE ".$where." GROUP BY gaa.goods_id HAVING ".$where_having['having'].' limit 1';
			$total = Db::query($sql);	
			if(!$total) return ;

			$sql = "SELECT g.*, CONCAT(',',GROUP_CONCAT(gaa.attr_id),',') cc FROM vgoods g
					   LEFT JOIN zm_goods_associate_attr gaa ON g.id = gaa.goods_id 
					   LEFT JOIN zm_goods_diamond_matching gdm ON g.id = gdm.goods_id 
					   WHERE ".$where." GROUP BY gaa.goods_id HAVING ".$where_having['having'].' order by '.$order.' limit '.($page-1)*$size.','.$size;
			$list = Db::query($sql);

			$result['page']=$page;
	        $result['size']=$size;
	        $result['total']=$total[0]['count'];
	        $result['data']=$list;
	        return $result;
		}
	}

	//zwx 获取定制成品商品详情参数显示
	public function getGoodsAssociateAttrShow($goods_id){
		$goods_attr = M('goods_attr')->alias('ga')
			        ->join('goods_associate_attr gaa','ga.id = gaa.attr_id','left')
			        ->field('ga.*')
			        ->where(['goods_id'=>$goods_id])->order('id asc')->select();
        $goods_attr = convert_arr_key($goods_attr,'id');
        $attr_id_in = implode(',', array_keys($goods_attr));

		$goods_associate_attr = M('goods_associate_attr')
        						->where(['attr_id'=>['in',$attr_id_in]])->where(['goods_id'=>$goods_id])->select();
        foreach ($goods_associate_attr as $k => $v) {
        	$goods_attr[$v['attr_id']]['sub'][] = $v['value'];
        	$goods_attr[$v['attr_id']]['substr'] = implode('、', $goods_attr[$v['attr_id']]['sub']);
        }
        return $goods_attr;
	}

	//zwx 获取钻石商品详情参数显示 $GoodsDiamond 表goods_diamond 单条数据 
	public function getGoodsDiamondAssociateAttrShow($GoodsDiamond){
		$data[0]['name'] = '类型';
		$data[0]['substr'] = ConditionShow($GoodsDiamond['type'],'goods_diamond','type');
		$data[1]['name'] = '形状';
		$data[1]['substr'] = $GoodsDiamond['shape'];
		$data[2]['name'] = '荧光';
		$data[2]['substr'] = $GoodsDiamond['fluor'];
		$data[3]['name'] = '重量';
		$data[3]['substr'] = $GoodsDiamond['weight'];
		$data[4]['name'] = '颜色';
		$data[4]['substr'] = $GoodsDiamond['color'];
		$data[5]['name'] = '净度';
		$data[5]['substr'] = $GoodsDiamond['clarity'];
		$data[6]['name'] = '切工';
		$data[6]['substr'] = $GoodsDiamond['cut'];
		$data[7]['name'] = '抛光';
		$data[7]['substr'] = $GoodsDiamond['polish'];
		$data[8]['name'] = '对称';
		$data[8]['substr'] = $GoodsDiamond['symmetry'];
		$data[9]['name'] = '库存';
		$data[9]['substr'] = ConditionShow($GoodsDiamond['inventory_type'],'goods_diamond','inventory_type');
		return $data;
	}

	//zwx 根据goods ID 获取钻石单条数据
	public function getGoodsDiamondInfoJoinG($id,$agent_id){
		if(!is_numeric($id)||$id<=0||!$agent_id) return ;
		
        $where = logic('DataProcessing')->get_goods_product_status($agent_id);
        $where.= ' and g.id = '.$id;

        $info = M('goods_diamond')->alias("gd")
				->field('gd.*,g.product_status,g.thumb,g.price,g.supply_goods_id')
				->join('zm_goods g','g.id=gd.goods_id','left')
				->where($where)->find();

		$info = logic('PriceCalculation')->goods_price([$info]);
        return $info[0];
	}

	//zwx 托配钻
	public function getGoodsDiamondListTpz($data,$page=1,$size=20,$order='id desc'){
		//获取钻石获取条件
		$where = logic('DataProcessing')->get_goods_diamond_product_status($data['agent_id']);
        //速订购钻石不参与匹配 暂时默认白钻
        $where.=' and supply_gtype = 0 and type = 0';
        $shape = $data['shape'];
	    $weight = $data['weight'];

	    $weightArr = logic('DataProcessing')->goods_Zpt($weight);
		$where.= $shape?' and shape = "'.$shape.'"':'';
        $where.= $weight?' and weight >='.$weightArr['weight_egt'].' and weight<'.$weightArr['weight_lt']:'';

        // $sql = "SELECT count(*) count FROM zm_goods_diamond 
        //         WHERE $where";
        // $total = Db::query($sql);

        $sql = "SELECT * FROM zm_goods_diamond 
                WHERE $where  order by $order limit ".($page-1)*$size.",$size";
        
        $list = Db::query($sql);

        $list = logic('PriceCalculation')->goods_price($list,['supply_goods_id'=>'supply_gid','supply_goods_type'=>'supply_gtype']);

		$result['page']=$page;
        $result['size']=$size;
        // $result['total']=$total[0]['count'];
        $result['data']=$list;

        return $result;
	}

	//zwx 释放库存 $data二维数组 表 order 数据
	public function goodsReleaseStock($data){
		$data = convert_arr_key($data,'id');
		$keyArr = array_keys($data);
		$keyStr = implode(',', $keyArr);
		$where['og.order_id'] = ['in',$keyStr];
		$where['og.stock_status'] = 1; //现货才释放库存

		$list = M('order_goods')->alias('og')
		        ->join('zm_goods g','g.id = og.goods_id','left')
		        ->join('zm_goods_sku gs','gs.attributes = og.spec_key and gs.goods_id = og.goods_id','left')
		        ->field('
		            og.order_id og_order_id,og.goods_id og_goods_id,og.goods_num og_goods_num,og.stock_status og_stock_status,
		            g.stock_num g_stock_num,
		            gs.goods_number gs_goods_number,gs.id gs_sku_id
		            ')
		        ->where($where)
		        ->select();

		//订单过期 释放库存
		M('order')->where(['id'=>['in',$keyStr]])->update(['order_status'=>7,'release_stock'=>1]);

		if(!$list) return;

		//库存释放
		foreach ($list as $k => $v) {
			if($v['gs_sku_id']>0){ //有sku
				M('goods_sku')->where(['id'=>$v['gs_sku_id']])->setInc('goods_number',$v['og_goods_num']);
			}else{
				M('goods')->where(['id'=>$v['og_goods_id']])->setInc('stock_num',$v['og_goods_num']);
			}
		}
	}

	//zwx 商品详情获取当前位置
	public function getDetailPosition($id){
		if(!is_numeric($id)||$id<=0) return ['status'=>0,'msg'=>'ID错误','data'=>''];

		$info = M('goods')->alias('g')
				->field('g.type,g.category_id,g.jewelry_id,gcc.name gcc_name,gcj.name gcj_name')
				->join('goods_category gcc','gcc.id = g.category_id','left')
				->join('goods_category gcj','gcj.id = g.jewelry_id','left')
				->where(['g.id'=>$id])
				->find();
		if(!$info) return ['status'=>0,'msg'=>'商品不存在','data'=>''];

		$list[0]['name'] = '首页';
		$list[0]['url'] = url('index/index');

		if($info['type']==3){ //成品
			$list[1]['name'] = ConditionShow('goods/index','show_set_config','current_location');
			$list[1]['url'] = url('goods/index');
			$list[2]['name'] = $info['gcc_name'];
			$list[2]['url'] = url('goods/index',['category_id'=>$info['category_id']]);
			$list[3]['name'] = $info['gcj_name'];
			$list[3]['url'] = url('goods/index',['category_id'=>$info['category_id'],'jewelry_id'=>$info['jewelry_id']]);
		}elseif($info['type']==4 && $info['agent_id']>0){ //定制
			$list[1]['name'] = ConditionShow('goods/custom','show_set_config','current_location');
			$list[1]['url'] = url('goods/custom');
			$list[2]['name'] = $info['gcc_name'];
			$list[2]['url'] = url('goods/custom',['category_id'=>$info['category_id']]);
			$list[3]['name'] = $info['gcj_name'];
			$list[3]['url'] = url('goods/custom',['category_id'=>$info['category_id'],'jewelry_id'=>$info['jewelry_id']]);
		}elseif($info['type']==4 && $info['agent_id']==0){ //下单通
			$list[1]['name'] = ConditionShow('goods/custom_xdt','show_set_config','current_location');
			$list[1]['url'] = url('goods/custom_xdt');
			$list[2]['name'] = $info['gcc_name'];
			$list[2]['url'] = url('goods/custom_xdt',['category_id'=>$info['category_id']]);
			$list[3]['name'] = $info['gcj_name'];
			$list[3]['url'] = url('goods/custom_xdt',['category_id'=>$info['category_id'],'jewelry_id'=>$info['jewelry_id']]);
		}else{ //钻石
			$list[1]['name'] = '钻石';
			$list[1]['url'] = url('goods/diamond');
		}
		
		return ['status'=>100,'msg'=>'','data'=>$list];
	}
	
	//获取商品搜索部分
	public function GetData($Condition,$is_limit=1){
	    $this->agent_id = get_agent_id();
	    switch ($Condition['type']){
	        case 0:				//白钻
	        case 1:				//彩钻
	            $data = [];
	            //$data['Where']['zgd.type'] 		=  $Condition['DiaType'];
	            $data['Where']['zg.type']  		=  $Condition['DiaType'];
	            $data['Where']['zg.agent_id'] 	=  $this->agent_id;
	
	            unset($Condition['DiaType']);
	            unset($Condition['type']);
	
	            if(isset($Condition['PageID']) && $Condition['PageID']==0){
	                $data['Limit']  	 = 0;
	            }
	            foreach ($Condition as $key=>$val){
	                if($val){
	                    if($key=='stock_num'||$key=='price'|| $key=='new' || $key=='id'){						//排序
	                        $data['Order'] = ['zg.'.$key=>$val];
	                    }elseif(is_array($val)){																//区间
	                        if($key=='priceinterval'){
	                            $data['Where']['zg.price'] = [['<',$val[0]],['>',$val[1]],'or'];
	                        }elseif($key=='weightinterval'){
	                            $data['Where']['zgd.weight']= [['<',$val[0]],['>',$val[1]],'or'];
	                        }else{
	                            $data['Where']['zgd.'.$key] =['in',$val];
	                        }
	                    }else{																					//单项
	                        if($key=='naika'){
	                            $data['Where']['zgd.milk'] 	 = $val ? '有奶' : '无奶';
	                            $data['Where']['zgd.coffee'] = $val ? '有咖' : '无咖';;
	                        }elseif($key=='PageID'){
	                            $data['Limit']  	  = $val;
	                        }elseif($key=='PageID' || $key=='PageSize'){
	                            $data['Limit']  	 .= (isset($val) && is_numeric($val)) ? ','.$val : ',20';
	                        }else{
	                            $data['Where']['zgd.'.$key] = $val;
	                        }
	                    }
	                }
	            }
	
	
	            $GoodsList['data']  				= model('Goods')->GetDiamondList($data);
	            if($is_limit){
	                $GoodsList['count']  			= model('Goods')->GetDiamondListCount($data);
	            }
	            break;
	        case 2:				//2散货
	            break;
	        case 3:				//成品
	        case 4:				//定制
	            $CustomWhere  = '';			//为传入自定义where条件段。
	            $body 		  = '';
	            if($Condition['Attrs']){
	                foreach ($Condition['Attrs'] as $key=>$val){
	                    if($key && $val){
	                        $body .= ' attr_id = '.$key.' and attr_value_id in('.rtrim($val,',').') ';
	                        $body .= 'and';
	                    }
	                }
	                	
	                if($body){
	                    $CustomWhere  = 'and ( '.rtrim($body, 'and').' )';
	                }
	            }
	
	            if($Condition['keyword']){
	                $CustomWhere = " and ( zg.keyword like '%".$Condition['keyword']."%' )";
	            }
	
	            if(isset($Condition['price']) && $Condition['price']){
	                $Condition['order'] = 'zg.price';
	                $Condition['orderby'] = $Condition['price'];
	            }elseif(isset($Condition['stock_num']) && $Condition['stock_num']){
	                $Condition['order'] = 'zg.stock_num';
	                $Condition['orderby'] = $Condition['stock_num'];
	            }elseif(isset($Condition['id']) && $Condition['id']){
	                $Condition['order'] = 'zg.id';
	                $Condition['orderby'] = $Condition['id'];
	            }else{
	                $Condition['order']   ='zg.id';
	                $Condition['orderby'] = 'desc';
	            }
	
	
	            $GoodsList['data']  			= model('Goods')->GetGoodsList($this->agent_id,$Condition['category_id'],$Condition['type'],$CustomWhere,$Condition['order'],$Condition['orderby'],$Condition['PageID'],$Condition['PageSize']);
	
	            if($is_limit){
	                $GoodsList['count']  			= model('Goods')->GetGoodsListCount($this->agent_id,$Condition['category_id'],$Condition['type'],$CustomWhere,$Condition['order'],$Condition['orderby'],$Condition['PageID'],$Condition['PageSize']);
	            }
	            break;
	    }
	
	    return $GoodsList;
	    	
	
	}
	
}