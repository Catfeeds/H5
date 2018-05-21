<?php
namespace app\api\controller;

use think\Controller;
use think\Session;
use app\common\controller\ApiBase;
use think\Db;

/**
 * 商品api
 * Class Goods
 * @package app\api\controller
 */
class Goods extends ApiBase
{
    protected function _initialize()
    {
        parent::_initialize();
    }  
    
    //首页搜索
    public function searchgoods()
    {
        $data = $this->request->only('keyword,type,page');
        $data['page'] = $data['page'] ?  $data['page'] : 1;
        if (!is_numeric($data['type']) && $data['type'] < 0) {return json(['status'=>101,'msg'=>'type erro','data'=>'']);}
        if (!$data['keyword']) {return json(['status'=>101,'msg'=>'请输入搜索关键字','data'=>'']);}
        switch ((int)$data['type']) {
            case 0:  //钻石
                return $this->diamond($data['page'],$data['keyword']);
            break;
            default:  //成品搜索
                // return $this->index(0,$data['keyword'],$data['page']);
                return json(['status'=>100,'msg'=>'搜索接口调整']);
            break;
        }
    }
     /**
     *珠宝搜索
     *page:当前页，缺省为1
     *pagesize:每页条数，缺省为20
     *orderby:排序字段和方向，缺省为id倒序，id desc
     *attrJson:查询参数{"attr_id":"attr_value_id,可多个，逗号分隔"}
     *return:100,data为钻石列表
     */
 //    public function index($category_id=0,$keyword=null,$page=1,$pagesize=20,$orderby="id desc"){
 //        $params=[];
 //        $attrstr=I('goods_attr_filter');
 //        if($attrstr){
 //            $arr=explode(";",$attrstr);
 //            foreach ($arr as $item){
 //                $kv=explode(":",$item);
 //                if(count($kv)>1){
 //                    $params[$kv[0]]=$kv[1];
 //                    $kvvarr=explode(",",$kv[1]);
 //                    if(count($kvvarr)>1){
 //                        $params[$kv[0]]=$kvvarr;
 //                    }
 //                }            
 //            }
 //        }     
 //        // getGoodsListCustomized
 //        $data=logic("Goods")->getGoodsList($params,$category_id,$keyword,$page,$pagesize,$orderby,0);
 //        $this->ajaxReturn(['status'=>100,'data'=>$data]);
 // }

    /**
     *珠宝搜索
     *page:当前页，缺省为1
     *pagesize:每页条数，缺省为20
     *orderby:排序字段和方向，缺省为id倒序，id desc
     *attrJson:查询参数{"attr_id":"attr_value_id,可多个，逗号分隔"}
    */
    public function index(){
        $data['agent_id'] = $this->agent_id; //分销商
        if (I('goods_type')) { 
            //下单通与定制取4
            $data['type'] = I('goods_type') < 4 ? I('goods_type') : 4; 
        } else {
            $data['type'] = 3; //默认成品
        }
        $data['category_id'] = I('category_id') ? I('category_id') : 0; //分类
        $data['keyword'] = I('keyword') ? I('keyword') : ''; //搜索
        $data['goods_attr_filter'] = I('goods_attr_filter') ? I('goods_attr_filter') : ''; //属性搜索
        $data['orderby'] = I('orderby') ? I('orderby') : 'id desc'; //排序

        $data['diamond_id'] = I('diamond_id') ? I('diamond_id') : '';// 定制钻配托 goods表 ID
        $data['shape'] = I('shape') ? I('shape') : ''; // 定制钻配托 形状
        $data['weight'] = I('weight') ? I('weight') : ''; // 定制钻配托 重量

        $page = I('page')>0?I('page'):1;
        $pagesize = I('pagesize')?I('pagesize'):20;

        if (I('goods_type') == 5) {
            //下单通
            $data['category_id'] = 0; //分类
            $res = logic("Goods")->getGoodsListXdt($data,$page,$pagesize,'id desc');
        } else {
            $res = logic("Goods")->getGoodsListCustomized($data,$page,$pagesize);
        }
        // //判断登录，获取收藏
        if ($this->checkuserinfo()) {
            $res["data"] = logic("DataProcessing")->get_check_goods_collection($res["data"],$this->checkuserinfo());
        }
        $res ? $result = ['status'=>100,'msg'=>'获取列表成功','data'=>$res['data']] : $result = ['status'=>101,'msg'=>'无商品列表','data'=>''];
        return json($result);
    }


    /**
     * 珠宝详情    
     * $id goods表id
     */
    public function getgoodsinfo($id)
    {        
        //查询商品属性
        $data = model('goods')->getGoodsDetail($id);
        if($data['content']) {
            $data['content'] = htmlspecialchars_decode($data['content']);
        }
        if(empty($data)){
            $result = array('status'=>-1,'msg'=>'商品不存在');
            $this->ajaxReturn($result);
        }else{
            $this->ajaxReturn(['status'=>100,'data'=>$data]);            
        }        
    }

    /**
     * 珠宝详情-主显示属性,sku
     * $id goods表id
     */
    public function getgoodssku($id){
        $data=model("Goods")->getGoodsSkuById($id);
        if(empty($data)){
            $result = array('status'=>-1,'msg'=>'商品不存在');
            $this->ajaxReturn($result);
        }else{
            $this->ajaxReturn(['status'=>100,'data'=>$data]);
        }
    }
    

    /**
     * 珠宝详情-主显示属性,sku
     * $id goods表id
     */
    public function newgetgoodsattr($id){        
        
        $data=model("Goods")->getgoodsattr($id);
        if(empty($data)){
            $result = array('status'=>-1,'msg'=>'商品不存在');
            return json($result);
        }else{
            return json(['status'=>100,'data'=>$data]);
        }
    }   


    /**
     * 珠宝详情-主显示属性,sku app端专用
     * $id goods表id
     */
    public function appnewgetgoodsattr($id){        
        
        $data=model("Goods")->appgetgoodsattr($id);
        if(empty($data)){
            $result = array('status'=>-1,'msg'=>'商品不存在');
            return json($result);
        }else{
            return json(['status'=>100,'data'=>$data]);
        }
    }   

    /**
     * 珠宝详情-主显示属性,sku
     * $id goods表id
     */
    public function getgoodsattr($id,$is_main=0,$is_sku=0){ 
        
        $data=model("Goods")->getGoodsAttrById($id,$is_main,$is_sku);
        if(empty($data)){
            $result = array('status'=>-1,'msg'=>'商品不存在');
            $this->ajaxReturn($result);
        }else{
            $this->ajaxReturn(['status'=>100,'data'=>$data]);
        }
    }   
    
     /**
     *钻石搜索
     *page:当前页，缺省为1
     *pagesize:每页条数，缺省为20
     *orderby:排序字段和方向，缺省为id倒序，id desc
     *goods_attr_filter:查询参数{"字段名称":"字段值"}
     *return:100,data为钻石列表
     */
    public function diamond($page=1,$keyword=null,$pagesize=20,$orderby="id desc"){
        $data['agent_id'] = $this->agent_id; //分销商
        $data['orderby'] = I("orderby");
        $data['goods_attr_filter'] = I('goods_attr_filter');
        $data['keyword'] = $keyword;
        $result = logic("Goods")->getDiamondListzwx2($data,$page,$pagesize);
        $data['page'] = $page;
        $data['count']=$result['total'];
        $data['debug']='';
        $data['data']=$result['data'];
        $this->ajaxReturn(['status'=>100,'data'=>$data]); 
        // $params=[];
        // $attrstr=I('goods_attr_filter');
        // if($attrstr){
        //     $arr=explode(";",$attrstr);
        //     foreach ($arr as $item){
        //         $kv=explode(":",$item);
        //         if(count($kv)>1){
        //             $params[$kv[0]]=$kv[1];
        //         }                                
        //     }
        // }       
        // $data=logic("Goods")->getDiamondList($params,$keyword,$page,$pagesize,$orderby,0);
        // $this->ajaxReturn(['status'=>100,'data'=>$data]);
    }

    /**
     * 在线配石列表 托配钻
     * shape形状 weight重量
     */
    public function gettpzlist($shape,$weight,$page=1)
    {      
        $data['shape'] = $shape;
        $data['weight'] = $weight;
        $data['agent_id'] = $this->agent_id;

        $page = $page>0?$page:1;
        $result = logic('Goods')->getGoodsDiamondListTpz($data,$page);
        if ($result['data']) {
            $res = ['status'=>100,'msg'=>'数据请求成功','data'=>$result];
        } else {
            $res = ['status'=>101,'msg'=>'没有符合条件的钻石','data'=>''];
        }
        return json($result);
    }

    /**
     * 钻石详情
     * $id goods表id
     */
    public function getdiamondinfo($id)
    {      
        //查询商品属性
        $data = model('goods')->getDiamondDetail($id);
        if(empty($data)){
            $result = array('status'=>-1,'msg'=>'商品不存在');
            $this->ajaxReturn($result);
        }else{
            $this->ajaxReturn(['status'=>100,'data'=>$data]);
        }
    }
        
    
    /**
     * 获取分类分类，已经转成sub树
     * wxh 2017-12-15
     * return:二级树型对象
     */
    public function getcategory(){
        $is_see = (isset($this->user)) ? '0,1' : '0'; //登录可见
        $goods_type = I('goods_type') ? I('goods_type') : 3;
        if ($goods_type < 5) {
            $listcat= model('GoodsCategory')->GetListByAgent(get_agent_id(),0,$goods_type);
        } else {
            //获取下单通分类 agent_id=0，is_see显示，goods_type=4，type=1 下单通商品
            $listcat= model('GoodsCategory')->GetListByAgent(0,$is_see,4,1);

        }
        $data=array2treesingle($listcat);
        $this->ajaxReturn(['status'=>100,'data'=>$data]);
    }
    
    
    /**
     * 获取分类下面的属性
     * wxh 2017-12-15
     * return:属性对象
     */
    public function getattrsbycat($category_id,$goods_type=3){        
        if(is_numeric($category_id)&&$category_id>0){
            $data = logic('GoodsAttr')->GetAttrsByCat($category_id,$goods_type);
            $this->ajaxReturn(['status'=>100,'data'=>$data]);
        }
    }  

    /**
     * 获取商家新品数据
     * wxh 2017-12-15
     * return:商品列表
     */
    public function getgoodsnew($num=5){
        $agent_id=get_agent_id();
        $data=model('goods')->getListTraderByTag($agent_id,'新品',1,$num);
        $this->ajaxReturn(['status'=>100,'data'=>$data]);
    }
    
    /**
     * 获取商家热卖数据
     * wxh 2017-12-15
     * return:商品列表
     */
    public function getgoodshot($num=5){
        $agent_id=get_agent_id();
        $data=model('goods')->getListTraderByTag($agent_id,'热卖',1,$num);
        $this->ajaxReturn(['status'=>100,'data'=>$data]);
    }
    
    /**
     * 获取商家标签商品数据
     * wxh 2017-12-15
     * return:商品列表
     */
    public function getgoodsbytag($tag,$num=5){
        $agent_id=get_agent_id();
        $data=model('goods')->getListTraderByTag($agent_id,$tag,1,$num);
        $this->ajaxReturn(['status'=>100,'data'=>$data]);
    }


    /**
     * @author guihongbing
     * @date 20180206
     * 查询珠宝最新价格
     * 实例：http://myzbfx.com/api/goods/getgoodsprice?goods_id=44&attributes=1:1^2:14^4:30
     * return :{"status":100,"data":"152.00"}
     */
    public function getgoodsprice(){
        $agent_id=get_agent_id();
        //珠宝商品
        $goods_id = I('goods_id',0);
        //SKU属性
        $attributes = I('attributes',0);
        //查询价格
        $result = model('goods')->getGoodsPrice($agent_id,$goods_id,$attributes);
        // dump($result);die();
        $this->ajaxReturn(['status'=>100,'data'=>$result]);
    }

    /**
     * @author sxm
     * @date 20180312
     * 获取分类页广告图
     */
    public function getcatead()
    {
        $agent_id=get_agent_id();
        $str = 'mobile_template_id';
        $config_id=Db::name('agent_config a')
                ->join('zm_template b','b.id=a.mobile_template_id','LEFT')
                ->where('a.agent_id',$agent_id)
                ->value('config_id');//模板编号    
        $templateitem=model("template")->getComListByPage($agent_id,$config_id,'goods');
        
        $result = [];
        foreach ($templateitem as $key => $val) {
            if ($val['code'] == 'ad') {
                $result = $val;
            }
        }
        return json(['status'=>100,'msg'=>'success','data'=>$result]);
    }

    /**
     * @author sxm
     * @date 20180416
     * 获取钻石属性筛选
     */
    public function diamondattr($type)
    {
        if (!is_numeric($type) || $type < 0) {
            return json(['status'=>101,'msg'=>'获取失败','data'=>'']);}
        switch ($type) {
            case 0:
                $diamondattr = config('white_diamond_attr');
            break;
            case 1:
                $diamondattr = config('color_diamond_attr');
            break;
            default:
                $diamondattr = config('white_diamond_attr');
            break;
        }
        return json(['status'=>100,'msg'=>'获取成功','data'=>$diamondattr]);
    }

    
    /**
     * 获取下单通商品属性
     * $id 商品表id
     */
    public function xdtdetails($id){
        if (!is_numeric($id) || $id < 0) {
            return json(['status'=>101,'msg'=>'获取失败','data'=>'']);}

        $agent_id = $this->agent_id;
        //根据条件获取商品详情组装数据
        $Info = model('goods')->getxdtdetail($id,$agent_id);
        // 处理成客户端可用数据
    
        if ($Info) {
            return json(['status'=>100,'msg'=>'获取成功','data'=>$Info]);
        } else {
            return json(['status'=>101,'msg'=>'无商品信息','data'=>'']);
        }
    }

    /**
     * 获取下单通商品属性
     * $goods_id 钻明商品id
     * $material_id 材质id
     * $luozuan_ids 主石id
     */
    public function getxdtprice($goods_id,$material_id,$luozuan_ids){
        if (!is_numeric($goods_id) && !is_numeric($material_id) && !is_numeric($luozuan_ids)) {
            return json(['status'=>101,'msg'=>'参数不正确','data'=>'']);}

        $Openzm = logic('Openzm')->get_customize_calculatePrice($goods_id,$material_id,$luozuan_ids);
        if($Openzm['code']==100200){
            $goods['type'] = 4;
            $goods['agent_id'] = 0;
            $goods['price'] = $Openzm['data']['price'];
            //计算价格判断 supply_goods_id 大于0
            $goods['supply_goods_id'] = 1;
            //计算下单通戒托 销售价格
            $goods = logic('PriceCalculation')->goods_price([$goods])[0];
            return json(['status'=>100,'msg'=>'获取价格成功','data'=>['price'=>$goods['price']]]);
        } else {
            return json(['status'=>101,'msg'=>'获取钻明价格失败','data'=>'']);
        }
    }

    /**
     * 下单通商品在线配石列表
     * $weight 钻重
     */
    public function getxdtmatch($weight){
        if (!is_numeric($weight)) {
            return json(['status'=>101,'msg'=>'参数不正确','data'=>'']);}

        $Openzm = logic('Openzm')->get_customize_matchOnline($weight);
        
        if($Openzm['code']==100200){
            foreach ($Openzm['data'] as $k => $v) {
              foreach ($v as $k1 => $v1) {
                $v1['type'] = 4;
                $v1['agent_id'] = 0;
                //计算价格判断 supply_goods_id 大于0
                $v1['supply_goods_id'] = 1;
                $list[] = $v1;
              }
            }
            //计算下单通在线配石 销售价格
            $list = logic('PriceCalculation')->goods_price($list);
            return json(['status'=>100,'msg'=>'获取列表成功','data'=>$list]);
        } else {
            return json(['status'=>101,'msg'=>'获取列表失败','data'=>'']);
        }
    }
    
}