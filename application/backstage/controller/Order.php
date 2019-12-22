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

/**
 * 订单管理
 * Class Order
 * @package app\backstage\controller
 */
class Order extends Controller
{

    /**
     * 指定当前数据表
     * @var string
     */
    public $table = 'XmOrder';
    public $status = [
        0=>'未完成',
        1=>'已支付',
        2=>'已退款',
    ];

    /**
     * 订单列表管理
     * @auth true
     * @menu true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $this->title = '订单列表';
        $query = $this->_query($this->table)->like('ordernum,tradeid,nickname');
        if($this->request->isGet()){
            $query->alias('o')->join('xm_member m','m.id=o.uid','LEFT')->dateBetween('o.create_at')->dateBetween('o.buytime')->where(['o.is_deleted' => '0'])->order('o.id desc')->field('o.*,m.nickname as nickname')->page();
        }else{
            $query->alias('o')->join('xm_member m','m.id=o.uid','LEFT')->dateBetween('o.create_at')->dateBetween('o.buytime')->where(['o.is_deleted' => '0'])->order('o.id desc')->field('o.*,m.nickname as nickname')->where('o.status',$this->request->status)->page();
        }

    }

    /**
     * 列表数据处理
     * @auth true
     * @param array $data
     * @throws \Exception
     */
    protected function _index_page_filter(&$data)
    {
        $total_fee_all = 0;
        $pay_fee_all = 0;

        foreach($data as $k=>$v){
            $status_span = '';
            switch($v['status']){
                case 0: $status_span = '<span class="layui-btn layui-btn-warm layui-btn-xs">'.$this->status[$v['status']].'</span>';break;
                case 1: $status_span = '<span class="layui-btn layui-btn layui-btn-xs">'.$this->status[$v['status']].'</span>';break;
                case 2: $status_span = '<span class="layui-btn layui-btn-disabled layui-btn-xs">'.$this->status[$v['status']].'</span>';break;
            }
            $data[$k]['status_span'] = $status_span;


            $total_fee_all += $v['total_fee'];
            $pay_fee_all += $v['pay_fee'];
        }

        $this->total_fee_all = sprintf("%.2f",$total_fee_all);
        $this->pay_fee_all = sprintf("%.2f",$pay_fee_all);
        $this->count = count($data);


    }

    /**
     * 删除信息
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
