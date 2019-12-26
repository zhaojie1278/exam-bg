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
use PHPExcel_IOFactory;
use PHPExcel;


/**
 * 试卷
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
     * 试卷管理
     * @auth true
     * @menu true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $this->title = '试卷列表';
        $query = $this->_query($this->table)->like('name,cate_id');
        $query->dateBetween('create_at')->where(['is_deleted' => '0'])->order('id desc')->page();
    }

    // 试题数据统计：平均分、考试人数、正确率
    private function sub_class_statis($sub_cid) {
        $list = Db::name("XmSubjectClass")
            ->alias('c')
            ->join('xm_subject_paper p', 'c.id=p.cid')
            ->join('xm_subject s', 's.cid=c.id')
            ->field('c.*,avg(p.score) as avg_score,count(distinct s.id) as subject_count,count(distinct p.uid) as member_count, avg(p.right_pre) as avg_right_pre')
            ->group('c.id')
            ->where(['c.id' => $sub_cid])->select();
            return $list;
    }

    // 考试学员统计：考试程序倒序排序
    private function paper_statis($sub_cid) {
        $list = Db::name("XmSubjectPaper")
            ->alias('p')
            ->join('xm_subject_class c', 'c.id=p.cid')
            ->join('xm_member m', 'p.uid=m.id')
            ->join('xm_member_class mc', 'mc.id=m.class_id')
            ->field('p.*,c.name as subject_class_name,m.real_name,m.class_no,m.create_at as paper_time,mc.name as mc_name')
            ->order('p.score desc')            
            ->where(['p.cid' => $sub_cid])->select();
            return $list;
    }

    // 试题数据分析：错题数、未做数、标注数
    private function sub_statis($sub_cid) {
        $rs_data = [];

        $count = Db::name("XmSubjectStatis")->where(['cid' => $sub_cid])->count('id');
        if ($count == 0) {
            // 整合数据：题目错题人数、题目标注人数、题目已做人数

            $all_subs = Db::name("XmSubject")->where(['cid' => $sub_cid])->order('id')->select();

            $sub_statised = [];

            // 1. 正确/错误
            $right_subs = Db::name('XmSubjectPaperSingle')
                ->field('sub_id,is_right,count(id) as s_count')
                ->where(['cid' => $sub_cid, 'is_done' => 1])
                ->group('sub_id,is_right')
                ->select();
            foreach($right_subs as $r) {
                $sub_id = $r['sub_id'];
                if ($r['is_right'] == 1) {
                    $sub_statised[$sub_id]['right_count'] = $r['s_count'];
                } else if ($r['is_right'] == 0) {
                    $sub_statised[$sub_id]['unright_count'] = $r['s_count'];
                }
            }

            // 2. 标注/未标注
           $mark_subs = Db::name('XmSubjectPaperSingle')
               ->field('sub_id,is_mark,count(id) as s_count')
               ->where(['cid' => $sub_cid])
               ->group('sub_id,is_mark')
               ->select();
            foreach($mark_subs as $m) {
                $sub_id = $m['sub_id'];
                if ($m['is_mark'] == 1) {
                    $sub_statised[$sub_id]['mark_count'] = $m['s_count'];
                } else if ($m['is_mark'] == 0) {
                    $sub_statised[$sub_id]['unmark_count'] = $m['s_count'];
                }
            }

            // 3. 题做/未做
           $done_subs = Db::name('XmSubjectPaperSingle')
               ->field('sub_id,is_done,count(id) as s_count')
               ->where(['cid' => $sub_cid])
               ->group('sub_id,is_done')
               ->select();
            foreach($done_subs as $d) {
                $sub_id = $d['sub_id'];
                if ($d['is_done'] == 1) {
                    $sub_statised[$sub_id]['done_count'] = $d['s_count'];
                } else if ($d['is_done'] == 0) {
                    $sub_statised[$sub_id]['undone_count'] = $d['s_count'];
                }
            }

            $sub_statised_data = [];
            foreach($all_subs as $sub) {
                $single_sub_statised = array(
                    'cid' => $sub_cid,
                    'sub_id' => $sub['id'],
                    'done_count' => 0,
                    'undone_count' => 0,
                    'right_count' => 0,
                    'unright_count' => 0,
                    'mark_count' => 0,
                    'unmark_count' => 0,
                    'create_at' => date('Y-m-d H:i:s', time()),
                );
                $sub_statised_data[] = array_merge($single_sub_statised, $sub_statised[$sub['id']]);
            }
            $done_subs = Db::name('XmSubjectStatis')->insertAll($sub_statised_data);
        }

        if (1) {
            // 查找错题数最多的题目
            $wrong_sub = Db::name('XmSubjectStatis')
                ->alias('ss')
                ->join('xm_subject sub', 'ss.sub_id=sub.id')
                ->field('ss.*,sub.question')
                ->where(['ss.cid' => $sub_cid])->order('unright_count desc')->find();
            if (!empty($wrong_sub) && $wrong_sub['unright_count'] > 0) {
                $rs_data['wrong'] = $wrong_sub;
            } else {
                $rs_data['wrong'] = [];
            }

            // 查找正确率最高的题目
            $right_sub = Db::name('XmSubjectStatis')
                ->alias('ss')
                ->join('xm_subject sub', 'ss.sub_id=sub.id')
                ->field('ss.*,sub.question')
                ->where(['ss.cid' => $sub_cid])->order('right_count desc')->find();
            if (!empty($right_sub) && $right_sub['right_count'] > 0) {
                $rs_data['right'] = $right_sub;
            } else {
                $rs_data['right'] = [];
            }

            // 标注数最多的题目
            $mark_sub = Db::name('XmSubjectStatis')
                ->alias('ss')
                ->join('xm_subject sub', 'ss.sub_id=sub.id')
                ->field('ss.*,sub.question')
                ->where(['ss.cid' => $sub_cid])->order('mark_count desc')->find();
            if (!empty($mark_sub) && $mark_sub['mark_count'] > 0) {
                $rs_data['mark'] = $mark_sub;
            } else {
                $rs_data['mark'] = [];
            }

            // 未做最多的题目
            $undo_sub = Db::name('XmSubjectStatis')
                ->alias('ss')
                ->join('xm_subject sub', 'ss.sub_id=sub.id')
                ->field('ss.*,sub.question')
                ->where(['ss.cid' => $sub_cid])->order('undone_count desc')->find();
            if (!empty($undo_sub) && $undo_sub['undone_count'] > 0) {
                $rs_data['undo'] = $undo_sub;
            } else {
                $rs_data['undo'] = [];
            }
        }
        return $rs_data;
    }

    // 导出前判断
    public function exportpaperstatisbefore() {
        $params = $this->request->param();
        $subject_class_id = $params[0];

        $list = $this->sub_class_statis($subject_class_id);
        if (empty($list) || count($list) == 0) {
            $this->error('当前试卷无考试数据');
        } else {
            if ($list[0]['end_time'] > time()) {
                $this->error('考试尚未结束，暂无法导出');
            }
            $this->success('正在创建导出请求，请不要关闭浏览器', url('exportpaperstatis', ['id' => $subject_class_id]));
        }
    }

    // 考试数据统计导出
    public function exportpaperstatis() {
        $params = $this->request->param();
        $subject_class_id = $params[0];


        //1.从数据库中取出数据
        $subclass_list = $this->sub_class_statis($subject_class_id);
        // dump($subclass_list);
        // dump(count($subclass_list));
        if (empty($subclass_list) || count($subclass_list) == 0) {
            $this->error('当前试卷无考试统计数据');
        }
        $paper_list = $this->paper_statis($subject_class_id);
        if (empty($paper_list) || count($paper_list) == 0) {
            $this->error('当前试卷无考试学员数据');
        }


        // 统计数据
        $statis_list = $this->sub_statis($subject_class_id);
       
        

        //2.实例化PHPExcel类
        $objPHPExcel = new \PHPExcel();

        // 考试统计信息 -- sheet 1 -- begin

        //3.激活当前的sheet表
        $sheet1 = 0;
        $objPHPExcel->setActiveSheetIndex($sheet1);
        //4.设置表格头（即excel表格的第一行）
        $objPHPExcel->setActiveSheetIndex($sheet1)
                ->setCellValue('A1', '序号')
                ->setCellValue('B1', '试卷')
                ->setCellValue('C1', '题目数量')
                ->setCellValue('D1', '考试时间')
                ->setCellValue('E1', '考试人数')
                ->setCellValue('F1', '平均分')
                ->setCellValue('G1', '正确率')
                ->setCellValue('H1', '是否打乱题目')
                ->setCellValue('I1', '错误最多题目')
                ->setCellValue('J1', '正确最多题目')
                ->setCellValue('K1', '标注最多题目')
                ->setCellValue('L1', '未做最多题目');

        //设置F列水平居中
        $objPHPExcel->setActiveSheetIndex($sheet1)->getStyle('A')->getAlignment()
                    ->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
        $objPHPExcel->setActiveSheetIndex($sheet1)->getStyle('B')->getAlignment()
                    ->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
        $objPHPExcel->setActiveSheetIndex($sheet1)->getStyle('C')->getAlignment()
                    ->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
        $objPHPExcel->setActiveSheetIndex($sheet1)->getStyle('D')->getAlignment()
                    ->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
        $objPHPExcel->setActiveSheetIndex($sheet1)->getStyle('E')->getAlignment()
                    ->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
        $objPHPExcel->setActiveSheetIndex($sheet1)->getStyle('F')->getAlignment()
                    ->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
        $objPHPExcel->setActiveSheetIndex($sheet1)->getStyle('G')->getAlignment()
                    ->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
        $objPHPExcel->setActiveSheetIndex($sheet1)->getStyle('H')->getAlignment()
                    ->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT);

        //设置单元格宽度
        $objPHPExcel->setActiveSheetIndex($sheet1)->getColumnDimension('B')->setWidth(30);
        $objPHPExcel->setActiveSheetIndex($sheet1)->getColumnDimension('D')->setWidth(50);
        $objPHPExcel->setActiveSheetIndex($sheet1)->getColumnDimension('H')->setWidth(30);

        $objPHPExcel->setActiveSheetIndex($sheet1)->getColumnDimension('I')->setWidth(30);
        $objPHPExcel->setActiveSheetIndex($sheet1)->getColumnDimension('J')->setWidth(30);
        $objPHPExcel->setActiveSheetIndex($sheet1)->getColumnDimension('K')->setWidth(30);
        $objPHPExcel->setActiveSheetIndex($sheet1)->getColumnDimension('L')->setWidth(30);

        //5.循环刚取出来的数组，将数据逐一添加到excel表格。
        for($i=0;$i<count($subclass_list);$i++){
            $objPHPExcel->getActiveSheet()->setCellValue('A'.($i+2),$i+1);// 序号
            $objPHPExcel->getActiveSheet()->setCellValue('B'.($i+2),$subclass_list[$i]['name']);
            $objPHPExcel->getActiveSheet()->setCellValue('C'.($i+2),$subclass_list[$i]['subject_count']);
            $objPHPExcel->getActiveSheet()->setCellValue('D'.($i+2),date('Y-m-d H:i:s', $subclass_list[$i]['begin_time']) . '至'.date('Y-m-d H:i:s', $subclass_list[$i]['end_time']));
            $objPHPExcel->getActiveSheet()->setCellValue('E'.($i+2),$subclass_list[$i]['member_count']);
            $objPHPExcel->getActiveSheet()->setCellValue('F'.($i+2),$subclass_list[$i]['avg_score']);
            $objPHPExcel->getActiveSheet()->setCellValue('G'.($i+2),$subclass_list[$i]['avg_right_pre']);
            $objPHPExcel->getActiveSheet()->setCellValue('H'.($i+2),$subclass_list[$i]['is_rand'] == 1 ? '打乱' : '未打乱');

            $objPHPExcel->getActiveSheet()->setCellValue('I'.($i+2), !empty($statis_list['wrong']) ? $statis_list['wrong']['question'].'('.$statis_list['wrong']['unright_count'].'人)' : '无');
            $objPHPExcel->getActiveSheet()->setCellValue('J'.($i+2), !empty($statis_list['right']) ? $statis_list['right']['question'].'('.$statis_list['right']['right_count'].'人)' : '无');
            $objPHPExcel->getActiveSheet()->setCellValue('K'.($i+2), !empty($statis_list['mark']) ? $statis_list['mark']['question'].'('.$statis_list['mark']['mark_count'].'人)' : '无');
            $objPHPExcel->getActiveSheet()->setCellValue('L'.($i+2), !empty($statis_list['undo']) ? $statis_list['undo']['question'].'('.$statis_list['undo']['undone_count'].'人)' : '无');
        }

        //7.设置当前激活的sheet表格名称；
        $objPHPExcel->getActiveSheet()->setTitle('试卷总体统计');
        // -- sheet 1 -- end

        // -- sheet 2 学员信息 -- begin
        if ($paper_list) {
            $sheet2 = 1;
            $objPHPExcel->createSheet();
            $objPHPExcel->setActiveSheetIndex($sheet2);
            //4.设置表格头（即excel表格的第一行）
            $objPHPExcel->setActiveSheetIndex($sheet2)
                    ->setCellValue('A1', '序号')
                    ->setCellValue('B1', '试卷')
                    ->setCellValue('C1', '试卷题数')
                    ->setCellValue('D1', '学员姓名')
                    ->setCellValue('E1', '班级')
                    ->setCellValue('F1', '答题用时')
                    ->setCellValue('G1', '交卷时间')
                    ->setCellValue('H1', '得分')
                    ->setCellValue('I1', '正确率')
                    ->setCellValue('J1', '共做题')
                    ->setCellValue('K1', '答对题数');

            //设置F列水平居中
            $objPHPExcel->setActiveSheetIndex($sheet2)->getStyle('A')->getAlignment()
                        ->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
            $objPHPExcel->setActiveSheetIndex($sheet2)->getStyle('B')->getAlignment()
                        ->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
            $objPHPExcel->setActiveSheetIndex($sheet2)->getStyle('C')->getAlignment()
                        ->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
            $objPHPExcel->setActiveSheetIndex($sheet2)->getStyle('D')->getAlignment()
                        ->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
            $objPHPExcel->setActiveSheetIndex($sheet2)->getStyle('E')->getAlignment()
                        ->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
            $objPHPExcel->setActiveSheetIndex($sheet2)->getStyle('F')->getAlignment()
                        ->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
            $objPHPExcel->setActiveSheetIndex($sheet2)->getStyle('G')->getAlignment()
                        ->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
            $objPHPExcel->setActiveSheetIndex($sheet2)->getStyle('H')->getAlignment()
                        ->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
            $objPHPExcel->setActiveSheetIndex($sheet2)->getStyle('I')->getAlignment()
                        ->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
            $objPHPExcel->setActiveSheetIndex($sheet2)->getStyle('J')->getAlignment()
                        ->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
            $objPHPExcel->setActiveSheetIndex($sheet2)->getStyle('K')->getAlignment()
                        ->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT);

            //设置单元格宽度
            $objPHPExcel->setActiveSheetIndex($sheet2)->getColumnDimension('E')->setWidth(15);
            //5.循环刚取出来的数组，将数据逐一添加到excel表格。
            for($i=0;$i<count($paper_list);$i++){
                $objPHPExcel->getActiveSheet()->setCellValue('A'.($i+2),$i+1);// 序号
                $objPHPExcel->getActiveSheet()->setCellValue('B'.($i+2),$paper_list[$i]['subject_class_name']);
                $objPHPExcel->getActiveSheet()->setCellValue('C'.($i+2),$paper_list[$i]['sub_id'] ? count(json_decode($paper_list[$i]['sub_id'], true)) : 0);
                $objPHPExcel->getActiveSheet()->setCellValue('D'.($i+2),$paper_list[$i]['real_name']);
                $objPHPExcel->getActiveSheet()->setCellValue('E'.($i+2),$paper_list[$i]['mc_name']);
                $objPHPExcel->getActiveSheet()->setCellValue('F'.($i+2),time_remainder($paper_list[$i]['time']));
                $objPHPExcel->getActiveSheet()->setCellValue('G'.($i+2),$paper_list[$i]['create_at']);
                $objPHPExcel->getActiveSheet()->setCellValue('H'.($i+2),$paper_list[$i]['score']);
                $objPHPExcel->getActiveSheet()->setCellValue('I'.($i+2),$paper_list[$i]['right_pre'].'%');
                $objPHPExcel->getActiveSheet()->setCellValue('J'.($i+2),$paper_list[$i]['u_answer'] ? count(json_decode($paper_list[$i]['u_answer'], true)) : 0);
                $objPHPExcel->getActiveSheet()->setCellValue('K'.($i+2),$paper_list[$i]['right_count']);
            }

            //7.设置当前激活的sheet表格名称；
            $objPHPExcel->getActiveSheet()->setTitle('试卷学员成绩');
            // -- end
        }

        //6.设置保存的Excel表格名称
        $filename = '试卷统计数据导出-'.date('ymd',time()).'.xlsx';

        //8.设置浏览器窗口下载表格
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header('Content-Disposition:inline;filename="'.$filename.'"');

        //生成excel文件
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        //下载文件在浏览器窗口
        $objWriter->save('php://output');
        exit;
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
