<?php
namespace app\common\model;

use think\Model;


class GoodsDiamond extends Model
{
	protected $insert = ['create_time'];
	/*public $goods_diamond_model = M('goods_diamond');
	public $goods_model = M('goods');*/
	
    /**
     * 创建时间
     * @return bool|string
     */
    protected function setCreateTimeAttr()
    {
        return date('Y-m-d H:i:s');
    }
	
	/**
	 * 添加证书货
	 * auth	：zengmm
	 * @param：$ids  多个ID
	 * time	：2017-11-27
	**/
    public function getListByIds($idArr)
    {
		$where["gd.id"] = array("in",$idArr);
		$field = "gd.*,g.product_status";
		$goodsDiamondList = db("goods_diamond")->alias("gd")
		->join('goods g','g.id=gd.goods_id','left')
		->where($where)
		->field($field)
		->select();	
		return $goodsDiamondList;
	}
	
	/**
	 * 添加证书货
	 * auth	：zengmm
	 * time	：2017-11-27
	**/
    public function addInfo($data)
    {
		$this->startTrans();
		if($data["thumb"]){ $data_g["thumb"] = $data["thumb"]; }
		if($data["videoId"]){  $data_v["id"] = $data["videoId"]; }
		if($data["images"]){  $goodsImages = $data["images"]; }
		//向goods表插入一条数据
		$data_g["agent_id"] = $data["agent_id"];
		$data_g["name"] = $data["goods_name"];
		$data_g["type"] = $data["type"];
		$data_g["price"] = $data["global_price"];
		$data_g["stock_num"] = $data["goods_number"];
		$data_g["stock_status"] = $data["inventory_type"];
		$data_g["create_time"] = date("Y-m-d H:i:s");
		$data_g["create_user"] = $_SESSION["think"]["admin_id"];
		$data_g["code"] = D('goods')->getGoodsCode();
		unset($data["thumb"]); 
		unset($data["videoId"]);
		unset($data["video_url"]);
		unset($data["images"]);
		$goods_id = M('goods')->insertGetId($data_g);
		if($goods_id){
			$data["goods_id"] = $goods_id;
			$action	= M('goods_diamond')->insert($data);
			if($action){ 
				//如果多图存在
				if($goodsImages){
					$is_success = 1;
					foreach($goodsImages as $vv){
						$data_gi["small"] = $vv; 
						$data_gi["big"] = $vv; 
						$data_gi["goods_id"] = $goods_id;
						$data_gi["agent_id"] = $data["agent_id"]; 
						$rs_gi = M('goods_images')->insert($data_gi);	
						if($rs_gi == false){
							$is_success = 2;	
						}	
					}
					if(is_success == 2){
						$this->rollback();
						return false; 	
					}
				}
				//如果视频存在，添加视频到zm_goods_videos表
				if($data_v["id"]){
					$data_v["goods_id"] = $goods_id;
					$data_v["create_time"] = date("Y-m-d H:i:s");
					$gvId = M('goods_videos')->update($data_v);
					if($gvId){
						$this->commit(); 
						return true;	
					}else{
						$this->rollback();
						return false; 
					}
				}else{
					$this->commit(); 
					return true;
				}
			}else{ 
				$this->rollback();
				return false;
			}
		}else{
			$this->rollback();
			return false;
		}	
	}
	
	/**
	 * 通过证书编号获取钻石信息
	 * auth	：zengmm
	 * @param：$certificate_number  证书编号
	 * time	：2017-11-27
	**/
    public function getInfoByCertificateNumber($certificate_number=""){
		if($certificate_number!=""){
			$info = db('goods_diamond')->where('certificate_number="'.$certificate_number.'"')->find();
			if($info){ return $info; }else{ return false; }
    	}else{ return false; }
	}
	
	/**
	 * 通过ID获取钻石信息
	 * auth	：zengmm
	 * time	：2017-11-27
	**/
	public function getInfoById($id){
		$info = db('goods_diamond')->where('id='.$id)->find();		
		$info["thumb"] = db("goods")->where('id',$info["goods_id"])->value('thumb');
		$info["agent_id"] = db("goods")->where('id',$info["goods_id"])->value('agent_id');
		$info["video_url"] = db("goods_videos")->where('goods_id',$info["goods_id"])->value('url');
		$info["images"] = db("goods_images")->where('goods_id='.$info["goods_id"])->field('big')->select();
		
		if($info){ return $info; }else{ return false; }
	}
	
	/**
	 * 修改证书货
	 * auth	：zengmm
	 * time	：2017-11-27
	**/
    public function saveInfo($data)
    {
		$this->startTrans();
		if($data["thumb"]){ $data_g["thumb"] = $data["thumb"]; }
		if($data["videoId"]){  $data_v["id"] = $data["videoId"]; }
		if($data["images"]){  $goodsImages = $data["images"]; }
		unset($data["thumb"]);
		unset($data["images"]);
		unset($data["videoId"]);
		unset($data["video_url"]);
		//向goods表插入一条数据
		$data_g["id"] = $data["goods_id"];
		$data_g["name"] = $data["goods_name"];
		$data_g["type"] = $data["type"];
		$data_g["price"] = $data["global_price"];
		$data_g["stock_num"] = $data["goods_number"];
		$data_g["stock_status"] = $data["inventory_type"];
		$rs = M('goods')->update($data_g);
		if($rs !== false){
			$action	= M('goods_diamond')->update($data);
			if($action !== false){ 
				//如果多图存在
				if($goodsImages){
					//查询产品原来图片
					$oldGoodsImagesList = M('goods_images')->where("goods_id=".$data["goods_id"])->select();
					$oldGoodsImages = array();
					foreach($oldGoodsImagesList as $gv){
						$oldGoodsImages[] = $gv["big"];		
					}
					//先增加新图片
					$is_success = 1;
					foreach($goodsImages as $gi){
						if(!in_array($gi,$oldGoodsImages)){
							$data_gi["small"] = $gi; 
							$data_gi["big"] = $gi; 
							$data_gi["goods_id"] = $data["goods_id"];
							$rs_gi = M('goods_images')->insert($data_gi);	
							if($rs_gi == false){
								$is_success = 2;	
							}			
						}
					}
					//再删除老图片
					foreach($oldGoodsImages as $ogi){
						if(!in_array($ogi,$goodsImages)){
							M('goods_images')->where("big='".$ogi."'")->delete();
							$ogiUrl = substr($ogi,1);
							unlink($ogiUrl);
						}	
					}
					if(is_success == 2){
						$this->rollback();
						return false; 	
					}
				}
				//如果视频存在，添加视频到zm_goods_videos表
				if($data_v["id"]){
					//删除前一条记录及视频文件
					$videosArr = M('goods_videos')->where("goods_id=".$data["goods_id"])->find();
					if(!empty($videosArr)){
						$rsgv = M('goods_videos')->delete($videosArr["id"]);
						$gvUrl = substr($videosArr["url"],1);
						unlink($gvUrl);
					}
					//更新新的视频记录
					$data_v["goods_id"] = $data["goods_id"];
					$data_v["create_time"] = date("Y-m-d H:i:s");
					$gvId = M('goods_videos')->update($data_v);
					if($gvId !== false){
						$this->commit(); 
						return true;	
					}else{
						$this->rollback();
						return false; 
					}
				}else{
					$this->commit(); 
					return true;
				}
			}else{ 
				$this->rollback();
				return false;
			}
		}else{
			$this->rollback();
			return false;
		}	
	}
	
	/**
	 * 删除证书货
	 * auth	：zengmm
	 * time	：2017-11-27
	**/
    public function del($id)
    {
		$data["isdel"] = 1;	
		$rs = M("goods")->where("id=".$id)->update($data);
		if($rs){ 
			return true;
		}else{
			return false;	
		}
		/*$this->startTrans();
		$goodsDiamondInfo = $this->getInfoById($id);
		$rs_g = db("goods")->delete($goodsDiamondInfo["goods_id"]);
		if($rs_g){ 
			$rs_gd	= db('goods_diamond')->delete($id);	
			if($rs_g){ 
				//删除对应的图片
				$oldGoodsImagesList = db('goods_images')->where("goods_id=".$goodsDiamondInfo["goods_id"])->select();
				if($oldGoodsImagesList){
					foreach($oldGoodsImagesList as $ogi){
						$rs_ogi = db('goods_images')->where("big='".$ogi["big"]."'")->delete();	
						$ogiUrl = substr($ogi["big"],1);
						if($rs_ogi){ 
							@unlink($ogiUrl);
						}	
					}
				}
				//删除对应的视频
				$where["goods_id"] = $goodsDiamondInfo["goods_id"];
				$goodsVideosInfo = db('goods_videos')->where($where)->find();	
				if($goodsVideosInfo){
					$vUrl = substr($goodsVideosInfo["url"],1);
					$rs_gv = db('goods_videos')->delete($goodsVideosInfo["id"]);	
					if($rs_gv){ 
						@unlink($vUrl);
						$this->commit(); 
						return true;
					}else{
						$this->rollback();
						return false;		
					}	
				}else{
					$this->commit(); 
					return true;
				}
			}else{
				$this->rollback();
				return false;	
			}
		}else{ 
			$this->rollback();
			return false; 
		}*/	
	}
	
	/**
	 * 删除证书货
	 * auth	：zengmm
	 * time	：2017-11-27
	**/
    public function delMany($ids)
    {
		$this->startTrans();
		$goodsDiamondList = db("goods_diamond")->where("id in (".$ids.")")->select();
		foreach($goodsDiamondList as $key=>$val){
			$goodsArr = db("goods")->where("id=".$val["goods_id"])->find();
			if(!empty($goodsArr)){ $goodsIds[] = $val["goods_id"]; }
		}
		$newgIds = implode(",",$goodsIds);
		//删除三个地方的数据（goods_videos、goods、goods_diamond）
		if(!empty($newgIds)){
			//删除相关图片
			$oldGoodsImagesList = db('goods_images')->where("goods_id in (".$newgIds.")")->select();
			if($oldGoodsImagesList){
				foreach($oldGoodsImagesList as $ogi){
					$rs_ogi = db('goods_images')->where("big='".$ogi["big"]."'")->delete();	
					$ogiUrl = substr($ogi["big"],1);
					if($rs_ogi){ 
						@unlink($ogiUrl);
					}	
				}
			}
			//删除相关视频
			$goodsVideosList = db("goods_videos")->where("goods_id in (".$newgIds.")")->select();
			if(!empty($goodsVideosList)){
				$rs_gv = db('goods_videos')->where("goods_id in (".$newgIds.")")->delete();		
				foreach($goodsVideosList as $v){
					$nowWrl = substr($v["url"],1);
					@unlink($nowWrl);
				}
			}
			
			
			$rs_g = db("goods")->where("id in (".$newgIds.")")->delete();
			if($rs_g){
				$rs_gd	= db('goods_diamond')->where("id in (".$ids.")")->delete();	
				if($rs_gd){
					$this->commit(); 
					return true;
				}else{
					$this->rollback();
					return false;
				}	
			}else{
				$this->rollback();
				return false;		
			}						
		}else{
			$rs_gd	= db('goods_diamond')->where("id in (".$ids.")")->delete();	
			if($rs_gd){
				$this->commit(); 
				return true;
			}else{
				$this->rollback();
				return false;
			}		
		}
	}

	//zwx 获取钻石列表
	public function getGoodsDiamondList($where='',$page=1,$size=15,$order='id desc'){
		$total = M('goods_diamond')->alias("gd")
				->join('goods g','g.id=gd.goods_id','left')
				->where($where)->count();

		if(!$total) return ;
		$list = M('goods_diamond')->alias("gd")
				->field('gd.*,g.product_status')
				->join('goods g','g.id=gd.goods_id','left')
				->where($where)
				->order($order)->page($page,$size)->select();

		$list = logic('PriceCalculation')->goods_price($list);
		
		$result['page']=$page;
        $result['size']=$size;
        $result['total']=$total;
        $result['data']=$list;
        return $result;
	}

	//zwx 获取钻石单个信息
	public function getGoodsDiamondInfo($where=''){
		$info = M('goods_diamond')->alias("gd")
				->field('gd.*,g.product_status,g.thumb,g.price')
				->join('goods g','g.id=gd.goods_id','left')
				->where($where)->find();
		$info = logic('PriceCalculation')->goods_price([$info]);
        return $info[0];
	}
	
	/**
	 * 导入裸钻数据操作（添加数据）
	 * auth	：zengmm
	 * time	：2017-12-27
	**/
    public function addImportInfo($data)
    {
		//向goods表插入一条数据
		$data_g = array();
		$data_g["agent_id"] = $data["agent_id"];
		$data_g["name"] = $data["goods_name"];
		$data_g["type"] = $data["type"];
		$data_g["price"] = $data["global_price"];
		$data_g["stock_num"] = $data["goods_number"];
		$data_g["stock_status"] = $data["inventory_type"];
		$data_g["create_time"] = date("Y-m-d H:i:s");
		$data_g["create_user"] = $_SESSION["think"]["admin_id"];
		$data_g["product_status"] = 1;
		$data_g["code"] = D('goods')->getGoodsCode();
		$goods_id = D('goods')->insertGetId($data_g);
		//向裸钻表插入一条数据
		$data["goods_id"] = $goods_id;
		$action	= D('goods_diamond')->insert($data);
		if($action){	
			return true;	
		}else{
			return false;
		}	
	}

	/**
	 * 导入裸钻数据操作（修改数据）
	 * auth	：zengmm
	 * time	：2017-12-27
	**/
    public function saveImportInfo($data,$id)
    {
		//修改goods表数据
		$data_g = array();
		$goods_id = $data["goods_id"];
		$data_g["name"] = $data["goods_name"];
		$data_g["type"] = $data["type"];
		$data_g["price"] = $data["global_price"];
		$data_g["stock_num"] = $data["goods_number"];
		$data_g["stock_status"] = $data["inventory_type"];
		$rs = D('goods')->where("id=".$goods_id)->update($data_g);
		//修改裸钻数据
		$action	= D('goods_diamond')->where("id=".$id)->update($data);
		if($action !== false){
			return true;	
		}else{
			return false;
		}	
	}
	
	/**
	 * 同步裸钻数据操作（添加数据）
	 * auth	：zengmm
	**/
    public function addSynchroInfo($data)
    {
		//向goods表插入一条数据
		$data_g = array();
		$data_g["agent_id"] = $data["agent_id"];
		$data_g["supply_id"] = $data["supply_id"];
		$data_g["supply_goods_id"] = $data["supply_gid"];
		$data_g["supply_goods_type"] = $data["supply_goods_type"];
		$data_g["name"] = $data["goods_name"];
		$data_g["type"] = $data["type"];
		$data_g["price"] = $data["goods_price"];
		$data_g["stock_num"] = $data["goods_number"];
		$data_g["stock_status"] = $data["inventory_type"];
		$data_g["create_time"] = $data["create_time"];
		$data_g["update_time"] = $data["create_time"];
		$data_g["product_status"] = $data["product_status"];
		$data_g["code"] = D('goods')->getGoodsCode();
		$goods_id = M('goods')->insertGetId($data_g);
		$imageurl = $data["imageurl"];
		$videourl = $data["videourl"];
		unset($data["supply_goods_type"]);
		unset($data["imageurl"]);
		unset($data["videourl"]);
		unset($data["create_time"]);
		unset($data["product_status"]);
		//向裸钻表插入一条数据
		$data["goods_id"] = $goods_id;
		$action	= M('goods_diamond')->insert($data);
		if($action){
			//如果图片存在
			/*if(strstr($imageurl,"http://") !== false){
				$data_gi["small"] = $imageurl; 
				$data_gi["big"] = $imageurl; 
				$data_gi["goods_id"] = $goods_id;
				$data_gi["agent_id"] = 0; 
				$rs_gi = M('goods_images')->insert($data_gi);
				if(!$rs_gi){
					return false; 	
				}
			}
			//如果视频存在，添加视频到zm_goods_videos表
			if(strstr($videourl,"http://") !== false){
				$data_v["url"] = $videourl;
				$data_v["goods_id"] = $goods_id;
				$data_v["create_time"] = date("Y-m-d H:i:s");
				$data_v["agent_id"] = 0; 
				$gvId = M('goods_videos')->insert($data_v);
				if($gvId){
					return true;	
				}else{
					return false; 
				}
			}else{
				return true;
			}*/
			return true;
		}else{
			return false;
		}	
	}
	
	/**
	 * 同步裸钻数据操作（修改数据）
	 * auth	：zengmm
	**/
    public function saveSynchroInfo($data,$id)
    {
		//修改goods表数据
		$data_g = array();
		$data_g["supply_id"] = $data["supply_id"];
		$data_g["supply_goods_id"] = $data["supply_gid"];
		$data_g["supply_goods_type"] = $data["supply_goods_type"];
		$data_g["name"] = $data["goods_name"];
		$data_g["type"] = $data["type"];
		$data_g["price"] = $data["goods_price"];
		$data_g["stock_num"] = $data["goods_number"];
		$data_g["stock_status"] = $data["inventory_type"];
		$data_g["update_time"] = $data["create_time"];
		$data_g["product_status"] = $data["product_status"];
		M('goods')->where("id=".$data["goods_id"])->update($data_g);
		/*$imageurl = $data["imageurl"];
		$videourl = $data["videourl"];*/
		unset($data["supply_goods_type"]);
		unset($data["imageurl"]);
		unset($data["videourl"]);
		unset($data["create_time"]);
		unset($data["product_status"]);
		//修改裸钻数据
		$action	= M('goods_diamond')->where("id=".$id)->update($data);
		if($action != false){
			//如果图片存在
			/*if(strstr($imageurl,"http://") !== false){
				$data_gi["small"] = $imageurl; 
				$data_gi["big"] = $imageurl; 
				$rs_gi = M('goods_images')->where("goods_id=".$goods_id)->update($data_gi);	
				if(!$rs_gi){
					return false; 	
				}
			}
			//如果视频存在，添加视频到zm_goods_videos表
			if(strstr($videourl,"http://") !== false){
				$data_v["url"] = $videourl;
				$gvId = M('goods_videos')->where("goods_id=".$goods_id)->update($data_v);
				if($gvId){
					return true;	
				}else{
					return false; 
				}
			}else{
				return true;
			}*/
			return true;	
		}else{
			return false;
		}
	}
}	