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
use think\facade\Request;
use library\File;

/**
 * 用户信息接口
 * Class Member
 * @package app\wemini\controller
 */
class Member extends Common
{
    /**
     * 获取小程序配置
     * @return array
     */
    private function config()
    {
        return config('wechat.miniapp');
    }

    public function test(){
        if(input('post.text')){
           $this->success('恭喜您['.$this->member['openid'].']',input('post.text'));
        }

    }

    public function getuserinfo()
    {
        try{
            $uid = $this->member['id'];
            $result_callback = Db::name('XmMember')->where('id',$uid)->field('id as uid,openid,nickname,headimg,phone,token,mail,cate_id')->find();

            $result_callback['otype'] = $this->otype;

            $this->success('ok', $result_callback);
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            $this->error("{$exception->getMessage()}");
        }
    }

    public function editmember()
    {
        try{
            $data = [];
            if(input('post.nickname')){
                $data['nickname'] = input('post.nickname');
            }
            if(input('post.phone')){
                $data['phone'] = input('post.phone');
            }
            if(input('post.mail')){
                $data['mail'] = input('post.mail');
            }
            if(input('post.cate_id')){
                $data['cate_id'] = input('post.cate_id');
            }
            if(input('post.headimg')){
                $data['headimg'] = input('post.headimg');
            }

            if(count($data)){
                $uid = $this->member['id'];

                $res = Db::name('XmMember')->where('id',$uid)->update($data);

                if($res){
                    $result_callback = Db::name('XmMember')->where('id',$uid)->field('id as uid,nickname,headimg,phone,token,mail,cate_id')->find();

					$result_callback['otype'] = $this->otype;


                    $this->success('保存成功！', $result_callback);
                }else{
                    $this->error("您未做任何修改");
                }
            }else{
                $this->error("您未做任何修改");
            }
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            $this->error("{$exception->getMessage()}");
        }
    }


    public function update_headimg()
    {
            $file = request()->file('headimg');

            if (!$file||empty($file)) {
                $this->success('文件上传异常，文件可能过大或未上传');
            }
            if (!$file->checkExt(strtolower(sysconf('storage_local_exts')))) {
                $this->error('文件上传类型受限，请在后台配置');
            }
            if ($file->checkExt('php,sh')) {
                $this->error('可执行文件禁止上传到本地服务器');
            }
            $this->safe = false;
            $this->uptype = 'local';
            $this->extend = pathinfo($file->getInfo('name'), PATHINFO_EXTENSION);
            $name = File::name($file->getPathname(), $this->extend, '', 'sha1');
            $info = File::instance($this->uptype)->save($name, file_get_contents($file->getRealPath()), $this->safe);
            if (is_array($info) && isset($info['url'])) {
                $uid = $this->member['id'];

                if(Db::name('XmMember')->where('id',$uid)->update(['headimg'=>$info['url']])){
                    $this->success('上传成功！',$info['url']);
                }else{
                    $this->error('上传失败！');
                }
            } else {
                $this->error('文件处理失败，请稍候再试！');
            }

    }
}
