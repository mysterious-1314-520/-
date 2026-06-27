<?php
/* 祈福导航系统 V1.3 官方开源：https://gitee.com/qifuxitong/daohang */
 
if(!defined('IN_CRONLITE'))exit();

$my=isset($_GET['my'])?$_GET['my']:null;

$clientip=real_ip();

if(isset($_COOKIE["admin_token"]))
{
	$token=authcode(daddslashes($_COOKIE['admin_token']), 'DECODE', SYS_KEY);
	list($user, $sid) = array_pad(explode("\t", $token, 2), 2, '');
	$session=md5($conf['admin_user'].$conf['admin_pwd'].$password_hash);
	if($session==$sid) {
		$islogin=1;
	}
}
?>
