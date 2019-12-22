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
use library\tools\Data;
use think\Db;
use think\Exception;
use think\exception\HttpResponseException;

/**
 * 题目接口
 * Class Subject
 * @package app\wemini\controller
 */
class Subject extends Common
{
    /*智能考点随机试题*/
    public function range_subject()
    {
        try {
            $uid = $this->member['id'];

            $cate_id = input('post.cate_id');
            if(!$cate_id){
                $cate_id = $this->member['cate_id']?$this->member['cate_id']:1;
            }
            $cids = Db::name('XmSubjectClass')->where('cate_id',$cate_id)->column('id');

            $subject_ctn = Db::name('XmSubject')->whereIn('cid',$cids)->where('is_deleted',0)->column('id');

            if(count($subject_ctn) <= 1){
                $this->error('试题数量不够');
            }

            $arr = range(0,count($subject_ctn)-1);

            $rand_key = array_rand($arr,15);

            $rand_id =[];
            foreach($rand_key as $k=>$v){
                $rand_id[] = $subject_ctn[$v];
            }


            $subject = Db::name('XmSubject')->whereIn('id',$rand_id)->field('id,cid,question,image,scene,answer,analysis')->select();

            $s_answer = [];
            $data = [];
            if($subject){
                foreach($subject as $k=>$v){
                    $subject[$k]['cname'] = Db::name('XmSubjectClass')->where('id',$v['cid'])->value('name');
                    $subject[$k]['answer'] = json_decode($v['answer'],true);
                    $subject[$k]['is_collect'] = Db::name('XmSubjectCollect')->where('uid',$uid)->where('subject_id',$v['id'])->count();
                }
            }

            $this->success('ok',$subject);

        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            $this->error("失败，{$exception->getMessage()}");
        }
    }
    /*真题模考  列表*/
    public function getreallist()
    {
        $cate_id = input('post.cate_id');
        if(!$cate_id){
            $cate_id = $this->member['cate_id']?$this->member['cate_id']:1;
        }

        $sclass = Db::name('XmSubjectClass')->where('cate_id',$cate_id)->where('is_deleted',0)->order('id asc')->select();



        $this->success('ok',$sclass);
    }
    /*真题模考 根据真题id 获取试题*/
    public function real_subject()
    {
        try {
            $uid = $this->member['id'];

            $cid = input('post.cid');
            if(!$cid){
                $this->error('缺少重要参数');
            }

            $subject = Db::name('XmSubject')->where('cid',$cid)->where('is_deleted',0)->field('id,cid,question,image,scene,answer,analysis')->select();

            $s_answer = [];
            $data = [];
            if($subject){
                foreach($subject as $k=>$v){
                    $subject[$k]['cname'] = Db::name('XmSubjectClass')->where('id',$v['cid'])->value('name');
                    $subject[$k]['answer'] = json_decode($v['answer'],true);
                    $subject[$k]['is_collect'] = Db::name('XmSubjectCollect')->where('uid',$uid)->where('subject_id',$v['id'])->count();
                }
            }

            $this->success('ok',$subject);

        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            $this->error("失败，{$exception->getMessage()}");
        }
    }

    /*巩固模考  列表*/
    public function getenhancelist()
    {
        $cate_id = input('post.cate_id');
        if(!$cate_id){
            $cate_id = $this->member['cate_id']?$this->member['cate_id']:1;
        }

        $paper = Db::name('XmCustomPaper')->where('cate_id',$cate_id)->where('is_deleted',0)->order('id asc')->select();

        $this->success('ok',$paper);
    }
    /*巩固模考 根据试卷id 获取试题*/
    public function enhance_subject()
    {
        try {
            $uid = $this->member['id'];

            $id = input('post.id');
            if(!$id){
                $this->error('缺少重要参数');
            }

            $paper = Db::name('XmCustomPaper')->where('id',$id)->where('is_deleted',0)->find();
            if(!$paper){
                $this->error('试卷不存在');
            }

            $ids = json_decode($paper['sub_id'],true);

            $idss =implode(',',$ids);
            $sql = "select * from xm_subject where id in (".$idss.") order by field(id,".$idss.");";
            $subject =Db::query($sql);

            $s_answer = [];
            $data = [];
            if($subject){
                foreach($subject as $k=>$v){
                    $subject[$k]['cname'] = Db::name('XmSubjectClass')->where('id',$v['cid'])->value('name');
                    $subject[$k]['answer'] = json_decode($v['answer'],true);
                    $subject[$k]['is_collect'] = Db::name('XmSubjectCollect')->where('uid',$uid)->where('subject_id',$v['id'])->count();
                }
            }

            $this->success('ok',$subject);

        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            $this->error("失败，{$exception->getMessage()}");
        }
    }

    /*交卷*/
    public function submit_subject(){
        Db::startTrans();
        try {
            $uid = $this->member['id'];
            $subject = request()->post('subject');
            if(!count($subject)){
                $this->error("缺少试题");
            }
            $u_answer = request()->post('u_answer');
            if(!count($u_answer)){
                $this->error("缺少答题卡");
            }
            $otype = input('post.otype');
            if(!$otype){
                $this->error("缺少考试类型");
            }
            $cid=0;
            if($otype == 3||$otype == 4){
                //真题模考
                if(!input('post.cid')){
                    $this->error("缺少考试类型");
                }
                $cid=input('post.cid');
            }
			$test_ids = [];
			foreach($subject as $k=>$v){
				$test_ids[] = $v['id'];
			}
			

            //$s_answer = Db::name('XmSubject')->whereIn('id',$test_ids)->column('check_answer');

            $right = 0;
            $wrong = 0;
            $s_answer = [];
            $w = [];
            if($u_answer){
                foreach($u_answer as $k=>$v){
                    if($k)
					
					$check_answer = Db::name('XmSubject')->whereIn('id',$k)->value('check_answer');

                    if($v == $check_answer){
                        //正确
                        $right ++;
                    }else{
                        //错误
                        $wrong ++;
						$w[] = $k;
                    }
					
					$s_answer[$k] = $check_answer;
                }
            }

            //正确率
            $right_pre = 100 * ($right/count($subject));

            //分数
            $score = $right;

            //答题时间
            $time = request()->post('time');

            //
            $test_id = Db::name('XmSubjectPaper')->insertGetId(['sub_id'=>json_encode($test_ids),'otype'=>$otype,'uid'=>$uid,'score'=>$score,'cid'=>$cid,'u_answer'=>json_encode($u_answer),'time'=>$time,'right_pre'=>$right_pre,'s_answer'=>json_encode($s_answer),'create_at'=>date("Y-m-d H:i:s")]);
			
			if(!$test_id){
                Db::rollback();
				$this->error('交卷失败');
			}

            /*used 自增*/
            Db::name('XmSubject')->whereIn('id',$test_ids)->setInc('used',1);
			
			if(count($w)){
				$subject_id = Db::name('XmSubjectError')->where('uid',$uid)->column('subject_id');
				
				$w_data = [];
				$nowdate = date("Y-m-d H:i:s");
				foreach($w as $kkk=>$vvv){
					$tmp = [];
					if(in_array($vvv,$subject_id)){
						unset($w[$kkk]);
					}else{
						$tmp['uid'] = $uid;
						$tmp['subject_id'] = $vvv;
						$tmp['create_at'] = $nowdate;
						
						$w_data[] = $tmp;
					}
				}
				
				if(count($w_data)){
					Db::name('XmSubjectError')->insertAll($w_data);
				}
			}



            //返回数据
            $idss =implode(',',$test_ids);
            $sql = "select * from xm_subject where id in (".$idss.") order by field(id,".$idss.");";
            $return_subject =Db::query($sql);
			foreach($return_subject as $k=>$v){
				$return_subject[$k]['answer'] = json_decode($v['answer'],true);
				$return_subject[$k]['u_answer'] = array_key_exists($v['id'],$u_answer)?$u_answer[$v['id']]:'';
				$return_subject[$k]['cname'] = Db::name('XmSubjectClass')->where('id',$v['cid'])->value('name');
				$return_subject[$k]['is_collect'] = Db::name('XmSubjectCollect')->where('uid',$uid)->where('subject_id',$v['id'])->count();
			}
			
			$return_paper = Db::name('XmSubjectPaper')->where('id',$test_id)->find();

            $return_paper['sub_id']= json_decode($return_paper['sub_id'],true);
			
			$return['subject'] = $return_subject;
			$return['paper'] = $return_paper;

            Db::commit();
            $this->success('ok',$return);


        } catch (HttpResponseException $exception) {
        throw $exception;
        } catch (\Exception $exception) {
            Db::rollback();
            $this->error("失败，{$exception->getMessage()}");
        }
    }

    /*章节智能练习*/
    public function chapter()
    {
        $cate_id = input('post.cate_id');
        if(!$cate_id){
            $cate_id = $this->member['cate_id']?$this->member['cate_id']:1;
        }
        $cids = Db::name('XmSubjectClass')->where('cate_id',$cate_id)->column('id');

        $chapter = Db::name('XmChapter')->where('is_deleted',0)->order('sort desc')->select();
        foreach ($chapter as &$vo) {
            if($vo['pid'] == 0){
                $ids = Db::name('XmChapter')->where('pid',$vo['id'])->column('id');
                $ids[] = $vo['id'];
                $vo['sub_num'] = Db::name('XmSubject')->whereIn('cid',$cids)->whereIn('chapter_id',$ids)->where('is_deleted',0)->count();
            }else{
                $vo['sub_num'] = Db::name('XmSubject')->whereIn('cid',$cids)->where('chapter_id',$vo['id'])->where('is_deleted',0)->count();
            }
            $vo['ids'] = join(',', Data::getArrSubIds($chapter, $vo['id']));
        }
        $chapter = Data::arr2tree($chapter);


        $this->success('ok',$chapter);
    }

    /*根据章节智能练习分类id，获取试题*/
    public function chapter_subject()
    {
        $uid = $this->member['id'];
        $cate_id = input('post.cate_id');
        if(!$cate_id){
            $cate_id = $this->member['cate_id']?$this->member['cate_id']:1;
        }
        $cids = Db::name('XmSubjectClass')->where('cate_id',$cate_id)->column('id');

        $chapter_id = input('post.chapter_id');
        if(!$chapter_id){
            $this->error('缺少参数');
        }
		

        /*如果是父节点  需要拉去所有的子节点*/
		$chapter_ids = Db::name('XmChapter')->where('pid',$chapter_id)->column('id');
		
		$chapter_ids[] = $chapter_id;
		

        /*已练习过的题目*/

        $sub_ids = [];
        /*$sub_id = Db::name('XmSubjectPaper')->where('uid',$uid)->where('otype',2)->column('sub_id');
        if(count($sub_id)){
            foreach($sub_id as $k=>$v){
                $sub_ids = array_merge($sub_ids,json_decode($v,true));
            }
        }

        if(count($sub_ids)){
            $sub_ids = array_unique($sub_ids);
        }*/


        $subject = Db::name('XmSubject')->whereIn('cid',$cids)->whereIn('chapter_id',$chapter_ids)->whereNotIn('id',$sub_ids)->where('is_deleted',0)->limit(15)->field('id,cid,question,image,scene,answer,analysis')->select();


        $s_answer = [];
        $data = [];
        if($subject){
            foreach($subject as $k=>$v){
                $subject[$k]['cname'] = Db::name('XmSubjectClass')->where('id',$v['cid'])->value('name');
                $subject[$k]['answer'] = json_decode($v['answer'],true);
                $subject[$k]['is_collect'] = Db::name('XmSubjectCollect')->where('uid',$uid)->where('subject_id',$v['id'])->count();
            }
        }else{
            $this->error('已完成所有练习');
        }

        $this->success('ok',$subject);
    }

    /*收藏试题*/
    public function collect_subject()
    {
        try{
            $uid = $this->member['id'];
            $subject_id = input('post.id');
            if(!$subject_id){
                $this->error('未找到试题ID');
            }

            $subject = Db::name('XmSubject')->where('id',$subject_id)->find();
            if(!$subject){
                $this->error('未找到试题');
            }

            $subject_collect = Db::name('XmSubjectCollect')->where('uid',$uid)->where('subject_id',$subject_id)->find();
            if($subject_collect){
                //取消收藏
                $res = Db::name('XmSubjectCollect')->where('subject_id',$subject_id)->delete();
                if($res){
                    $this->success('取消成功');
                }
            }else{
                //添加收藏

                $res = Db::name('XmSubjectCollect')->insert(['uid'=>$uid,'subject_id'=>$subject_id,'create_at'=>date('Y-m-d H:i:s')]);
                if($res){
                    $this->success('收藏成功');
                }
            }

            $this->error('操作失败');



        }catch(Exception $e){

            $this->error($e->getMessage());
        }
    }


    /*获取收藏列表 章节-题目*/
    public function getcollectlist()
    {

        try{
            $uid = $this->member['id'];

            $subject_id = Db::name('XmSubjectCollect')->where('uid',$uid)->column('subject_id');

            $chapter = Db::name('XmChapter')->where('is_deleted',0)->order('sort desc')->select();
            foreach ($chapter as &$vo) {
                if($vo['pid'] == 0){
                    $ids = Db::name('XmChapter')->where('pid',$vo['id'])->column('id');
                    $ids[] = $vo['id'];
                    $vo['sub_num'] = Db::name('XmSubject')->whereIn('chapter_id',$ids)->whereIn('id',$subject_id)->where('is_deleted',0)->count();
                }else{
                    $vo['sub_num'] = Db::name('XmSubject')->where('chapter_id',$vo['id'])->whereIn('id',$subject_id)->where('is_deleted',0)->count();
                }
                $vo['ids'] = join(',', Data::getArrSubIds($chapter, $vo['id']));
            }
            $chapter = Data::arr2tree($chapter);

            foreach($chapter as $k=>&$v){
                if(!$v['sub_num']){
                    unset($chapter[$k]);
                    continue;
                }
                if(array_key_exists('sub',$v)){

                    foreach($v['sub'] as $kk=>$vv){
                        if(!$vv['sub_num']){
                            unset($v['sub'][$kk]);
                        }
                    }
                    $chapter[$k]['sub'] = array_values($v['sub']);
                }

            }

            $this->success('ok',array_values($chapter));



        }catch(Exception $e){

        }

    }


    /*根据分类id，获取试题*/
    public function collect_subject_list()
    {
        $uid = $this->member['id'];
        $chapter_id = input('post.chapter_id');
        if(!$chapter_id){
            $this->error('缺少参数');
        }

        /*如果是父节点  需要拉去所有的子节点*/
		$chapter_ids = Db::name('XmChapter')->where('pid',$chapter_id)->column('id');
		
		$chapter_ids[] = $chapter_id;

        /*收藏题目*/
        $subject_id = Db::name('XmSubjectCollect')->where('uid',$uid)->column('subject_id');



        $subject = Db::name('XmSubject')->whereIn('chapter_id',$chapter_ids)->whereIn('id',$subject_id)->where('is_deleted',0)->limit(15)->field('id,cid,question,image,scene,answer,analysis')->select();


        if($subject){
            foreach($subject as $k=>$v){
                $subject[$k]['cname'] = Db::name('XmSubjectClass')->where('id',$v['cid'])->value('name');
                $subject[$k]['answer'] = json_decode($v['answer'],true);
                $subject[$k]['is_collect'] = 1;
            }
        }else{
            $this->error('题库数量不够');
        }

        $this->success('ok',$subject);
    }


    /*获取错题列表 章节-题目*/
    public function geterrorlist()
    {

        try{
            $uid = $this->member['id'];

            $subject_id = Db::name('XmSubjectError')->where('uid',$uid)->column('subject_id');

            $chapter = Db::name('XmChapter')->where('is_deleted',0)->order('sort desc')->select();
            foreach ($chapter as &$vo) {
                if($vo['pid'] == 0){
                    $ids = Db::name('XmChapter')->where('pid',$vo['id'])->column('id');
                    $ids[] = $vo['id'];
                    $vo['sub_num'] = Db::name('XmSubject')->whereIn('chapter_id',$ids)->whereIn('id',$subject_id)->where('is_deleted',0)->count();
                }else{
                    $vo['sub_num'] = Db::name('XmSubject')->where('chapter_id',$vo['id'])->whereIn('id',$subject_id)->where('is_deleted',0)->count();
                }
                $vo['ids'] = join(',', Data::getArrSubIds($chapter, $vo['id']));
            }
            $chapter = Data::arr2tree($chapter);

            foreach($chapter as $k=>&$v){
                if(!$v['sub_num']){
                    unset($chapter[$k]);
                    continue;
                }
                if(array_key_exists('sub',$v)){

                    foreach($v['sub'] as $kk=>$vv){
                        if(!$vv['sub_num']){
                            unset($v['sub'][$kk]);
                        }
                    }
                    $chapter[$k]['sub'] = array_values($v['sub']);
                }

            }

            $this->success('ok',array_values($chapter));



        }catch(Exception $e){

        }

    }


    /*根据分类id，获取试题*/
    public function error_subject_list()
    {
        $uid = $this->member['id'];
        $chapter_id = input('post.chapter_id');
        if(!$chapter_id){
            $this->error('缺少参数');
        }
		

        /*如果是父节点  需要拉去所有的子节点*/
		$chapter_ids = Db::name('XmChapter')->where('pid',$chapter_id)->column('id');
		
		$chapter_ids[] = $chapter_id;

        /*收藏题目*/
        $subject_id = Db::name('XmSubjectError')->where('uid',$uid)->column('subject_id');



        $subject = Db::name('XmSubject')->whereIn('chapter_id',$chapter_ids)->whereIn('id',$subject_id)->where('is_deleted',0)->limit(15)->select();


        if($subject){
            foreach($subject as $k=>$v){
                $subject[$k]['cname'] = Db::name('XmSubjectClass')->where('id',$v['cid'])->value('name');
                $subject[$k]['answer'] = json_decode($v['answer'],true);
                $subject[$k]['is_collect'] = 1;
            }
        }else{
            $this->error('题库数量不够');
        }

        $this->success('ok',$subject);
    }
	
	

    /*获取练习历史*/
    public function gethistorylist()
    {

        try{
            $uid = $this->member['id'];

            $subject_paper = Db::name('XmSubjectPaper')->where('uid',$uid)->order('id desc')->select();
			
			$return = [];
			$total_num = 0;
			$total_time = 0;
            foreach ($subject_paper as $key=>$vo) {
				$total_num += count(json_decode($vo['sub_id'],true));
				$total_time += $vo['time'];
				$tmp = [];
                $test_ids = json_decode($vo['sub_id'],true);
                $idss =implode(',',$test_ids);
                $sql = "select * from xm_subject where id in (".$idss.") order by field(id,".$idss.");";
                $return_subject =Db::query($sql);
				foreach($return_subject as $k=>$v){
					
					$u_answer = json_decode($vo['u_answer'],true);
                    $return_subject[$k]['answer'] = json_decode($v['answer'],true);
					$return_subject[$k]['u_answer'] = array_key_exists($v['id'],$u_answer)?$u_answer[$v['id']]:'';
					$return_subject[$k]['cname'] = Db::name('XmSubjectClass')->where('id',$v['cid'])->value('name');
					$return_subject[$k]['is_collect'] = Db::name('XmSubjectCollect')->where('uid',$uid)->where('subject_id',$v['id'])->count();
				}
				
				$vo['create_at'] = date('Y-m-d',strtotime($vo['create_at']));

                $vo['sub_id']= json_decode($vo['sub_id'],true);

                if($vo['otype'] == 3){
                    $vo['otype_name'] = Db::name('XmSubjectClass')->where('id',$vo['cid'])->value('name');
                }
                if($vo['otype'] == 4){
                    $vo['otype_name'] = Db::name('XmCustomPaper')->where('id',$vo['cid'])->value('title');
                }
				$tmp['paper']  = $vo;
				$tmp['subject']  = $return_subject;
				
				$return['list'][] = $tmp;
				
            }
            
			$return['total_num'] = $total_num;
			$return['total_time'] = round($total_time/60);
            $this->success('ok',$return);



        }catch(Exception $e){

            $this->error($e->getMessage());
        }

    }
}

