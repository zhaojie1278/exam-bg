<?php

/*
* Copyright (C) 2015 xiuno.com
*/

!defined('DEBUG') AND exit('Access Denied.');

// hook index_start.php
$openid = param('openid');
$_uid = param(1, 0);
empty($_uid) AND $_uid = $uid;
$pduser = user_read($_uid);

if(!$pduser){
	if($openid){
		$find_user = db_find_one('user', array('openid'=>$openid));
		if($find_user){
			$email = $find_user['email'];			// 邮箱或者手机号 / email or mobile
			$password = $find_user['password'];
			empty($email) AND message('email', lang('email_is_empty'));
			if(is_email($email, $err)) {
				$_user = user_read_by_email($email);
				empty($_user) AND message('email', lang('email_not_exists'));
			} else {
				$_user = user_read_by_username($email);
				empty($_user) AND message('email', lang('username_not_exists'));
			}

			!is_password($password, $err) AND message('password', $err);
			$check = ($password == $_user['password']);

			!$check AND message('password', lang('password_incorrect'));

			// 更新登录时间和次数
			// update login times
			user_update($_user['uid'], array('login_ip'=>$longip, 'login_date' =>$time , 'logins+'=>1));

			// 全局变量 $uid 会在结束后，在函数 register_shutdown_function() 中存入 session (文件: model/session.func.php)
			// global variable $uid will save to session in register_shutdown_function() (file: model/session.func.php)
			$uid = $_user['uid'];

			$_SESSION['uid'] = $uid;

			user_token_set($_user['uid']);

		}else{
			$_uname = xn_rand(5).time();
			$email = $_uname.'@jcjy.com';
			$username = $_uname;
			$password = md5(xn_rand(16));

			if($conf['user_create_email_on']) {
				$sess_email = _SESSION('user_create_email');
				empty($sess_email) AND message('code', lang('click_to_get_verify_code'));
				$email != $sess_email AND message('code', lang('verify_code_incorrect'));
			}

			!is_email($email, $err) AND message('email', $err);
			$_user = user_read_by_email($email);
			$_user AND message('email', lang('email_is_in_use'));

			!is_username($username, $err) AND message('username', $err);
			$_user = user_read_by_username($username);
			$_user AND message('username', lang('username_is_in_use'));

			!is_password($password, $err) AND message('password', $err);

			$salt = xn_rand(16);
			$pwd = md5($password.$salt);
			$gid = 101;
			$_user = array (
				'username' => $username,
				'email' => $email,
				'password' => $pwd,
				'salt' => $salt,
				'gid' => $gid,
				'create_ip' => $longip,
				'create_date' => $time,
				'logins' => 1,
				'login_date' => $time,
				'login_ip' => $longip,
				'openid' => $openid,
			);
			$uid = user_create($_user);
			$uid === FALSE AND message('email', lang('user_create_failed'));
			$user = user_read($uid);

			// 更新 session

			unset($_SESSION['user_create_email']);
			unset($_SESSION['user_create_code']);
			$_SESSION['uid'] = $uid;
		}
		echo "<script>window.location.reload()</script>";exit;
	}
}

$page = param(1, 1);
$order = $conf['order_default'];
$order != 'tid' AND $order = 'lastpid';
$pagesize = $conf['pagesize'];
$active = 'default';

// 从默认的地方读取主题列表
$thread_list_from_default = 1;

// hook index_thread_list_before.php
if($thread_list_from_default) {
	$fids = arrlist_values($forumlist_show, 'fid');
	$threads = arrlist_sum($forumlist_show, 'threads');
	$pagination = pagination(url("$route-{page}"), $threads, $page, $pagesize);

	// hook thread_find_by_fids_before.php
	$threadlist = thread_find_by_fids($fids, $page, $pagesize, $order, $threads);
}

// 查找置顶帖
if($order == $conf['order_default'] && $page == 1) {
	$toplist3 = thread_top_find(0);
	$threadlist = $toplist3 + $threadlist;
}

// 过滤没有权限访问的主题 / filter no permission thread
thread_list_access_filter($threadlist, $gid);

// SEO
$header['title'] = $conf['sitename']; 				// site title
$header['keywords'] = ''; 					// site keyword
$header['description'] = $conf['sitebrief']; 			// site description
$_SESSION['fid'] = 0;

// hook index_end.php

include _include(APP_PATH.'view/htm/index.htm');

?>