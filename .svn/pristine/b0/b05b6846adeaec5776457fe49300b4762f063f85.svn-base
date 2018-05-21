<?php
namespace app\common\model;
use think\Model;
use think\db;

class DiamondCertificate extends Model
{
	/**
	 * 查询有无数据
	 * zhy	find404@foxmail.com
	 * 2017年11月11日 14:09:42
	 */
    public function GetInfo($zid,$ztype){
        return  Db::name('report_certificate')->where(['zid'=>$zid,'ztype'=>$ztype])->field('data')->find();
    }	
	
	
	/**
	 * 插入一条数据库
	 * zhy	find404@foxmail.com
	 * 2017年11月13日 15:52:48
	 */
    public function InsertRow($data){
        return 	Db::name('report_certificate')->insertGetId($data);
    }

	
 
	
	
}