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
use think\Db;
use think\Request;

/**
 * 试题分类
 * Class Subjectclass
 * @package app\backstage\controller
 */
class Subjectclass extends Controller
{

    /**
     * 指定当前数据表
     * @var string
     */
    public $table = 'XmSubjectClass';
    public $category = [];

    public function __construct(){
        parent::__construct();
        $this->category = config('constant.category');
    }

    /**
     * 分类列表管理
     * @auth true
     * @menu true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $this->title = '试题分类';
        $query = $this->_query($this->table)->like('name,cate_id');
        $query->dateBetween('create_at')->where(['is_deleted' => '0'])->order('id desc')->page();
    }

    /**
     * 列表数据处理
     * @auth true
     * @param array $data
     * @throws \Exception
     */
    protected function _index_page_filter(&$data)
    {
        foreach($data as &$vo){
            $vo['snum']  = Db::name('XmSubject')->where(['is_deleted' => '0','cid'=>$vo['id']])->count();
        }
    }

    /**
     * 添加分类信息
     * @auth true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function add()
    {
        $this->applyCsrfToken();
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

            $sub_class_date = input('post.subject_class_date');
            $sub_class_time = input('post.subject_class_time');
            $begin_time = 0;
            $end_time = 0;
            if (!empty($sub_class_date) && !empty($sub_class_time)) {
                $sub_class_times = explode(' - ', $sub_class_time);
                $begin_time = strtotime($sub_class_date.' '.trim($sub_class_times[0]));
                $end_time = strtotime($sub_class_date.' '.trim($sub_class_times[1]));
                if ($begin_time >= $end_time) {
                    $this->error("考试结束时间需大于开始时间！");
                }
            }
           $data['begin_time'] = $begin_time;
           $data['end_time'] = $end_time;
           $data['create_at'] = date('Y-m-d H:i:s');

        }
    }

    /**
     * 编辑分类信息
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

    /**
     * 删除分类
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function remove()
    {
        $this->applyCsrfToken();
        $this->_delete($this->table);
    }

}
