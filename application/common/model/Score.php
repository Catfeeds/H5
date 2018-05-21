<?php
namespace app\common\model;
use think\db;
use think\Model;

//积分
class Score extends Model
{
  
    /**
     * 添加积分
     * param uid 用户编号
     * param agent_id 代理商编号
     * param actioncode 行为编码
     * param remark 备注
     * param datajson 用于计算的数组
     * param rate 积分比率，比如订单积分为1，订单用来算积分的金额为500，那么rate=500
     * @return bool
     */
    public function addrec($uid,$agent_id,$actioncode,$remark='',$data=NULL,$rate=1){
        $datajson=json_encode($data);
        $param['uid']=$uid;
        $param['agent_id']=$agent_id;
        $param['actioncode']=$actioncode;
        $param['remark']=$remark;
        $param['datajson']=$datajson;  
        $param['rate']=$rate;//积分比率
        DB::execute("call score_add (:uid,:agent_id,:actioncode,:remark,:datajson,:rate)",$param);
        return true;
    }

    //zwx 设置用户积分
    public function setUserScore($uid,$agent_id,$score,$remark,$actioncode='',$datajson=''){
        if($score==0||!$uid||!$agent_id) return false;

        if($score>0){
            $b = M('user')->where(['id'=>$uid])->update(['score'=>['exp','score+'.$score],'score_total'=>['exp','score_total+'.$score]]);
            $user = M('user')->where(['id'=>$uid])->find();

            $this->Membership_Upgrade($agent_id,$user); //会员升级
        }else{
            $b = M('user')->where(['id'=>$uid])->update(['score'=>['exp','score+'.$score]]);
        }

        if(!$b) return false;

        $user_score_add['uid'] = $uid;
        $user_score_add['agent_id'] = $agent_id;
        $user_score_add['score'] = $score;
        $user_score_add['remark'] = $remark;
        $user_score_add['actioncode'] = $actioncode;
        $user_score_add['datajson'] = $datajson;
        $bool = M('user_score')->insert($user_score_add);
        return $bool;
    }

    //zwx 会员升级
    public function Membership_Upgrade($agent_id,$user){
        $where['agent_id'] = $agent_id;
        $where['score_min'] = ['elt',$user['score_total']];

        $score_rank = M('score_rank')->where($where)->order('score_min desc')->find();
        if($score_rank&&$user['rank_id']!=$score_rank['id']){
            M('user')->where(['id'=>$user['id']])->update(['rank_id'=>$score_rank['id'],'rank_name'=>$score_rank['name']]);
        }
    }

    //zwx 
    public function getInfo($where=''){
        $info = M('score')->where($where)->find();
        return $info;
    }

    //zwx 设置积分行为 $data 要保存的数据 $act add,edit $where修改条件
    public function setScore($data,$act='',$where=''){
        isset($data['score'])?$save['score'] = $data['score']:''; //积分值

        if($act == 'add'){
            $id = M('score')->insertGetId($save);
            return $id;
        }else if($act == 'edit'){
            $bool = M('score')->where($where)->update($save);
            return $bool;
        }
    }

    //zwx 设置会员等级 $data 要保存的数据 $act add,edit $where修改条件
    public function setScoreRank($data,$act='',$where=''){
        isset($data['name'])?$save['name'] = $data['name']:''; //等级名称
        isset($data['note'])?$save['note'] = $data['note']:''; //等级备注
        isset($data['score_min'])?$save['score_min'] = $data['score_min']:''; //升级起始积分
        isset($data['scroe_max'])?$save['scroe_max'] = $data['scroe_max']:''; //升级最高积分
        if(isset($data['rate'])){
            $save['rate'] = $data['rate']!=''?$data['rate']:NULL; //积分率，0-100或空
        }
        isset($data['discount'])?$save['discount'] = $data['discount']:''; //折扣率,百分制
        isset($data['agent_id'])?$save['agent_id'] = $data['agent_id']:'';
        
        if($act == 'add'){
            $id = M('score_rank')->insertGetId($save);
            return $id;
        }else if($act == 'edit'){
            $bool = M('score_rank')->where($where)->update($save);
            return $bool;
        }
    }

    //zwx 
    public function getScoreRankInfo($where=''){
        $info = M('score_rank')->where($where)->find();
        return $info;
    }

    //设置会员积分分组 $data 要保存的数据 $act add,edit $where修改条件
    public function setScoreType($data,$act='',$where=''){
        isset($data['name'])?$save['name'] = $data['name']:''; //等级名称
        isset($data['note'])?$save['note'] = $data['note']:''; //等级备注
        if(isset($data['rate'])){
            $save['rate'] = $data['rate']!=''?$data['rate']:NULL; //积分率，0-100或空
        }
        isset($data['discount'])?$save['discount'] = $data['discount']:''; //折扣率,百分制
        isset($data['agent_id'])?$save['agent_id'] = $data['agent_id']:'';

        if($act == 'add'){
            $id = M('score_type')->insertGetId($save);
            return $id;
        }else if($act == 'edit'){
            $bool = M('score_type')->where($where)->update($save);
            return $bool;
        }
    }

    //zwx
    public function getScoreTypeInfo($where=''){
        $info = M('score_type')->where($where)->find();
        return $info;
    }
}