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
use think\Db;
use think\Exception;
use think\exception\HttpResponseException;
use think\facade\Request;

/**
 * 通用接口
 * Class Common
 * @package app\wemini\controller
 */
class Common extends Controller
{

    public $member;
	 
	public $otype = [
		1 => '专业实务',
		2 => '实践能力'
	];

    public function __construct()
    {
        if (request()->param('token')){
            $token = request()->param('token');


            //判断TOKEN是否真实
            $member = Db::name('XmMember')->where('token',$token)->where('is_deleted',0)->find();
            if($member){
                $this->member = $member;
            }else{
                $this->error('用户不存在');
            }

        }else{
            $this->error('非法操作,未接受到TOKEN');
        }

    }
}
