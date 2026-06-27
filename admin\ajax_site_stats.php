<?php
/* 祈福导航系统 V1.3 官方开源：https://gitee.com/qifuxitong/daohang */

include __DIR__ . "/../includes/common.php";
header('Content-Type: application/json; charset=utf-8');

$date = isset($_POST['date']) ? trim($_POST['date']) : '';
if(empty($date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)){
    echo json_encode([]);
    exit;
}

// 检查站点统计表是否存在
$chk = $DB->get_row("SHOW TABLES LIKE 'web_site_stats'");
if(empty($chk)){
    echo json_encode([]);
    exit;
}

// 查询该日期各站点点击量（按站点聚合）
$rows = $DB->get_results("
    SELECT w.id, w.name, w.url, w.category, s.views
    FROM web_site_stats s
    INNER JOIN web_dh w ON w.id = s.site_id
    WHERE s.stat_date = '$date' AND s.views > 0
    ORDER BY s.views DESC, w.id ASC
");

echo json_encode($rows ? $rows : [], JSON_UNESCAPED_UNICODE);
?>
