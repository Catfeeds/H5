<?php
namespace app\common\controller;
use think\Controller;
use think\Config;

class MobileBase extends Controller
{
    protected $agent_id;
    protected $agent_base;//代理商基本信息，需要缓存
    protected $agent_config;//代理商配置信息，需要缓存
    protected $agent_template;//网站模板信息    
    protected $user;//前端用户信息

	
    public function __construct(){  
        $this->agent_base	 = get_agent_base();        
        $this->agent_config  = get_agent_config();
        $this->agent_template= get_agent_template($this ->agent_config['mobile_template_id']); 
        if($this->agent_template){
            $path=$this->agent_template['path'];
            config("template.view_path",'./themes/mobile/'.$path.'/');
            config("view_replace_str.__IMG__","/public/mobile/".$path."/img");
            config("view_replace_str.__JS__","/public/mobile/".$path."/js");
            config("view_replace_str.__CSS__","/public/mobile/".$path."/css");
        }  
        
        parent::__construct();//上面代码一定要放在前面
        header('Access-Control-Allow-Origin:*');     
        $this->setTemplate($this->agent_config['mobile_template_id']); //设置模板   
        
        $this->user = session('user');	
        $this->assign("user",$this->user);
        
        //获取微信公众平台用户信息
        // if(is_weixin()
        //     &&$this->agent_config['iswxmp'] //是否绑定微信公众号
        //     &&empty($this->user)){
        //         $wxuser=get_agent_wxuser_base();
        //         if($wxuser['appid']){
        //             $this->redirect(url("Wxauth/index"));
        //         }
        // }   
        //jssdk调试
        if(is_weixin() && $this->agent_config['iswxmp']){ //是否绑定微信公众号
                $wxuser=get_agent_wxuser_base();
                if (empty($this->user) && $wxuser['appid']) {
                    $this->redirect(url("Wxauth/index"));
                } else {
                    $this->getjssdk($wxuser);
                }
        }   
        ///*临时代码，不应该用
        $this->assign('actionpath',$this->request->path());
        $this->assign('apiurl','http://'.$_SERVER['HTTP_HOST'].'/api');
        $this->assign('apiurl2',config('apiurl2'));
        //*/
        
        //检查是否有推荐人
        if(!empty($_GET['uuid'])){session("uuid",$_GET['uuid']);}
        if(!empty(session("uuid"))){$this->assign("uuid",session("uuid"));}
    }    
    
    /**
     * 检查并设置模板
     */
    public  function setTemplate($template_id){ 
        $this->assign("agent",$this ->agent_base);
        $this->assign("config",$this ->agent_config);
        
        $config_id=$this->agent_template['config_id'];//模板编号 =
        $this->assign("config_id",$config_id);        
        $this->agent_id=get_agent_id();
        
        //前台导航菜单
        $nav=model("nav")->where("status=1 and type=0")
                        ->where("agent_id",$this->agent_id)
                        ->cache("nav_".$this->agent_id,300)
                        ->select();
        $navtree=array2treesingle($nav);               
        $this->assign("nav",$navtree);    
        
        //全局广告项
        $templateitemall=cache("templateitemall_".$this->agent_id);
        if(empty($templateitemall)){
            $templateitemall=model("template")->getComListByPage($this->agent_id,$config_id,'all');
            cache("templateitemall_".$this->agent_id,$templateitemall,300);
        }
        $this->assign("templateitemall",$templateitemall);
    }
    
    
    
    /**
     *旧版ajax返回函数
     */
    public function ajaxReturn($data)
    {
        echo json_encode($data);
    }

    protected function checkuserinfo(){
        $uid= get_userid_token();
        return $uid ? $uid : 0;
    }

    public function getjssdk($wxuser)
    {    
        vendor("wechat.JSSDK");        
        $jssdk = new \JSSDK($wxuser['appid'], $wxuser['appsecret']);
        $signPackage = $jssdk->GetSignPackage();
        $this->assign("signPackage",$signPackage);
    }
}