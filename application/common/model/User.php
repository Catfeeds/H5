<?php
namespace app\common\model;
use think\Model;


class User extends Model
{
    protected $insert = ['create_time'];
	public $Where 	  = '';
	public $Data 	  = '';
    /**
     * 创建时间
     * @return bool|string
     */
    protected function setCreateTimeAttr()
    {
        return date('Y-m-d H:i:s');
    }
    
    /**
     * 登录
     * @param $username  邮箱或手机
     * @param $agent_id  代理商编号
     * @param $password  密码
     * @return array
     */
    public function login($agent_id,$username,$password){
        $result = array();
        if(!$username || !$password){
            $result= array('status'=>0,'msg'=>'请填写账号或密码');
        }
            
        $user =$this->getUserSingle($agent_id,$username);        
        
        if(!$user){
            $result = array('status'=>-1,'msg'=>'账号不存在!');
        }elseif(pwdHash($password,$username,$agent_id) != $user['password']){
            $result = array('status'=>-2,'msg'=>'密码错误!');
        }elseif($user['is_lock'] == 1){
            $result = array('status'=>-3,'msg'=>'账号异常已被锁定！！！');
        }else{
        	 //登录送积分
            logic('Score')->setScore($user['id'],'login',$agent_id);
            D('score')->Membership_Upgrade($agent_id,$user); //会员升级
            
            //保存登录状态
            $this->setLoginStatus($user['id']);

            $result = array('status'=>100,'msg'=>'登陆成功','data'=>$user);
        }
        return $result;
    }
       
    /**
     * 登录-使用id
     * @param $id  编号
     * @return array
     */
    public function loginById($agent_id,$id){
        $result = array();    
        $user =$this->getUserInfoById($id);
    
        if(!$user){
            $result = array('status'=>-1,'msg'=>'账号不存在!');
        }elseif($user['is_lock'] == 1){
            $result = array('status'=>-3,'msg'=>'账号异常已被锁定！！！');
        }else{
            //保存登录状态
            $this->setLoginStatus($user['id']);
            $result = array('status'=>100,'msg'=>'登陆成功','data'=>$user);
        }
        return $result;
    }
     
     
    /**
     * 注册
     * @param $username  用户名，邮箱或手机
     * @param $password  密码      
     * @return array
     */
    public function reg($agent_id,$username,$password, $first_leader=0){
        $map['agent_id'] = $agent_id;
        $map['username'] = $username;      
        
        if(check_email($username)){          
            $map['email_validated'] = 1;
            $map['email'] = $username; //邮箱注册
        }
    
        if(check_mobile($username)){           
            $map['phone_validated'] = 1;
            $map['phone'] = $username; //手机注册
        }    
    
       if(!$username || !$password){
           return array('status'=>-1,'msg'=>'请输入用户名或密码');
       }  
       
       $user=$this->getUserSingle($agent_id,$username);
       //验证是否存在用户名
       if($user){
           return array('status'=>-1,'msg'=>'账号已存在','data'=>$user);
       }
       
        if($first_leader>0){
       	 	$first_leader_user = M('user')->where(['id'=>$first_leader,'agent_id'=>$agent_id])->find();
	       	if(!$first_leader_user){ //找不到推荐人
	       		$first_leader = 0;
	       	}
        }

       $map['password'] = pwdHash($password,$username,$agent_id);
       $map['reg_time'] = date("Y-m-d H:i:s");  
       $map['usercode'] = md5(time().mt_rand(1,999999999));
       $map['first_leader'] = $first_leader;  
       $map['last_login_time'] = date("Y-m-d H:i:s");
       $user_id = $this->insertGetId($map);
       if(empty($user_id)){
           return array('status'=>-1,'msg'=>'注册失败,请稍候再试');
       }
        
       $user =$this->getUserInfoById($user_id);
       if($first_leader>0){
       		//推荐注册送积分
            logic('Score')->setScore($first_leader,'recommend',$agent_id);
       }

       D('score')->Membership_Upgrade($agent_id,$user); //会员升级

       return array('status'=>100,'msg'=>'注册成功','data'=>$user);
    }
    
    public function checkUser($agent_id,$username){
        return $this->where("agent_id",$agent_id)->where("username",$username)->count();
    }
    
    /**
     * 找回密码并登录
     * @param $username  用户名，邮箱或手机
     * @param $password  密码   
     * @return array
     */
    public function loginfgword($agent_id,$username,$password){
        $result = array();
        if(!$username || !$password){
            $result= array('status'=>0,'msg'=>'请填写账号或密码');
        }
            
        $user =$this->getUserSingle($agent_id,$username); 
        if(!$user){
            $result = array('status'=>-1,'msg'=>'账号不存在!');
        }elseif($user['is_lock'] == 1){
            $result = array('status'=>-3,'msg'=>'账号异常已被锁定！！！');
        }else{            
            //保存登录状态
            // $this->setLoginStatus($user['id'],$password);
            // $result = array('status'=>100,'msg'=>'密码修改成功，即将登录','data'=>$user);
        	//修改为跳转到登录页
        	$data['password']=$password;
			$this->where("id",$user['id'])->update($data);
			
            $result = array('status'=>100,'msg'=>'密码修改成功','data'=>$user);
        }
        return $result;
    }
    
    
    //检查用户是否存在
    public function getUserSingle($agent_id,$username){        
        $map['agent_id']=$agent_id;
        $map['username']=$username;
        $map['phone']=$username;
        $map['email']=$username;        
        return $this
        ->where("agent_id=:agent_id and (username=:username or phone_validated=1 and phone=:phone or email_validated=1 and email=:email)",$map)
        ->field("id,username,realname,rank_id,type_id,password,is_lock,email,email_validated,phone,phone_validated,head_pic,agent_id,reg_time,last_login_ip,last_login_time,rank_id,score_total")
        ->find();
    }
    
    //获取用户详情
    public function getUserInfoById($id){
        return $this->where("id", $id)
        ->field("id,username,realname,rank_id,type_id,password,is_lock,email,email_validated,phone,phone_validated,head_pic,agent_id,reg_time,last_login_ip,last_login_time,birthday,sex,discount,type_id,type_name,score,score_total,rank_name")
        ->find();
    }
    
    //写入登录状态
    public function setLoginStatus($id,$password=null){
        $data['last_login_time']=time();
        $data['last_login_ip']=get_client_ip();  
        if($password){
            $data['password']=$password;
        }
        return $this->where("id",$id)->update($data);
    }
    
    //app写入登录状态
    public function setLoginToken($uid,$token,$agent_id){
        $data['uid']=$uid;
        $data['agent_id']=$agent_id;
        $data['exp_time']=time()+ 7200;        
        $data['token']=$token;        
        db('user_token')->where("uid",$uid)->where("agent_id",$agent_id)->delete();  
        $result= db('user_token')->insert($data);        
        return $result;        
    }
    
    //从数据库检查token,以后可改为从缓存读取
    public function checkToken($uid,$agent_id){        
        $result= M('user_token')->where("uid",$uid)->where("agent_id",$agent_id)->find();        
        return $result;
    }    

	/**
	 * 添加用户	
	 * auth	：zengmm
	 * @param：$data:要添加的数据
	 * time	：2017-11-06
	**/
	public function addInfo($data){
       $data['password'] = pwdHash($data["password"],$data["username"],$data["agent_id"]);
       $data['reg_time'] = date("Y-m-d H:i:s");  
       $data['usercode'] = md5(time().mt_rand(1,999999999));
       $data['last_login_time'] = date("Y-m-d H:i:s");  
       $user_id = $this->insertGetId($data);
       if(empty($user_id)){ return false; }else{ return true; }
	}
	
	/**
	 * 更新用户	
	 * auth	：zengmm
	 * @param：$data:要更新的数据
	 * time	：2017-11-06
	**/
	public function saveInfo($data){
	   //$data['password'] = pwdHash($data["password"],$data["username"],$data["agent_id"]);
       $data['last_login_time'] = date("Y-m-d H:i:s");  
       $rs = db("User")->update($data);
       if(empty($rs)){
           return false;
       }else{
       	   return true;
	   }
	}
	
	/**
	 * 删除用户	
	 * auth	：zengmm
	 * @param：$id 用户ID
	 * time	：2017-11-06
	**/
	public function userDel($id){
		$action	= db("User")->delete($id);
		if($action){ return true; }else{ return false; }	
	}
	
	/**
	 * 用户地址列表	
	 * auth	：zengmm
	 * @param：$id用户ID
	 * time	：2017-11-06
	**/
	public function getRegionInfoById($id){
		$regionData	= db("region")->where("id=".$id)->find();
		return $regionData;
	}
	
	/**
	 * 根据上级ID获取地址，2省级3市区级4区县级
	 * auth	：zengmm
	 * @param：$pid	 用户上级ID
	 * time	：2017-11-06
	**/ 
    public function getRegionList($param=array()){
		if(!empty($param)){
			$data = db("region")->where($param)->select();
			return $data;
		}else{ 
		    $data = db("region")->select();
		    return $data; 
		}
    }
	
	/**
	 * 获取用户收货地址列表
	 * auth	：zengmm
	 * @param：$param	 地址的相参数
	 * time	：2017-11-07
	**/ 
    public function getAddressList($param=array()){
		if(!empty($param)){
			$address_list = db("user_address")->where($param)->order('id DESC')->select();
			return $address_list;
		}else{ return false; }
	}
	
	/**
	 * 添加用户收货地址
	 * auth	：zengmm
	 * @param：$data	 地址信息
	 * time	：2017-11-07
	**/ 
    public function addAddress($data){
    	if ($data['is_default'] == 1) {
    		$this->setDefalut($data);
    	}
        $ua_id = db("user_address")->insertGetId($data);
        if(empty($ua_id)){ return false; }else{ return $ua_id; }
	}

	/**
	 * 获取用户收货地址详情
	 * auth	：zengmm
	 * @param：$id	 地址ID
	 * time	：2017-11-07
	**/ 
    public function getAddressInfoById($id){
		$data = db("user_address")->where("id=".$id)->find();
        return $data;
	}

	//有默认的情况下更改其他地址为非默认
	public function setDefalut($data)
	{
		M('user_address')->where(['uid'=>$data['uid']])->update(['is_default'=>0]);
	}
	/**
	 * 更新用户收货地址
	 * auth	：zengmm
	 * @param：$data	 地址信息
	 * time	：2017-11-07
	**/ 
    public function saveAddress($data){
    	if ($data['is_default'] == 1) {
    		$this->setDefalut($data);
    	}
        $rs = M("user_address")->where("id",$data['id'])->update($data);
        if(empty($rs)){ return false; }else{ return true; }
	}

	/**
	 * 删除用户收货地址
	 * auth	：zengmm
	 * @param：$data	 地址信息
	 * time	：2017-11-07
	**/ 
    public function delAddress($id){
        $rs = db("user_address")->delete($id);
        if(empty($rs)){ return false; }else{ return true; }
	}
	
	/**
	 * 查询用户信息
	 * zhy	find404@foxmail.com
	 * 2017年11月11日 14:09:42
	 */
    public function getUserInfo(){   
        return db('user')->where($this->Where)->field($this->field)->find();
    }	
	
	
	/**
	 * 插入一条用户
	 * zhy	find404@foxmail.com
	 * 2017年11月13日 15:52:48
	 */
    public function InsertRow(){   
        return 	db('user')->insertGetId($this->Data);
    }

	
	/**
	 * 更新用户
	 * zhy	find404@foxmail.com
	 * 2017年11月13日 15:52:48
	 */
    public function UpdateField(){
		return db('user')->where($this->Where)->update($this->Data);
    }	
 
    
    /**
     * 插入一条用户第三方平台信息
     * fire
     */
	public function addUserOpen($data){
	    return 	db('user_open')->insertGetId($data);
	}
	
	/**
	 * 更新一条用户第三方平台信息
	 * fire
	 */
	public function saveUserOpen($id,$data){
	    return 	db('user_open')->where("id",$id)->update($data);
	}
	
	
	/**
	 * 获取角色权限组列表
	 * auth	：zengmm
	 * time	：2017-11-30
	**/ 
    public function getAuthGroupList($agent_id){
		return $authGroupList = db("auth_group")->where("agent_id=".$agent_id)->select();
	}
	
	/**
	 * 获取权限组列表
	 * auth	：zengmm
	 * time	：2017-11-30
	**/ 
    public function getAuthRuleList(){
		$list = db("auth_rule")->where("pid=0 and status=1")->select();
		foreach($list as $key=>$val){
			$list[$key]["sonList"] = db("auth_rule")->where("pid=".$val["id"]." and status=1")->select();	
		}
		return $list;
	}

	/**
	* 添加角色
	* auth: zengmm
	* time: 2017/11/30
	**/
	public function addAuthGroupInfo($data)
	{
		return $rs = db('auth_group')->insert($data);
	}
	
	/**
	* 根据ID获取角色信息
	* auth: zengmm
	* time: 2017/11/30
	**/
	public function getAuthGroupInfoById($id)
	{
		return $info = db('auth_group')->where("id=".$id)->find();
	}
	
	/**
	* 修改角色
	* auth: zengmm
	* time: 2017/12/1
	**/
	public function saveAuthGroupInfo($data)
	{
		return $rs = db('auth_group')->update($data);
	}

	/**
	* 删除角色
	* auth: zengmm
	* time: 2017/12/1
	**/
	public function delAuthGroup($id)
	{
		return $rs	= db('auth_group')->where("id=".$id)->delete();	
	}
	
	/**
	* 获取角色与管理员关联的信息列表
	* auth: zengmm
	* time: 2017/12/1
	**/
	public function getAuthGroupAccessList($group_id){
		return $list = db("auth_group_access")->where("group_id=".$group_id)->select();	
	}

	//zwx 获取收藏列表
	public function getUserGoodsCollectionList($where='',$page=1,$size=15,$order='ugc.id desc'){
		$total = M('user_goods_collection')->alias('ugc')->join('goods g','g.id = ugc.goods_id','left')->where($where)->count();
		if(!$total) return ;
		
		$list = M('user_goods_collection')->alias('ugc')
				->join('goods g','g.id = ugc.goods_id','left')
				->join('zm_goods_diamond gd','g.id = gd.goods_id','left')
				->field('g.supply_goods_id,gd.weight,gd.global_price,gd.dia_discount,ugc.id,ugc.uid,ugc.goods_id,ugc.create_time,g.type,g.name,g.code,g.thumb,g.price')
				->where($where)->order($order)->page($page,$size)->select();
				
		$list = logic('PriceCalculation')->goods_price($list);
        $result['page']=$page;
        $result['size']=$size;
        $result['total']=$total;
        $result['data']=$list;

        return $result;
	}
	
	//zwx 获取收藏单个信息
	public function getUserGoodsCollectionInfo($where){
        return M('user_goods_collection')->field('*')->where($where)->find();
	}

	//zwx 获取用户浏览记录
	public function getUserGoodsViewList($where='',$page=1,$size=15,$order='ugv.create_time desc'){
		$total = M('user_goods_view')->alias('ugv')->join('goods g','g.id = ugv.goods_id','left')->where($where)->count();
		if(!$total) return ;
		
		$list = M('user_goods_view')->alias('ugv')
				->join('goods g','g.id = ugv.goods_id','left')
				->join('zm_goods_diamond gd','g.id = gd.goods_id','left')
				->field('g.supply_goods_id,gd.weight,gd.global_price,gd.dia_discount,ugv.id,ugv.uid,ugv.goods_id,ugv.create_time,g.type,g.name,g.code,g.thumb,g.price')
				->where($where)->order($order)->page($page,$size)->select();

		$list = logic('PriceCalculation')->goods_price($list);

        $result['page']=$page;
        $result['size']=$size;
        $result['total']=$total;
        $result['data']=$list;

        return $result;
	}
}