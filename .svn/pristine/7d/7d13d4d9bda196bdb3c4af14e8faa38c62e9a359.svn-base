<?php
/**
 * 上传文件
 * Created by PhpStorm.
 * User: guihongbing
 * Date: 2017/12/19
 */

namespace app\api\controller;
use think\Controller;
use think\image;
use app\common\controller\ApiBase;


class Upload extends ApiBase
{

    protected function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 上传产品图片
     * @return $result array
     * @author guihongbing
     * @date 20171219
     */
    public function goodsImage()
    {
        $host = get_agent_host();
        //获取表单上传文件
        $file = request()->file('image');
        //移动到框架应用根目录/public/uploads/goods目录下
        $info = $file->validate(['size'=>24800,'ext'=>'jpg,png,gif'])->move(ROOT_PATH . 'public' . DS . 'uploads'. DS.'goods');
        if($info){
            //图片域名地址
            $data['url'] = $host.DS.'public' . DS . 'uploads'. DS . 'goods' .DS. $info->getSaveName();
            //文件路径
            $data['image'] = DS.'public' . DS . 'uploads'. DS . 'goods' .DS. $info->getSaveName();

            $image = \think\Image::open ( DS.'public' . DS . 'uploads'. DS . 'goods' .DS . '/' . $info->getSaveName () );
            $image->thumb ( 100, 100,1 )->save ( DS.'public' . DS . 'uploads'. DS . 'goods' .DS . '/thumb/' . $info->getSaveName () );
            //可以获取打开图片的信息
            $image = Image::open('image');
            //按照原图的比例生成一个最大为150*150的缩略图
            $data['thumb'] = $image->thumb(150, 150)->save($data['thumb']);


            $this->ajaxReturn(array('ret'=>0,'msg'=>'','data'=>$data));
        }else{
            //上传失败获取错误信息
            $this->ajaxReturn(array('ret'=>1024,'msg'=>$info->getError(),'data'=>''));
        }
    }
}