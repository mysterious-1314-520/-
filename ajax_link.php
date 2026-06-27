<?php
/* 祈福导航系统 V1.3 官方开源：https://gitee.com/qifuxitong/daohang */

error_reporting(0);
ini_set('display_errors', 0);

define('DH_JSON_RESPONSE', true);
ob_start();
include __DIR__ . "/includes/common.php";
ob_end_clean();

header('Content-Type: application/json; charset=utf-8');

function link_json($code, $msg)
{
    echo json_encode(array('code' => $code, 'msg' => $msg), JSON_UNESCAPED_UNICODE);
    exit;
}

function link_has_column($table, $column)
{
    global $DB;
    $column = addslashes($column);
    $rows = $DB->get_results("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
    return !empty($rows);
}

function link_ensure_tables()
{
    global $DB;

    $chk = $DB->get_row("SHOW TABLES LIKE 'web_links'");
    if (empty($chk)) {
        $ok = $DB->query("CREATE TABLE `web_links` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(100) NOT NULL,
            `url` varchar(255) NOT NULL,
            `description` varchar(255) DEFAULT NULL,
            `icon` varchar(255) DEFAULT NULL,
            `category` varchar(50) DEFAULT NULL,
            `email` varchar(100) DEFAULT NULL,
            `status` int(11) NOT NULL DEFAULT 0,
            `ip` varchar(50) DEFAULT NULL,
            `addtime` int(11) NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8");
        if (!$ok) {
            link_json(0, '友链数据表创建失败，请联系管理员');
        }
        return;
    }

    if (!link_has_column('web_links', 'description')) {
        $DB->query("ALTER TABLE `web_links` ADD COLUMN `description` varchar(255) DEFAULT NULL");
    }
    if (!link_has_column('web_links', 'icon')) {
        $DB->query("ALTER TABLE `web_links` ADD COLUMN `icon` varchar(255) DEFAULT NULL");
    }
    if (!link_has_column('web_links', 'category')) {
        $DB->query("ALTER TABLE `web_links` ADD COLUMN `category` varchar(50) DEFAULT NULL");
    }
    if (!link_has_column('web_links', 'email')) {
        $DB->query("ALTER TABLE `web_links` ADD COLUMN `email` varchar(100) DEFAULT NULL");
    }
    if (!link_has_column('web_links', 'status')) {
        $DB->query("ALTER TABLE `web_links` ADD COLUMN `status` int(11) NOT NULL DEFAULT 0");
    }
    if (!link_has_column('web_links', 'ip')) {
        $DB->query("ALTER TABLE `web_links` ADD COLUMN `ip` varchar(50) DEFAULT NULL");
    }
    if (!link_has_column('web_links', 'addtime')) {
        $DB->query("ALTER TABLE `web_links` ADD COLUMN `addtime` int(11) NOT NULL DEFAULT 0");
    }
}

function link_strlen($value)
{
    return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
}

function link_escape($value)
{
    global $DB;
    return method_exists($DB, 'escape') ? $DB->escape($value) : addslashes($value);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    link_json(0, '请求方式错误');
}

link_ensure_tables();

$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$url = isset($_POST['url']) ? trim($_POST['url']) : '';
$desc = isset($_POST['desc']) ? trim($_POST['desc']) : '';
$cat = isset($_POST['cat']) ? trim($_POST['cat']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';

if ($name === '' || $url === '') {
    link_json(0, '请填写网站名称和URL');
}
if (link_strlen($name) > 100) {
    link_json(0, '网站名称不能超过100个字符');
}
if (link_strlen($desc) > 255) {
    link_json(0, '网站描述不能超过255个字符');
}
if (!preg_match('/^https?:\/\/[^\s]+$/i', $url) || !filter_var($url, FILTER_VALIDATE_URL)) {
    link_json(0, '请填写正确的网站URL，必须以 http:// 或 https:// 开头');
}
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    link_json(0, '请填写正确的通知邮箱');
}

$name_sql = link_escape($name);
$url_sql = link_escape($url);
$desc_sql = link_escape($desc);
$cat_sql = link_escape($cat);
$email_sql = link_escape($email);
$ip_sql = link_escape(real_ip());
$addtime = time();

$exists = $DB->get_row("SELECT id FROM `web_links` WHERE `url`='{$url_sql}' AND `status`=0 LIMIT 1");
if (!empty($exists)) {
    link_json(0, '该网站已提交过申请，请等待管理员审核');
}

$ok = $DB->query("INSERT INTO `web_links`
    (`name`, `url`, `description`, `icon`, `category`, `email`, `status`, `ip`, `addtime`)
    VALUES ('{$name_sql}', '{$url_sql}', '{$desc_sql}', '', '{$cat_sql}', '{$email_sql}', 0, '{$ip_sql}', '{$addtime}')");

if (!$ok) {
    link_json(0, '提交失败，请稍后再试');
}

link_json(1, '提交成功，请等待管理员审核');
