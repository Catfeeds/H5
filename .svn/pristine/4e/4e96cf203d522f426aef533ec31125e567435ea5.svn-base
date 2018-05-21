<?php
namespace app\api\controller;

use think\Controller;
use think\Session;
use app\common\controller\ApiBase;

/**
 * 公共api
 * Class Com
 * @package app\api\controller
 */
class Com extends ApiBase
{
    protected function _initialize()
    {
        parent::_initialize();       
    }  
        
    /**
     *找回密码
     *username:手机号
     *password:设置密码
     *return:100,用户信息
     */
    public function fgword()
    {
        $agent_id=get_agent_id();
        $username=I('username');
        $password=pwdHash(I('password'), $username, $agent_id);
        $code=I('mobliecode');
        $checkcode=cache("regcode_".$username);
        if($code==$checkcode){
            $user=model("user");
            $result=$user->loginfgword($agent_id,$username,$password);
            if($result['status']==100){
                $data = array(
                    'uid' => $result['data']['id'],
                    'agent_id' => $agent_id,
                    'exp_time'=>time()+11117200
                );
                //生成token
                vendor('jwt.JWT');
                $result['data']['token']=\JWT::encode($data, config("login_key"));
                
                //保存登录状态
                $user->setLoginToken($result['data']['id'],$result['data']['token'],$agent_id);
            }
        }else{
            $result['status']=-1;
            $result['msg']='验证码错误';
        }
        
        $this->ajaxReturn($result);
    }
    
   
    /**
     *登录
     *username:手机号
     *password:设置密码
     *return:100,用户信息
     */
    public function login() {
        $agent_id=get_agent_id();
        $user=model('user');
        $username		= I('username','');
        $password		= I('password','');
        $result = $user->login($agent_id, $username, $password);     
      
        if($result['status']==100){
            $data = array(
                'uid' => $result['data']['id'],
                'agent_id' => $agent_id,
                'exp_time'=>time()+11117200
            );
            //生成token
            vendor('jwt.JWT');
            $result['data']['token']=\JWT::encode($data, config("login_key"));
    
            //保存登录状态
            $user->setLoginToken($result['data']['id'],$result['data']['token'],$agent_id);
        }
        $this->ajaxReturn($result);
    }
    
    /**
     *注册
     *username:手机号
     *password:设置密码
     *return:100,用户信息
     */
    public function reg(){
        $agent_id=get_agent_id();
        $username= I('username','');
        $password= I('password','');
        $first_leader=I("uuid",'');//推荐人
        $verifycode=input('verifycode');
        if ($verifycode == cache("regcode_".$username)) {
            $result= model('user')->reg($agent_id,$username,$password,$first_leader);
        } else {
            $result=['status'=>-1,'msg'=>'验证码错误'];
        }
        $this->ajaxReturn($result);
    }
    
   
    /**
     *发送验证码，考虑安全性
     *username:手机号  
     *return:100,是否成功
     */
    public function sendcode($type='regis_sms_enable')
    {
        $to=I('username');
        $agent_id=get_agent_id();
        
        if($type=='regis_sms_enable'){
            $isexit=model("user")->checkUser($agent_id,$to);
            $this->result['status'] = 0;
            if($isexit){
                $this->result['msg'] = '手机已经被占用了！';
                $this->ajaxReturn($this->result);
            }
        }
    
        $code = getrandcode(6, '0123456789');       
        if (! check_mobile_number($to)) {
            $this->result['msg'] = '填写的用户名格式错误!';
            $this->ajaxReturn($this->result);
        }
        $msg = new \app\common\logic\Msg();    
        //$data =$msg->sendTemplateSms($this->agent_id, 'regis_sms_enable', $to, $code);
        $data =$msg->sendSms($this->agent_id, $type, $to, ["code"=>$code]);
        if ($data) {
            cache("regcode_".$to,$code,300);//缓存5分钟
            $this->result['status'] = 100;
            $this->result['msg'] = '短信发送成功，请注意查收！';
        } else {
            $this->result['msg'] = '短信发送失败，请稍后！';
        }
        $this->ajaxReturn($this->result);
    }
    
    
    /**
     *根据微信openid读取用户信息
     *openid:微信公众号openid
     *type:平台类型，0为平台，1为QQ开放平台，2为微信开放平台，3为微信公众号
     *return:100,是否成功
     */
    public function loginbyopenid($type=3){
        $open_id=I('openid');
        $this->result['status'] = 0;
        if($open_id){
            $useropen=model('UserOpen')
                ->where("type",$type)
                ->where("openid",$open_id)
                ->find();
            //echo model('UserOpen')->getlastsql();
           
            if($useropen){                   
                $user=model('user');             
                $userdata =$user->getUserInfoById($useropen['uid']);
                
                $agent_id=get_agent_id();
                if($userdata){
                    $data = array(
                        'uid' => $userdata['id'],
                        'agent_id' => $agent_id,
                        'exp_time'=>time()+7200
                    );
                    //生成token
                    vendor('jwt.JWT');
                    $userdata['token']=\JWT::encode($data, config("login_key"));
                
                    //保存登录状态
                    $user->setLoginToken($userdata['id'],$userdata['token'],$agent_id);
                }
                $this->result['status'] = 100;
                $this->result['msg']="登录成功";
                $this->result['data']=$userdata;  
            }else{
                $this->result['status'] = 101;
                $this->result['msg']="用户未绑定";               
            }
        }else{
            $this->result['status'] = 102;
            $this->result['msg']="参数不正确，请提供openid";
        }
        $this->ajaxReturn($this->result);
    }

  
    /**
     *绑定微信openid到某个用户
     *openid:微信公众号openid，
     *可选参数：nick,head,appid
     *type:平台类型，0为平台，1为QQ开放平台，2为微信开放平台，3为微信公众号
     *return:100,是否成功
     */
    public function setuserbyopenid($type=3){
        $open_id=I('openid');  
        $data['openid']=$open_id;
        $data['nick']=I('nick'); 
        $data['head']=I('head');
        $data['appid']=I('appid');
        $data['type']=$type;
        $user_id=get_userid_token();
        $data['uid']=$user_id;
        $this->result['status'] = 0;        
        if($open_id){
            $useropen=model('UserOpen')
            ->where("type",$type)
            ->where("openid",$open_id)
            ->find();
        }
        if($useropen){                       
            //更新
            model("User")->where("id",$useropen['id'])->saveUserOpen($data);
        }else{
            //插入
            model("User")->addUserOpen($data);
        }
        $this->result['status'] = 100;
        $this->result['msg']="用户绑定成功";
        $this->ajaxReturn($this->result);
    }
    
 
     /**
     *获取省市区所有数据
     *return:array
     */
    public function getRegionAll(){  
        ini_set('max_execution_time', '0');
        $list = model('Region')->select();        
        $data=array2treesingle($list);        
        $this->ajaxReturn($data);
    }    
    
}