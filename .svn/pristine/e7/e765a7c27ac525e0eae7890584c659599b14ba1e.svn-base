<?php
namespace app\api\controller;

use think\Db;
use function think\name;
use app\common\controller\ApiBase;

class Other extends ApiBase
{
    /**
     * 搜索裸钻详情
     * zhy find404@foxmail.com
     * 2017年11月21日 14:05:55
     */
    public function diamonddetail()
    {
        if (! $_POST['zid'] && ! is_numeric($_POST['zid'])) {
            $result['status'] = 101;
            $result['msg'] = '请输入正确的证书号！';
            $this->ajaxReturn($result);
            exit();
        }
        
        if (! $_POST['ztype'] && ! is_numeric($_POST['ztype'])) {
            $result['status'] = 101;
            $result['msg'] = '请输入正确的裸钻标识！';
            $this->ajaxReturn($result);
            exit();
        }
        
        $Logic = new \app\common\logic\DiamondCertificate();
        $result['status'] = 100;
        $result['data'] = $Logic->GetData($_POST['zid'], $_POST['ztype'], $_POST['weight']);
        $this->ajaxReturn($result);
        exit();
    }

    /**
     * 下载证书
     * zhy find404@foxmail.com
     * 2017年11月21日 14:06:06
     */
    public function downloadGIA()
    {
        if (empty($_POST['reportNumber']) && ! is_numeric($_POST['reportNumber'])) {
            $result['status'] = 101;
            $result['msg'] = '请输入正确的证书号！';
            $this->ajaxReturn($result);
            exit();
        }
        
        $Logic = new \app\common\logic\DiamondCertificate();
        $result['url'] = $Logic->DownloadGIA($_POST['reportNumber']);
        if ($result['url']) {
            $result['status'] = 100;
            $result['msg'] = '下载中，请稍后！';
        } else {
            $result['status'] = 101;
            $result['msg'] = '暂无数据，请稍后！';
        }
        $this->ajaxReturn($result);
        exit();
    }    
  
}
