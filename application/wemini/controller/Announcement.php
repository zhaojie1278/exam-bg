<?php

// +----------------------------------------------------------------------
// | ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2019 广州楚才信息科技有限公司 [ http://www.cuci.cc ]
// +----------------------------------------------------------------------
// | 官方网站: http://demo.thinkadmin.top
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/ThinkAdmin
// | github 代码仓库：https://github.com/zoujingli/ThinkAdmin
// +----------------------------------------------------------------------

namespace app\wemini\controller;

use library\Controller;
use library\tools\Data;
use think\Db;
use think\Exception;
use think\exception\HttpResponseException;

/**
 * 公告接口
 * Class Subject
 * @package app\wemini\controller
 */
class Announcement extends Common
{
    
    /*获取公告列表*/
    public function getlist()
    {

        try{
            $uid = $this->member['id'];

            $list = Db::name('XmAnnouncement')->where('is_deleted',0)->order('id desc')->select();
			if($list)
			{
				foreach($list as &$vo){
					
					
					$vo['title'] = mb_substr($vo['title'],0,15);
					$vo['create_at'] = date("Y-m-d H:i",strtotime($vo['create_at']));
				}
			
			}
			
            $this->success('ok',$list);



        }catch(Exception $e){

            $this->error($e->getMessage());
        }

    }
	
	
    /*获取公告内容*/
    public function getcontent()
    {

        try{
            $uid = $this->member['id'];
			
			if(!request()->post('id')){
				$this->error('未找到参数');
			}

            $list = Db::name('XmAnnouncement')->where('id',request()->post('id'))->find();
			
			
            $this->success('ok',$list);



        }catch(Exception $e){

            $this->error($e->getMessage());
        }

    }
}

