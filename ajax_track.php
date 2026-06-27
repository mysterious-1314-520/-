<?php
/* 祈福导航系统 V1.3 官方开源：https://gitee.com/qifuxitong/daohang */

include __DIR__ . "/includes/common.php";
header('Content-Type: application/json; charset=utf-8');

// 老版本升级上来时自动补齐站点总点击字段
$click_col = $DB->query("SHOW COLUMNS FROM web_dh LIKE 'clicks'");
if(!$DB->fetch($click_col)){
    $DB->query("ALTER TABLE web_dh ADD COLUMN clicks int(11) NOT NULL DEFAULT 0");
}

// 确保站点统计表存在
$chk = $DB->get_row("SHOW TABLES LIKE 'web_site_stats'");
if(empty($chk)){
    $DB->query("CREATE TABLE web_site_stats (
        id int(11) NOT NULL AUTO_INCREMENT,
        site_id int(11) NOT NULL,
        stat_date date NOT NULL,
        views int(11) NOT NULL DEFAULT 0,
        PRIMARY KEY (id),
        KEY site_date (site_id,stat_date),
        UNIQUE KEY site_date_unique (site_id,stat_date)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8");
}

$site_id = isset($_POST['site_id']) ? intval($_POST['site_id']) : 0;
$today = date('Y-m-d');

if($site_id > 0){
    $site = $DB->get_row("SELECT id FROM web_dh WHERE id='$site_id' LIMIT 1");
    if($site){
        $DB->query("INSERT INTO web_site_stats (site_id, stat_date, views) VALUES ('$site_id', '$today', 1)
                    ON DUPLICATE KEY UPDATE views = views + 1");
        $DB->query("UPDATE web_dh SET clicks=clicks+1 WHERE id='$site_id'");
        echo json_encode(['code'=>1]);
        exit;
    }
}

echo json_encode(['code'=>0]);
?>
