<?php
/* 祈福导航系统 V1.3 官方开源：https://gitee.com/qifuxitong/daohang */

error_reporting(0);
ini_set('display_errors', 0);

define('DH_JSON_RESPONSE', true);
ob_start();
include __DIR__ . "/includes/common.php";
include __DIR__ . "/includes/site_status.php";
ob_end_clean();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

function status_json($online, $http_code = 0, $latency = 0, $reason = '')
{
    echo json_encode(array(
        'code' => 1,
        'online' => $online ? 1 : 0,
        'http_code' => intval($http_code),
        'latency' => intval($latency),
        'reason' => $reason
    ), JSON_UNESCAPED_UNICODE);
    exit;
}

$url = isset($_GET['url']) ? trim($_GET['url']) : '';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

dh_site_status_ensure_columns();

if ($id > 0) {
    $row = $DB->get_row("SELECT id,url FROM `web_dh` WHERE id='{$id}' LIMIT 1");
    if (!$row) status_json(false);
    $result = dh_site_status_update_row($row['id'], $row['url']);
    status_json(!empty($result['online']), $result['http_code'], $result['latency'], $result['reason']);
}

$result = dh_site_status_check($url);
status_json(!empty($result['online']), $result['http_code'], $result['latency'], $result['reason']);
?>
