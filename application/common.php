<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------


// 剩余时间
function time_remainder($time)
{
    $strtime = '';
    if ($time >= 86400) {
        $strtime .= intval($time/86400).'天';
        $time = $time % 86400;
    }
    if ($time >= 3600) {
        $strtime .= intval($time/3600).'小时';
        $time = $time % 3600;
    } else {
        $strtime .= '';
    }
    if ($time >= 60) {
        $strtime .= intval($time/60).'分钟';
        $time = $time % 60;
    } else {
        $strtime .= '';
    }
    if ($time > 0) {
        $strtime .= intval($time).'秒';
    }

    if (!$strtime) {
        $strtime = "时间错误";
    }
    return $strtime;
}


/**
 * 中间加密 用正则
 */
function encrypt_sub_phone($tel) {
    $new_tel = preg_replace('/(\d{3})\d{4}(\d{4})/', '$1****$2', $tel);
    return $new_tel;
}