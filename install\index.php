<?php
/* 祈福导航系统 V1.3 官方开源：https://gitee.com/qifuxitong/daohang */
error_reporting(0);
@header('Content-Type: text/html; charset=UTF-8');
$do=isset($_GET['do'])?$_GET['do']:'0';
$qifu_version = 'V1.3';
$installed=false;
if(file_exists('install.lock')){
	$installed=true;
	$do='0';
}

function checkfunc($f,$m = false) {
	if (function_exists($f)) {
		return '<font color="green">可用</font>';
	} else {
		if ($m == false) {
			return '<font color="black">不支持</font>';
		} else {
			return '<font color="red">不支持</font>';
		}
	}
}

function checkclass($f,$m = false) {
	if (class_exists($f)) {
		return '<font color="green">可用</font>';
	} else {
		if ($m == false) {
			return '<font color="black">不支持</font>';
		} else {
			return '<font color="red">不支持</font>';
		}
	}
}

function build_config($db_host, $db_port, $db_user, $db_pwd, $db_name) {
	$config = array(
		'host' => $db_host,
		'port' => intval($db_port),
		'user' => $db_user,
		'pwd' => $db_pwd,
		'dbname' => $db_name,
	);
	return "<?php\n/* 数据库配置：由安装程序自动生成 */\n\$dbconfig=".var_export($config, true).";\n?>";
}

$root_dir = dirname(__DIR__);
$config_file = $root_dir.'/config.php';
$php_ok = version_compare(PHP_VERSION, '7.4.0', '>=');
$db_ext_ok = extension_loaded('mysqli') || extension_loaded('pdo_mysql');
$mb_ok = function_exists('mb_strlen');
$curl_ok = function_exists('curl_exec');
$file_get_ok = function_exists('file_get_contents');
$config_writable = (file_exists($config_file) && is_writable($config_file)) || (!file_exists($config_file) && is_writable($root_dir));
$install_writable = is_writable(__DIR__);
$env_ok = $php_ok && $db_ext_ok && $mb_ok && $curl_ok && $file_get_ok && $config_writable;

function checkok($ok) {
	return $ok ? '<font color="green">可用</font>' : '<font color="red">不支持</font>';
}

function write_install_lock() {
	return @file_put_contents(__DIR__.'/install.lock', '安装锁') !== false;
}

function install_success_message($account_text) {
	$lock_ok = write_install_lock();
	$lock_tip = $lock_ok ? '' : '<br/><br/><font color="#FF0033">install.lock 写入失败，请自行在 install/ 目录建立 install.lock 文件，否则系统会继续进入安装流程！</font>';
	return '<div class="alert alert-info"><font color="green">安装完成！'.$account_text.'</font><br/><br/><a href="../">>>网站首页</a>｜<a href="../admin/">>>后台管理</a><hr/>更多设置选项请登录后台管理进行修改。'.$lock_tip.'</div>';
}

?>


<html lang="zh-cn">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<meta name="viewport" content="initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0,user-scalable=no,minimal-ui">
<title>安装程序 - 祈福导航系统</title>
<link href="//cdn.bootcss.com/bootstrap/3.3.5/css/bootstrap.min.css" rel="stylesheet"/>
<style>
body{background:#f4f7fb;color:#263238}
.install-wrap{padding-top:74px;padding-bottom:40px}
.install-card{border:0;border-radius:8px;box-shadow:0 12px 36px rgba(30,68,120,.12);overflow:hidden}
.install-card .panel-heading{background:#12a53b!important;border:0;padding:16px 20px}
.install-card .panel-title{font-size:20px;font-weight:700}
.install-hero{background:linear-gradient(135deg,#f7fff9,#eef8ff);padding:28px 32px;border-bottom:1px solid #e7eef5}
.install-hero h1{font-size:28px;margin:0 0 12px;font-weight:700;color:#14324a}
.install-hero p{font-size:15px;line-height:1.8;margin:0;color:#51606d}
.install-brand{margin-top:16px;padding:12px 14px;background:#fff;border:1px solid #dce9f3;border-radius:6px;color:#31475a;font-size:14px;line-height:1.7}
.install-brand a{color:#0d8f36;font-weight:700;word-break:break-all}
.feature-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-top:22px}
.feature-item{background:#fff;border:1px solid #e5edf3;border-radius:8px;padding:16px;min-height:98px}
.feature-item b{display:block;color:#17324d;margin-bottom:8px;font-size:15px}
.feature-item span{display:block;color:#6c7a86;line-height:1.6;font-size:13px}
.install-section{padding:24px 32px}
.install-section h3{font-size:17px;margin:0 0 14px;color:#17324d;font-weight:700}
.install-list{padding-left:18px;line-height:2;color:#44515c}
.env-list{display:grid;grid-template-columns:repeat(2,1fr);gap:10px;margin:0;padding:0;list-style:none}
.env-list li{background:#f8fafc;border:1px solid #e7edf3;border-radius:6px;padding:10px 12px}
.start-actions{text-align:center;padding:0 32px 30px}
.start-actions .btn{min-width:180px;border-radius:4px;font-weight:700;padding:11px 24px}
@media(max-width:768px){.feature-grid,.env-list{grid-template-columns:1fr}.install-hero,.install-section{padding:22px 18px}}
body{min-height:100vh;background:radial-gradient(circle at 12% 8%,rgba(38,166,154,.18),transparent 28%),radial-gradient(circle at 88% 4%,rgba(47,128,237,.16),transparent 30%),linear-gradient(180deg,#f8fbff 0,#eef5fb 100%);font-family:"Microsoft YaHei","PingFang SC",Arial,sans-serif}
.navbar-default{background:rgba(255,255,255,.84);border:0;border-bottom:1px solid rgba(203,218,232,.75);backdrop-filter:blur(14px);box-shadow:0 10px 30px rgba(31,60,95,.06)}
.navbar-default .navbar-brand{font-weight:900;color:#17324d}
.navbar-default .navbar-brand{font-size:0}
.navbar-default .navbar-brand:after{content:"祈福导航系统安装向导";font-size:18px}
.install-card{border-radius:22px;box-shadow:0 24px 70px rgba(30,68,120,.14)}
.install-card .panel-heading{background:linear-gradient(135deg,#14324d,#0f9f8f)!important;padding:18px 22px}
.install-card .panel-title{letter-spacing:.08em}
.install-card .panel-title{font-size:0!important}
.install-card .panel-title:after{content:"安装说明";font-size:20px}
.install-hero{position:relative;background:linear-gradient(135deg,#f5fffb,#eef8ff 58%,#fffaf0);padding:36px 38px 30px;overflow:hidden}
.install-hero:before{content:"";position:absolute;right:-80px;top:-90px;width:260px;height:260px;border-radius:50%;background:linear-gradient(135deg,rgba(17,199,163,.18),rgba(47,128,237,.12))}
.install-hero h1{position:relative;font-size:32px;line-height:1.25;margin-bottom:14px;color:#102a43}
.install-hero>h1{display:none}
.install-title-modern{position:relative;margin-bottom:14px}
.install-title-modern h1{display:block!important;font-size:32px;line-height:1.25;margin:0;color:#102a43;font-weight:900}
.version-pill{display:inline-flex;align-items:center;gap:8px;margin-left:10px;padding:8px 13px;border-radius:999px;background:linear-gradient(135deg,#ffb020,#ff6b35);color:#fff;font-size:15px;font-weight:900;vertical-align:middle;box-shadow:0 12px 26px rgba(255,107,53,.25)}
.release-strip{position:relative;display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-top:20px}
.release-item{border:1px solid rgba(186,210,229,.72);border-radius:16px;background:rgba(255,255,255,.72);padding:14px 16px;box-shadow:0 10px 24px rgba(50,90,130,.06)}
.release-item b{display:block;color:#12304a;font-size:15px}
.release-item span{display:block;color:#657589;font-size:12px;margin-top:5px}
.install-brand{position:relative;border-radius:14px;border-color:#d8e8f4;box-shadow:0 10px 26px rgba(45,80,120,.05)}
.feature-item{border-radius:16px;transition:.22s;box-shadow:0 10px 24px rgba(45,80,120,.045)}
.feature-item:hover{transform:translateY(-2px);border-color:#abd7ee;box-shadow:0 18px 34px rgba(45,110,155,.10)}
.env-list li{border-radius:12px;background:#fff;box-shadow:0 8px 18px rgba(50,80,110,.04)}
.start-actions .btn,.btn-primary{border:0;border-radius:14px;background:linear-gradient(135deg,#13b7d7,#2f80ed)!important;box-shadow:0 14px 28px rgba(47,128,237,.25);font-weight:800}
.start-actions .btn:hover,.btn-primary:hover{transform:translateY(-1px);box-shadow:0 18px 34px rgba(47,128,237,.32)}
.progress{height:10px;border-radius:999px;box-shadow:none;background:#e8f0f7}
.progress-bar-success{background:linear-gradient(90deg,#11c7a3,#2f80ed)}
@media(max-width:768px){.release-strip{grid-template-columns:1fr}.version-pill{display:inline-flex;margin:10px 0 0}.install-hero h1{font-size:25px}}

/* Minimal install wizard skin. */
:root {
  --install-bg: #f6f8fb;
  --install-card: #fff;
  --install-line: #e8edf3;
  --install-text: #17233d;
  --install-muted: #697386;
  --install-primary: #16a34a;
  --install-primary-soft: #ecfdf3;
  --install-danger: #ef4444;
  --install-warning: #f59e0b;
  --install-radius: 18px;
}

html,
body {
  min-height: 100vh;
  background: var(--install-bg) !important;
  color: var(--install-text);
  font-family: "Source Han Sans SC", "Noto Sans SC", "PingFang SC", "Microsoft YaHei UI", "Microsoft YaHei", Arial, sans-serif !important;
}

.navbar-default {
  min-height: 66px;
  background: rgba(255,255,255,.96) !important;
  border: 0 !important;
  border-bottom: 1px solid var(--install-line) !important;
  box-shadow: none !important;
  backdrop-filter: blur(12px);
}

.navbar-default .container {
  min-height: 66px;
  display: flex;
  align-items: center;
}

.navbar-default .navbar-header {
  float: none;
}

.navbar-default .navbar-brand {
  height: auto;
  padding: 0;
  display: inline-flex;
  align-items: center;
  gap: 12px;
  color: var(--install-text) !important;
  font-size: 0 !important;
  font-weight: 600 !important;
  line-height: 1;
}

.navbar-default .navbar-brand:before {
  content: "";
  width: 36px;
  height: 36px;
  display: inline-block;
  border-radius: 12px;
  background: linear-gradient(135deg, #22c55e, #16a34a);
  box-shadow: 0 8px 20px rgba(22, 163, 74, .18);
}

.navbar-default .navbar-brand:after {
  content: "祈福导航安装向导" !important;
  color: var(--install-text);
  font-size: 18px !important;
  letter-spacing: .01em;
}

.install-wrap {
  width: 100%;
  max-width: 1040px;
  padding-top: 32px !important;
  padding-bottom: 56px !important;
}

.install-wrap > .center-block {
  max-width: 960px;
}

.install-wrap .panel,
.install-card {
  overflow: hidden;
  border: 1px solid var(--install-line) !important;
  border-radius: var(--install-radius) !important;
  background: var(--install-card) !important;
  box-shadow: 0 12px 36px rgba(15, 23, 42, .06) !important;
}

.install-wrap .panel-heading,
.install-card .panel-heading {
  padding: 22px 28px !important;
  border: 0 !important;
  border-bottom: 1px solid var(--install-line) !important;
  background: #fff !important;
}

.install-wrap .panel-title,
.install-card .panel-title {
  margin: 0;
  color: var(--install-text) !important;
  font-size: 19px !important;
  font-weight: 600 !important;
  letter-spacing: 0 !important;
  text-align: left !important;
}

.install-card .panel-title:after {
  display: none !important;
  content: none !important;
}

.install-wrap .panel-title:before {
  content: "";
  width: 8px;
  height: 8px;
  display: inline-block;
  margin-right: 10px;
  border-radius: 50%;
  background: var(--install-primary);
  vertical-align: 2px;
  box-shadow: 0 0 0 5px rgba(22, 163, 74, .10);
}

.install-wrap .panel-body {
  padding: 28px !important;
}

.install-hero {
  position: relative;
  padding: 34px 36px !important;
  background: #fff !important;
  border-bottom: 1px solid var(--install-line) !important;
}

.install-hero:before {
  display: none !important;
}

.install-title-modern h1,
.install-hero h1 {
  color: var(--install-text) !important;
  font-size: 30px !important;
  font-weight: 600 !important;
  letter-spacing: -.02em;
}

.install-hero p {
  max-width: 780px;
  color: var(--install-muted) !important;
  font-size: 15px !important;
  line-height: 1.9 !important;
}

.version-pill {
  padding: 5px 10px !important;
  border: 1px solid #bbf7d0;
  background: var(--install-primary-soft) !important;
  color: var(--install-primary) !important;
  box-shadow: none !important;
  font-size: 12px !important;
  font-weight: 700 !important;
}

.release-strip,
.feature-grid,
.env-list {
  gap: 12px !important;
}

.release-item,
.feature-item,
.env-list li,
.install-brand {
  border: 1px solid var(--install-line) !important;
  border-radius: 14px !important;
  background: #fff !important;
  box-shadow: none !important;
}

.feature-item:hover {
  transform: none !important;
  border-color: #bbf7d0 !important;
  box-shadow: none !important;
}

.release-item b,
.feature-item b,
.install-section h3 {
  color: var(--install-text) !important;
  font-weight: 600 !important;
}

.release-item span,
.feature-item span,
.install-list,
.env-list li {
  color: var(--install-muted) !important;
}

.install-section {
  padding: 28px 36px !important;
}

.install-list {
  padding-left: 22px !important;
  line-height: 2.05 !important;
}

.progress {
  height: 4px !important;
  margin: 0 28px !important;
  border-radius: 999px !important;
  background: #f1f5f9 !important;
  box-shadow: none !important;
  overflow: hidden;
}

.progress-bar-success {
  background: var(--install-primary) !important;
  box-shadow: none !important;
}

.table {
  margin-bottom: 20px;
  border: 1px solid var(--install-line);
  border-radius: 14px;
  overflow: hidden;
  background: #fff;
}

.table > thead > tr > th {
  border: 0 !important;
  background: #f8fafc !important;
  color: var(--install-muted);
  font-size: 13px;
  font-weight: 500;
}

.table > tbody > tr > td {
  padding: 15px 18px !important;
  border-top: 1px solid var(--install-line) !important;
  color: var(--install-text);
  vertical-align: middle !important;
}

.table-striped > tbody > tr:nth-of-type(odd) {
  background: #fff !important;
}

.form-sign {
  max-width: 560px;
  margin: 0 auto;
}

.form-sign label {
  margin-top: 15px;
  margin-bottom: 8px;
  color: var(--install-text);
  font-weight: 500;
}

.form-control {
  height: 44px;
  border: 1px solid #d8dee8;
  border-radius: 12px;
  box-shadow: none !important;
  color: var(--install-text);
}

.form-control:focus {
  border-color: #86efac;
  box-shadow: 0 0 0 3px rgba(34, 197, 94, .12) !important;
}

.btn {
  border: 0 !important;
  border-radius: 12px !important;
  padding: 10px 18px !important;
  font-weight: 500 !important;
  box-shadow: none !important;
  transition: background-color .16s ease, color .16s ease, border-color .16s ease !important;
}

.btn-primary,
.start-actions .btn {
  background: var(--install-primary) !important;
  color: #fff !important;
}

.btn-primary:hover,
.start-actions .btn:hover {
  transform: none !important;
  background: #15803d !important;
  box-shadow: none !important;
}

.btn-info {
  background: #e0f2fe !important;
  color: #0369a1 !important;
}

.btn-warning {
  background: #fff7ed !important;
  color: #c2410c !important;
}

.alert {
  border: 1px solid transparent !important;
  border-radius: 14px !important;
  box-shadow: none !important;
  line-height: 1.8;
}

.alert-success,
.alert-info {
  border-color: #bbf7d0 !important;
  background: var(--install-primary-soft) !important;
  color: #166534 !important;
}

.alert-warning {
  border-color: #fed7aa !important;
  background: #fff7ed !important;
  color: #9a3412 !important;
}

.alert-danger {
  border-color: #fecaca !important;
  background: #fef2f2 !important;
  color: #991b1b !important;
}

.list-group-item {
  border-color: var(--install-line) !important;
}

.start-actions {
  padding: 0 36px 34px !important;
}

.install-wrap a {
  color: var(--install-primary);
  text-decoration: none !important;
}

.install-wrap a:hover {
  color: #15803d;
}

.install-wrap font[color="green"],
.install-wrap font[color=green] {
  color: var(--install-primary) !important;
  font-weight: 600;
}

.install-wrap font[color="red"],
.install-wrap font[color=red],
.install-wrap font[color="#FF0033"] {
  color: var(--install-danger) !important;
  font-weight: 600;
}

.install-wrap font[color="black"],
.install-wrap font[color=black] {
  color: var(--install-muted) !important;
}

.install-wrap .panel > p,
.install-wrap .panel-body > p {
  margin: 20px 28px 0;
  min-height: 44px;
}

.install-wrap .panel-body > p {
  margin-left: 0;
  margin-right: 0;
}

.install-wrap .panel > .alert {
  margin: 20px 28px 28px !important;
}

.install-wrap .panel-body > .alert:first-child {
  margin-top: 0;
}

.install-wrap .btn-block {
  height: 44px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.list-group-item {
  margin: 0 0 12px;
  border-radius: 14px !important;
  background: #fff !important;
}

.list-group-item-info {
  border-color: #bfdbfe !important;
  background: #eff6ff !important;
  color: #1d4ed8 !important;
}

.install-wrap hr {
  margin: 18px 0;
  border-top-color: var(--install-line);
}

.install-wrap .panel-body > br,
.form-sign + br {
  display: none;
}

.form-sign + br + * {
  display: inline-block;
  margin-top: 18px;
  color: var(--install-muted);
  font-size: 14px;
}

@media (max-width: 768px) {
  .navbar-default .container {
    padding-left: 18px;
    padding-right: 18px;
  }
  .install-wrap {
    padding-top: 18px !important;
    padding-left: 12px;
    padding-right: 12px;
  }
  .install-wrap .panel-heading,
  .install-wrap .panel-body,
  .install-hero,
  .install-section {
    padding: 22px !important;
  }
  .install-title-modern h1,
  .install-hero h1 {
    font-size: 24px !important;
  }
  .release-strip,
  .feature-grid,
  .env-list {
    grid-template-columns: 1fr !important;
  }
}
</style>
</head>
<body>
  <div class="container install-wrap">
    <div class="col-xs-12 col-sm-11 col-lg-10 center-block" style="float: none;">

<?php if($do=='0'){?>
<div class="panel panel-primary install-card">
	<div class="panel-heading">
		<h3 class="panel-title" align="center">安装说明</h3>
	</div>
	<div class="panel-body" style="padding:0;">
		<div class="install-hero">
			<div class="install-title-modern">
				<h1>欢迎使用祈福导航系统 <span class="version-pill"><?php echo $qifu_version; ?></span></h1>
			</div>
			<div class="release-strip">
				<div class="release-item"><b>V1.3 正式版</b><span>简约后台与安装体验全面升级</span></div>
				<div class="release-item"><b>PHP 7.4+</b><span>兼容 PHP 7.4 及以上版本</span></div>
				<div class="release-item"><b>官方开源</b><span>代码文件头统一标注官方开源来源</span></div>
			</div>
			<h1>欢迎使用祈福导航系统 V1.3 正式版。</h1>
			<p>祈福导航系统是一套轻量、易部署、便于二次维护的网址导航与友链管理程序，适合资源导航、内部工具导航、个人收藏站和企业导航页使用。</p>
			<div class="install-brand">
				<b>官方开源：</b>祈福导航系统<br>
				<b>项目地址：</b><a href="https://gitee.com/qifuxitong/daohang" target="_blank" rel="noopener">https://gitee.com/qifuxitong/daohang</a>
			</div>
			<div class="feature-grid">
				<div class="feature-item"><b>站点分类管理</b><span>支持分类、图标、排序、显示状态管理，导航内容更清晰。</span></div>
				<div class="feature-item"><b>友链申请审核</b><span>前台提交友链，后台审核通过后可自动加入站点列表。</span></div>
				<div class="feature-item"><b>简约后台 UI</b><span>白色主题、绿色强调、侧边栏分组和右侧内容局部刷新。</span></div>
				<div class="feature-item"><b>服务器状态</b><span>关于我们页面实时展示 CPU、内存、硬盘、GPU、PHP 和数据库状态。</span></div>
				<div class="feature-item"><b>前台界面设置</b><span>可设置公告、搜索、时钟、卡片大小、列数、背景和动画效果。</span></div>
				<div class="feature-item"><b>统计与备份</b><span>后台提供访问统计、操作日志、数据备份和恢复工具。</span></div>
			</div>
		</div>
		<div class="install-section">
			<h3>安装环境</h3>
			<ul class="env-list">
				<li><b>PHP：</b>PHP &gt;= 7.4</li>
				<li><b>数据库：</b>MySQL 5.6+ / MariaDB 10.x</li>
				<li><b>扩展：</b>mysqli 或 pdo_mysql、mbstring、curl</li>
				<li><b>权限：</b>config.php、install/、images/bg/ 可写</li>
			</ul>
		</div>
		<div class="install-section" style="padding-top:0;">
			<h3>安装步骤</h3>
			<ol class="install-list">
				<li>将程序上传到网站根目录，绑定域名后访问首页。</li>
				<li>系统会自动进入安装流程，按提示填写数据库信息。</li>
				<li>创建数据表完成后进入后台，默认账号 admin，默认密码 123456。</li>
				<li>首次登录后台后请立即修改密码，并根据需要配置分类、站点、友链和邮件通知。</li>
			</ol>
		</div>
		<?php if($installed){ ?>
		<div class="alert alert-warning" style="margin:0 32px 30px;">您已经安装过，如需重新安装请删除<font color=red> install/install.lock </font>文件后再安装！</div>
		<?php }else{?>
		<div class="start-actions"><a class="btn btn-primary" href="index.php?do=1">开始安装</a></div>
		<?php }?>
	</div>
</div>

<?php }elseif($do=='1'){?>
<div class="panel panel-primary">
	<div class="panel-heading">
		<h3 class="panel-title" align="center">环境检查</h3>
	</div>
<div class="progress progress-striped">
  <div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="40" aria-valuemin="0" aria-valuemax="100" style="width: 10%">
	<span class="sr-only">10%</span>
  </div>
</div>
<table class="table table-striped">
	<thead>
		<tr>
			<th style="width:20%">函数检测</th>
			<th style="width:15%">需求</th>
			<th style="width:15%">当前</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td>PHP &gt;= 7.4</td>
			<td>必须</td>
			<td><?php echo $php_ok ? '<font color="green">'.phpversion().'</font>' : '<font color="red">'.phpversion().'</font>'; ?></td>
		</tr>
		<tr>
			<td>mysqli / pdo_mysql</td>
			<td>必须</td>
			<td><?php echo checkok($db_ext_ok); ?></td>
		</tr>
		<tr>
			<td>mbstring</td>
			<td>必须</td>
			<td><?php echo checkok($mb_ok); ?></td>
		</tr>
		<tr>
			<td>curl_exec()</td>
			<td>必须</td>
			<td><?php echo checkok($curl_ok); ?></td>
		</tr>
		<tr>
			<td>file_get_contents()</td>
			<td>必须</td>
			<td><?php echo checkok($file_get_ok); ?></td>
		</tr>
		<tr>
			<td>config.php 写入权限</td>
			<td>必须</td>
			<td><?php echo $config_writable ? '<font color="green">可写</font>' : '<font color="red">不可写</font>'; ?></td>
		</tr>
		<tr>
			<td>install 目录写入权限</td>
			<td>建议</td>
			<td><?php echo $install_writable ? '<font color="green">可写</font>' : '<font color="black">不可写，安装完成后需手动创建 install.lock</font>'; ?></td>
		</tr>
	</tbody>
</table>
<p><span><a class="btn btn-primary" href="index.php?do=0">上一步</a></span>
<?php if($env_ok){ ?>
<span style="float:right"><a class="btn btn-primary" href="index.php?do=2" align="right">下一步</a></span>
<?php } ?></p>
<?php if(!$env_ok){ ?>
<div class="alert alert-danger" style="margin-top:15px;">当前环境未满足安装要求，请修复红色项目后继续。</div>
<?php } ?>
</div>

<?php }elseif($do=='2'){?>
<div class="panel panel-primary">
	<div class="panel-heading">
		<h3 class="panel-title" align="center">数据库配置</h3>
	</div>
<div class="progress progress-striped">
  <div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="40" aria-valuemin="0" aria-valuemax="100" style="width: 30%">
	<span class="sr-only">30%</span>
  </div>
</div>
	<div class="panel-body">
	<?php
if(!$env_ok)
echo '<div class="alert alert-danger">当前环境未满足安装要求，请返回环境检查页处理后继续。</div>';
elseif(defined("SAE_ACCESSKEY"))
echo <<<HTML
检测到您使用的是SAE空间，支持一键安装，请点击 <a href="?do=3">下一步</a>
HTML;
else
echo <<<HTML
		<form action="?do=3" class="form-sign" method="post">
		<label for="name">数据库地址:</label>
		<input type="text" class="form-control" name="db_host" value="localhost">
		<label for="name">数据库端口:</label>
		<input type="text" class="form-control" name="db_port" value="3306">
		<label for="name">数据库用户名:</label>
		<input type="text" class="form-control" name="db_user">
		<label for="name">数据库密码:</label>
		<input type="text" class="form-control" name="db_pwd">
		<label for="name">数据库名:</label>
		<input type="text" class="form-control" name="db_name">
		<br><input type="submit" class="btn btn-primary btn-block" name="submit" value="保存配置">
		</form><br/>
		（如果已事先填写好config.php相关数据库配置，请 <a href="?do=3&jump=1">点击此处</a> 跳过这一步！）
HTML;
?>
	</div>
</div>

<?php }elseif($do=='3'){
?>
<div class="panel panel-primary">
	<div class="panel-heading">
		<h3 class="panel-title" align="center">保存数据库</h3>
	</div>
<div class="progress progress-striped">
  <div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="40" aria-valuemin="0" aria-valuemax="100" style="width: 50%">
	<span class="sr-only">50%</span>
  </div>
</div>
	<div class="panel-body">
<?php
require __DIR__ . '/db.class.php';
if(defined("SAE_ACCESSKEY") || (isset($_GET['jump']) && $_GET['jump']==1)){
	if(defined("SAE_ACCESSKEY"))include_once dirname(__DIR__) . '/includes/sae.php';
	else include_once dirname(__DIR__) . '/config.php';
	if(!$dbconfig['user']||!$dbconfig['pwd']||!$dbconfig['dbname']) {
		echo '<div class="alert alert-danger">请先填写好数据库并保存后再安装！<hr/><a href="javascript:history.back(-1)"><< 返回上一页</a></div>';
	} else {
		if(!$con=DB::connect($dbconfig['host'],$dbconfig['user'],$dbconfig['pwd'],$dbconfig['dbname'],$dbconfig['port'])){
			if(DB::connect_errno()==2002)
				echo '<div class="alert alert-warning">连接数据库失败，数据库地址填写错误！</div>';
			elseif(DB::connect_errno()==1045)
				echo '<div class="alert alert-warning">连接数据库失败，数据库用户名或密码填写错误！</div>';
			elseif(DB::connect_errno()==1049)
				echo '<div class="alert alert-warning">连接数据库失败，数据库名不存在！</div>';
			else
				echo '<div class="alert alert-warning">连接数据库失败，['.DB::connect_errno().']'.DB::connect_error().'</div>';
		}else{
			echo '<div class="alert alert-success">数据库配置文件保存成功！</div>';
			if(DB::query("select * from web_config where 1")==FALSE)
				echo '<p align="right"><a class="btn btn-primary btn-block" href="?do=4">创建数据表>></a></p>';
			else
				echo '<div class="list-group-item list-group-item-info">系统检测到你已安装过祈福导航系统</div>
				<div class="list-group-item">
					<a href="?do=6" class="btn btn-block btn-info">跳过安装</a>
				</div>
				<div class="list-group-item">
					<a href="?do=4" onclick="if(!confirm(\'全新安装将会清空所有数据，是否继续？\')){return false;}" class="btn btn-block btn-warning">强制全新安装</a>
				</div>';
		}
	}
}else{
	$db_host=isset($_POST['db_host'])?$_POST['db_host']:NULL;
	$db_port=isset($_POST['db_port'])?$_POST['db_port']:NULL;
	$db_user=isset($_POST['db_user'])?$_POST['db_user']:NULL;
	$db_pwd=isset($_POST['db_pwd'])?$_POST['db_pwd']:NULL;
	$db_name=isset($_POST['db_name'])?$_POST['db_name']:NULL;

	if($db_host==null || $db_port==null || $db_user==null || $db_pwd==null || $db_name==null){
		echo '<div class="alert alert-danger">保存错误,请确保每项都不为空<hr/><a href="javascript:history.back(-1)"><< 返回上一页</a></div>';
	} else {
		$config = build_config($db_host, $db_port, $db_user, $db_pwd, $db_name);
		if(!$con=DB::connect($db_host,$db_user,$db_pwd,$db_name,$db_port)){
			if(DB::connect_errno()==2002)
				echo '<div class="alert alert-warning">连接数据库失败，数据库地址填写错误！</div>';
			elseif(DB::connect_errno()==1045)
				echo '<div class="alert alert-warning">连接数据库失败，数据库用户名或密码填写错误！</div>';
			elseif(DB::connect_errno()==1049)
				echo '<div class="alert alert-warning">连接数据库失败，数据库名不存在！</div>';
			else
				echo '<div class="alert alert-warning">连接数据库失败，['.DB::connect_errno().']'.DB::connect_error().'</div>';
		}elseif(file_put_contents('../config.php',$config)){
			echo '<div class="alert alert-success">数据库配置文件保存成功！</div>';
			if(DB::query("select * from web_config where 1")==FALSE)
				echo '<p align="right"><a class="btn btn-primary btn-block" href="?do=4">创建数据表>></a></p>';
			else
				echo '<div class="list-group-item list-group-item-info">系统检测到你已安装过祈福导航系统</div>
				<div class="list-group-item">
					<a href="?do=6" class="btn btn-block btn-info">跳过安装</a>
				</div>
				<div class="list-group-item">
					<a href="?do=4" onclick="if(!confirm(\'全新安装将会清空所有数据，是否继续？\')){return false;}" class="btn btn-block btn-warning">强制全新安装</a>
				</div>';
		}else
			echo '<div class="alert alert-danger">保存失败，请确保网站根目录有写入权限<hr/><a href="javascript:history.back(-1)"><< 返回上一页</a></div>';
	}
}
?>
	</div>
</div>
<?php }elseif($do=='4'){?>
<div class="panel panel-primary">
	<div class="panel-heading">
		<h3 class="panel-title" align="center">创建数据表</h3>
	</div>
<div class="progress progress-striped">
  <div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="40" aria-valuemin="0" aria-valuemax="100" style="width: 70%">
	<span class="sr-only">70%</span>
  </div>
</div>
	<div class="panel-body">
<?php
if(defined("SAE_ACCESSKEY"))include_once dirname(__DIR__) . '/includes/sae.php';
else include_once dirname(__DIR__) . '/config.php';
if(!$dbconfig['user']||!$dbconfig['pwd']||!$dbconfig['dbname']) {
	echo '<div class="alert alert-danger">请先填写好数据库并保存后再安装！<hr/><a href="javascript:history.back(-1)"><< 返回上一页</a></div>';
} else {
	require __DIR__ . '/db.class.php';
	$sql=file_get_contents(__DIR__."/install.sql");
	$sql=explode(';',$sql);
	$cn = DB::connect($dbconfig['host'],$dbconfig['user'],$dbconfig['pwd'],$dbconfig['dbname'],$dbconfig['port']);
	if (!$cn) die('err:'.DB::connect_error());
	DB::query("set sql_mode = ''");
	DB::query("set names utf8");
	$t=0; $e=0; $error='';
	for($i=0;$i<count($sql);$i++) {
		$query = trim($sql[$i]);
		if ($query==='')continue;
		if(DB::query($query)) {
			++$t;
		} else {
			++$e;
			$error.=DB::error().'<br/>';
		}
	}
}
if($e==0) {
	echo '<div class="alert alert-success">安装成功！<br/>SQL成功'.$t.'句/失败'.$e.'句</div><p align="right"><a class="btn btn-block btn-primary" href="index.php?do=5">下一步>></a></p>';
} else {
	echo '<div class="alert alert-danger">安装失败<br/>SQL成功'.$t.'句/失败'.$e.'句<br/>错误信息：'.$error.'</div><p align="right"><a class="btn btn-block btn-primary" href="index.php?do=4">点此进行重试</a></p>';
}
?>
	</div>
</div>

<?php }elseif($do=='5'){?>
<div class="panel panel-primary">
	<div class="panel-heading">
		<h3 class="panel-title" align="center">安装完成</h3>
	</div>
<div class="progress progress-striped">
  <div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="40" aria-valuemin="0" aria-valuemax="100" style="width: 100%">
	<span class="sr-only">100%</span>
  </div>
</div>
	<div class="panel-body">
<?php
	echo install_success_message('管理账号和密码是:admin/123456');
?>
	</div>
</div>

<?php }elseif($do=='6'){?>
<div class="panel panel-primary">
	<div class="panel-heading">
		<h3 class="panel-title" align="center">安装完成</h3>
	</div>
<div class="progress progress-striped">
  <div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="40" aria-valuemin="0" aria-valuemax="100" style="width: 100%">
	<span class="sr-only">100%</span>
  </div>
</div>
	<div class="panel-body">
<?php
	echo install_success_message('管理账号和密码:admin/123456');
?>
	</div>
</div>

<?php }?>

</div>
</body>
</html>
