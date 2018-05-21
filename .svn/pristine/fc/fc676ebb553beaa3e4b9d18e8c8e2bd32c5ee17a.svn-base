<?php
namespace app\mobile\controller;
use think\Db;
use function think\name;
use app\common\controller\MobileBase;
use app\common\logic\Msg;

class Com extends MobileBase
{    
    
    /**
     * 登录 *    
     */
    public function login(){
        return $this->fetch();
    }
    
    /**
     * 注册 *
     */
    public function reg(){
        return $this->fetch();
    }
    /**
     * 忘记密码 *
     */
    public function wjpassword(){
        return $this->fetch();
    }

    /**
     * 登出 *
     */
    public function logout(){
        return $this->fetch();
    }
    
    /**
     * 在线客服 *
     */
    public function kefu(){
        // 临时代码
        $agentinfo = get_agent_base();        
        $kfappkey='24646659';
        if ($agentinfo['kf_appkey']) {
            $kfappkey = $agentinfo['kf_appkey'];
        }      
        $this->assign("kfappkey", $kfappkey);
        
        $kfgroupid = '162739905';
        if ($agentinfo['kf_groupid']) {
            $kfgroupid = $agentinfo['kf_groupid'];
        }
        $this->assign("kfgroupid", $kfgroupid);
        
        $kfusername = '速易宝宝宝:阳阳';
        if ($agentinfo['kf_username']) {
            $kfusername = $agentinfo['kf_username'];
        }
        $this->assign("kfusername", $kfusername);
        
        $imusername = '';
        $impassword = '';
        
        
        $kfsecretKey="3b1d1833b91e5add8dd42d0c366e11f4";
        if ($agentinfo['kf_secretKey']) {
            $kfsecretKey = $agentinfo['kf_secretKey'];
        }
        
        //echo $kfappkey;echo $kfsecretKey;exit();
        
        // 已经登录
        if ($this->user) {
             // 检查当前用户是 否已经有通讯编号
             $imusername = $this->user['username'];
             $msg = new Msg();
             $impassword = $msg->imGetUser($imusername,$kfappkey,$kfsecretKey);
             if (empty($impassword)) {
                 $impassword = $msg->imAddUser($imusername,$imusername,$kfappkey,$kfsecretKey);
             }
         }
        
        if (empty($imusername)) {
            $imusername = session_id();
            $nick = "访客(" . $imusername . ")";
            $msg = new Msg();
            $impassword = $msg->imAddUser($imusername, $nick);
        }
        
        $this->assign("imusername", $imusername);
        $this->assign("impassword", $impassword);
        
        return $this->fetch();
    }    
}
