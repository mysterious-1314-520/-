<?php
/* 祈福导航系统 V1.3 官方开源：https://gitee.com/qifuxitong/daohang */
 
error_reporting(0);
define('CACHE_FILE', 0);
define('IN_CRONLITE', true);
define('SYSTEM_ROOT', dirname(__FILE__).'/');
define('ROOT', dirname(SYSTEM_ROOT).'/');
define('SYS_KEY', 'daishua_key');
define('CC_Defender', 1); //防CC攻击开关(1为session模式)

date_default_timezone_set("PRC");
$date = date("Y-m-d H:i:s");
if(session_status() === PHP_SESSION_NONE) session_start();

$script_name = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
$server_port = isset($_SERVER['SERVER_PORT']) ? $_SERVER['SERVER_PORT'] : '';
$http_host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
$scriptpath=str_replace('\\','/',$script_name);
$sitepath_pos = strrpos($scriptpath, '/');
$sitepath = $sitepath_pos === false ? '' : substr($scriptpath, 0, $sitepath_pos);
$siteurl = ($server_port == '443' ? 'https://' : 'http://').$http_host.$sitepath.'/';
$rootpath = preg_replace('#/(admin|install)$#', '', $sitepath);
$rooturl = ($server_port == '443' ? 'https://' : 'http://').$http_host.$rootpath.'/';

if(is_file(SYSTEM_ROOT.'360safe/360webscan.php')){//360网站卫士
    require_once(SYSTEM_ROOT.'360safe/360webscan.php');
}

if(!function_exists('dh_json_exit')) {
function dh_json_exit($msg, $code = 0) {
	if(defined('DH_JSON_RESPONSE') && DH_JSON_RESPONSE) {
		while(ob_get_level() > 0) @ob_end_clean();
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array('code'=>$code, 'msg'=>$msg), JSON_UNESCAPED_UNICODE);
		exit();
	}
}
}

if(!is_file(ROOT.'install/install.lock')){
	if(defined('DH_JSON_RESPONSE') && DH_JSON_RESPONSE){
		dh_json_exit('祈福导航系统尚未安装，请先访问 /install/ 完成安装');
	}
	@header('Location: '.$rooturl.'install/');
	exit();
}

require ROOT.'config.php';

if(!defined('SQLITE') && (!$dbconfig['user']||!$dbconfig['pwd']||!$dbconfig['dbname']))//检测安装
{
dh_json_exit('祈福导航系统配置缺失，请重新安装');
header('Content-type:text/html;charset=utf-8');
echo '祈福导航系统未完成安装！<a href="'.$rooturl.'install/">点此安装</a>';
exit();
}

//连接数据库
include_once(SYSTEM_ROOT."db.class.php");
$DB=new DB($dbconfig['host'],$dbconfig['user'],$dbconfig['pwd'],$dbconfig['dbname'],$dbconfig['port']);

if($DB->query("select * from web_config where 1")==FALSE)//检测安装2
{
dh_json_exit('祈福导航系统数据库未初始化，请先完成安装');
header('Content-type:text/html;charset=utf-8');
echo '祈福导航系统未完成安装！<a href="'.$rooturl.'install/">点此安装</a>';
exit();
}

include SYSTEM_ROOT.'cache.class.php';
$CACHE=new CACHE();
$conf=$CACHE->pre_fetch();//获取系统配置
$ad_defaults = array(
	'ad_enabled' => '0',
	'ad_position' => 'below_search',
	'ad_show_below' => '1',
	'ad_show_right' => '0',
	'ad_show_left' => '0',
	'ad_image' => '',
	'ad_link' => '',
	'ad_title' => '',
	'ad_alt' => '',
	'ad_image2' => '',
	'ad_link2' => '',
	'ad_title2' => '',
	'ad_alt2' => '',
	'ad_image3' => '',
	'ad_link3' => '',
	'ad_title3' => '',
	'ad_alt3' => '',
	'ad_image4' => '',
	'ad_link4' => '',
	'ad_title4' => '',
	'ad_alt4' => '',
	'ad_right_image' => '',
	'ad_right_link' => '',
	'ad_right_title' => '',
	'ad_right_alt' => '',
	'ad_left_image' => '',
	'ad_left_link' => '',
	'ad_left_title' => '',
	'ad_left_alt' => '',
	'ad_new_window' => '1'
);
$ad_need_update = false;
foreach($ad_defaults as $ad_key => $ad_value){
	if(!isset($conf[$ad_key])){
		$DB->query("REPLACE INTO web_config SET k='".$DB->escape($ad_key)."',v='".$DB->escape($ad_value)."'");
		$conf[$ad_key] = $ad_value;
		$ad_need_update = true;
	}
}
if($ad_need_update){
	$CACHE->clear();
	$conf=$CACHE->update();
}
$password_hash='!@#%!s!0';
include_once(SYSTEM_ROOT."function.php");
include_once(SYSTEM_ROOT."ad_helper.php");
qifu_ad_ensure_tables();
qifu_ad_ensure_config();
qifu_ad_seed_legacy();
@// mail.php 暂不全局加载，由具体功能页面按需引入
include_once(SYSTEM_ROOT."member.php");
include_once(SYSTEM_ROOT."authcode.php");
include_once(SYSTEM_ROOT."version.php");
include_once(SYSTEM_ROOT."brand.php");

?>
