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
 * 记录管理
 * Class Paper
 * @package app\backstage\controller
 */
class Paper extends Controller
{

    /**
     * 指定当前数据表
     * @var string
     */
    public $table = 'XmSubjectPaper';
    public $otype = [
        1 => '智能考点练习',
        2 => '章节考点练习',
        3 => '真题模考'
    ];

    /**
     * 考试记录列表管理
     * @auth true
     * @menu true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $this->title = '考试记录';
        $this->assign('otype',$this->otype);

        $this->assign('subject_class',Db::name("XmSubjectClass")->where(['is_deleted' => '0'])->order('id desc')->select());
        $this->assign('member_class',Db::name("XmMemberClass")->where(['is_deleted' => '0'])->order('id desc')->select());
        $query = $this->_query($this->table)->like('otype,real_name')->equal('cid,m.class_id#mc_id');
        $query->alias('p')
            ->join('xm_member m','m.id = p.uid','LEFT')
            ->join('xm_subject_class sc','sc.id = p.cid','LEFT')
            ->dateBetween('p.create_at#create_at')
            ->order('p.id')
            ->field('m.real_name,m.class_no,p.*,sc.name as subject_class_name')->page();

    }

    /**
     * 列表数据处理
     * @auth true
     * @param array $data
     * @throws \Exception
     */
    protected function _index_page_filter(&$data)
    {
        $right_pre = 0;
        $score = 0;
        $time = 0;
        foreach($data as &$vo){
            $vo['sub_id'] = json_decode($vo['sub_id'],true);
            $right_pre += $vo['right_pre'];
            $score += $vo['score'];
            $time += $vo['time'];
            //$vo['uname'] = Db::name('XmMember')->where('id',$vo['uid'])->value('nickname');
        }

        $this->assign('avg_right_pre',count($data)?round($right_pre/count($data),2):0);
        $this->assign('avg_score',count($data)?round($score/count($data)):0);
        $this->assign('avg_time',count($data)?round($time/count($data)):0);
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

    /**
     * 删除
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function remove()
    {
        $this->applyCsrfToken();
        $this->_delete($this->table);
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
                $return_subject =Db::name('XmSubject')->whereIn('id',json_decode($info['sub_id'],true))->select();
                $class_name = Db::name('XmSubjectClass')->where('id',$info['cid'])->value('name');
                foreach($return_subject as $k=>$v){

                    $u_answer = json_decode($info['u_answer'],true);
                    $return_subject[$k]['sub_stem'] = $v['sub_stem'];
                    $return_subject[$k]['answer'] = json_decode($v['answer'],true);
                    $return_subject[$k]['u_answer'] = array_key_exists($v['id'],$u_answer)?$u_answer[$v['id']]:'';
                    $return_subject[$k]['cname'] = $class_name;
                    // $return_subject[$k]['is_collect'] = Db::name('XmSubjectCollect')->where('uid',$info['uid'])->where('subject_id',$v['id'])->count();
                }

                $this->assign('subject',$return_subject);
            }

        }
    }



}
