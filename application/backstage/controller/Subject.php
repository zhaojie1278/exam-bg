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
 * 题库管理
 * Class Subject
 * @package app\backstage\controller
 */
class Subject extends Controller
{

    /**
     * 指定当前数据表
     * @var string
     */
    public $table = 'XmSubject';

    /**
     * 试题列表管理
     * @auth true
     * @menu true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $this->title = '试题列表';
        $this->assign('category',Db::name("XmSubjectClass")->where(['is_deleted' => '0'])->select());
        $query = $this->_query($this->table)->like('question,cid');
        $query->alias('s')->join('xm_subject_class a','s.cid=a.id')->field('s.*,a.name as cname')->dateBetween('s.create_at')->where(['s.is_deleted' => '0'])->order('s.id desc')->page();

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
            $vo['chapter'] = Db::name('XmChapter')->where('id',$vo['chapter_id'])->where('is_deleted',0)->value('name');
        }

    }

    /**
     * 添加试题
     * @auth true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function add()
    {
        $this->title = '添加试题';
        $this->isAddMode = '1';
        $this->_form($this->table, 'form');
    }

    /**
     * 批量添加试题
     * @auth true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function batchadd()
    {
        if ($this->request->isPost()) {
            $excel = $this->request->post('excel');
            $chapter = $this->request->post('chapter');

            if(!$this->request->post('cid')){
                $this->error('缺少参数');
            }
            //$excel = 'http://huli.chengzonghua.com/upload/dd6101efeb6cb16c/a799ebfb596aba40.xlsx';

            $excel = substr($excel,stripos($excel,'upload'));

            $data = $this->initExcel($excel,$this->request->post('cid'),$chapter);
            //dump($data);
            if(count($data)){
                $res = Db::name('XmSubject')->insertAll($data);
                if($res){
                    $this->success("导入成功！");
                }else{
                    $this->error("导入失败！");
                }
            }else{
                $this->error("解析数据失败！");
            }

            exit;
        }
        $this->title = '批量添加试题';

        $chapter = Db::name('XmChapter')->where('is_deleted',0)->select();

        foreach ($chapter as &$vo) {
            if($vo['pid'] == 0){
                $ids = Db::name('XmChapter')->where('pid',$vo['id'])->column('id');
                $ids[] = $vo['id'];
                $vo['sub_num'] = Db::name('XmSubject')->whereIn('chapter_id',$ids)->where('is_deleted',0)->count();
            }else{
                $vo['sub_num'] = Db::name('XmSubject')->where('chapter_id',$vo['id'])->where('is_deleted',0)->count();
            }
            $vo['ids'] = join(',', Data::getArrSubIds($chapter, $vo['id']));
        }
        $chapter = Data::arr2table($chapter);

        $this->assign('chapter',$chapter);
        $this->_form($this->table, 'form_batch');
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

            $input = $this->request->post();

            //答案选项
            if(!array_key_exists('answers',$input)){
                $this->error("请填写答案选项！");
            }
            $answers = json_decode($input['answers'],true);

            $check_answer = false;
            if($answers){
                foreach($answers as $k=>$v){
                    if(!$v['a']){
                        $this->error("请填写答案选项！");
                    }
                    if(!$v['t']){
                        $this->error("请填写答案内容！");
                    }
                    if($v['c']){
                        if($check_answer){
                            $this->error("只能有一个正确答案！");
                        }
                        $check_answer = $v['a'];
                    }
                }
                if(!$check_answer){
                    $this->error("请选择一个正确答案！");
                }

                $data['create_at'] = date('Y-m-d H:i:s');
                $data['check_answer'] = $check_answer;
                $data['answer'] = $input['answers'];
            }else{
                $this->error("请填写答案选项！");
            }

        }elseif ($this->request->isGet()) {
            $class = Db::name('XmSubjectClass')->select();
            $this->class = $class;


            $chapter = Db::name('XmChapter')->where('is_deleted',0)->select();
            foreach ($chapter as &$vo) {
                $vo['ids'] = join(',', Data::getArrSubIds($chapter, $vo['id']));
            }
            $chapter = Data::arr2table($chapter);

            $this->chapter = $chapter;
        }
    }

    /**
     * 表单结果处理
     * @param boolean $result
     */
    protected function _form_result($result)
    {
        if ($result && $this->request->isPost()) {
            $this->success('编辑成功！', 'javascript:history.back()');
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

    private function initExcel($url,$cid,$chapter)
    {
        $PHPReader = new PHPExcel_Reader_Excel2007();
        //载入文件
        $PHPExcel = $PHPReader->load($url, $encode = 'utf-8');

        $data = [];
        $currentSheet = $PHPExcel->getSheet(0);
        //获取总列数
        $allColumn = $currentSheet->getHighestColumn();
        //获取总行数
        $allRow = $currentSheet->getHighestRow();
        //循环获取表中的数据，$currentRow表示当前行，从哪行开始读取数据，索引值从0开始
        for ($currentRow = 1; $currentRow <= $allRow; $currentRow++) {
            //从哪列开始，A表示第一列
            for ($currentColumn = 'A'; $currentColumn <= $allColumn; $currentColumn++) {
                //数据坐标
                $address = $currentColumn . $currentRow;
                //读取到的数据，保存到数组$arr中

                $value = $currentSheet->getCell($address)->getValue();

                if(is_object($value))  $value= $value->__toString();

                $data[$currentRow][$currentColumn] = $value;
            }
        }


        $returnData = [];
        if(count($data)){
            $scene = '';
            foreach($data as $k=>$v){
                $tmp = [];
                $tmp['cid'] =  $cid;
                if($v['A']==''&&$scene){
                    $tmp['scene'] = $scene;
                }else{
                    $tmp['scene'] = str_replace("【情景】",'',$v['A']);
                    $scene = $tmp['scene'];
                }
                $tmp['question'] =  preg_replace("/^[0-9]+[.]/",'', $v['B']);
                $tmp['check_answer'] =  $v['H'];

                $tmp_ans = [];
                $tmp_ans['a'] = 'A';
                if($v['H'] == 'A'){
                    $tmp_ans['c'] = true;
                }else{
                    $tmp_ans['c'] = false;
                }
                $tmp_ans['t'] = preg_replace("/^[A-Z]+[.]/",'',$v['C']);
                $tmp['answer'][] =  $tmp_ans;

                $tmp_ans = [];
                $tmp_ans['a'] = 'B';
                if($v['H'] == 'B'){
                    $tmp_ans['c'] = true;
                }else{
                    $tmp_ans['c'] = false;
                }
                $tmp_ans['t'] = preg_replace("/^[A-Z]+[.]/",'',$v['D']);
                $tmp['answer'][] =  $tmp_ans;

                $tmp_ans = [];
                $tmp_ans['a'] = 'C';
                if($v['H'] == 'C'){
                    $tmp_ans['c'] = true;
                }else{
                    $tmp_ans['c'] = false;
                }
                $tmp_ans['t'] = preg_replace("/^[A-Z]+[.]/",'',$v['E']);
                $tmp['answer'][] =  $tmp_ans;

                $tmp_ans = [];
                $tmp_ans['a'] = 'D';
                if($v['H'] == 'D'){
                    $tmp_ans['c'] = true;
                }else{
                    $tmp_ans['c'] = false;
                }
                $tmp_ans['t'] = preg_replace("/^[A-Z]+[.]/",'',$v['F']);
                $tmp['answer'][] =  $tmp_ans;

                $tmp_ans = [];
                $tmp_ans['a'] = 'E';
                if($v['H'] == 'E'){
                    $tmp_ans['c'] = true;
                }else{
                    $tmp_ans['c'] = false;
                }
                $tmp_ans['t'] = preg_replace("/^[A-Z]+[.]/",'',$v['G']);
                $tmp['answer'][] =  $tmp_ans;

                $tmp['answer'] = json_encode($tmp['answer']);
                if($chapter){
                    $tmp['chapter_id'] = $chapter;
                }
                $tmp['create_at'] = date("Y-m-d H:i:s");
                $tmp['analysis'] = str_replace("【解析】",'',$v['I']);

                $returnData[] = $tmp;
            }
        }

        return $returnData;

    }


}
