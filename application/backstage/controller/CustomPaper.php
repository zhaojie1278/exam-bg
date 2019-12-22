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
use PHPExcel_Reader_Excel2007;
use think\Db;

/**
 * 自定义试卷管理
 * Class CustomPaper
 * @package app\backstage\controller
 */
class CustomPaper extends Controller
{

    /**
     * 指定当前数据表
     * @var string
     */
    public $table = 'XmCustomPaper';
    public $category = [];

    public function __construct(){
        parent::__construct();
        $this->category = config('constant.category');
    }

    /**
     * 自定义试卷列表管理
     * @auth true
     * @menu true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $this->title = '自定义试卷列表';
        $query = $this->_query($this->table)->like('otype,nickname');
        $query->dateBetween('create_at')->where(['is_deleted'=>0])->page();

    }

    /**
     * 列表数据处理
     * @auth true
     * @param array $data
     * @throws \Exception
     */
    protected function _index_page_filter(&$data)
    {

    }

    /**
     * 生成自定义试卷
     * @auth true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function add()
    {
        $this->_form($this->table, 'form');
    }

    /**
     * 查看自定义试卷
     * @auth true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function info()
    {
        $this->_form($this->table, 'info');
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
        if ($this->request->isGet()) {
            $id = input('id');

            $info = Db::name($this->table)->where('id',$id)->find();

            if($info){
                $ids = json_decode($info['sub_id'],true);
                $idss =implode(',',$ids);
                //$return_subject =Db::name('XmSubject')->whereIn('id',$idss)->order("INSTR(','.$idss.',',CONCAT(',',id,','))")->select();
                $sql = "select * from xm_subject where id in (".$idss.") order by field(id,".$idss.");";
                $return_subject =Db::query($sql);
                foreach($return_subject as $k=>$v){
                    $return_subject[$k]['answer'] = json_decode($v['answer'],true);
                    $return_subject[$k]['cname'] = Db::name('XmSubjectClass')->where('id',$v['cid'])->value('name');
                }

                $this->assign('subject',$return_subject);
            }

        }

        if($this->request->isPost()){
            $num = $this->request->post('num');
            if(!$num){
                $this->error('请输入题目数量');
            }
            $cate_id = $this->request->post('cate_id');
            $cids = Db::name('XmSubjectClass')->where('cate_id',$cate_id)->where('is_deleted',0)->column('id');
            if(!$cids){
                $this->error('当前科目下未找到试题');
            }

            $chapter_pid = Db::name('XmChapter')->where('pid',0)->where('is_deleted',0)->column('id');
            $chapter_pre = round(1/(count($chapter_pid) + 1),2);

            //困难占比
            $diff_pre = $this->request->post('diff_pre');
            if($diff_pre > 100){
                $this->error('困难占比最多不能超过100');
            }

            $diff_num = round(($diff_pre/100)*$num);
            $diff = [];
            if($diff_num>0){
                if(count($chapter_pid)){
                    foreach($chapter_pid as $k=>$v){
                        $tmp = [];
                        $ids = Db::name('XmChapter')->where('pid',$v)->where('is_deleted',0)->column('id');
                        $ids[] = $v;

                        $cnum = round($diff_num * $chapter_pre);

                        $tmp = Db::name('XmSubject')->whereIn('cid',$cids)->where('difficulty',2)->where('is_deleted',0)->whereIn('chapter_id',$ids)->limit($cnum)->field('*,RAND() as r')->order('r')->select();

                        $diff = array_merge($diff,$tmp);
                    }

                    $no_ids = [];
                    if(count($diff)){
                        foreach($diff as $k=>$v){
                            $no_ids[] = $v['id'];
                        }
                    }

                    $diff_no_num = $diff_num - (count($diff));
                    if($diff_no_num > 0){
                        $tmp = Db::name('XmSubject')->whereNotIn('id',$no_ids)->whereIn('cid',$cids)->where('difficulty',2)->where('is_deleted',0)->limit($diff_no_num)->field('*,RAND() as r')->order('r')->select();
                        $diff = array_merge($diff,$tmp);
                    }

                }else{
                    $diff = Db::name('XmSubject')->whereIn('cid',$cids)->where('difficulty',2)->where('is_deleted',0)->limit($diff_num)->field('*,RAND() as r')->order('r')->select();
                }
            }
            $easy_num = $num - count($diff);
            $easy = [];

            if($easy_num>0){
                if(count($chapter_pid)){
                    foreach($chapter_pid as $k=>$v){
                        $tmp = [];
                        $ids = Db::name('XmChapter')->where('pid',$v)->where('is_deleted',0)->column('id');
                        $ids[] = $v;

                        $cnum = round($easy_num * $chapter_pre);

                        $tmp = Db::name('XmSubject')->whereIn('cid',$cids)->where('difficulty',1)->where('is_deleted',0)->whereIn('chapter_id',$ids)->limit($cnum)->field('*,RAND() as r')->order('r')->select();

                        $easy = array_merge($easy,$tmp);
                    }

                    $no_ids = [];
                    if(count($easy)){
                        foreach($easy as $k=>$v){
                            $no_ids[] = $v['id'];
                        }
                    }

                    $easy_no_num = $easy_num - (count($easy));
                    if($easy_no_num > 0){
                        $tmp = Db::name('XmSubject')->whereNotIn('id',$no_ids)->whereIn('cid',$cids)->where('difficulty',1)->where('is_deleted',0)->limit($easy_no_num)->field('*,RAND() as r')->order('r')->select();
                        $easy = array_merge($easy,$tmp);
                    }

                }else{
                    $easy = Db::name('XmSubject')->whereIn('cid',$cids)->where('difficulty',1)->where('is_deleted',0)->limit($easy_num)->field('*,RAND() as r')->order('r')->select();
                }
            }


            $subject = array_merge($diff,$easy);

            $sub_id = [];

            if(count($subject)){
                shuffle($subject);
                foreach($subject as $k=>$v){
                    $sub_id[] = $v['id'];
                }
            }else{
                $this->error('未随机到题目');
            }


            $data['sub_id'] = json_encode($sub_id);
            $data['create_at'] = date('Y-m-d H:i:s');


        }

    }
    /**
     * 表单结果处理
     * @param boolean $result
     */
    protected function _form_result($result)
    {
        if ($result && $this->request->isPost()) {
            $this->success('组卷成功！', 'javascript:history.back()');
        }
    }

}
