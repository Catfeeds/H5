<?php
namespace app\common\logic;
use think\Model;
use org\Ucpaas;
use think\Db;
use org\AliSms;
class Msg
{
    public function __construct(){
        
    }
    
    /**
     * 发送营销服务模板短信,作废
     * 参数  tempatetypekey 模板类型
     * 参数 to 目标手机号码
     * 参数 param：模板中的替换参数，如果有多个参数则需要写在同一个字符串中，以逗号分隔. （如：param=“a,b,c”）
     * @return type
     */
    public function sendServiceSms($agent_id,$tempatetypekey,$to,$param){
        $acount=model('sms')
        ->where('agent_id',$agent_id)
        ->where('clientId',2)
        ->find(); 
        if(empty($acount)){
            return false;//没有开通
        }
        //初始化必填
        $options['accountsid']=config('UCSMS.accountsid');
        $options['token']=config('UCSMS.token');
        $ucpass = new Ucpaas($options);
        header("Content-Type:text/html;charset=utf-8");
        $appId =config('UCSMS.appid');
    
        //读取模板编号
        $templateId=M('sms_template')
        ->where('typekey',$tempatetypekey)
        ->where('status',1)
        ->limit(1)
        ->column('template_id');
        $resultJson= $ucpass->templateSMS($appId,$to,$templateId,$param);
        $result=json_decode($resultJson);
        if($result->resp->respCode==='000000'){
            cache($tempatetypekey.'_'.$to,$param,720);
            //写日志
            $data['phone']=$to;
            $data['content']=$param;
            $data['sendtime']=time();
            $data['status']=1;
            $data['typekey']=$tempatetypekey;
            M('sms_log')->insert($data);
            return true;
        }else{
            return false;
        }
    }
    
     /**
     * 发送模板短信,作废
     * 参数  tempatetypekey 模板类型
     * 参数 to 目标手机号码
     * 参数 param：模板中的替换参数，如果有多个参数则需要写在同一个字符串中，以逗号分隔. （如：param=“a,b,c”）
     * @return type
     */
    public function sendTemplateSms($agent_id,$tempatetypekey,$to,$param){  
        $acount=model('sms')
        ->where('agent_id',$agent_id)
        ->where('clientId',1)
        ->find();   
        if($acount){
            config('UCSMS.accountsid',$acount['accountsid']);
            config('UCSMS.token',$acount['token']);
            config('UCSMS.appid',$acount['appid']);
        }        
        //初始化必填
        $options['accountsid']=config('UCSMS.accountsid');
        $options['token']=config('UCSMS.token');         
        
        $ucpass = new Ucpaas($options);   
        header("Content-Type:text/html;charset=utf-8");
        $appId =config('UCSMS.appid');
        
        //读取模板编号
        $templateId=M('sms_template')
        ->where('typekey',$tempatetypekey)
        ->where('status',1)
        ->limit(1)
        ->column('template_id');
        
        $resultJson= $ucpass->templateSMS($appId,$to,$templateId,$param);     
        $result=json_decode($resultJson);      
        
        
       if($result->resp->respCode==='000000'){           
           cache($tempatetypekey.'_'.$to,$param,720);           
           //写日志
           $data['phone']=$to;
           $data['content']=$param;
           $data['sendtime']=time();
           $data['status']=1;
           $data['typekey']=$tempatetypekey;
           M('sms_log')->insert($data);
           return true;
       }else{
           return false;
       }
    }   

    
    /**
     * 统一发送模板短信
     * 参数  tempatetypekey 模板类型
     * 参数 to 目标手机号码
     * 参数 param：key\value数组，必须与模型一致["sn"=>"","tel"=>""]
     * @return type
     */
    public function sendSms($agent_id,$tempatetypekey,$to,$param){
        header("Content-Type:text/html;charset=utf-8");        
        $data=M('sms_template')
        ->alias('a')
        ->join(" zm_sms_channel c","c.id=a.channel_id")
        ->join(" zm_sms_type t","t.id=a.type_id")
        ->where("a.typekey",$tempatetypekey)
        ->where("a.agent_id",$agent_id)
        ->where("a.status",1)
        ->field("a.template_id,c.channel,c.config,t.params")
        ->find();     
        
        //echo M('sms_template')->getlastsql();
        if($data){ 
            $data['config']=json_decode($data['config'],true);
            $data['params']=json_decode($data['params'],true);
            //阿里云通信
            if($data['channel']){                
                 return $this->sendAli($agent_id, $tempatetypekey, $to, $param, $data);             
            }else{//云之讯
                return $this->sendSmsUC($agent_id, $tempatetypekey, $to, $param, $data);
            }            
        }else{//平台
            if($tempatetypekey=='regis_sms_enable'
                ||$tempatetypekey=='forget_pwd_sms_enable'){
                    $data['config']['accountsid']=config('UCSMS.accountsid');
                    $data['config']['token']=config('UCSMS.token');
                    $data['config']['appid'] =config('UCSMS.appid');
                    $data['template_id'] =config('UCSMS.template_id');
                    return $this->sendSmsUC($agent_id, $tempatetypekey, $to, $param, $data);
            }else{
                return  ["status"=>-1,"msg"=>'未开通短信通道'];
            }            
        }
    }
    
    //阿里云通信
    private function sendAli($agent_id,$tempatetypekey,$to,$param,$data){
        $config=$data['config']; 
        
        //$param参数为数组
        $alisms = new AliSms($config['appid'], $config['appsecret']);
        $template_id=$data['template_id'];
        $sign=$config['sign'];
        $result = $alisms->sign($sign)->data($param)->code($template_id)->send($to);
        if($result.Code=='OK'){
            cache($tempatetypekey.'_'.$to,json_encode($param),300);
            return true;
        }else{            
            return false;
        }
    }
    
    //云之讯短信
    private function sendSmsUC($agent_id,$tempatetypekey,$to,$param,$data){
        $config=$data['config'];
        
        //云之讯，参数的组装方式是逗号分隔
        $pstr=null;
        foreach ($param as $key=>$v){
            $pstr.=$param[$key].",";
        }
        
        $options['accountsid']=$config['accountsid'];
        $options['token']=$config['token'];
        $ucpass = new Ucpaas($options);
        $appId =$config['appid'];
        
        $template_id=$data['template_id'];
        $pstr=rtrim($pstr, ",");
        
        // dump($pstr);exit();
        
        $resultJson= $ucpass->templateSMS($appId,$to,$template_id,$pstr);
        $result=json_decode($resultJson);     
        if($result->resp->respCode==='000000'){
            cache($tempatetypekey.'_'.$to,$pstr,300);
            return true;
        }else{
            return false;
        }
    }
    
    //写日志，暂未使用
    private function sendSmsLog($to,$pstr,$tempatetypekey,$data){        
        //写日志        
         $data['phone']=$to;
         $data['content']=$pstr;
         $data['sendtime']=time();
         $data['status']=1;
         $data['typekey']=$tempatetypekey;
         M('sms_log')->insert($data);         
    }
    
    
    /**
     * 检查获取短信
     * 参数  tempatetypekey 模板类型
     * 参数 to 目标手机号码  
     * @return param：模板中的替换参数，如果有多个参数则需要写在同一个字符串中，以逗号分隔. （如：param=“a,b,c”）
     */
    public function checkTemplateSms($tempatetypekey,$to){
        $param=cache($tempatetypekey.'_'.$to);      
        return isset($param);
    }
    
    /**
     * 添加一个即时通讯帐号
     * 参数  username 用户名，最好用手机
     * 参数 to 目标手机号码
     * @return 密码
     */
    public function imAddUser($username,$nick=null,$appkey=null,$secretKey=null){
       if(empty($nick)){
           $nick=$username;
       }
       vendor("openim.Aliim");  
       $aliim=new \Aliim($appkey,$secretKey);       
       return $aliim->adduser($nick,$username);       
    }
    
    /**
     * 添加一个即时通讯帐号
     * 参数  username 用户名，最好用手机
     * 参数 to 目标手机号码
     * @return 密码
     */
    public function imGetUser($username,$appkey=null,$secretKey=null){
       vendor("openim.Aliim"); 
       $aliim=new \Aliim($appkey,$secretKey);
       return $aliim->getuser($username); 
    }
    
    /**
     * 发送一条系统消息 
     * 参数  $content 消息内容
     * 参数$user_id 目标用户
     * 参数$sender 发送者
     * @return bool
     */
    public function sendUserMsg($content,$user_id,$sender=0){
        //推送一条即时通讯消息
        
        //保存数据库
        $data['uid']=$user_id;
        $data['type']=0;
        $data['content']=$content;
        $data['create_user']=$sender;
        $result=M("user_msg")->insertGetId($data);
        return $result;
    }    
    
    
}