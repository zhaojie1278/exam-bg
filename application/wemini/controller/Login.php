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
use think\exception\HttpResponseException;

/**
 * 应用入口
 * Class Index
 * @package app\wemini\controller
 */
class Login extends Controller
{
    /**
     * 获取小程序配置
     * @return array
     */
	 
	public $otype = [
		1 => '专业实务',
		2 => '实践能力'
	
	];
	 
    private function config()
    {
        return config('wechat.miniapp');
    }


    public function login()
    {
        try {
            $code = $this->request->get('code');
            $result = \We::WeMiniCrypt($this->config())->session($code);
            if (isset($result['openid'])) {
                data_save('XmMember', ['openid' => $result['openid'],'session_key' => $result['session_key'],'token' => sha1($result['openid'].$result['session_key'])], 'openid',['is_deleted'=>0]);

                $result_callback = Db::name('XmMember')->where(['openid' => $result['openid'],'is_deleted'=>0])->field('id as uid,openid,nickname,headimg,phone,token,mail,cate_id')->find();
				
				$result_callback['otype'] = $this->otype;

                $this->success('授权CODE信息换取成功！', $result_callback);
            } else {
                $this->error("[{$result['errcode']}] {$result['errmsg']}");
            }
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            $this->error("授权CODE信息换取失败，{$exception->getMessage()}");
        }
    }
}
