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
 * Class Banner
 * @package app\wemini\controller
 */
class Banner extends Controller
{

    public function getbanner()
    {
        try{

            $banner = Db::name('XmBanner')->where('is_deleted',0)->order('sort desc')->select();

            $this->success('ok',$banner);
        }
        catch(Exception $e){
            $this->error('内部错误');
        }

    }
}
