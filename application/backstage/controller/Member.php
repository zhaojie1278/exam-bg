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
use think\Log;

/**
 * 会员管理
 * Class Member
 * @package app\backstage\controller
 */
class Member extends Controller
{

    /**
     * 指定当前数据表
     * @var string
     */
    public $table = 'XmMember';

    /**
     * 会员列表管理
     * @auth true
     * @menu true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $this->title = '学员列表';
        $this->assign('member_class',Db::name("XmMemberClass")->where(['is_deleted' => '0'])->select());
        $query = $this->_query($this->table)->like('nickname,phone,status,real_name,class_no')->equal('class_id');
        $query->alias('s')->join('xm_member_class a','s.class_id=a.id', 'left')->field('s.*,a.name as class_name')->dateBetween('s.create_at')->where(['s.is_deleted' => '0'])->order('s.id desc')->page();
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
     * 批量添加学员
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

            if(!$this->request->post('class_id')){
                $this->error('缺少参数');
            }
            //$excel = 'http://huli.chengzonghua.com/upload/dd6101efeb6cb16c/a799ebfb596aba40.xlsx';

            $excel = substr($excel,stripos($excel,'upload'));

            $data = $this->initExcel($excel,$this->request->post('class_id'));
            if(count($data)){
                $res = Db::name('XmMember')->insertAll($data);
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
        $this->title = '批量添加学员';

        $this->_form($this->table, 'form_batch');
    }

    /**
     * 添加
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

            $input = $this->request->post();

            if (empty($input['class_id'])) {
                $this->error("请选择班级");
            }

            if (empty($input['class_no'])) {
                $this->error("请输入学号");
            }

            if (empty($input['real_name'])) {
                $this->error("请输入姓名");
            }
        } elseif ($this->request->isGet()) {
            $memberclass = Db::name('XmMemberClass')->where('is_deleted',0)->select();
            $this->memberclass = $memberclass;
        }
    }

    /**
     * 编辑用户
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
     * 冻结用户
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function forbid()
    {
        $this->applyCsrfToken();
        $this->_save($this->table, ['status' => '0']);
    }

    /**
     * 启用用户
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function resume()
    {
        $this->applyCsrfToken();
        $this->_save($this->table, ['status' => '1']);
    }

    /**
     * 删除会员信息
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function remove()
    {
        $this->applyCsrfToken();
        $this->_delete($this->table);
    }

    // 导入学员
    private function initExcel($url,$class_id)
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

        // 解决获取不到 AB 这样的列
        ++$allColumn;
        ++$allColumn;

        //循环获取表中的数据，$currentRow表示当前行，从哪行开始读取数据，索引值从0开始
        for ($currentRow = 2; $currentRow <= $allRow; $currentRow++) {
            //从哪列开始，A表示第一列
            for ($currentColumn = 'A'; $currentColumn != $allColumn; $currentColumn++) {
                //数据坐标
                $address = $currentColumn . $currentRow;
                //读取到的数据，保存到数组$arr中
                $value = $currentSheet->getCell($address)->getValue();

                if(is_object($value))  $value= $value->__toString();

                $data[$currentRow][$currentColumn] = $value;
            }
        }

        // trace('-------------$data::'.var_export(count($data), true));

        $returnData = [];
        if(count($data)){
            $scene = '';
            foreach($data as $k=>$v){
                $tmp = [];
                $tmp['class_id'] =  $class_id;
                if (empty($v['A']) || empty($v['B'])) {
                    // 没有学号或姓名
                    continue;
                }

                // 题目
                $tmp['class_no'] =  trim($v['A']);
                $tmp['real_name'] =  trim($v['B']);
                $tmp['phone'] =  empty($v['C']) ? '' : trim($v['C']);
                $tmp['mail'] =  empty($v['D']) ? '' : trim($v['D']);
                if ($tmp['mail'] && stripos($tmp['mail'], '@') == 0) {
                    // trace('-------------$data::'.var_export($tmp['mail'], true));
                    // 邮箱不正确
                    trace('-------------'.$tmp['class_no'].': 邮箱不正确::'.var_export($tmp['mail'], true));
                    $returnData = [];
                    break;
                }

                $tmp['create_at'] = date("Y-m-d H:i:s");

                $returnData[] = $tmp;
            }
        }

        return $returnData;

    }
}
