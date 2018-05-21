<?php
namespace app\common\model;

use think\Model;

class Article extends Model
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
	 * 添加文章 
	 * auth	：zengmm
	 * @param：$data 要保存的数据
	 * time	：2017-11-04
	**/
	public function addInfo($data){
		$action	= db('article')->insert($data);
		if($action){ return true; }else{ return false; }
	}
	
	/**
	 * 获取文章详情
	 * auth	：zengmm
	 * @param：$id 文章ID 
	 * time	：2017-11-04
	**/
	public function getInfoById($id){
		$articleInfo = db('article')->where('id='.$id)->find();
		if($articleInfo){ return $articleInfo; }else{ return false; }
	}
	
	/**
	 * 获取文章详情
	 * auth	：zengmm
	 * @param：$param 文章参数
	 * time	：2017-11-09
	**/
	public function getInfo($param=array()){
		if(!empty($param)){
			$articleInfo = db('article')->where($param)->find();
			if($articleInfo){ return $articleInfo; }else{ return false; }
		}else{
			return false;	
		}
	}
	
	/**
	 * 更新文章数据 
	 * auth	：zengmm
	 * @param：$data 要更新的数据
	 * time	：2017-11-04
	**/
	public function saveInfo($data){
		$action	= db('article')->update($data);
		if($action !== false){ return true; }else{ return false; }
	}

	/**
	* 帮助内容列表
	* auth: zengmm
	* time: 2017/11/29
	**/
	public function getArticleDefaultList($agent_id)
	{
		return M("article_default")->alias("ad")
		->join('article_default_cat adc','adc.id=ad.type','left')
		->where("ad.agent_id=".$agent_id." and adc.agent_id=".$agent_id)
		->field("ad.*,adc.name adcName")
		->select();
	}
	
	/**
	* 添加帮助内容
	* auth: zengmm
	* time: 2017/11/29
	**/
	public function addArticleDefaultInfo($data)
	{
		return $rs = db('article_default')->insert($data);
	}
	
	/**
	* 根据ID获取帮助内容信息
	* auth: zengmm
	* time: 2017/11/29
	**/
	public function getArticleDefaultInfoById($id)
	{
		return $info = db('article_default')->where("id=".$id)->find();
	}
	
	/**
	* 修改帮助内容
	* auth: zengmm
	* time: 2017/11/29
	**/
	public function saveArticleDefaultInfo($data)
	{
		return $rs = db('article_default')->update($data);
	}

	/**
	* 删除帮助内容
	* auth: zengmm
	* time: 2017/11/29
	**/
	public function delArticleDefault($id)
	{
		return $rs	= db('article_default')->where("id=".$id)->delete();	
	}

	/**
	* 公告列表
	* auth: zengmm
	* time: 2017/11/30
	**/
	public function getNoticesList($agent_id)
	{
		return $noticesList = db('notices')->where("agent_id=".$agent_id)->select();
	}
	
	/**
	* 添加公告
	* auth: zengmm
	* time: 2017/11/30
	**/
	public function addNoticesInfo($data)
	{
		return $rs = db('notices')->insert($data);
	}
	
	/**
	* 根据ID获取公告信息
	* auth: zengmm
	* time: 2017/11/30
	**/
	public function getNoticesInfoById($id)
	{
		return $info = db('notices')->where("id=".$id)->find();
	}
	
	/**
	* 修改公告
	* auth: zengmm
	* time: 2017/11/30
	**/
	public function saveNoticesInfo($data)
	{
		return $rs = db('notices')->update($data);
	}

	/**
	* 删除公告
	* auth: zengmm
	* time: 2017/11/30
	**/
	public function delNotices($id)
	{
		return $rs	= db('notices')->where("id=".$id)->delete();	
	}

	/**
	* 根据ID获取评论信息
	* auth: zengmm
	* time: 2017/11/30
	**/
	public function getArticleCommentInfoById($id)
	{
		return $info = db('article_comment')->where("id=".$id)->find();
	}

	/**
	* 审核文章评论
	* auth: zengmm
	* time: 2017/11/30
	**/
	public function saveArticleComment($data)
	{
		return $rs = db('article_comment')->update($data);
	}

	/**
	* 删除评论
	* auth: zengmm
	* time: 2017/11/30
	**/
	public function delArticleComment($id)
	{
		return $rs	= db('article_comment')->where("id=".$id)->delete();	
	}

	
		
	/**
	* 门店列表
	* auth: zengmm
	* time: 2017/12/4
	**/
	public function getStoreList($agent_id)
	{
		return $noticesList = db('store')->where("agent_id=".$agent_id)->select();
	}

	/**
	* 添加门店
	* auth: zengmm
	* time: 2017/12/2
	**/
	public function addStoreInfo($data)
	{
		return $rs = db('store')->insert($data);
	}
	
	/**
	* 根据ID获取门店信息
	* auth: zengmm
	* time: 2017/12/2
	**/
	public function getStoreInfoById($id)
	{
		return $info = db('store')->where("id=".$id)->find();
	}
	
	/**
	* 修改门店信息
	* auth: zengmm
	* time: 2017/12/2
	**/
	public function saveStoreInfo($data)
	{
		return $rs = db('store')->update($data);
	}

	/**
	* 删除门店信息
	* auth: zengmm
	* time: 2017/12/2
	**/
	public function delStore($id)
	{
		return $rs	= db('store')->where("id=".$id)->delete();	
	}
	
	/**
	* 修改门店回访信息
	* auth: zengmm
	* time: 2017/12/4
	**/
	public function getStoreBookedInfo($id)
	{
		return $rows = db('store_booked')->where("id=".$id)->find();
	}
	
	/**
	* 修改门店回访信息
	* auth: zengmm
	* time: 2017/12/4
	**/
	public function saveStoreBooked($data)
	{
		return $rs = db('store_booked')->update($data);
	}
	
	//zwx footer 底部显示数据
	public function getArticleDefaultListShow($agent_id)
	{
		$list = M("article_default")->alias("ad")
		->join('article_default_cat adc','adc.id=ad.type','left')
		->where("ad.agent_id=".$agent_id." and adc.agent_id=".$agent_id)
		->field("ad.*,adc.name adcName")
		->order("sort desc")
		->select();

		$list_adcName = [];
		foreach ($list as $k => $v) {
			$list_adcName[$v['adcName']][] = $v;
		}

		//返回app 使用数据结构
		$list_adcName_app = [];
		foreach ($list_adcName as $k => $v) {
			$list_adcName_app[] = ['adcName'=>$k,'sub'=>$v];
		}
		// dump($list_adcName_app);
		return $list_adcName_app;
	}


}