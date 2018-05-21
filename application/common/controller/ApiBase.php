<?php
namespace app\common\controller;
use think\Controller;
use think\Config;


class ApiBase extends Controller
{  
    protected $agent_id;
    protected $token;
    protected $agent_base;//代理商基本信息，需要缓存
    protected $agent_config;//代理商配置信息，需要缓存
    protected $agent_template;//网站模板信息
    public function __construct(){ 
        parent::__construct();        
    }

    protected function _initialize()
    { 
        header('Access-Control-Allow-Origin:*');
        header("Access-Control-Allow-Headers: token,Origin, X-Requested-With, Content-Type, Accept");
        header('Access-Control-Allow-Methods: POST,GET');
        $this->agent_id=get_agent_id();
        $this->token=get_header_token();
        parent::_initialize();                       
    }
    
    /**
     * auth	：fire
     * content：检查用户登录状态,由各方法手动调用
     * time	：2017-10-31
     **/
    protected function checkUserLogin(){
        $uid= get_userid_token();
        if(empty($uid)){
            $result['status']=-1;
            $result['msg']='会话已失效，请重新登录';           
            exit(json_encode($result));
        }
        return $uid;
    }

    
    /**
     *旧版ajax返回函数
     */
    public function ajaxReturn($data)
    {
        exit(json_encode($data));
    }
    
    /**
     *api json返回函数
     */
    public function ajaxFormat($data,$status=100,$msg='success')
    {
        $result['status']=$status;
        $result['msg']=$msg;
        $result['data']=$data;        
        exit(json_encode($result));
    }

    /**
     * auth ：sxm
     * content：这里是第二个判断登录  用于检查登录用户与不登录用户状态区分
     * time ：2017-10-31
     **/
    protected function checkuserinfo(){
        $uid= get_userid_token();
        return $uid ? $uid : 0;
    }
}