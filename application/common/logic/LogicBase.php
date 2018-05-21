<?php

namespace app\common\logic;

/**
 * logic基类，简易的没有model部分的，不需要走逻辑层，有逻辑判断类的才走logic，便于复用，
 * zhy	find404@foxmail.com
 * 2017年12月2日 16:04:37
 */ 
class LogicBase {

	
    public function __construct(){
		$this->agent_id = get_agent_id();
    }
 
}