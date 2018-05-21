<?php
namespace app\common\model;

use think\Model;
use think\db;
class Goods extends Model
{
	
	public $Join 		='';
	public $Where		='';
	public $WhereCustom	='';
	public $Field		='';
	public $Order		='';
	
	

    
    protected function _initialize()
    {
        parent::_initialize();
    }

    /** 
     * zwx
     * @param array     $param 搜索条件
     * @return array
     */
    public function getInfo($param){
        $where = [];
        isset($param['id'])?$where['g.id'] = $param['id']:'';
        isset($param['agent_id'])?$where['agent_id'] = $param['agent_id']:'';
        isset($param['product_status'])?$where['product_status'] = $param['product_status']:'';
        
        $vo = M('Goods')->alias('g')
            ->join('zm_goods_info gi','g.id = gi.goods_id','left')
            ->field('g.id,g.name,g.keyword,g.sn,g.code,g.type,g.brand_id,g.category_id,g.jewelry_id,g.thumb,g.thumb1,g.agent_id,g.remark,g.price,g.price_seller,g.stock_status,g.stock_unit,g.stock_num,g.product_status,g.trader_status,g.create_time,g.create_user,g.update_time,g.update_user,g.attrStr,g.tagStr,g.isscore,gi.id goods_info_id,gi.content')
            ->where($where)
            ->order($order)
            ->find();
        return $vo;
    }

    //zwx 获取戒托 钻石 商品详情
    public function getGoodsDiamondInfoJoinGd($param){

        $agent_id = get_agent_id();
        $where = logic('DataProcessing')->get_goods_product_status($agent_id);
        $where.= ' and g.id = '.$param['id'];

        $info = M('goods')->alias('g')
        ->join('zm_goods_diamond gd','g.id = gd.goods_id','left')
        ->join('zm_goods_trader gt','gt.goods_id = g.id and gt.agent_id = '.$agent_id,'left')
        ->field('g.*,gd.weight,gd.global_price,gd.dia_discount,gt.status')
        ->where($where)
        ->find();
        $info = logic('PriceCalculation')->goods_price([$info]);
        return $info[0];
    }

    /** 
     * zwx
     * @param      
     * @return array 组装商品详情页数据
     */
    public function getComInfo($id,$agent_id){
        if(!is_numeric($id)||$id<=0||!$agent_id) return ;

        //获取上架条件
        $where = logic('DataProcessing')->get_goods_product_status($agent_id);
        //商品ID
        $where.= ' and g.id = '.$id;

        $Goods = M('Goods')->alias('g')
            ->join('zm_goods_info gi','g.id = gi.goods_id','left')
            ->join('zm_goods_trader gt','gt.goods_id = g.id and gt.agent_id ='.$agent_id,'left')
            ->field('g.id,g.supply_goods_id,g.supply_goods_type,g.name,g.keyword,g.sn,g.code,g.type,g.brand_id,g.category_id,g.jewelry_id,g.thumb,g.agent_id,g.remark,g.price,g.price_seller,g.stock_status,g.stock_unit,g.stock_num,g.product_status,g.trader_status,g.create_time,g.create_user,g.update_time,g.update_user,g.attrStr,g.tagStr,gi.id goods_info_id,gi.content,gt.status')
            ->where($where)->find();

        //goods表 product_status=1 上架  goods表 type 012 钻石没有上下架 goods表 type 34 戒托如果不是自己的戒托判断分销表goods_trader表 status=1 为上架 
        if(!$Goods||(($Goods['type']==4||$Goods['type']==3)&&$agent_id!=$Goods['agent_id']&&$Goods['status']!=1)) return ;

        if($agent_id>0){
            //售后服务
            $Goods['ArticleDefaultType3'] = M('article_default')->where(['agent_id'=>$agent_id,'type'=>3,'is_show'=>1])->select();
        }

        $where = [];
        $where['goods_id'] = $Goods['id'];
        //详情轮播图片
        $Goods['GoodsImages'] = D('GoodsImages')->getList($where);
        //详情轮播视频
        $Goods['GoodsVideos'] = D('GoodsVideos')->getInfo($where);
        
        switch ($Goods['type']) {
            case 3:
            case 4:
                $Goods = $this->GoodsSkuDetailCom($Goods);
                //获取定制成品商品详情参数显示
                $Goods['GoodsAssociateAttr'] = logic('Goods')->getGoodsAssociateAttrShow($Goods['id']);
                break;
            case 0:
            case 1:
            case 2:
                //获取钻石信息
                $Goods['GoodsDiamond'] = $this->getGoodsDiamondByIds([$Goods['id']])[0];
                //钻石价格计算参数赋值
                $Goods['global_price'] = $Goods['GoodsDiamond']['global_price'];
                $Goods['weight'] = $Goods['GoodsDiamond']['weight'];
                $Goods['dia_discount'] = $Goods['GoodsDiamond']['dia_discount'];
                //获取钻石商品详情参数显示
                $Goods['GoodsAssociateAttr'] = logic('Goods')->getGoodsDiamondAssociateAttrShow($Goods['GoodsDiamond']);
                break;
        }

        //销售价格计算
        $Goods = logic('PriceCalculation')->goods_price([$Goods])[0];
        //下单通调用接口获取详情数据
        if($Goods['type']==4&&$Goods['supply_goods_id']>0){
            $Openzm = logic('Openzm')->get_customize_detail($Goods['supply_goods_id']);
            if($Openzm['code']==100200){
                $Goods['GoodsXdt'] = $Openzm['data'];
            }else{
                return;
            }
        }
        
        if(!$Goods) return ;
        return $Goods;
    }

    //zwx 商品详情数据组装
    public function GoodsSkuDetailCom($Goods){
        $where['goods_id'] = $Goods['id'];
        $Goods['GoodsSku'] = $this->getGoodsSkuList($where);
        // $Goods['GoodsSkuCeshi'] = $Goods['GoodsSku'];
        if(!$Goods['GoodsSku']) return $Goods;

        //成品规格
        if($Goods['type'] == 3){ 
            $Goods['GoodsSku_K'] = convert_arr_key($Goods['GoodsSku'],'attributes');
            foreach ($Goods['GoodsSku_K'] as $k => $v) {
                //为了计算加点
                $Goods['GoodsSku_K'][$k]['agent_id'] = $Goods['agent_id'];
                $Goods['GoodsSku_K'][$k]['type'] = $Goods['type'];
                $Goods['GoodsSku_K'][$k]['supply_goods_id'] = $Goods['supply_goods_id'];
            }
            
            //计算sku加点
            $Goods['GoodsSku_K'] = logic('PriceCalculation')->goods_price($Goods['GoodsSku_K'],['price'=>'goods_price']);

            $Goods['GoodsSku_K_Arr_1'] = array_keys($Goods['GoodsSku_K']);
            foreach ($Goods['GoodsSku_K_Arr_1'] as $k => $v) {
                $Goods['GoodsSku_K_Arr_2'][$v] = explode('^',$v);
            }
            $GoodsSku_id = [];
            $GoodsSku_attr_id = [];
            $GoodsSku_attr_value_id = [];
            foreach ($Goods['GoodsSku'] as $k => $v) {
                $attributes = explode('^', $v['attributes']);
                $Goods['GoodsSku'][$k]['attributesArr'] = $attributes;
                foreach ($attributes as $k1 => $v1) {
                   $attributes1 = explode(':', $v1);
                   if(!in_array($attributes1[0], $GoodsSku_attr_id)){
                        $GoodsSku_attr_id[] = $attributes1[0];
                   }
                   if(!in_array($attributes1[1], $GoodsSku_attr_value_id)){
                        $GoodsSku_attr_value_id[] = $attributes1[1];
                   }

                   $GoodsSku_id[$attributes1[0]][] =$attributes1[1]; 
                   $GoodsSku_id[$attributes1[0]] = array_unique($GoodsSku_id[$attributes1[0]]);
                }
            }

            $ga_list = M('goods_attr')->where(['id'=>['in',implode(',', $GoodsSku_attr_id)]])->order('system_code')->select();
            $ga_list = convert_arr_key($ga_list,'id');
            $gav_list = M('goods_attr_value')->where(['id'=>['in',implode(',', $GoodsSku_attr_value_id)]])->select();
            $gav_list = convert_arr_key($gav_list,'id');
            
            ksort($GoodsSku_id);
            $GoodsSkuArr = [];
            foreach ($GoodsSku_id as $k => $v) {
                $GoodsSkuArr[$k] = $ga_list[$k];
                foreach ($v as $k1 => $v1) {
                    $gav_list[$v1]['id_vid'] = $GoodsSkuArr[$k]['id'].':'.$gav_list[$v1]['id'];
                    $GoodsSkuArr[$k]['goods_attr_value'][] = $gav_list[$v1];
                }
            }
            $Goods['GoodsSkuArr'] = $GoodsSkuArr;
        }

        //定制规格
        if($Goods['type'] == 4){
            $Goods['GoodsSku_K'] = convert_arr_key($Goods['GoodsSku'],'attributes');

            foreach ($Goods['GoodsSku_K'] as $k => $v) {
                //为了计算加点
                $Goods['GoodsSku_K'][$k]['agent_id'] = $Goods['agent_id'];
                $Goods['GoodsSku_K'][$k]['type'] = $Goods['type'];
                $Goods['GoodsSku_K'][$k]['supply_goods_id'] = $Goods['supply_goods_id'];
            }

            //计算sku加点
            $Goods['GoodsSku_K'] = logic('PriceCalculation')->goods_price($Goods['GoodsSku_K'],['price'=>'goods_price']);

            $Goods['GoodsSku_K_Arr_1'] = array_keys($Goods['GoodsSku_K']);
            foreach ($Goods['GoodsSku_K_Arr_1'] as $k => $v) {
                $Goods['GoodsSku_K_Arr_2'][$v] = explode('^',$v);
            }
            $GoodsSku_id = [];
            $GoodsSku_attr_id = [];
            $GoodsSku_attr_value_id = [];
            foreach ($Goods['GoodsSku'] as $k => $v) {

                $attributes = explode('^', $v['attributes']);
                $Goods['GoodsSku'][$k]['attributesArr'] = $attributes;
                foreach ($attributes as $k1 => $v1) {
                   $attributes1 = explode(':', $v1);
                   if(!in_array($attributes1[0], $GoodsSku_attr_id)){
                        $GoodsSku_attr_id[] = $attributes1[0];
                   }
                   if(!in_array($attributes1[1], $GoodsSku_attr_value_id)){
                        $GoodsSku_attr_value_id[] = $attributes1[1];
                   }

                   $GoodsSku_id[$attributes1[0]][] =$attributes1[1]; 
                   $GoodsSku_id[$attributes1[0]] = array_unique($GoodsSku_id[$attributes1[0]]);
                }
            }

            $ga_list = M('goods_attr')->where(['id'=>['in',implode(',', $GoodsSku_attr_id)]])->order('system_code')->select();
            $ga_list = convert_arr_key($ga_list,'id');
            $gav_list = M('goods_attr_value')->where(['id'=>['in',implode(',', $GoodsSku_attr_value_id)]])->select();
            $gav_list = convert_arr_key($gav_list,'id');
            
            ksort($GoodsSku_id);
            $GoodsSkuArr = [];
            foreach ($GoodsSku_id as $k => $v) {
                $GoodsSkuArr[$k] = $ga_list[$k];
                foreach ($v as $k1 => $v1) {
                    $gav_list[$v1]['id_vid'] = $GoodsSkuArr[$k]['id'].':'.$gav_list[$v1]['id'];
                    $GoodsSkuArr[$k]['goods_attr_value'][] = $gav_list[$v1];
                }
            }
            $Goods['GoodsSkuArr'] = array_values($GoodsSkuArr);
            $Goods['GoodsDiamondMatching'] = M('goods_diamond_matching')->where(['goods_id'=>$Goods['id']])->select();
        }

        return $Goods;
    }

    /** 
     * zwx,,upt by wxh 用视图代替了goods表，tp分页
     * @param array     $param 搜索条件
     * @return array
     */
    public function getGoodsPage($param,$list_rows=15,$order='id desc'){
        $where = [];
        
        if(isset($param['id'])){
            $where['id'] = is_array($param['id'])?['in',implode(',',$param['id'])]:$param['id'];
        }
        
        //这个只读珠宝，应该去掉这个条件
        if(isset($param['type'])){
            $where['type'] = is_array($param['type'])?['in',implode(',',$param['type'])]:$param['type'];
        }
        isset($param['name'])?$where['name'] = ['like','%'.$param['name'].'%']:'';
        isset($param['code'])?$where['code'] = ['like','%'.$param['code'].'%']:'';
        isset($param['jewelry_id'])?$where['category_id|jewelry_id'] = $param['jewelry_id']:'';
        isset($param['agent_id'])?$where['agent_id'] = $param['agent_id']:'';
        $where['isdel'] = 0;

        $list = Db::table('vgoods')
                ->field('*')
                ->where($where)
                ->order($order)
                ->paginate($list_rows,false,['query'=>$param]);
             
        return $list;
    }
    
    /**
     * wxh 获取钻石列表，tp分页
     * @param array     $param 搜索条件
     * @return array
     */
    public function getDiamondPage($param,$list_rows=15,$order='id desc'){
        $where = [];    
	
        $agent_id=$param['agent_id'];
        
        $certificate_number=$param['certificate_number'];
        $goods_name=$param['goods_name'];
        $luozuanCate=$param['luozuanCate'];
        $type=$param['type'];
        $clarity=$param['clarity'];
        $cut=$param['cut'];
        $min_weight=$param['min_weight'];
        $max_weight=$param['max_weight'];
        $product_status=$param['product_status'];
        
		if($certificate_number){ $where["certificate_number"] = $certificate_number; }
		if($goods_name){ $where["goods_name"] = $goods_name; }
		if($luozuanCate){ $where["goods_name"] = array("like","%".$luozuanCate."%"); }
		if($type !== ""){ $where["type"] = $type; }
		if($clarity){ $where["clarity"] = $clarity; }
		if($cut){ $where["cut"] = $cut; }
		if($min_weight && $max_weight){
			$where['weight']	= array(array('egt',$min_weight),array('elt',$max_weight));
		}else if($min_weight){
			$where['weight']	= array('egt',$min_weight);
		}else if($max_weight){
			$where['weight']	= array('elt',$max_weight);
		}
		if($product_status !== ""){
			$where["product_status"] = $product_status;
		}
		$where["agent_id"] = $agent_id;
    
        $list = Db::table('vdiamond')
        ->field('*')
        ->where($where)
        ->order($order)
        ->paginate($list_rows,false,['query'=>$param]);
         //echo Db::table('vdiamond')->getlastsql();exit();
        return $list;
    }   
    
    
    /**
     * fire
     * 获取珠宝详情
     * @param $id good表id   
     * @return array
     */
    public function getGoodsDetail($id){
        $data= $this->alias('g')
        ->join('zm_goods_info gi','g.id = gi.goods_id','left')
        ->field('g.*,gi.id goods_info_id,gi.content,gi.lease_status,gi.lease_price,lease_percentage')          
        ->find($id);

        $map['goods_id']=$id;
        if ($data['type'] == 4) { //定制珠宝选取主石匹配
            $data['GoodsDiamondMatching'] = M('goods_diamond_matching')->where($map)->select();
        }
        $data['image']=M('goods_images')->where($map)->select();
        $data['videos']=M('goods_videos')->where($map)->select();        
        return $data;
    }
    
    /**
     * fire
     * 获取珠石详情
     * @param $id good表id
     * @return array
     */
    public function getDiamondDetail($id){
        $data= $this->alias('g')
        ->join('zm_goods_diamond gi','g.id = gi.goods_id','left')
        ->field('gi.*,g.stock_num,g.stock_debit,g.thumb,g.isscore,g.price,g.price_seller')
        ->find($id);
        $map['goods_id']=$id;
        $data['image']=M('goods_images')->where($map)->select();
        $data['videos']=M('goods_videos')->where($map)->select();
        return $data;
    }
    
    
    /**
     * fire
     * 综合查询成品数据，前台使用,如果pagesize>200不分页
     * @param $agent_id 代理商编号
     * @param $where 综合查询参数
     * @return array
     */
    public function getGoodsTrader(
            $agent_id,$category_id,
            $keyword,$attrWhere,
            $page=1,$pagesize=15,$orderby,$iscount=1){
        
        $start=($page-1)*$pagesize;  
        $where=' where product_status=1 and agent_id='.$agent_id;
        $where=$where.' and g.type=3 '; //成品
        if($category_id){
            $where.=' and (category_id='.$category_id.' or jewelry_id='.$category_id.')';
        }
        if($keyword){
            $where.=" and (name like '%$keyword%' or keyword like '%$keyword%' or tagStr like '%$keyword%')";
        }        
        
        $total=0;      
        if(empty($attrWhere)){
            $sql='select g.*  FROM vgoods g '.$where;
        }else{
            $sql='select g.*  FROM vgoods g JOIN('.$attrWhere.') t ON g.id=t.goods_id'.$where;
        }  
        
        if($iscount){
           $listcount=Db::query("select count(*) cc from(".$sql.') tt');
           $total=$listcount[0]['cc'];
        }
        $list= Db::query($sql." limit $start,$pagesize"); 
        if(empty($total)){
            $total=count($list);
        }
        
        $result['status']=100;
        $result['msg']='成功';
        $result['page']=$page;
        $result['size']=$pagesize;
        $result['count']=$total;
        $result['data']=$list;
        return $result;
    }
    
    /**
     * fire,快速查询
     * 综合查询成品数据，前台使用,如果pagesize>200不分页
     * @param $agent_id 代理商编号
     * @param $where 综合查询参数
     * @return array
     */
    public function getGoodsTraderFast(
        $agent_id,$category_id,
        $keyword,$attrWhere,
        $page=1,$pagesize=15,$orderby,$iscount=1){
    
            $start=($page-1)*$pagesize;
            $where=' where a.type=3 and a.product_status=1 and IFNULL(b.agent_id,a.agent_id)='.$agent_id;          
            if($category_id){
                $where.=' and (a.category_id='.$category_id.' or a.jewelry_id='.$category_id.')';
            }
            if($keyword){
                $where.=" and (a.name like '%$keyword%' or a.keyword like '%$keyword%' or a.tagStr like '%$keyword%')";
            }            

            if($orderby){
                $where=$where." order by a.".$orderby;
            }
    
            $total=0;    
            if($iscount){                
                $sqlcount="select count(*)  
                            from zm_goods a
                            LEFT JOIN zm_goods_trader b ON a.id=b.goods_id".$where;
                if(!empty($attrWhere)){
                    $sqlcount="select count(*)
                            from zm_goods a
                            JOIN(".$attrWhere.") t ON a.id=t.goods_id
                            LEFT JOIN zm_goods_trader b ON a.id=b.goods_id".$where;
                }                
                $datacount=Db::query($sqlcount);
                $total= $datacount[0]['c'];
            }
            
            //查询符合条件的goods_id数据
            $sqlIds="select GROUP_CONCAT(a.id) ids
            from zm_goods a           
            LEFT JOIN zm_goods_trader b ON b.goods_id=b.id $where limit $start,$pagesize";
            if(!empty($attrWhere)){
                $sqlIds="select GROUP_CONCAT(a.id) ids
                from zm_goods a
                JOIN(".$attrWhere.") t ON a.id=t.goods_id
                LEFT JOIN zm_goods_trader b ON b.goods_id=b.id $where limit $start,$pagesize";
            }            
            $dataids=Db::query($sqlIds);
            
            if(!empty($dataids)){
                $ids=$dataids[0]['ids'];
                
                $sql=" select	
                    `a`.`id` AS `id`,
                	`a`.`name` AS `name`,
                	`a`.`keyword` AS `keyword`,
                	`a`.`sn` AS `sn`,
                	`a`.`code` AS `code`,
                	`a`.`type` AS `type`,
                	`a`.`brand_id` AS `brand_id`,
                	`a`.`category_id` AS `category_id`,
                	`a`.`jewelry_id` AS `jewelry_id`,
                	`a`.`thumb` AS `thumb`,
                	`a`.`agent_id` AS `p_agent_id`,
                	`a`.`agent_id` AS `agent_id`,
                	`a`.`remark` AS `remark`,
                	`a`.`price` AS `price`,                	
                	`a`.`stock_status` AS `stock_status`,
                	`a`.`stock_unit` AS `stock_unit`,
                	`a`.`stock_num` AS `stock_num`,
                	`a`.`product_status` AS `product_status`,
                	`a`.`trader_status` AS `trader_status`,
                	`a`.`create_time` AS `create_time`,
                	`a`.`create_user` AS `create_user`,
                	`a`.`update_time` AS `update_time`,
                	`a`.`update_user` AS `update_user`,                	
                	ifnull(	`a`.`price_seller`,`a`.`price`*(c.rate+c.point)/100 AS  price_seller
                    from zm_goods a  ";
                
                if(!empty($attrWhere)){
                    $sql=$sql." JOIN(".$attrWhere.") t ON a.id=t.goods_id ";
                }
                $sql=$sql." LEFT JOIN zm_goods_trader c ON c.goods_id=a.id
                            LEFT JOIN zm_trader dd ON dd.id=c.trader_id
                            LEFT JOIN zm_trader_price e ON e.goods_type=a.type AND e.trader_id=dd.id ".$where." and a.id in($ids)";
                
                $list= Db::query($sql);
                if(empty($total)){
                    $total=count($list);
                }
            }
    
            $result['status']=100;
            $result['msg']='成功';
            $result['page']=$page;
            $result['size']=$pagesize;
            $result['count']=$total;
            $result['data']=$list;
            return $result;
    }
    
    
    /**
     * fire
     * 综合查询钻石数据，前台使用
     * @param $agent_id 代理商编号
     * @param $param 综合查询参数
     * @return array
     */
    public function getDiamondTrader($agent_id,$where,$keyword,
        $page=1,$pagesize=15,$orderby,$iscount=1){
        
        $where['agent_id']=$agent_id;  
        $where['product_status']=1;
        if($keyword){
            $where['certificate_number']=['like','%'.$keyword.'%'];
        }
        $start=($page-1)*$pagesize;        
        $total=0;
        $field="*";
        if($iscount){
            $total= Db::table('vdiamond')->where($where)->count();
        }       
        
        $list= Db::table('vdiamond')->where($where)->field($field)->order($orderby)->limit($start,$pagesize)->select();
        
        if(empty($total)){
            $total=count($list);
        }        
        //视图已经计算价格
        //$list = logic('PriceCalculation')->goods_price($list);
        
        $result['status']=100;
        $result['msg']='成功';
        $result['page']=$page;
        $result['size']=$pagesize;
        $result['count']=$total;
        $result['data']=$list;
        return $result;
    }
    
    
    /**
     * fire,快速查询
     * 综合查询钻石数据，前台使用
     * @param $agent_id 代理商编号
     * @param $param 综合查询参数
     * @return array
     */
    public function getDiamondTraderFast($agent_id,$where,$keyword,
        $page=1,$pagesize=15,$orderby,$iscount=1){
        
        $where=$where." and IFNULL(c.agent_id,b.agent_id)=$agent_id";  
        $where=$where." and b.product_status=1";       
        if($keyword){          
            $where=$where." and b.certificate_number like '%'.$keyword.'%'";
        }
        
        if($orderby){
             $where=$where." order by a.".$orderby;
        }
        
        $start=($page-1)*$pagesize;        
        $total=0;
        $field="*";
        if($iscount){            
            $sqlcount="select count(*) c 
            from zm_goods_diamond a
            JOIN zm_goods b on a.goods_id=b.id
            LEFT JOIN zm_goods_trader c ON c.goods_id=b.id ".$where;      
            
            //echo $sqlcount;exit();
            
            $datacount=Db::query($sqlcount);
            $total= $datacount[0]['c'];
        }       
        
        //查询符合条件的goods_id数据
        $sqlIds="select GROUP_CONCAT(b.id) ids
            from zm_goods_diamond a
            JOIN zm_goods b on a.goods_id=b.id
            LEFT JOIN zm_goods_trader c ON c.goods_id=b.id $where limit $start,$pagesize";        
        
        $dataids=Db::query($sqlIds);
        
        //根据goods_id数据去获取详细数据
        if(!empty($dataids)){
            $ids=$dataids[0]['ids'];            
            $sql="select 
            	`d`.`id` AS `id`,
            	`d`.`supply_id` AS `supply_id`,
            	`d`.`supply_gid` AS `supply_gid`,
            	`d`.`goods_id` AS `goods_id`,
            	`d`.`goods_name` AS `goods_name`,
            	`d`.`certificate_type` AS `certificate_type`,
            	`d`.`certificate_number` AS `certificate_number`,
            	`b`.`type` AS `type`,
            	`d`.`intensity` AS `intensity`,
            	`d`.`location` AS `location`,
            	`d`.`quxiang` AS `quxiang`,
            	`d`.`shape` AS `shape`,
            	`d`.`color` AS `color`,
            	`d`.`clarity` AS `clarity`,
            	`d`.`cut` AS `cut`,
            	`d`.`polish` AS `polish`,
            	`d`.`symmetry` AS `symmetry`,
            	`d`.`fluor` AS `fluor`,
            	`d`.`milk` AS `milk`,
            	`d`.`coffee` AS `coffee`,
            	`d`.`belongs` AS `belongs`,
            	`d`.`dia_table` AS `dia_table`,
            	`d`.`dia_depth` AS `dia_depth`,
            	`d`.`dia_size` AS `dia_size`,
            	`d`.`global_price` AS `global_price`,
            	`d`.`dia_discount` AS `dia_discount`,	
            	`d`.`weight` AS `weight`,
            	`d`.`goods_number` AS `goods_number`,
            	`d`.`address` AS `address`,
            	`d`.`update_time` AS `update_time`,
                `d`.`inventory_type` AS `inventory_type`,
                `i`.`dollar_huilv` AS `dollar_huilv`,
            	`b`.`price` AS `price`,
            	`b`.`price_seller` AS `price_seller`,
            	`b`.`stock_num` AS `stock_num`,
            	`b`.`thumb` AS `thumb`,
            	`b`.`agent_id` AS `agent_id`,	
                dd.t_agent_id as p_agent_id,
            	`b`.`product_status` AS `product_status`,
            	`d`.`global_price`*(`d`.`dia_discount`+IFNULL(e.rate+e.point,0))/100*`d`.`weight`* ifnull(`i`.`dollar_huilv`, 1) as goods_price
            from zm_goods_diamond d
            JOIN zm_goods b on d.goods_id=b.id
            JOIN zm_agent_info i on i.agent_id=b.agent_id
            LEFT JOIN zm_goods_trader c ON c.goods_id=b.id
            LEFT JOIN zm_trader dd ON dd.id=c.trader_id
            LEFT JOIN zm_trader_price e ON e.goods_type=b.type AND e.trader_id=dd.id where b.id in($ids)";  
            
            $list= Db::query($sql);
            if(empty($total)){
                $total=count($list);
            }
        }
        
        $result['status']=100;
        $result['msg']='成功';
        $result['page']=$page;
        $result['size']=$pagesize;
        $result['count']=$total;
        $result['data']=$list;
        return $result;
    }
    
    
    
    /**
     * fire
     * 根据分类查询商品列表
     * @param $agent_id 代理商编号
     * @param $cat_id 分类编号
     * @return array
     */
    public function getListTraderByCat($agent_id,$cat_id,$page=1,$pagesize=15,$iscount=0,$orderby="id desc"){  
        $start=($page-1)*$pagesize;
        $where['agent_id']=$agent_id;
        $where['product_status']=1;
        $where['type'] = 3; //成品
        if($cat_id){
            $where['category_id|jewelry_id']=$cat_id;
        }
        $list=Db::table('vgoods')
        ->where($where)   
        ->order($orderby)
        ->limit($start,$pagesize)        
        ->select();
        
        if($iscount){
            $total=Db::table('vgoods')
            ->where($where)  
            ->count();
            
            $result['status']=100;
            $result['msg']='成功';
            $result['page']=$page;
            $result['size']=$pagesize;
            $result['count']=$total;
            $result['data']=$list;
            return $result;
        }
        
        
        return $list;
    }
        
    /**
     * fire
     * 根据标签查询商品列表
     * @param $agent_id 代理商编号
     * @param $tag 标签名称
     * @return array
     */
    public function getListTraderByTag($agent_id,$tag,$page=1,$pagesize=15){ 
        $start=($page-1)*$pagesize;
        $where = "(a.type=3 or a.type=4) ";
        $where .= ' and a.product_status=1 and a.isdel=0';
        if ($agent_id) {
            $where .= ' and IFNULL(b.agent_id,a.agent_id)=' . $agent_id;
        }       
        if ($tag) {
            $where .= " and (a.tagStr like '%$tag%')";
        }
        
        $sql = "select `a`.*,p.point,p.point_my
        from zm_goods a
        LEFT JOIN zm_trader_price p ON p.goods_type=a.type AND p.agent_id=$agent_id
        LEFT JOIN zm_goods_trader b ON b.goods_id=a.id and b.agent_id=$agent_id WHERE " . $where . " limit $start,$pagesize";        
        //dump($sql);exit();
        $list = Db::query($sql);
        return $list;
    }
    
    
    /**
     * fire
     * 根据goods_id数组查询钻石数据,用于比较
     * @param array     $ids goods_id列表
     * @return array
     */
    public function getGoodsDiamondByIds($ids){
        $list=M('goods_diamond')
            ->alias('d')
            ->join('goods g','d.goods_id=g.id')
            ->field("d.id,d.goods_id,d.goods_name,d.goods_price,d.global_price,d.dia_discount,d.weight,d.shape,d.cut,d.color,d.clarity,d.symmetry,d.dia_depth,d.dia_table,d.dia_size,d.polish,d.milk,d.coffee,d.fluor,g.thumb,g.type,g.supply_id,g.supply_goods_id,g.supply_goods_type,g.agent_id,d.certificate_number,d.certificate_type,d.location,d.inventory_type")
            ->whereIn("g.id",implode(',',$ids))
            ->select();        
        return $list;
    }
    
    /**
     * fire
     * 获取一个商品的规格属性信息
     * @param id     goods_id
     * @return array
     */
    public function getGoodsSkuById($goods_id){        
        $skulist = M('goods_sku')
        ->field('id,goods_number,goods_price,material_mass,attributes,name')
        ->where("goods_id",$goods_id)
        ->limit(500)
        ->select();        
        return $skulist;
    }
    

    /**
     * fire
     * 获取一个商品的属性信息，
     * @param id     goods_id
     * @param 属性查询对象，数组$attrparam如is_main=1 and is_sku=0
     * @return array
     */
    public function getGoodsAttrById($goods_id,$is_main=0,$is_sku=0){
        
        if($is_main){
            $where['attr.is_main']=1;
        }
        if($is_sku){
            $where['attr.is_sku']=1;
        }
        
        $mainattrlist = M('goods_associate_attr')
        ->alias('ga')
        ->join('zm_goods_attr attr attr',' ga.attr_id=attr.id')
        ->join('zm_goods_attr_agent attragent','attragent.attr_id=attr.id','Left')
        ->join('zm_goods_attr_value attrvalue',' ga.attr_value_id=attrvalue.id')
        ->field('ga.goods_id,ga.attr_id,ga.attr_value_id,attr.`name` attrname ,attrvalue.`name` attrvaluename')
        ->where("ga.goods_id",$goods_id)
        ->where('( ISNULL(attragent.is_use) or attragent.is_use=0)')
        ->where($where)
            ->order('ga.attr_id asc')
        ->select();

        //$sql = Db::table('goods_associate_attr')->getLastSql();
        //取分类值  guihongbing 20180202
        $attr_id_list = convert_arr_key($mainattrlist,'attr_id');
        $associate_attr = array();
        foreach($attr_id_list as $key => $val){
            foreach($mainattrlist as $k => $v){
                if($v['attr_id'] == $val['attr_id']){
                    $arr['goods_id'] = $v['goods_id'];
                    $arr['attr_id'] = $v['attr_id'];
                    $arr['attr_value_id'] = $v['attr_value_id'];
                    $arr['attrname'] = $v['attrname'];
                    $arr['attrvaluename'] = $v['attrvaluename'];
                    $attr_id_list[$key]['sub_attr_id'][] = $arr;
                }
            }
            unset($attr_id_list[$key]['attr_value_id']);
            unset($attr_id_list[$key]['attrvaluename']);
        }
    //     $sql = M('goods_associate_attr')->getlastsql();
        return $attr_id_list;
    }
    

    /** 
     * zwx
     * @param array     $param 搜索条件
     * @return array
     */
    public function getGoodsSkuInfo($param){
        $where = [];
        
        if(isset($param['id'])){
            $where['id'] = is_array($param['id'])?['in',implode(',',$param['id'])]:$param['id'];
        }
        isset($param['agent_id'])?$where['agent_id'] = $param['agent_id']:'';
        isset($param['attributes'])?$where['attributes'] = $param['attributes']:'';
        isset($param['goods_id'])?$where['goods_id'] = $param['goods_id']:'';
        $vo = M('goods_sku')
                ->field('id,sn,name,goods_id,goods_number,goods_price,material_mass,remark,attributes,agent_id,unforgecode')
                ->where($where)
                ->find();
        return $vo;
    }

    /** 
     * zwx
     * @param array     $param 搜索条件
     * @return array
     */
    public function getGoodsSkuList($param,$order='id desc'){
        $where = [];
        
        if(isset($param['id'])){
            $where['id'] = is_array($param['id'])?['in',implode(',',$param['id'])]:$param['id'];
        }
        isset($param['agent_id'])?$where['agent_id'] = $param['agent_id']:'';
        isset($param['goods_id'])?$where['goods_id'] = $param['goods_id']:'';
        $list = M('goods_sku')
                ->field('id,sn,name,goods_id,goods_number,goods_price,material_mass,remark,attributes,agent_id,unforgecode')
                ->where($where)
                ->order($order)
                ->select();
        return $list;
    }

    public function getGoodsCode(){
        return date('Ymdhis',time()).rand(10000, 99999);//code 速易宝标准编码
    }

	/** 
	 * zwx
	 * @param array     $data 要保存的数据 $act add,edit $param修改条件
	 * @return 
	 */
    public function setGoods($data,$act,$param='')
    {
        $save = [];
        isset($data['agent_id'])?$save['agent_id'] = $data['agent_id']:'';
        isset($data['name'])?$save['name'] = $data['name']:'';//name 商品名称
        isset($data['keyword'])?$save['keyword'] = $data['keyword']:'';//keyword 搜索关键词
        isset($data['thumb'])?$save['thumb'] = $data['thumb']:'';//thumb 缩略图
        isset($data['thumb1'])?$save['thumb1'] = $data['thumb1']:'';//thumb 缩略图2
        isset($data['product_status'])?$save['product_status'] = $data['product_status']:'';//product_status 上架状态，0为下架，1为上架
        isset($data['type'])?$save['type'] = $data['type']:'';//type 商品类型
        isset($data['stock_status'])?$save['stock_status'] = $data['stock_status']:'';//stock_status 库存类型
        isset($data['stock_num'])?$save['stock_num'] = $data['stock_num']:'';//stock_num 库存数量
        isset($data['tagStr'])?$save['tagStr'] = implode(',', $data['tagStr']):'';//tagStr 标签 热卖 新品
        if(isset($data['isscore'])){ //是否开启积分奖励
            $data['isscore'] == 1?$save['isscore'] = 1:$save['isscore'] = 0;
        }
        if(isset($data['price'])){
            $data['price']>0?$save['price'] = $data['price']:$save['price'] = 0; //价格
        }
        
        if(isset($data['jewelry_id'])){
            $GoodsCategory = D('GoodsCategory')->getInfo(['id'=>$data['jewelry_id']]);
            $save['category_id'] = $GoodsCategory['pid'];//category_id 分类
            $save['jewelry_id'] = $GoodsCategory['id'];//jewelry_id 款式
        }

        $where = [];
        if(isset($param['id'])){
            $where['id'] = is_array($param['id'])?['in',implode(',',$param['id'])]:$param['id'];
        }

        if($act == 'edit' && $where){ //save
            $save['update_time'] = date('Y-m-d H:i:s',time());//create_time 更新时间
            $save['update_user'] = session('admin_id');//update_user 更新人
            
            $bool =  M('Goods')->where($where)->update($save);
            $this->afterGoods($where['id'],$data);
            return $bool;
        }else if($act == 'add'){ //add
            $save['code'] = $this->getGoodsCode();//code 速易宝标准编码
            $save['create_time'] = date('Y-m-d H:i:s',time());//create_time 创建时间
            $save['create_user'] = session('admin_id');//create_user 创建人

            $id = M('Goods')->insertGetId($save);
            $this->afterGoods($id,$data);
            return $id;
        }
		//sn 供应商编码
		$save['sn'] = $data['sn']?$data['sn']:'';
		//brand_id 品牌
		$save['brand_id'] = $data['brand_id']?$data['brand_id']:'';
		//remark 摘要
		$save['remark'] = $data['remark']?$data['remark']:'';
		//price_seller 销售价
		$save['price_seller'] = $data['price_seller']?$data['price_seller']:'';
		//stock_unit 库存单位
		$save['stock_unit'] = $data['stock_unit']?$data['stock_unit']:'';
		//trader_status 分销状态
		$save['trader_status'] = $data['trader_status']?$data['trader_status']:'';
        //attrStr 冗余属性,暂不用
        $save['attrStr'] = '';
    }

    /** 
     * zwx
     * @param array     $param 删除条件
     * @return array
     */
    public function delGoods($param){
        $where = [];
        if(isset($param['id'])){
            $where['id'] = is_array($param['id'])?['in',implode(',',$param['id'])]:$param['id'];
        }
        isset($param['agent_id'])?$where['agent_id'] = $param['agent_id']:'';
        if(!empty($where['id'])&&$where['agent_id']){ //删除指定传入id

            Db::startTrans();
            $bool[] = M('Goods')->where($where)->update(['product_status'=>0,'isdel'=>1]);
            
            //暂不做物理删除
            // $bool[] = M('Goods')->where($where)->delete();

            // $where_after['goods_id'] = $where['id'];
            // $where_after['agent_id'] = $where['agent_id'];
            // if(M('goods_videos')->where($where_after)->find()){
            //     $bool[] = D('GoodsVideos')->delGoodsVideos($where);
            // }
            // if(M('goods_images')->where($where_after)->find()){
            //     $bool[] = D('GoodsImages')->delGoodsImages($where);
            // }
            // if(M('goods_associate_attr')->where($where_after)->find()){
            //     $bool[] = M('goods_associate_attr')->where($where_after)->delete();
            // }
            // if(M('goods_sku')->where($where_after)->find()){
            //     $bool[] = M('goods_sku')->where($where_after)->delete();
            // }
            // if(M('goods_diamond_matching')->where(['goods_id'=>$where['id']])->find()){
            //     $bool[] = M('goods_diamond_matching')->where(['goods_id'=>$where['id']])->delete();
            // }

            if(in_array(0,$bool)){
                Db::rollback();
                return false;
            }else{
                Db::commit();
                return true;
            }
        }
    }

    //zwx
    public function afterGoods($id,$data){
        if($id && $data['goods_images_id']){//产品图片
            $this->afterGoodsImages($id,['goods_images_id'=>$data['goods_images_id']]);
        }
        if($id && $data['content']){ //产品详情
            $this->afterGoodsInfo($id,['content'=>$data['content']]);
        }
        if($id && $data['goods_videos_id']){//产品视频
            $this->afterGoodsVideos($id,['goods_videos_id'=>$data['goods_videos_id']]);
        }
        if($id && $data['attr_id']){//产品属性
            $where = [];
            $save = [];
            $save_add = [];

            $M_goods_associate_attr = M('goods_associate_attr');
            $where['agent_id'] = $data['agent_id'];
            $where['goods_id'] = $id;
            $list = $M_goods_associate_attr->where($where)->select();

            foreach ($list as $k => $v) { //获取数据库已存在属性
                unset($list[$k]);
                $list[$v['attr_id'].','.$v['goods_id'].','.$v['agent_id'].','.$v['value']] = $v;
            }

            foreach ($data['attr_id'] as $k => $v) { //最新提交属性和数据库属性进行处理
                $k_a = explode(',',$k);
                $list_k = $k_a[0].','.$id.','.$data['agent_id'].','.$v;
                if($list[$list_k]){ //数据库已存在属性 和 提交属性一致 剔除掉已存在属性
                    unset($data['attr_id'][$k]); //剩余 待添加属性
                    unset($list[$list_k]);  //剩余 待删除属性
                }
            }

            //获取 goods_attr_value id
            $where = ' agent_id = 0';
            $where.= ' or agent_id = '.$data['agent_id'];

            $gav_list = M('goods_attr_value')->where($where)->select();
            foreach ($gav_list as $k => $v) {
                unset($gav_list[$k]);
                $gav_list[$v['attr_id'].','.$v['name']] = $v;
            }

            foreach ($data['attr_id'] as $k => $v) { //生成新增数组
                if(!empty(trim($v))){
                    $k_a = explode(',',$k);
                    $save['attr_id'] = $k_a[0];
                    $save['value'] = $v;
                    $save['goods_id'] = $id;
                    $save['agent_id'] = $data['agent_id'];
                    if($gav_list[$k_a[0].','.$v]){
                        $save['attr_value_id'] = $gav_list[$k_a[0].','.$v]['id'];
                    }else{
                        $save['attr_value_id'] = 0;
                    }
                    $save_add[] = $save;
                }
            }

            if($save_add){ //新增
                $M_goods_associate_attr->insertAll($save_add); 
            }

            $id_str = implode(',', array_column($list,'id'));
            if($list){ //删除
                $M_goods_associate_attr->where(['id'=>['in',$id_str]])->delete();
            }
        }
        if($id && $data['GoodsSku_attributes']){//产品规格
            $where = [];
            $save = [];
            $save_add = [];

            $M_goods_sku = M('goods_sku');
            $where['agent_id'] = $data['agent_id'];
            $where['goods_id'] = $id;
            $gs_list = $M_goods_sku->where($where)->select();
            $gs_list = convert_arr_key($gs_list,'attributes'); 
            
            foreach ($data['GoodsSku_goods_price'] as $k => $v) { //最新提交sku处理
                if(empty(trim($v))){ //价格为空 不添加数据
                    unset($data['GoodsSku_attributes'][$k]);
                    unset($data['GoodsSku_goods_number'][$k]);
                    unset($data['GoodsSku_goods_price'][$k]);
                    unset($data['GoodsSku_sn'][$k]);
                    unset($data['GoodsSku_remark'][$k]);
                    unset($data['GoodsSku_material_mass'][$k]);
                    unset($data['GoodsSku_name'][$k]);
                }else{
                    $save['attributes'] = $data['GoodsSku_attributes'][$k];
                    $save['agent_id'] = $data['agent_id'];
                    $save['goods_id'] = $id;
                    $save['goods_number'] = $data['GoodsSku_goods_number'][$k];
                    $save['goods_price'] = $data['GoodsSku_goods_price'][$k];
                    $save['sn'] = $data['GoodsSku_sn'][$k];
                    $save['remark'] = $data['GoodsSku_remark'][$k];
                    $save['material_mass'] = $data['GoodsSku_material_mass'][$k];
                    $save['name'] = $data['GoodsSku_name'][$k];

                    $goods_sku_v = $gs_list[$data['GoodsSku_attributes'][$k]];
                    if($goods_sku_v){ //数据库已存在数据 就更新
                        unset($gs_list[$data['GoodsSku_attributes'][$k]]); // 剩余要删除数据
                        $M_goods_sku->where(['id'=>$goods_sku_v['id']])->update($save);
                    }else{ //数据库不存在 就新增
                        $save_add[] = $save;
                    }
                }
            }

            if($save_add){ //新增
                $M_goods_sku->insertAll($save_add); 
            }

            $id_str = implode(',', array_column($gs_list,'id'));
            if($id_str){ //删除
                $M_goods_sku->where(['id'=>['in',$id_str]])->delete();
            }
            //如果有规格 修改表goods 价格
            $sku_info = $M_goods_sku->where($where)->order('id desc')->find();
            if($sku_info){
                M('goods')->where(['id'=>$id])->update(['price'=>$sku_info['goods_price']]);
            }
        }
        
        if($id && $data['gdm_shape'] && $data['goods_diamond_matching']){ //主石匹配
            $where = [];
            $save = [];
            $save_add = [];
            $M_goods_diamond_matching = M('goods_diamond_matching');

            //获取已添加主石
            $where['goods_id'] = $id;
            $gdm_list = $M_goods_diamond_matching->where($where)->select();
            $gdm_list = convert_arr_key($gdm_list,'attr'); 

            $gdm_list_del = $gdm_list;

            foreach ($data['gdm_shape'] as $k => $v) {
                $save['goods_id'] = $id;
                $save['shape'] = $data['gdm_shape'][$k];
                $save['shape_name'] = $data['gdm_shape_name'][$k];
                $save['weight'] = $data['gdm_weight'][$k];
                $save['weight_type'] = $data['gdm_weight_type'][$k];
                $save['attr'] = $save['shape_name'].' '.$save['weight'].$save['weight_type'];

                if(array_key_exists($save['attr'],$gdm_list)){
                    unset($gdm_list_del[$save['attr']]); //留下要删除数据
                }else{
                    $save_add[$save['attr']] = $save; //新增要添加数据
                }
            }

            if($gdm_list_del){ // 删除
                $gdm_list_del = convert_arr_key($gdm_list_del,'id');
                M('goods_diamond_matching')->where(['id'=>['in',array_keys($gdm_list_del)]])->delete();
            }
            if($save_add){ // 新增
                M('goods_diamond_matching')->insertAll($save_add);
            }
        }else if($data['goods_diamond_matching']){ //删除所有
            M('goods_diamond_matching')->where(['goods_id'=>$id])->delete();
        }
    }

    //zwx
    public function afterGoodsImages($id,$data){
        $saveGoodsImages['goods_id'] = $id;
        D('GoodsImages')->setGoodsImages($saveGoodsImages,'edit',['id'=>$data['goods_images_id']]);
    }

    //zwq
    public function afterGoodsInfo($id,$data){
        $goods_info = M('goods_info')->where(['goods_id'=>$id])->find();
        $save['goods_id'] = $id;
        $save['content'] = $data['content']; 
        if($goods_info){
            M('goods_info')->where(['goods_id'=>$id])->update($save);
        }else{
            M('goods_info')->insertGetId($save);
        }
    }

    //zwq
    public function afterGoodsVideos($id,$data){
        $saveGoodsVideos['goods_id'] = $id;
        $where['id'] = $data['goods_videos_id'];
        $where['goods_id'] = $id;
        if(!D('GoodsVideos')->getInfo($where)){
            D('GoodsVideos')->delGoodsVideos(['goods_id'=>$id]);
            D('GoodsVideos')->setGoodsVideos($saveGoodsVideos,'edit',['id'=>$data['goods_videos_id']]);
        }
        
    }
	

	/**
	 * 获取成品定制列表
	 * zhy	find404@foxmail.com
	 * 2017年11月22日 10:19:12
	 */
    public function GetProductList(){
		return Db::query('select goods_id from zm_goods_associate_attr as zgaa LEFT JOIN zm_goods as zg ON  zg.id = zgaa.goods_id where zg.category_id = '.$this->Where['zg.category_id']
						.' and zg.type = '.$this->Where['zg.type']
						.' and zg.product_status = 1 and ( '.$this->Where['Custom'].') group by goods_id ORDER BY '.$this->Order);
    }	
	
	/**
	 * 获取裸钻列表
	 * zhy	find404@foxmail.com
	 * 2017年11月22日 10:19:12
	 */
    public function GetDiamondList($data){
		return Db::name('goods')
		->alias('zg')
		->join('goods_diamond zgd','zgd.goods_id=zg.id','Left')
		->where($data['Where'])
		->field('zgd.goods_id,zgd.location,zgd.shape,zgd.certificate_number,zgd.weight,zgd.color,zgd.intensity,zgd.cut,zgd.polish,zgd.symmetry,zgd.fluor,zgd.milk,zgd.coffee,zg.price,zgd.global_price,zgd.dia_discount')
		->order($data['Order'])	
		->limit($data['Limit'])
		->group('zg.id')
		->select();
		//echo Db::getLastSql(); 
    }	 

	/**
	 * 获取裸钻总数
	 * zhy	find404@foxmail.com
	 * 2017年11月22日 10:19:12
	 */
    public function GetDiamondListCount($data){
		return Db::name('goods')
		->alias('zg')
		->join('goods_diamond zgd','zgd.goods_id=zg.id','Left')
		->where($data['Where'])
		->field('zgd.goods_id,zgd.location,zgd.shape,zgd.certificate_number,zgd.weight,zgd.color,zgd.intensity,zgd.cut,zgd.polish,zgd.symmetry,zgd.fluor,zgd.milk,zgd.coffee,zg.price,zgd.global_price,zgd.dia_discount')
		->order($data['Order'])
		->group('zg.id')
		->count();
    }

	/**
	 * 获取裸钻详情
	 * zhy	find404@foxmail.com
	 * 2017年11月22日 10:19:12
	 */
    public function GetDiamondInfo($agent_id,$goods_id){
		return Db::name('goods')
		->alias('zg')
		->join('goods_diamond zgd','zgd.goods_id=zg.id','Left')
		->where(['zg.id'		=>$goods_id
				,'zg.type'		=>['in','0,1']
				,'zg.agent_id' =>$agent_id])
		->field('zg.name,zg.thumb,zg.price,zgd.shape,zgd.weight,zgd.color,zgd.clarity')
		->find();
	}	
 
	/**
	 * 获取成品定制列表
	 * zhy	find404@foxmail.com
	 * 2017年11月22日 10:19:12
	 */
    public function GetGoodsList($agent_id,$category_id,$type,$CustomWhere,$order,$orderby,$pageid,$pagesize){
		if($category_id){
			$category  = ' zg.category_id = '.$category_id;
		}else{
			$category  = ' 1 = 1 ';
		}
		return Db::query('select distinct zg.id,zg.name,zg.type,zg.price,zg.thumb  from zm_goods as zg LEFT JOIN zm_goods_associate_attr as zgaa ON  zg.id = zgaa.goods_id  where '
						.$category
						.' and zg.type in ('.$type.')'
						.' and zg.agent_id = '.$agent_id
						.' and zg.product_status = 1 '
						.$CustomWhere
						.' ORDER BY '.$order.' '.$orderby
						.' limit '.$pageid.','.$pagesize);
    }
	
	/**
	 * 获取成品定制列表总数
	 * zhy	find404@foxmail.com
	 * 2017年11月28日 16:39:41
	 */
    public function GetGoodsListCount($agent_id,$category_id,$type,$CustomWhere,$order,$orderby){
		if($category_id){
			$category  = ' zg.category_id = '.$category_id;
		}else{
			$category  = ' 1 = 1 ';
		}
		return Db::query('select COUNT(distinct zg.id) as count from zm_goods as zg LEFT JOIN zm_goods_associate_attr as zgaa   ON  zg.id = zgaa.goods_id  where '
						. $category
						.' and zg.type in ('.$type.')'
						.' and zg.agent_id = '.$agent_id
						.' and zg.product_status = 1 '
						. $CustomWhere
						.' ORDER BY '.$order.' '.$orderby);
    }
	
	/**
	 * 获取产品图片
	 * zhy	find404@foxmail.com
	 * 2017年11月28日 16:56:32
	 */	
    public function GetImgInfo($agent_id,$goods_id){
		return Db::name('goods_images')
				->where(['goods_id' => $goods_id
						,'agent_id' => $agent_id])
				->value('small');
	}

	/**
	* 品牌系列列表
	* auth: zengmm
	* time: 2017/11/29
	**/
	public function getGoodsBrandList($agent_id)
	{
		$where["agent_id"] = $agent_id;	
		$goodsBrandList = db('goods_brand')
		->where($where)
        ->select();
		return $goodsBrandList;
	}
	
	/**
	* 添加品牌系列
	* auth: zengmm
	* time: 2017/11/29
	**/
	public function addGoodsBrandInfo($data)
	{
		return $rs = db('goods_brand')->insert($data);
	}
	
	/**
	* 根据ID获取品牌系列信息
	* auth: zengmm
	* time: 2017/11/29
	**/
	public function getGoodsBrandById($id)
	{
		return $info = db('goods_brand')->where("id=".$id)->find();
	}
	
	/**
	* 修改品牌系列
	* auth: zengmm
	* time: 2017/11/29
	**/
	public function saveGoodsBrandInfo($data)
	{
		return $rs = db('goods_brand')->update($data);
	}

	/**
	* 根据品牌系列ID获取商品列表
	* auth: zengmm
	* time: 2017/11/29
	**/
	public function getGoodsByBrandId($brand_id)
	{
		$where["brand_id"] = $brand_id;	
		$goodsList = db('goods')
		->where($where)
        ->select();
		return $goodsList;
	}

	/**
	* 删除品牌系列
	* auth: zengmm
	* time: 2017/11/29
	**/
	public function delGoodsBrand($id)
	{
		return $rs	= db('goods_brand')->where("id=".$id)->delete();	
	}
	
	/**
	* 商品标签列表
	* auth: zengmm
	* time: 2017/11/29
	**/
	public function getGoodsTagList($agent_id)
	{
		return $goodsTagList = db('goods_tag')->where("agent_id=".$agent_id)->select();
	}
	
	/**
	* 添加标签
	* auth: zengmm
	* time: 2017/11/29
	**/
	public function addGoodsTagInfo($data)
	{
		return $rs = db('goods_tag')->insert($data);
	}
	
	/**
	* 根据ID获取标签信息
	* auth: zengmm
	* time: 2017/11/29
	**/
	public function getGoodsTagById($id)
	{
		return $info = db('goods_tag')->where("id=".$id)->find();
	}
	
	/**
	* 修改标签
	* auth: zengmm
	* time: 2017/11/29
	**/
	public function saveGoodsTagInfo($data)
	{
		return $rs = db('goods_tag')->update($data);
	}

	/**
	* 根据标签ID获取商品列表
	* auth: zengmm
	* time: 2017/11/29
	**/
	public function getGoodsByTagStr($tag_id)
	{
		return $goodsList = db('goods')->where("tagStr like '".$tag_id.",'")->select();
	}

	/**
	* 删除品牌系列
	* auth: zengmm
	* time: 2017/11/29
	**/
	public function delGoodsTag($id)
	{
		return $rs	= db('goods_tag')->where("id=".$id)->delete();	
	}
	
	/**
	 * 同步版房产品数据操作（添加数据）
	 * auth	：zengmm
	**/
    public function addSynchroInfo($goodsData,$data)
    {
		$extra_goods_attrs = $goodsData["extra_goods_attrs"];
		unset($goodsData["extra_goods_attrs"]);
		//1、添加goods表数据
		$goods_id = M("goods")->insertGetId($goodsData);	
		if($goods_id){
			//2、添加goods_info表数据
			$dataInfo["goods_id"] 					= $goods_id;
			$dataInfo["content"] 					= $data["content"];
			$dataInfo["extra_goods_luozuan"] 		= json_encode($data["extra_goods_luozuan"]);
			$dataInfo["extra_goods_deputystones"] 	= json_encode($data["extra_goods_deputystones"]);
			$giRs = M("goods_info")->insert($dataInfo);	
			//3、添加goods_images表数据
			$imagesList = $data["extra_goods_images"];
			foreach($imagesList as $k=>$v){
				$data_i["goods_id"] = $goods_id;	
				$data_i["small"] = $v;	
				$data_i["big"] = $v;
				M("goods_images")->insert($data_i);		
			}
			//4、同步商品属性
			foreach($extra_goods_attrs as $key=>$val){
				$attr_id1 = explode(",",$val["attr_id1"]);
				$attr_id2 = explode(",",$val["attr_id2"]);
				$attr_id3 = explode(",",$val["attr_id3"]);
				if($val["level"] == 1){				//存在一级的情况
					$out_value_code = $attr_id1[0];
					$attValue = $attr_id1[1];
				}elseif($val["level"] == 2){		//存在二级的情况
					$out_value_code = $attr_id2[0];
					$attValue = $attr_id2[1];
				}elseif($val["level"] == 3){		//存在三级的情况
					$out_value_code = $attr_id3[0];
					$attValue = $attr_id3[1];	
				}
				$goods_attr_value_info = M("Goods_attr_value")->where("out_value_code=".$out_value_code)->field("id,attr_id")->find();
				$goods_attr_info	   = M("Goods_attr")->where("out_code=".$goods_attr_value_info["attr_id"])->field("id")->find();
				$atData["attr_id"] = $goods_attr_info["id"];
				$atData["attr_value_id"] = $goods_attr_value_info["id"];
				$atData["value"] = $attValue;
				$atData["goods_id"] = $goods_id;
				M("goods_associate_attr")->insert($atData);	
			}
			return true;
		}else{
			return false;
		}
	}
	
	/**
	 * 同步版房产品数据操作（修改数据）
	 * auth	：zengmm
	**/
    public function saveSynchroInfo($goodsData,$data,$goods_id)
    {
		$extra_goods_attrs = $goodsData["extra_goods_attrs"];
		unset($goodsData["extra_goods_attrs"]);
		//1、修改goods表数据
		$gRs = M("goods")->where("id=".$goods_id)->update($goodsData);
		if($gRs !== false){	
			//2、修改goods_info表数据
			$dataInfo["content"] 					= $data["content"];
			$dataInfo["extra_goods_luozuan"] 		= json_encode($data["extra_goods_luozuan"]);
			$dataInfo["extra_goods_deputystones"] 	= json_encode($data["extra_goods_deputystones"]);
			M("goods_info")->where("goods_id=".$goods_id)->update($dataInfo);		
			//3、修改goods_images表数据
			$imagesList = $data["extra_goods_images"];
			$imgList = M("goods_images")->where("goods_id=".$goods_id)->select();
			if(!empty($imgList)){			
				foreach($imgList as $kk=>$vv){
					$imgKey = array_search($vv["big"],$imagesList);
					if($imgKey === false){						//不存在即删除
						M("goods_images")->where("id=".$vv["id"])->delete();		
					}else{										//存在即删除imagesList数组里面的数据
						unset($imagesList[$imgKey]);
					}	
				}
			}
			if(!empty($imagesList)){						//存在即添加
				foreach($imagesList as $k=>$v){
					$data_i["goods_id"] = $goods_id;	
					$data_i["small"] = $v;	
					$data_i["big"] = $v;
					M("goods_images")->insert($data_i);		
				}	
			}
			//4、同步商品属性
			foreach($extra_goods_attrs as $key=>$val){
				$attr_id1 = explode(",",$val["attr_id1"]);
				$attr_id2 = explode(",",$val["attr_id2"]);
				$attr_id3 = explode(",",$val["attr_id3"]);
				if($val["level"] == 1){				//存在一级的情况
					$out_value_code = $attr_id1[0];
					$attValue = $attr_id1[1];
				}elseif($val["level"] == 2){		//存在二级的情况
					$out_value_code = $attr_id2[0];
					$attValue = $attr_id2[1];
				}elseif($val["level"] == 3){		//存在三级的情况
					$out_value_code = $attr_id3[0];
					$attValue = $attr_id3[1];	
				}
				$goods_attr_value_info = M("Goods_attr_value")->where("out_value_code=".$out_value_code)->field("id,attr_id")->find();
				$goods_attr_info	   = M("Goods_attr")->where("out_code=".$goods_attr_value_info["attr_id"])->field("id")->find();
				$atData["attr_id"] = $goods_attr_info["id"];	
				$atData["attr_value_id"] = $goods_attr_value_info["id"];
				$atData["value"] = $attValue;
				$atData["goods_id"] = $goods_id;
				$goods_associate_attr_info = M("goods_associate_attr")->where("goods_id={$atData['goods_id']} and attr_value_id={$atData['attr_value_id']}")->field("id")->find();					
				if($goods_associate_attr_info["id"]){
					M("goods_associate_attr")->where("id=".$goods_associate_attr_info["id"])->update($atData);
				}else{
					M("goods_associate_attr")->insert($atData);	
				}	
			}
			return true;
		}else{
			return false;
		}
	}


    /**
     * @author guihongbing
     * @date 20180206
     * 查询珠宝最新价格
     * @param $agent_id int 分销商ID
     * @param $goods_id int 商品ID
     * @param $attributes string SKU属性
     * @return $goods_price decimal 商品价格
     */
    public function getGoodsPrice($agent_id,$goods_id,$attributes){
        $map['agent_id'] = $agent_id;
        $map['goods_id'] = $goods_id;
        $map['attributes'] = $attributes;
        $result = M('goods_sku')->field('goods_price,goods_number')->where($map)->find();
        return empty($result['goods_price']) ? 0 : $result;
    }

    /*
    * $id 商品id
    * 详情页筛选根据商品sku返回可选择的列表
    *
    */
    public function getgoodsattr($id)
    {
        $info = $this->getGoodsSkuById($id);
        $list = [];
        $papa = '';
        $son = '';
        foreach ($info as $key => $val) {
            $tmp = explode('^',$val['attributes']);
            foreach ($tmp as $k => $v) {
                $temp = explode(':',$v);
                $list[$temp[0]][] = $temp[1];
                $papa .= ','.$temp[0];
                $son .= ','.$temp[1];
            }
        }
        $attrname = M('goods_attr')->where(['id'=>['in',$papa]])->column('id,name');
        $sonattrname = M('goods_attr_value')->where(['id'=>['in',$son]])->column('id,name');

        $result = [];
        //去重
        foreach ($list as $key => $val) {
            $val = array_unique($val);
            $list[$key] = $val;
            $result[$key]['goods_id'] = $id;
            $result[$key]['attr_id'] = $key;
            $result[$key]['attrname'] = $attrname[$key];
            $tmparr = [];
            foreach ($val as $k => $v) {
                $tmparr['goods_id'] = $id;
                $tmparr['attr_id'] = $key;
                $tmparr['attr_value_id'] = $v;
                $tmparr['attrname'] = $attrname[$key];
                $tmparr['attrvaluename'] = $sonattrname[$v];
                $result[$key]['sub_attr_id'][] = $tmparr;
            }
        }
        return $result;
    }

    //app数据类型
    public function appgetgoodsattr($id)
    {
        $info = $this->getGoodsSkuById($id);
        $list = [];
        $papa = '';
        $son = '';
        foreach ($info as $key => $val) {
            $tmp = explode('^',$val['attributes']);
            foreach ($tmp as $k => $v) {
                $temp = explode(':',$v);
                $list[$temp[0]][] = $temp[1];
                $papa .= ','.$temp[0];
                $son .= ','.$temp[1];
            }
        }
        $attrname = M('goods_attr')->where(['id'=>['in',$papa]])->column('id,name');
        $sonattrname = M('goods_attr_value')->where(['id'=>['in',$son]])->column('id,name');

        $result = [];
        $res = [];
        foreach ($list as $key => $val) {
            $val = array_unique($val);
            $list[$key] = $val;
            $result[$key]['goods_id'] = $id;
            $result[$key]['attr_id'] = $key;
            $result[$key]['attrname'] = $attrname[$key];
            $tmparr = [];
            foreach ($val as $k => $v) {
                $tmparr['goods_id'] = $id;
                $tmparr['attr_id'] = $key;
                $tmparr['attr_value_id'] = $v;
                $tmparr['attrname'] = $attrname[$key];
                $tmparr['attrvaluename'] = $sonattrname[$v];
                $result[$key]['sub_attr_id'][] = $tmparr;
            }
        $res[] = $result[$key];
        }
        return $res;
    }

    //获取下单通产品详情
    public function getxdtdetail($id,$agent_id)
    {
        if(!is_numeric($id)||$id<=0||!$agent_id) return ;

        //获取上架条件
        $where = logic('DataProcessing')->get_goods_product_status($agent_id);
        //商品ID
        $where.= ' and g.id = '.$id;

        $Goods = M('Goods')->alias('g')
            ->join('zm_goods_info gi','g.id = gi.goods_id','left')
            ->join('zm_goods_trader gt','gt.goods_id = g.id and gt.agent_id ='.$agent_id,'left')
            ->field('g.id,g.supply_goods_id,g.supply_goods_type,g.name,g.keyword,g.sn,g.code,g.type,g.brand_id,g.category_id,g.jewelry_id,g.thumb,g.agent_id,g.remark,g.price,g.price_seller,g.stock_status,g.stock_unit,g.stock_num,g.product_status,g.trader_status,g.create_time,g.create_user,g.update_time,g.update_user,g.attrStr,g.tagStr,gi.id goods_info_id,gi.content,gt.status')
            ->where($where)->find();
        
        //销售价格计算
        $Goods = logic('PriceCalculation')->goods_price([$Goods])[0];
        
        //下单通调用接口获取详情数据
        if($Goods['type']==4&&$Goods['supply_goods_id']>0){
            $Openzm = logic('Openzm')->get_customize_detail($Goods['supply_goods_id']);
            if($Openzm['code']==100200){
                $Goods['GoodsXdt'] = $Openzm['data'];
            }else{
                return;
            }
        }

        if(!$Goods) return ;
        return $Goods;
    }
}