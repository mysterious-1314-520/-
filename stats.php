<?php
/* 祈福导航系统 V1.3 官方开源：https://gitee.com/qifuxitong/daohang */

// 璁垮缁熻杩借釜鑴氭湰 - 鍓嶅彴姣忎釜椤甸潰鍔犺浇鏃惰嚜鍔ㄨ皟鐢?
include __DIR__ . "/includes/common.php";

// 纭繚缁熻琛ㄥ瓨鍦?
$stats_chk = $DB->get_row("SHOW TABLES LIKE 'web_stats'");
if(empty($stats_chk)){
    $DB->query("CREATE TABLE web_stats (
        id int(11) NOT NULL AUTO_INCREMENT,
        stat_date date NOT NULL,
        views int(11) NOT NULL DEFAULT 0,
        unique_visitors int(11) NOT NULL DEFAULT 0,
        PRIMARY KEY (id),
        UNIQUE KEY stat_date (stat_date)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8");
}

// 鑾峰彇浠婃棩鏃ユ湡
$today = date('Y-m-d');

// 鑾峰彇璁垮鏍囪瘑锛堜娇鐢↖P鍝堝笇锛屽吋瀹笴DN锛?
$visitor_ip = real_ip();
$visitor_id = md5($visitor_ip . date('Y-m-d'));

// 妫€鏌ヤ粖鏃ヨ褰曟槸鍚﹀瓨鍦?
$row = $DB->get_row("SELECT * FROM web_stats WHERE stat_date='$today'");
if(empty($row)){
    $DB->query("INSERT INTO web_stats (stat_date,views,unique_visitors) VALUES ('$today',1,1)");
} else {
    $DB->query("UPDATE web_stats SET views=views+1 WHERE stat_date='$today'");
}

// 杈撳嚭1x1閫忔槑GIF锛堟爣鍑嗙殑Beacon鍝嶅簲锛?
header('Content-Type: image/gif');
header('Cache-Control: no-store, no-cache, must-revalidate');
echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
?>
