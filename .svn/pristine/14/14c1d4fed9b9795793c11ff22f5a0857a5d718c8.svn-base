<?php
namespace app\mobile\controller;
use think\Controller;

class Wxauth extends Controller
{     
    /**
     * 微信登录 * 
     * by wxh
     */
    public function index()
    {
        vendor("wechat.Wechat");        
        $code = isset($_GET['code'])?$_GET['code']:''; 
        $scope = 'snsapi_base';  //'snsapi_userinfo';
        
        $wxuser=get_agent_wxuser_base(); 
        $options = array(
            'token'=>$wxuser['w_token'], //填写你设定的key
            'encodingaeskey'=>$wxuser['aeskey'], //填写加密用的EncodingAESKey
            'appid'=>$wxuser['appid'], //填写高级调用功能的app id
            'appsecret'=>$wxuser['appsecret'] //填写高级调用功能的密钥
        );        
        
        $we_obj = new \Wechat($options);
        if(empty($code)){
            if ($scope=='snsapi_base') {
                $url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
                $_SESSION['wx_redirect'] = $url;
            } else {
                $url = $_SESSION['wx_redirect'];
            }
            if (!$url) {
                unset($_SESSION['wx_redirect']);
                die('获取用户授权失败');
            }
            $oauth_url = $we_obj->getOauthRedirect($url,"wxbase",$scope);                     
            $this->redirect ($oauth_url );
        }else{            
            $json = $we_obj->getOauthAccessToken();
            if (!$json) {
                unset($_SESSION['wx_redirect']);
                die('获取用户授权失败，请重新确认');
            }
            $user['openid']= $json["openid"];  
            session("user",$user);
            
            $r_url=$_SESSION['wx_redirect'];
            if($r_url){
                $this->redirect($r_url);
            }else{
                $this->redirect(url("index/index"));
            }
        }          
    }   

}
