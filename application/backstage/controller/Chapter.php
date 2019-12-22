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

namespace app\backstage\controller;

use library\Controller;
use library\tools\Data;
use think\Db;

/**
 * 章节管理
 * Class Chapter
 * @package app\backstage\controller
 */
class Chapter extends Controller
{

    /**
     * 指定当前数据表
     * @var string
     */
    public $table = 'XmChapter';
    public $star = [
        0=>'无等级',
        1=>'一星级',
        2=>'二星级',
        3=>'三星级',
        4=>'四星级',
        5=>'五星级',
    ];

    /**
     * 章节列表管理
     * @auth true
     * @menu true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $this->title = '章节列表';
        $query = $this->_query($this->table);
        $query->dateBetween('create_at')->where(['is_deleted' => '0'])->order('sort desc')->page();

    }

    /**
     * 列表数据处理
     * @auth true
     * @param array $data
     * @throws \Exception
     */
    protected function _index_page_filter(&$data)
    {
        foreach ($data as &$vo) {
            if($vo['pid'] == 0){
                $ids = Db::name('XmChapter')->where('pid',$vo['id'])->column('id');
                $ids[] = $vo['id'];
                $vo['sub_num'] = Db::name('XmSubject')->whereIn('chapter_id',$ids)->where('is_deleted',0)->count();
            }else{
                $vo['sub_num'] = Db::name('XmSubject')->where('chapter_id',$vo['id'])->where('is_deleted',0)->count();
            }
            $vo['ids'] = join(',', Data::getArrSubIds($data, $vo['id']));
        }
        $data = Data::arr2table($data);
    }

    /**
     * 添加章节
     * @auth true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function add()
    {
        $this->title = '添加章节';
        $this->isAddMode = '1';
        $this->_form($this->table, 'form');
    }


    /**
     * 表单数据处理
     * @param array $data
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function _form_filter(&$data)
    {
        if ($this->request->isPost()) {



        }else{
            $class = Db::name($this->table)->where('pid',0)->select();
            $this->pidlist = $class;
        }
    }

    /**
     * 表单结果处理
     * @param boolean $result
     */
    protected function _form_result($result)
    {
        if ($result && $this->request->isPost()) {
            $this->success('编辑成功！', 'javascript:location.reload()');
        }
    }


    /**
     * 编辑试题
     * @auth true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function edit()
    {
        $this->applyCsrfToken();
        $this->_form($this->table, 'form');
    }



}
