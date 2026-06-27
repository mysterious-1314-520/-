<?php
if(!defined('IN_CRONLITE')) exit();

function qifu_ad_escape($value){
    global $DB;
    return method_exists($DB, 'escape') ? $DB->escape($value) : addslashes((string)$value);
}

function qifu_ad_positions(){
    return array(
        'below_search' => '搜索栏下方四等分',
        'pc_right' => 'PC右侧悬浮',
        'pc_left' => 'PC左侧悬浮',
    );
}

function qifu_ad_modes(){
    return array(
        'fixed' => '按排序展示',
        'random' => '按权重随机',
        'rotate' => '按权重轮播',
    );
}

function qifu_ad_config_defaults(){
    return array(
        'ad_mode_below_search' => 'fixed',
        'ad_mode_pc_right' => 'fixed',
        'ad_mode_pc_left' => 'fixed',
        'ad_stat_enabled' => '1',
    );
}

function qifu_ad_ensure_tables(){
    global $DB;
    $DB->query("CREATE TABLE IF NOT EXISTS `web_ads` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `position` varchar(30) NOT NULL DEFAULT 'below_search',
        `slot` int(11) NOT NULL DEFAULT 1,
        `title` varchar(100) NOT NULL DEFAULT '',
        `image` varchar(255) NOT NULL DEFAULT '',
        `link` varchar(255) NOT NULL DEFAULT '',
        `alt` varchar(255) NOT NULL DEFAULT '',
        `active` tinyint(1) NOT NULL DEFAULT 1,
        `start_at` varchar(19) NOT NULL DEFAULT '',
        `end_at` varchar(19) NOT NULL DEFAULT '',
        `sort` int(11) NOT NULL DEFAULT 100,
        `weight` int(11) NOT NULL DEFAULT 1,
        `created_at` int(11) NOT NULL DEFAULT 0,
        `updated_at` int(11) NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`),
        KEY `position_slot` (`position`,`slot`),
        KEY `active_time` (`active`,`start_at`,`end_at`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4");

    $DB->query("CREATE TABLE IF NOT EXISTS `web_ad_stats` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `ad_id` int(11) NOT NULL,
        `stat_date` date NOT NULL,
        `views` int(11) NOT NULL DEFAULT 0,
        `clicks` int(11) NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`),
        KEY `ad_date` (`ad_id`,`stat_date`),
        UNIQUE KEY `ad_date_unique` (`ad_id`,`stat_date`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4");
}

function qifu_ad_ensure_config(){
    global $DB, $conf, $CACHE;
    $changed = false;
    foreach(qifu_ad_config_defaults() as $key => $value){
        if(!isset($conf[$key])){
            $DB->query("REPLACE INTO web_config SET k='".qifu_ad_escape($key)."',v='".qifu_ad_escape($value)."'");
            $conf[$key] = $value;
            $changed = true;
        }
    }
    if($changed && isset($CACHE)){
        $CACHE->clear();
        $conf = $CACHE->update();
    }
}

function qifu_ad_normalize_url($url){
    $url = trim((string)$url);
    if($url === '') return '';
    if(strpos($url, '//') === 0) return 'https:'.$url;
    if(preg_match('/^https?:\/\//i', $url)) return $url;
    if(preg_match('/^[a-z0-9.-]+\.[a-z]{2,}(\/.*)?$/i', $url)) return 'https://'.$url;
    return $url;
}

function qifu_ad_is_active($ad, $now = null){
    if($now === null) $now = date('Y-m-d H:i:s');
    if(empty($ad) || intval($ad['active']) !== 1) return false;
    if(!empty($ad['start_at']) && $ad['start_at'] > $now) return false;
    if(!empty($ad['end_at']) && $ad['end_at'] < $now) return false;
    return !empty($ad['image']);
}

function qifu_ad_status_text($ad){
    $now = date('Y-m-d H:i:s');
    if(intval($ad['active']) !== 1) return array('off', '已停用');
    if(!empty($ad['start_at']) && $ad['start_at'] > $now) return array('wait', '待上线');
    if(!empty($ad['end_at']) && $ad['end_at'] < $now) return array('end', '已下线');
    if(empty($ad['image'])) return array('bad', '缺少图片');
    return array('on', '投放中');
}

function qifu_ad_seed_legacy(){
    global $DB, $conf, $CACHE;
    qifu_ad_ensure_tables();
    if(isset($conf['ad_legacy_seeded']) && $conf['ad_legacy_seeded'] == '1') return;
    $count = intval($DB->count("SELECT COUNT(*) FROM web_ads"));
    if($count > 0){
        $DB->query("REPLACE INTO web_config SET k='ad_legacy_seeded',v='1'");
        $conf['ad_legacy_seeded'] = '1';
        if(isset($CACHE)) $CACHE->clear();
        return;
    }
    $now = time();
    $legacy = array();
    for($i=1; $i<=4; $i++){
        $suffix = $i == 1 ? '' : strval($i);
        if(!empty($conf['ad_image'.$suffix])){
            $legacy[] = array(
                'position' => 'below_search',
                'slot' => $i,
                'title' => isset($conf['ad_title'.$suffix]) ? $conf['ad_title'.$suffix] : '',
                'image' => $conf['ad_image'.$suffix],
                'link' => isset($conf['ad_link'.$suffix]) ? $conf['ad_link'.$suffix] : '',
                'alt' => isset($conf['ad_alt'.$suffix]) ? $conf['ad_alt'.$suffix] : '',
                'sort' => 100 + $i,
            );
        }
    }
    foreach(array('right' => 'pc_right', 'left' => 'pc_left') as $old => $position){
        if(!empty($conf['ad_'.$old.'_image'])){
            $legacy[] = array(
                'position' => $position,
                'slot' => 1,
                'title' => isset($conf['ad_'.$old.'_title']) ? $conf['ad_'.$old.'_title'] : '',
                'image' => $conf['ad_'.$old.'_image'],
                'link' => isset($conf['ad_'.$old.'_link']) ? $conf['ad_'.$old.'_link'] : '',
                'alt' => isset($conf['ad_'.$old.'_alt']) ? $conf['ad_'.$old.'_alt'] : '',
                'sort' => 100,
            );
        }
    }
    foreach($legacy as $ad){
        $DB->query("INSERT INTO web_ads (`position`,`slot`,`title`,`image`,`link`,`alt`,`active`,`sort`,`weight`,`created_at`,`updated_at`) VALUES (
            '".qifu_ad_escape($ad['position'])."',
            '".intval($ad['slot'])."',
            '".qifu_ad_escape($ad['title'])."',
            '".qifu_ad_escape($ad['image'])."',
            '".qifu_ad_escape($ad['link'])."',
            '".qifu_ad_escape($ad['alt'])."',
            1,
            '".intval($ad['sort'])."',
            1,
            '".$now."',
            '".$now."'
        )");
    }
    $DB->query("REPLACE INTO web_config SET k='ad_legacy_seeded',v='1'");
    $conf['ad_legacy_seeded'] = '1';
    if(isset($CACHE)) $CACHE->clear();
}

function qifu_ad_all(){
    global $DB;
    qifu_ad_ensure_tables();
    return $DB->get_results("SELECT a.*,COALESCE(SUM(s.views),0) AS views,COALESCE(SUM(s.clicks),0) AS clicks
        FROM web_ads a
        LEFT JOIN web_ad_stats s ON a.id=s.ad_id
        GROUP BY a.id
        ORDER BY a.position ASC,a.slot ASC,a.sort ASC,a.id ASC");
}

function qifu_ad_front_groups(){
    global $DB;
    qifu_ad_ensure_tables();
    $now = qifu_ad_escape(date('Y-m-d H:i:s'));
    $rows = $DB->get_results("SELECT * FROM web_ads
        WHERE active=1 AND image<>'' AND (start_at='' OR start_at<='$now') AND (end_at='' OR end_at>='$now')
        ORDER BY position ASC,slot ASC,sort ASC,id ASC");
    $groups = array(
        'below_search' => array(1 => array(), 2 => array(), 3 => array(), 4 => array()),
        'pc_right' => array(1 => array()),
        'pc_left' => array(1 => array()),
    );
    foreach($rows as $row){
        $position = isset($groups[$row['position']]) ? $row['position'] : 'below_search';
        $slot = $position == 'below_search' ? max(1, min(4, intval($row['slot']))) : 1;
        $groups[$position][$slot][] = $row;
    }
    return $groups;
}

function qifu_ad_pick($ads, $mode = 'fixed'){
    if(empty($ads)) return null;
    if($mode === 'random' || $mode === 'rotate'){
        $pool = array();
        foreach($ads as $ad){
            $weight = max(1, min(50, intval($ad['weight'])));
            for($i=0; $i<$weight; $i++) $pool[] = $ad;
        }
        if(empty($pool)) return $ads[0];
        if($mode === 'random') return $pool[array_rand($pool)];
        $idx = intval(floor(time() / 10)) % count($pool);
        return $pool[$idx];
    }
    return $ads[0];
}

function qifu_ad_track($ad_id, $field){
    global $DB;
    qifu_ad_ensure_tables();
    $ad_id = intval($ad_id);
    if($ad_id <= 0 || !in_array($field, array('views', 'clicks'))) return false;
    $ad = $DB->get_row("SELECT id FROM web_ads WHERE id='$ad_id' LIMIT 1");
    if(!$ad) return false;
    $today = date('Y-m-d');
    $DB->query("INSERT INTO web_ad_stats (ad_id, stat_date, `$field`) VALUES ('$ad_id', '$today', 1)
        ON DUPLICATE KEY UPDATE `$field`=`$field`+1");
    return true;
}

function qifu_ad_check_image($url){
    $url = trim((string)$url);
    if($url === '') return array(false, '未设置图片');
    if(preg_match('/^https?:\/\//i', $url)){
        if(function_exists('curl_init')){
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
            curl_setopt($ch, CURLOPT_TIMEOUT, 3);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_USERAGENT, 'QIFU-Ad-Checker/1.0');
            curl_exec($ch);
            $code = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE));
            $type = strtolower((string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE));
            $err = curl_error($ch);
            curl_close($ch);
            if($code >= 200 && $code < 400 && ($type === '' || strpos($type, 'image/') !== false)){
                return array(true, '图片正常');
            }
            return array(false, $code ? '图片异常 HTTP '.$code : ($err ?: '远程图片不可访问'));
        }
        return array(null, '服务器未开启 curl，无法自动检测远程图片');
    }
    $path = $url;
    if(strpos($path, '/') === 0) $path = ROOT.ltrim($path, '/');
    else $path = ROOT.ltrim($path, './');
    return is_file($path) ? array(true, '图片正常') : array(false, '本地图片不存在');
}
?>
