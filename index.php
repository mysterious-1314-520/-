<?php
/* 祈福导航系统 V1.3 官方开源：https://gitee.com/qifuxitong/daohang */
include __DIR__ . "/includes/common.php";
include __DIR__ . "/includes/txprotect.php";
include __DIR__ . "/includes/site_status.php";

// 兼容所有 MySQL 版本的字段升级
function ensure_column($DB, $table, $col, $definition) {
    $rs = $DB->query("SHOW COLUMNS FROM `{$table}` LIKE '{$col}'");
    if (!$DB->fetch($rs)) { $DB->query("ALTER TABLE `{$table}` ADD COLUMN `{$col}` {$definition}"); }
}
ensure_column($DB, "web_dh", "category",    "varchar(50)  NOT NULL DEFAULT '常用推荐'");
ensure_column($DB, "web_dh", "description", "varchar(255) NOT NULL DEFAULT ''");
ensure_column($DB, "web_dh", "desc_marquee", "tinyint(1) NOT NULL DEFAULT 0");
ensure_column($DB, "web_dh", "desc_speed",   "varchar(20) NOT NULL DEFAULT 'normal'");
ensure_column($DB, "web_dh", "desc_color",   "varchar(20) NOT NULL DEFAULT 'default'");
ensure_column($DB, "web_dh", "icon",        "varchar(20)  NOT NULL DEFAULT ''");
ensure_column($DB, "web_dh", "sort",        "int(11)      NOT NULL DEFAULT 100");
dh_site_status_ensure_columns();

// 从数据库读取分类设置，前台分类顺序和图标都以这里为准
$category_meta = [];
$cat_icons = [];
$cat_rs = $DB->query("SELECT id,name,icon,sort FROM web_category WHERE active=1 ORDER BY sort ASC,id ASC");
while ($cat_row = $DB->fetch($cat_rs)) {
    $category_meta[$cat_row['name']] = $cat_row;
    if (!empty($cat_row['icon'])) {
        $cat_icons[$cat_row['name']] = $cat_row['icon'];
    }
}

// 查询所有启用链接：优先按分类管理里的排序，再按站点排序
$rs = $DB->query("SELECT d.*,c.icon AS category_icon,c.sort AS category_sort,c.id AS category_id
    FROM web_dh d
    LEFT JOIN web_category c ON d.category=c.name
    WHERE d.active=1 AND (c.id IS NULL OR c.active=1)
    ORDER BY CASE WHEN c.sort IS NULL THEN 1 ELSE 0 END ASC,c.sort ASC,c.id ASC,d.category ASC,d.sort ASC,d.id ASC");
$sections = [];
while ($res = $DB->fetch($rs)) {
    $cat = $res['category'] ?: '其他';
    if (!isset($sections[$cat])) {
        $sections[$cat] = [];
        if (!empty($res['category_icon'])) {
            $cat_icons[$cat] = $res['category_icon'];
        } elseif (isset($category_meta[$cat]) && !empty($category_meta[$cat]['icon'])) {
            $cat_icons[$cat] = $category_meta[$cat]['icon'];
        }
    }
    $sections[$cat][] = $res;
}

// 获取分类列表（友链申请用）
$link_cats = $DB->get_results("SELECT name FROM web_category WHERE active=1 ORDER BY sort ASC");

// 背景模式
$bg_mode = isset($conf['bg_mode']) ? $conf['bg_mode'] : 'default';
$bg_custom = isset($conf['bg_custom']) ? $conf['bg_custom'] : '';
$default_bg = 'images/moren.jpg';
$default_bg_webp = 'images/moren.webp';

// UI设置
$card_size = isset($conf['card_size']) ? $conf['card_size'] : 'normal';
$columns = isset($conf['columns']) ? $conf['columns'] : 'auto';
$time_format = isset($conf['time_format']) ? $conf['time_format'] : '24';
$clock_style = isset($conf['clock_style']) ? $conf['clock_style'] : 'digital';
$announcement = isset($conf['announcement']) ? $conf['announcement'] : '';
$show_search = isset($conf['show_search']) ? $conf['show_search'] : '1';
$show_clock = isset($conf['show_clock']) ? $conf['show_clock'] : '1';
$show_tags = isset($conf['show_tags']) ? $conf['show_tags'] : '1';
$show_link_apply = isset($conf['show_link_apply']) ? $conf['show_link_apply'] : '1';
$bg_animation = isset($conf['bg_animation']) ? $conf['bg_animation'] : '1';
$card_animation = isset($conf['card_animation']) ? $conf['card_animation'] : '1';
$footer_opacity = isset($conf['footer_opacity']) ? intval($conf['footer_opacity']) : 25;
$footer_size = isset($conf['footer_size']) ? intval($conf['footer_size']) : 12;
$footer_opacity = max(5, min(100, $footer_opacity));
$footer_size = max(10, min(18, $footer_size));
$footer_alpha = round($footer_opacity / 100, 2);
$footer_link_alpha = min(1, round(($footer_opacity + 15) / 100, 2));

// 音乐设置
$bg_music = isset($conf['bg_music']) ? $conf['bg_music'] : '';
$bg_music_volume = isset($conf['bg_music_volume']) ? $conf['bg_music_volume'] : '50';

// Ping延迟设置
$ping_enabled = isset($conf['ping_enabled']) ? $conf['ping_enabled'] : '0';
$ping_alert_latency = isset($conf['ping_alert_latency']) ? intval($conf['ping_alert_latency']) : 3000;
$ping_alert_latency = max(500, min(30000, $ping_alert_latency));
$ping_last_run = isset($conf['ping_last_run']) ? $conf['ping_last_run'] : '';
$ping_need_refresh = $ping_enabled == '1' && $ping_last_run !== date('Y-m-d');

function qifu_front_legacy_side_ad($conf, $side) {
    $old = $side === 'right' ? 'right' : 'left';
    $image_key = 'ad_'.$old.'_image';
    if (empty($conf[$image_key])) return null;
    return array(
        'id' => 0,
        'position' => $side === 'right' ? 'pc_right' : 'pc_left',
        'slot' => 1,
        'title' => isset($conf['ad_'.$old.'_title']) ? $conf['ad_'.$old.'_title'] : '',
        'image' => $conf[$image_key],
        'link' => isset($conf['ad_'.$old.'_link']) ? $conf['ad_'.$old.'_link'] : '',
        'alt' => isset($conf['ad_'.$old.'_alt']) ? $conf['ad_'.$old.'_alt'] : '',
    );
}

// 广告设置
$ad_enabled = isset($conf['ad_enabled']) ? $conf['ad_enabled'] : '0';
$ad_show_below = isset($conf['ad_show_below']) ? $conf['ad_show_below'] : '1';
$ad_show_right = isset($conf['ad_show_right']) ? $conf['ad_show_right'] : '0';
$ad_show_left = isset($conf['ad_show_left']) ? $conf['ad_show_left'] : '0';
$ad_new_window = isset($conf['ad_new_window']) ? $conf['ad_new_window'] : '1';
$ad_target = $ad_new_window == '1' ? ' target="_blank" rel="noopener"' : '';
$ad_groups = qifu_ad_front_groups();
$ad_below_mode = isset($conf['ad_mode_below_search']) ? $conf['ad_mode_below_search'] : 'fixed';
$ad_right_mode = isset($conf['ad_mode_pc_right']) ? $conf['ad_mode_pc_right'] : 'fixed';
$ad_left_mode = isset($conf['ad_mode_pc_left']) ? $conf['ad_mode_pc_left'] : 'fixed';
$ad_below_items = array();
$ad_below_has_items = false;
for($i=1; $i<=4; $i++){
    $picked_ad = qifu_ad_pick($ad_groups['below_search'][$i], $ad_below_mode);
    if(!empty($picked_ad)) $ad_below_has_items = true;
    $ad_below_items[] = $picked_ad;
}
$ad_right = qifu_ad_pick($ad_groups['pc_right'][1], $ad_right_mode);
$ad_left = qifu_ad_pick($ad_groups['pc_left'][1], $ad_left_mode);
if(empty($ad_right)) $ad_right = qifu_front_legacy_side_ad($conf, 'right');
if(empty($ad_left)) $ad_left = qifu_front_legacy_side_ad($conf, 'left');
$ad_below_show = $ad_enabled == '1' && $ad_show_below == '1' && $ad_below_has_items;
$ad_right_show = $ad_enabled == '1' && $ad_show_right == '1' && !empty($ad_right);
$ad_left_show = $ad_enabled == '1' && $ad_show_left == '1' && !empty($ad_left);

// 卡片尺寸CSS
$card_size_css = [
    'small' => 'padding:10px 12px;gap:8px;',
    'normal' => 'padding:15px 17px;gap:14px;',
    'large' => 'padding:20px 22px;gap:18px;'
];
$card_size_css_default = isset($card_size_css[$card_size]) ? $card_size_css[$card_size] : $card_size_css['normal'];

// 网格列数CSS
$columns_css = [
    '2' => 'repeat(2, 1fr)',
    '3' => 'repeat(3, 1fr)',
    '4' => 'repeat(4, 1fr)',
    'auto' => 'repeat(auto-fill, minmax(200px, 1fr))'
];
$columns_css_default = isset($columns_css[$columns]) ? $columns_css[$columns] : $columns_css['auto'];

function qifu_http_get($url, $timeout = 6) {
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
        $body = curl_exec($ch);
        $code = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE));
        curl_close($ch);
        if ($body !== false && ($code == 0 || ($code >= 200 && $code < 400))) {
            return $body;
        }
    }

    if (ini_get('allow_url_fopen')) {
        $ctx = stream_context_create([
            'http' => [
                'timeout' => $timeout,
                'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36\r\n"
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ]);
        $body = @file_get_contents($url, false, $ctx);
        if ($body !== false) {
            return $body;
        }
    }

    return '';
}

function qifu_url_add_param($url, $key, $value) {
    $join = strpos($url, '?') === false ? '?' : '&';
    return $url . $join . rawurlencode($key) . '=' . rawurlencode($value);
}

function qifu_full_bing_url($url) {
    if (!$url) {
        return '';
    }
    if (strpos($url, '//') === 0) {
        return 'https:' . $url;
    }
    if (strpos($url, '/') === 0) {
        return 'https://www.bing.com' . $url;
    }
    return $url;
}

function qifu_today_bing_wallpaper() {
    $today = date('Ymd');
    $api = 'https://www.bing.com/HPImageArchive.aspx?format=js&idx=0&n=1&mkt=zh-CN&uhd=1&uhdwidth=1920&uhdheight=1080';
    $json = qifu_http_get(qifu_url_add_param($api, 'qifu_day', $today));
    $bing_url = '';

    if ($json) {
        $data = json_decode($json, true);
        if (!empty($data['images'][0]['url'])) {
            $bing_url = $data['images'][0]['url'];
        } elseif (!empty($data['images'][0]['urlbase'])) {
            $bing_url = $data['images'][0]['urlbase'] . '_1920x1080.jpg';
        }
    }

    if (!$bing_url) {
        $xml_api = 'https://www.bing.com/HPImageArchive.aspx?idx=0&n=1&mkt=zh-CN';
        $xml = qifu_http_get(qifu_url_add_param($xml_api, 'qifu_day', $today));
        if ($xml && preg_match('/<url>(.*?)<\/url>/', $xml, $m)) {
            $bing_url = html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
        } elseif ($xml && preg_match('/<urlBase>(.*?)<\/urlBase>/', $xml, $m)) {
            $bing_url = html_entity_decode($m[1], ENT_QUOTES, 'UTF-8') . '_1920x1080.jpg';
        }
    }

    $bing_url = qifu_full_bing_url($bing_url);
    return $bing_url ? qifu_url_add_param($bing_url, 'qifu_bing', $today) : '';
}

if ($bg_mode == 'bing') {
    $bing_url = qifu_today_bing_wallpaper();
    $bing_url = str_replace("'", "%27", $bing_url);
    $bg_style = $bing_url ? "background:url('{$bing_url}') center/cover no-repeat;" : "background-image:url('{$default_bg}');background-image:image-set(url('{$default_bg_webp}') type('image/webp'), url('{$default_bg}') type('image/jpeg'));background-position:center;background-size:cover;background-repeat:no-repeat;";
} elseif ($bg_mode == 'custom' && $bg_custom) {
    $bg_style = "background:url('{$bg_custom}') center/cover no-repeat;";
} else {
    $bg_style = "background-image:url('{$default_bg}');background-image:image-set(url('{$default_bg_webp}') type('image/webp'), url('{$default_bg}') type('image/jpeg'));background-position:center;background-size:cover;background-repeat:no-repeat;";
}

$site_title = !empty($conf['sitename']) ? $conf['sitename'] : '祈福导航系统';
$page_title = $site_title.(!empty($conf['title']) ? ' - '.$conf['title'] : '');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+SC:wght@300;400;500&display=swap" rel="stylesheet">
<title><?php echo htmlspecialchars($page_title); ?></title>
<meta name="keywords"    content="<?php echo htmlspecialchars($conf['keywords']); ?>">
<meta name="description" content="<?php echo htmlspecialchars($conf['description']); ?>">
<link rel="icon" type="image/svg+xml" href="favicon.svg">
<link rel="alternate icon" href="favicon.ico">
<?php if($bg_mode=='default'){ ?>
<link rel="preload" as="image" href="<?php echo htmlspecialchars($default_bg_webp); ?>" type="image/webp" fetchpriority="high">
<?php } ?>
<style>
*{box-sizing:border-box;margin:0;padding:0}
html{-webkit-text-size-adjust:100%;text-size-adjust:100%}
body{font-family:'Noto Sans SC',sans-serif;min-height:100vh;overflow-x:hidden;}
.bg{position:fixed;inset:0;z-index:0;<?php echo $bg_style; ?><?php echo $bg_animation=='1'?"animation:drift 30s ease-in-out infinite alternate;":""; ?>}
@keyframes drift{0%{transform:scale(1.06) translate(0,0)}100%{transform:scale(1.1) translate(-1.5%,-1%)}}
.overlay{position:fixed;inset:0;z-index:1;background:linear-gradient(145deg,rgba(8,18,50,.52),rgba(12,25,65,.4) 50%,rgba(5,12,40,.56));}
.wrap{position:relative;z-index:10;max-width:1020px;margin:0 auto;padding:0 24px 64px}
.bar{display:flex;align-items:center;justify-content:flex-end;padding:10px 0 0}
.logo{font-size:1.4rem;font-weight:300;color:#e8eeff;letter-spacing:.06em;text-shadow:0 2px 20px rgba(0,0,0,.4)}
.logo b{font-weight:600;color:rgba(160,200,255,.95)}
.bar-actions{display:flex;gap:10px;align-items:center}
.bar-btn{padding:8px 18px;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.18);border-radius:50px;color:rgba(255,255,255,.82);font-size:.78rem;cursor:pointer;transition:.25s;text-decoration:none;font-weight:500}
.bar-btn:hover{background:rgba(255,255,255,.18);border-color:rgba(255,255,255,.32);box-shadow:0 4px 24px rgba(0,0,0,.2)}
.hero{text-align:center;padding:18px 0 30px}
#clock{font-size:clamp(3.4rem,13vw,6.5rem);font-weight:200;letter-spacing:.06em;color:#fff;line-height:1;text-shadow:0 4px 40px rgba(0,0,0,.5)}
#date{font-size:.9rem;color:rgba(255,255,255,.5);letter-spacing:.22em;margin-top:8px;margin-bottom:24px;font-weight:400}
.search{display:flex;max-width:660px;margin:0 auto;background:rgba(0,0,0,.3);border:1px solid rgba(255,255,255,.22);border-radius:50px;overflow:visible;box-shadow:0 16px 56px rgba(0,0,0,.3),inset 0 1px 0 rgba(255,255,255,.1);transition:.3s}
.search:focus-within{background:rgba(0,0,0,.4);border-color:rgba(255,255,255,.38);box-shadow:0 20px 60px rgba(0,0,0,.4),inset 0 1px 0 rgba(255,255,255,.15)}
.eng{position:relative;display:flex;align-items:center;padding:0 8px;border-right:1px solid rgba(255,255,255,.16);flex-shrink:0}
.engine-current{min-width:86px;height:42px;border:0;border-radius:999px;background:transparent;color:rgba(255,255,255,.86);display:flex;align-items:center;gap:8px;justify-content:center;padding:0 10px;font-family:inherit;font-size:.85rem;font-weight:500;cursor:pointer;box-shadow:none;transition:.22s}
.engine-current:hover,.engine-picker.open .engine-current{background:rgba(255,255,255,.08);box-shadow:none;color:#fff}
.engine-current .engine-badge{display:none}
.engine-badge{width:22px;height:22px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;background:rgba(255,255,255,.18);font-size:.72rem;font-weight:700;line-height:1}
.engine-arrow{width:7px;height:7px;border-right:1.5px solid rgba(255,255,255,.75);border-bottom:1.5px solid rgba(255,255,255,.75);transform:rotate(45deg);margin-top:-4px;transition:.2s}
.engine-picker.open .engine-arrow{transform:rotate(225deg);margin-top:4px}
.engine-menu{position:absolute;left:8px;top:calc(100% + 10px);width:156px;padding:8px;border-radius:16px;background:rgba(10,16,45,.92);border:1px solid rgba(255,255,255,.18);box-shadow:0 18px 55px rgba(0,0,0,.45),inset 0 1px 0 rgba(255,255,255,.08);backdrop-filter:blur(16px);opacity:0;transform:translateY(-8px) scale(.98);pointer-events:none;transition:.2s;z-index:30}
.engine-picker.open .engine-menu{opacity:1;transform:translateY(0) scale(1);pointer-events:auto}
.engine-option{width:100%;border:0;background:transparent;color:rgba(255,255,255,.78);display:flex;align-items:center;gap:10px;padding:10px 11px;border-radius:11px;font-family:inherit;font-size:.86rem;cursor:pointer;text-align:left;transition:.18s}
.engine-option:hover,.engine-option.active{background:rgba(255,255,255,.13);color:#fff}
.engine-option .engine-badge{background:rgba(99,102,241,.28)}
.sinp{flex:1;background:transparent;border:0;outline:0;padding:15px 20px;color:#fff;font-size:.95rem;font-family:inherit;min-width:0;font-weight:400}
.sinp::placeholder{color:rgba(255,255,255,.4)}
.sbtn{padding:0 28px;background:rgba(255,255,255,.15);border:0;border-left:1px solid rgba(255,255,255,.16);color:#fff;font-size:1.3rem;cursor:pointer;transition:.3s;height:100%;display:flex;align-items:center;border-radius:0 50px 50px 0;line-height:1}
.sbtn:hover{background:rgba(255,255,255,.28)}
.tags{display:flex;justify-content:center;flex-wrap:wrap;gap:10px;margin-top:12px}
.tag{padding:7px 18px;background:rgba(0,0,0,.3);border:1px solid rgba(255,255,255,.16);border-radius:50px;font-size:.75rem;color:rgba(255,255,255,.7);cursor:pointer;text-decoration:none;transition:.25s;font-weight:500}
.tag:hover{background:rgba(0,0,0,.5);border-color:rgba(255,255,255,.3);color:#fff;box-shadow:0 6px 24px rgba(0,0,0,.3)}
.ad-link{display:block;text-decoration:none;color:inherit}
.ad-img{display:block;width:100%;height:auto;object-fit:cover}
.ad-grid{max-width:900px;margin:16px auto 0;display:grid;grid-template-columns:repeat(2,1fr);gap:10px}
.ad-cell{height:92px;display:flex;align-items:center;justify-content:center;overflow:hidden;background:rgba(0,0,0,.16);border:1px solid rgba(255,255,255,.24);border-radius:18px;box-shadow:0 14px 42px rgba(0,0,0,.2),inset 0 1px 0 rgba(255,255,255,.12)}
.ad-cell-empty{display:block;pointer-events:none}
.ad-banner{width:100%;height:100%;overflow:hidden;transition:.25s;border-radius:18px}
.ad-banner:hover{transform:scale(1.015);filter:brightness(1.08)}
.ad-banner .ad-img{height:100%}
.ad-side{position:fixed;top:50%;z-index:120;width:168px;border-radius:18px;overflow:hidden;background:rgba(0,0,0,.3);border:1px solid rgba(255,255,255,.18);box-shadow:0 16px 48px rgba(0,0,0,.32),inset 0 1px 0 rgba(255,255,255,.12);transition:.25s;transform:translateY(-50%)}
.ad-side-right{right:28px}
.ad-side-left{left:28px}
.ad-side:hover{transform:translateY(calc(-50% - 3px));border-color:rgba(255,255,255,.32);box-shadow:0 22px 58px rgba(0,0,0,.4),inset 0 1px 0 rgba(255,255,255,.18)}
.ad-side .ad-img{aspect-ratio:3/4;max-height:260px}
.sec{margin-top:26px}
.sec-hd{display:flex;align-items:center;gap:10px;margin-bottom:16px}
.dot{width:9px;height:9px;border-radius:50%;background:rgba(140,190,255,.9);box-shadow:0 0 16px rgba(100,160,255,.5),0 0 32px rgba(100,160,255,.2);flex-shrink:0;animation:pulse 3s ease-in-out infinite}
@keyframes pulse{0%,100%{box-shadow:0 0 16px rgba(100,160,255,.5),0 0 32px rgba(100,160,255,.2)}50%{box-shadow:0 0 22px rgba(100,160,255,.7),0 0 44px rgba(100,160,255,.35)}}
.sec-title{font-size:.92rem;font-weight:600;color:rgba(255,255,255,.92);letter-spacing:.1em}
.sec-line{flex:1;height:1px;background:linear-gradient(to right,rgba(255,255,255,.15),transparent)}
.grid{display:grid;grid-template-columns:<?php echo $columns_css_default; ?>;gap:14px}
.card{display:flex;align-items:center;<?php echo $card_size_css_default; ?>background:rgba(0,0,0,.3);border:1px solid rgba(255,255,255,.18);border-radius:18px;text-decoration:none;color:#fff;cursor:pointer;position:relative;overflow:hidden;<?php echo $card_animation=='1'?"transition:all .3s cubic-bezier(.4,0,.2,1);":""; ?>box-shadow:0 10px 40px rgba(0,0,0,.2),inset 0 1px 0 rgba(255,255,255,.12)}
.card::before{content:'';position:absolute;inset:0;background:linear-gradient(135deg,rgba(255,255,255,.08),transparent 55%);opacity:1;transition:.25s;border-radius:18px;pointer-events:none}
.card:hover{background:rgba(0,0,0,.5);border-color:rgba(255,255,255,.3);transform:translateY(-5px);box-shadow:0 20px 56px rgba(0,0,0,.35),inset 0 1px 0 rgba(255,255,255,.2)}
.card:hover::before{opacity:.5}
.ico{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.25rem;flex-shrink:0;overflow:hidden}
.ico img{width:100%;height:100%;object-fit:contain;border-radius:8px}
.inf{overflow:hidden;flex:1;min-width:0}
.nm{font-size:1rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;color:#fff}
.ds{font-size:13px;color:rgba(255,255,255,.45);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:3px;font-weight:400;line-height:18px;height:18px;max-width:100%;-webkit-text-size-adjust:100%;text-size-adjust:100%}
.ds .ds-text{display:inline-block;max-width:100%;vertical-align:top;font-size:inherit;line-height:inherit}
.ds:not(.marquee) .ds-text{display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.ds.marquee{text-overflow:clip}
.ds.marquee .ds-text{max-width:none;min-width:100%;padding-left:100%;animation:descMarquee var(--desc-speed,10s) linear infinite}
.card:hover .ds.marquee .ds-text{animation-play-state:paused}
@keyframes descMarquee{0%{transform:translateX(0)}100%{transform:translateX(-100%)}}
.ds.desc-color-red{color:#ff6b6b}.ds.desc-color-orange{color:#ff9f43}.ds.desc-color-yellow{color:#ffd166}.ds.desc-color-green{color:#62f29a}.ds.desc-color-cyan{color:#5ee6ff}.ds.desc-color-blue{color:#8ab4ff}.ds.desc-color-purple{color:#c4a7ff}
.ds.desc-color-rainbow{color:#fff;text-shadow:none}.ds.desc-color-rainbow .ds-text{background:linear-gradient(90deg,#ff5f6d,#ffc371,#5df1b0,#58d8ff,#8ab4ff,#c084fc,#ff7a9e);-webkit-background-clip:text;background-clip:text;color:transparent}
.foot{text-align:center;padding:52px 0 20px;font-size:<?php echo $footer_size; ?>px;color:rgba(255,255,255,<?php echo $footer_alpha; ?>);letter-spacing:.12em;font-weight:400}
.foot a{color:rgba(255,255,255,<?php echo $footer_link_alpha; ?>)!important;text-decoration:none}
#clock.simple{font-size:clamp(2rem,10vw,4rem);font-weight:300;letter-spacing:.08em}
.music-btn{position:fixed;bottom:28px;left:28px;z-index:100;width:46px;height:46px;border-radius:50%;background:rgba(0,0,0,.3);border:1px solid rgba(255,255,255,.2);color:#fff;font-size:1rem;cursor:pointer;transition:.25s;display:flex;align-items:center;justify-content:center;box-shadow:0 8px 30px rgba(0,0,0,.25)}
.music-btn:hover{background:rgba(255,255,255,.22);transform:scale(1.12)}
.music-panel{position:fixed;bottom:82px;left:28px;z-index:100;background:rgba(0,0,0,.6);backdrop-filter:blur(12px);border:1px solid rgba(255,255,255,.15);border-radius:16px;padding:16px 20px;display:none;min-width:200px}
.music-panel.active{display:block}
.music-panel .vol{display:flex;align-items:center;gap:10px;margin-top:8px}
.music-panel .vol input{flex:1;accent-color:#8ab4ff}
.music-panel .vol span{font-size:.7rem;color:rgba(255,255,255,.6);min-width:30px}
.ping-badge{position:absolute;top:10px;right:10px;z-index:2;display:block;width:11px;height:11px;padding:0;border-radius:50%;border:1px solid rgba(255,255,255,.72);background:rgba(148,163,184,.88);box-shadow:0 0 0 3px rgba(148,163,184,.16),0 0 14px rgba(148,163,184,.45)}
.ping-badge.checking{display:block;animation:pingBlink 1.1s ease-in-out infinite}
.ping-badge.online{display:block;background:#22c55e;box-shadow:0 0 0 3px rgba(34,197,94,.18),0 0 16px rgba(34,197,94,.78)}
.ping-badge.offline{display:block;background:#ef4444;box-shadow:0 0 0 3px rgba(239,68,68,.18),0 0 16px rgba(239,68,68,.76)}
@keyframes pingBlink{0%,100%{opacity:.52}50%{opacity:1}}
@media(max-width:768px){
  .wrap{padding:0 16px 48px}
  .hero{padding:12px 0 22px}
  .bar{padding:8px 0 0}
  .bar-btn{padding:7px 14px;font-size:.74rem}
  .search{max-width:100%}
  .sinp{padding:14px 14px;font-size:.9rem}
  .sbtn{padding:0 20px;font-size:1.15rem}
  .tags{gap:8px;margin-top:10px}
  .tag{padding:6px 14px;font-size:.72rem}
  .ad-grid{margin-top:12px;gap:8px}
  .ad-cell,.ad-banner{height:72px;border-radius:14px}
}
@media(max-width:600px){
  .grid{grid-template-columns:repeat(2,1fr);gap:10px}
  .card{padding:12px 13px;gap:10px}
  .ico{width:36px;height:36px;font-size:1.1rem}
  .nm{font-size:.82rem}.ds{font-size:11px;line-height:15px;height:15px}
  #clock{font-size:3.5rem}
  .bar{align-items:flex-start;gap:12px}
  .bar-actions{flex-wrap:wrap;justify-content:flex-end}
  .bar-btn{font-size:.72rem;padding:6px 12px}
  .ping-badge{top:7px;right:7px;width:8px;height:8px;box-shadow:0 0 0 2px rgba(148,163,184,.16),0 0 10px rgba(148,163,184,.42)}
  .ping-badge.online{box-shadow:0 0 0 2px rgba(34,197,94,.18),0 0 12px rgba(34,197,94,.75)}
  .ping-badge.offline{box-shadow:0 0 0 2px rgba(239,68,68,.18),0 0 12px rgba(239,68,68,.72)}
  .ad-grid{margin-top:12px;gap:8px}
  .ad-cell,.ad-banner{height:78px;border-radius:14px}
  .hero{padding:8px 0 18px}
  .wrap{padding:0 12px 40px}
  #date{font-size:.8rem;margin-bottom:18px}
  .sec{margin-top:20px}
  .sec-hd{margin-bottom:12px}
  .sec-title{font-size:.82rem}
  .foot{font-size:11px;padding:36px 0 16px}
  .lkm-box{width:96%;max-width:none;border-radius:16px}
  .lkm-hd{padding:16px 18px}
  .lkm-hd h3{font-size:16px}
  .lkm-bd{padding:18px}
  .lkm-row input,.lkm-row select{padding:12px 14px;font-size:14px}
}
@media(max-width:480px){
  html{font-size:15px}
  body{min-height:100vh;min-height:100dvh}
  .wrap{padding:0 10px 36px}
  .hero{padding:6px 0 14px}
  #clock{font-size:2.8rem}
  #date{font-size:.75rem;margin-bottom:14px}
  .search{border-radius:40px}
  .eng{padding:0 6px}
  .engine-current{min-width:60px;height:38px;padding:0 8px;font-size:.78rem;gap:4px}
  .sinp{padding:12px 10px;font-size:.85rem}
  .sbtn{padding:0 16px;font-size:1.05rem}
  .grid{grid-template-columns:repeat(2,1fr);gap:8px}
  .card{padding:10px 11px;gap:8px;border-radius:14px}
  .ico{width:32px;height:32px;font-size:1rem;border-radius:10px}
  .nm{font-size:.78rem}
  .ds{font-size:10px;line-height:14px;height:14px}
  .ad-grid{grid-template-columns:1fr;max-width:100%}
  .ad-cell,.ad-banner{height:68px;border-radius:12px}
  .sec{margin-top:16px}
  .sec-hd{gap:8px;margin-bottom:10px}
  .sec-title{font-size:.78rem}
  .dot{width:7px;height:7px}
  .tag{padding:5px 11px;font-size:.68rem}
  .tags{gap:6px;margin-top:8px}
  .bar{gap:8px;padding:6px 0 0}
  .bar-btn{padding:6px 10px;font-size:.68rem}
  .foot{font-size:10px;padding:30px 0 14px}
  .music-btn{bottom:20px;left:16px;width:40px;height:40px;font-size:.9rem}
  .music-panel{bottom:72px;left:16px;min-width:180px;padding:14px 16px;border-radius:14px}
  .lkm-box{border-radius:14px}
  .lkm-hd{padding:14px 16px}
  .lkm-bd{padding:14px}
  .lkm-hd h3{font-size:15px}
  .lkm-submit{font-size:15px;padding:14px}
  .lkm-close{font-size:24px;width:28px;height:28px}
  .ping-badge{top:5px;right:5px;width:7px;height:7px}
}
@media(max-width:380px){
  .grid{grid-template-columns:1fr}
  .card{padding:10px 12px}
  .ico{width:34px;height:34px}
  .nm{font-size:.8rem}
  .search{border-radius:36px}
  .engine-current{min-width:0;width:auto;padding:0 6px;font-size:.72rem}
  .engine-arrow{width:6px;height:6px}
  .sinp{padding:11px 8px;font-size:.8rem}
  .sbtn{padding:0 14px;font-size:.95rem}
  #clock{font-size:2.2rem}
  .wrap{padding:0 8px 30px}
  .ad-cell,.ad-banner{height:60px}
}
@media(hover:none) and (pointer:coarse){
  .card:hover{transform:none;box-shadow:0 10px 40px rgba(0,0,0,.2),inset 0 1px 0 rgba(255,255,255,.12)}
  .card:active{background:rgba(0,0,0,.5);border-color:rgba(255,255,255,.3);transform:scale(.97)}
  .bar-btn:hover{background:rgba(255,255,255,.1);border-color:rgba(255,255,255,.18)}
  .bar-btn:active{background:rgba(255,255,255,.2)}
  .sbtn:hover{background:rgba(255,255,255,.15)}
  .sbtn:active{background:rgba(255,255,255,.32)}
  .tag:hover{background:rgba(0,0,0,.3);border-color:rgba(255,255,255,.16);color:rgba(255,255,255,.7)}
  .tag:active{background:rgba(0,0,0,.5);border-color:rgba(255,255,255,.3)}
}
@media(max-width:1500px){.ad-side{width:136px}.ad-side-right{right:16px}.ad-side-left{left:16px}}
@media(max-width:1280px){.ad-side{width:92px;border-radius:14px}.ad-side-right{right:10px}.ad-side-left{left:10px}.ad-side .ad-img{max-height:220px}}
@media(max-width:980px){.ad-side{display:none}}

/* 平板横屏 & 小屏笔记本 (769-1199px) */
@media(min-width:769px) and (max-width:1199px){
  .wrap{max-width:960px;padding:0 20px 56px}
  .hero{padding:20px 0 26px}
  .search{max-width:620px}
  .sinp{padding:15px 18px}
  .grid{gap:12px}
  .sec{margin-top:24px}
  .ad-grid{max-width:820px}
}
/* 横屏优化 */
@media(orientation:landscape) and (max-height:600px){
  .hero{padding:4px 0 10px}
  #clock{font-size:2.5rem}
  #date{margin-top:4px;margin-bottom:10px}
  .search{max-width:560px}
  .sinp{padding:10px 14px;font-size:.82rem}
  .sbtn{padding:0 16px}
  .engine-current{height:34px;font-size:.78rem}
  .tags{margin-top:6px;gap:6px}
  .foot{padding:20px 0 10px}
}
/* Retina 高清屏微调 */
@media(-webkit-min-device-pixel-ratio:2),(min-resolution:192dpi){
  .card{box-shadow:0 10px 40px rgba(0,0,0,.15),inset 0 1px 0 rgba(255,255,255,.08)}
  .card:hover{box-shadow:0 20px 56px rgba(0,0,0,.25),inset 0 1px 0 rgba(255,255,255,.15)}
}
/* iPhone 安全区域适配 */
@supports(padding:max(0px)){
  .wrap{padding-left:max(24px, env(safe-area-inset-left));padding-right:max(24px, env(safe-area-inset-right))}
  .music-btn{left:max(28px, env(safe-area-inset-left))}
  .music-panel{left:max(28px, env(safe-area-inset-left))}
  .foot{padding-bottom:max(20px, env(safe-area-inset-bottom))}
}

/* PC端大屏适配 */
@media(min-width:1200px){
  .wrap{max-width:1140px}
  .search{max-width:720px}
  .sinp{padding:16px 22px;font-size:1rem}
  .sbtn{padding:0 32px}
  .engine-current{height:46px;font-size:.9rem}
  .grid{grid-template-columns:<?php echo $columns=='auto'?'repeat(auto-fill, minmax(220px, 1fr))':$columns_css_default; ?>}
}
@media(min-width:1400px){
  html{font-size:17pt}
  .wrap{max-width:1280px;padding:0 32px 72px}
  .search{max-width:800px}
  .hero{padding:30px 0 44px}
  #clock{font-size:clamp(4rem,10vw,7.5rem)}
  #date{font-size:1rem;margin-bottom:30px}
  .grid{grid-template-columns:<?php echo $columns=='auto'?'repeat(auto-fill, minmax(240px, 1fr))':$columns_css_default; ?>;gap:18px}
  .card{padding:18px 20px;gap:16px}
  .ico{width:48px;height:48px;font-size:1.3rem}
  .nm{font-size:1.05rem}
  .ds{font-size:14px;line-height:20px;height:20px}
  .bar-btn{padding:10px 22px;font-size:.82rem}
  .tag{padding:8px 20px;font-size:.8rem}
  .ad-grid{max-width:1024px;gap:14px}
  .ad-cell,.ad-banner{height:102px;border-radius:20px}
  .sec{margin-top:36px}
  .sec-hd{margin-bottom:20px}
  .sec-title{font-size:1rem}
  .foot{font-size:14px;padding:64px 0 28px}
}
@media(min-width:1800px){
  .wrap{max-width:1440px;padding:0 48px 88px}
  .search{max-width:880px}
  .hero{padding:40px 0 56px}
  .grid{grid-template-columns:<?php echo $columns=='auto'?'repeat(auto-fill, minmax(260px, 1fr))':$columns_css_default; ?>;gap:20px}
  .card{padding:20px 24px;gap:18px}
  .ico{width:52px;height:52px;border-radius:14px}
  .nm{font-size:1.1rem}
  .ad-grid{max-width:1140px;gap:16px}
  .ad-cell,.ad-banner{height:114px;border-radius:22px}
  .sec{margin-top:44px}
  .sec-hd{margin-bottom:24px}
  .foot{font-size:15px}
  .ad-side{width:180px}.ad-side-right{right:32px}.ad-side-left{left:32px}
}

/* 友联弹窗 - 完全自实现，不依赖任何外部库 */
#lkm-wrap{position:fixed;top:0;left:0;right:0;bottom:0;z-index:99999;display:none;align-items:center;justify-content:center;background:rgba(0,0,0,.8);backdrop-filter:blur(6px)}
#lkm-wrap.open{display:flex;animation:lkmFadeIn .2s ease}
@keyframes lkmFadeIn{from{opacity:0}to{opacity:1}}
.lkm-box{background:linear-gradient(145deg,#111827f0,#1e293bf0);border:1px solid rgba(255,255,255,.2);border-radius:20px;width:94%;max-width:440px;box-shadow:0 30px 90px rgba(0,0,0,.7);overflow:hidden;animation:lkmScaleIn .28s cubic-bezier(.2,.8,.4,1.4)}
@keyframes lkmScaleIn{from{opacity:0;transform:scale(.8) translateY(-20px)}to{opacity:1;transform:scale(1) translateY(0)}}
.lkm-hd{display:flex;align-items:center;justify-content:space-between;padding:20px 24px;border-bottom:1px solid rgba(255,255,255,.1)}
.lkm-hd h3{color:#fff;font-size:18px;font-weight:600;margin:0;letter-spacing:.05em}
.lkm-close{background:none;border:none;color:rgba(255,255,255,.55);font-size:28px;cursor:pointer;line-height:1;padding:0;width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;transition:.2s}
.lkm-close:hover{background:rgba(255,255,255,.1);color:#fff}
.lkm-bd{padding:24px}
.lkm-row{margin-bottom:16px}
.lkm-row label{color:rgba(255,255,255,.8);font-size:14px;display:block;margin-bottom:6px;font-weight:500}
.lkm-row input,.lkm-row select{width:100%;padding:13px 16px;background:rgba(0,0,0,.5);border:1px solid rgba(255,255,255,.18);border-radius:12px;color:#fff;font-size:15px;box-sizing:border-box;outline:none;transition:border-color .2s,box-shadow .2s;font-family:inherit}
.lkm-row input:focus,.lkm-row select:focus{border-color:rgba(99,102,241,.8);box-shadow:0 0 0 3px rgba(99,102,241,.15)}
.lkm-row input::placeholder{color:rgba(255,255,255,.35)}
.lkm-row select option{background:#1e293b}
.lkm-submit{width:100%;padding:15px;border:none;border-radius:12px;background:linear-gradient(135deg,rgba(99,102,241,.8),rgba(139,92,246,.8));color:#fff;font-size:16px;font-weight:600;cursor:pointer;transition:all .25s;letter-spacing:.05em;margin-top:6px}
.lkm-submit:hover{background:linear-gradient(135deg,rgba(99,102,241,.95),rgba(139,92,246,.95));transform:translateY(-1px);box-shadow:0 8px 30px rgba(99,102,241,.35)}
.lkm-submit:active{transform:translateY(0) scale(.99)}
.lkm-submit:disabled{opacity:.5;cursor:not-allowed;transform:none}
.lkm-tip{margin-top:14px;padding:12px 16px;border-radius:12px;font-size:14px;text-align:center;display:none;font-weight:500}
.lkm-tip.suc{background:rgba(34,197,94,.2);border:1px solid rgba(34,197,94,.5);color:#4ade80}
.lkm-tip.err{background:rgba(239,68,68,.2);border:1px solid rgba(239,68,68,.5);color:#f87171}
.lkm-tip.info{background:rgba(234,179,8,.2);border:1px solid rgba(234,179,8,.4);color:#facc15}
.lkm-done{text-align:center;padding:30px 24px}
.lkm-done .lkm-tick{font-size:64px;line-height:1;margin-bottom:16px;animation:lkmPop .5s cubic-bezier(.2,.8,.4,1.6)}
@keyframes lkmPop{from{transform:scale(0);opacity:0}to{transform:scale(1);opacity:1}}
.lkm-done p{color:#fff;font-size:18px;margin:0 0 6px;font-weight:600}
.lkm-done small{color:rgba(255,255,255,.55);font-size:14px}
.lkm-spin{width:22px;height:22px;border:3px solid rgba(255,255,255,.2);border-top-color:#fff;border-radius:50%;animation:lkmSpin .7s linear infinite;margin:0 auto}
@keyframes lkmSpin{to{transform:rotate(360deg)}}
</style>
</head>
<body>
<div class="bg"></div>
<div class="overlay"></div>
<div class="wrap">

  <?php if($show_link_apply!='0'): ?>
  <nav class="bar">
    <div class="bar-actions">
      <a class="bar-btn" href="admin/ad_register.php" target="_blank" rel="noopener">🚀 广告联盟</a>
      <button class="bar-btn" id="lkmBtn" type="button">🔗 提交友联</button>
    </div>
  </nav>
  <?php endif; ?>

  <div class="hero">
    <?php if(!empty($announcement)): ?>
    <div style="background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.2);border-radius:12px;padding:12px 20px;max-width:660px;margin:0 auto 20px;font-size:.85rem;color:rgba(255,255,255,.85);">
      📢 <?php echo htmlspecialchars($announcement); ?>
    </div>
    <?php endif; ?>
    <?php if($show_clock!='0'): ?>
    <div id="clock" class="<?php echo $clock_style; ?>">00:00:00</div>
    <div id="date"></div>
    <?php endif; ?>
    <?php if($show_search!='0'): ?>
    <div class="search">
      <form onsubmit="doSearch();return false" style="display:flex;width:100%;align-items:center;">
      <div class="eng engine-picker" id="enginePicker">
        <button class="engine-current" type="button" id="engineBtn" aria-haspopup="true" aria-expanded="false">
          <span class="engine-badge" id="engineBadge">百</span>
          <span id="engineLabel">百度</span>
          <span class="engine-arrow"></span>
        </button>
        <div class="engine-menu" id="engineMenu">
          <button class="engine-option active" type="button" data-label="百度" data-icon="百" data-url="https://www.baidu.com/s?wd="><span class="engine-badge">百</span><span>百度</span></button>
          <button class="engine-option" type="button" data-label="Google" data-icon="G" data-url="https://www.google.com/search?q="><span class="engine-badge">G</span><span>Google</span></button>
          <button class="engine-option" type="button" data-label="Bing" data-icon="B" data-url="https://www.bing.com/search?q="><span class="engine-badge">B</span><span>Bing</span></button>
        </div>
        <input type="hidden" id="eng" value="https://www.baidu.com/s?wd=">
      </div>
      <input class="sinp" id="sinp" type="text" placeholder="搜索网页、资源、工具..." autocomplete="off">
      <button class="sbtn" type="submit">⌕</button>
      </form>
    </div>
    <?php endif; ?>
    <?php if($ad_below_show): ?>
    <div class="ad-grid">
      <?php foreach($ad_below_items as $ad_item): ?>
      <div class="ad-cell">
        <?php if(!empty($ad_item) && $ad_item['image'] !== ''): ?>
        <a class="ad-link ad-banner" data-ad-id="<?php echo intval($ad_item['id']); ?>" href="<?php echo htmlspecialchars($ad_item['link'] ?: 'javascript:void(0)'); ?>"<?php echo $ad_item['link'] ? $ad_target : ''; ?> title="<?php echo htmlspecialchars($ad_item['title']); ?>">
          <img class="ad-img" src="<?php echo htmlspecialchars($ad_item['image']); ?>" alt="<?php echo htmlspecialchars($ad_item['alt'] ?: $ad_item['title'] ?: 'ad'); ?>">
        </a>
        <?php else: ?>
        <span class="ad-cell-empty"></span>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?php if($show_tags!='0'): ?>
    <div class="tags">
      <a class="tag" href="https://github.com/trending" target="_blank" rel="noopener">GitHub 趋势</a>
      <a class="tag" href="https://juejin.cn" target="_blank" rel="noopener">掘金</a>
      <a class="tag" href="https://producthunt.com" target="_blank" rel="noopener">Product Hunt</a>
      <a class="tag" href="https://sspai.com" target="_blank" rel="noopener">少数派</a>
    </div>
    <?php endif; ?>
  </div>

  <div id="sections">
<?php if (empty($sections)): ?>
  <div style="text-align:center;color:rgba(255,255,255,.45);padding:60px 0;font-size:.9rem;">
    暂无导航内容，请前往 <a href="admin/" style="color:rgba(160,200,255,.8);">后台管理</a> 添加站点
  </div>
<?php else: ?>
  <?php foreach ($sections as $cat => $items):
    $cat_emoji = isset($cat_icons[$cat]) ? $cat_icons[$cat] : mb_substr($cat, 0, 1, 'UTF-8');
    $palette = ['#6c63ff','#10a37f','#d97706','#0ea5e9','#ec4899','#f59e0b','#8b5cf6','#14b8a6','#ef4444','#3b82f6','#06b6d4','#84cc16'];
  ?>
  <div class="sec">
    <div class="sec-hd">
      <div class="dot"></div>
      <span style="font-size:.9rem"><?php echo htmlspecialchars($cat_emoji); ?></span>
      <span class="sec-title"><?php echo htmlspecialchars($cat); ?></span>
      <div class="sec-line"></div>
    </div>
    <div class="grid">
      <?php foreach ($items as $item):
        $name    = htmlspecialchars($item['name']);
        $url     = htmlspecialchars($item['url']);
        $desc    = htmlspecialchars($item['description']);
        $icon    = trim($item['icon']);
        $domain = '';
        if (preg_match('#^https?://([^/]+)#i', $item['url'], $m)) { $domain = $m[1]; }
        $ci    = abs(crc32($item['name'])) % count($palette);
        $color = $palette[$ci];
        $show_desc = $desc ?: $domain;
        $desc_speed_map = ['slow' => 16, 'normal' => 10, 'fast' => 7, 'rapid' => 4];
        $desc_color_map = ['default', 'red', 'orange', 'yellow', 'green', 'cyan', 'blue', 'purple', 'rainbow'];
        $desc_marquee = !empty($item['desc_marquee']) && intval($item['desc_marquee']) === 1;
        $desc_speed_key = isset($item['desc_speed']) && isset($desc_speed_map[$item['desc_speed']]) ? $item['desc_speed'] : 'normal';
        $desc_color_key = isset($item['desc_color']) && in_array($item['desc_color'], $desc_color_map) ? $item['desc_color'] : 'default';
        $desc_classes = 'ds'.($desc_marquee ? ' marquee' : '').($desc_color_key !== 'default' ? ' desc-color-'.$desc_color_key : '');
        $desc_style = $desc_marquee ? ' style="--desc-speed:'.$desc_speed_map[$desc_speed_key].'s"' : '';
        $show_favicon = !$icon && $domain;
        $initial = mb_substr($item['name'], 0, 1, 'UTF-8');
        $ping_status = isset($item['ping_status']) ? intval($item['ping_status']) : -1;
        $ping_latency = isset($item['ping_latency']) ? intval($item['ping_latency']) : 0;
        $ping_class = $ping_status === 1 ? 'online' : ($ping_status === 0 ? 'offline' : 'checking');
        if ($ping_status === 1) {
            $ping_title = $ping_latency > 0 ? '站点可访问，延迟 '.$ping_latency.'ms' : '站点可访问';
        } elseif ($ping_status === 0) {
            $ping_title = $ping_latency >= $ping_alert_latency ? '站点延迟过高，延迟 '.$ping_latency.'ms' : '站点无法访问';
        } else {
            $ping_title = '等待检测';
        }
      ?>
      <a class="card" href="<?php echo $url; ?>" target="_blank" rel="noopener" data-site-id="<?php echo intval($item['id']); ?>">
        <?php if($ping_enabled=='1'): ?><span class="ping-badge <?php echo $ping_class; ?>" title="<?php echo $ping_title; ?>"></span><?php endif; ?>
        <div class="ico" style="background:<?php echo $color; ?>22;border:1px solid <?php echo $color; ?>44;">
          <?php if ($icon): ?>
            <?php echo htmlspecialchars($icon); ?>
          <?php elseif ($show_favicon): ?>
            <img src="https://favicon.im/<?php echo htmlspecialchars($domain); ?>?larger=true" onerror="this.outerHTML='<?php echo htmlspecialchars($initial); ?>'" alt="<?php echo $name; ?>">
          <?php else: ?>
            <?php echo $initial; ?>
          <?php endif; ?>
        </div>
        <div class="inf">
          <div class="nm"><?php echo $name; ?></div>
          <div class="<?php echo $desc_classes; ?>"<?php echo $desc_style; ?>><span class="ds-text"><?php echo $show_desc; ?></span></div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endforeach; ?>
<?php endif; ?>
  </div>

  <div class="foot">
    <?php echo htmlspecialchars($conf['footer_text']); ?>
    <?php if(!empty($conf['footer_link'])): ?>
     · <a href="<?php echo htmlspecialchars($conf['footer_link']); ?>" target="_blank"><?php echo htmlspecialchars($conf['footer_link_text']); ?></a>
    <?php endif; ?>
    <?php if(!empty($conf['icp'])): ?>
     · <a href="https://beian.miit.gov.cn/" target="_blank"><?php echo htmlspecialchars($conf['icp']); ?></a>
    <?php endif; ?>
  </div>
</div>

<?php if($ad_right_show): ?>
<a class="ad-link ad-side ad-side-right" data-ad-id="<?php echo intval($ad_right['id']); ?>" href="<?php echo htmlspecialchars($ad_right['link'] ?: 'javascript:void(0)'); ?>"<?php echo $ad_right['link'] ? $ad_target : ''; ?> title="<?php echo htmlspecialchars($ad_right['title']); ?>">
  <img class="ad-img" src="<?php echo htmlspecialchars($ad_right['image']); ?>" alt="<?php echo htmlspecialchars($ad_right['alt'] ?: $ad_right['title'] ?: 'ad'); ?>">
</a>
<?php endif; ?>
<?php if($ad_left_show): ?>
<a class="ad-link ad-side ad-side-left" data-ad-id="<?php echo intval($ad_left['id']); ?>" href="<?php echo htmlspecialchars($ad_left['link'] ?: 'javascript:void(0)'); ?>"<?php echo $ad_left['link'] ? $ad_target : ''; ?> title="<?php echo htmlspecialchars($ad_left['title']); ?>">
  <img class="ad-img" src="<?php echo htmlspecialchars($ad_left['image']); ?>" alt="<?php echo htmlspecialchars($ad_left['alt'] ?: $ad_left['title'] ?: 'ad'); ?>">
</a>
<?php endif; ?>

<?php if($bg_music): ?>
<div class="music-btn" id="musicBtn" onclick="toggleMusic()">🎵</div>
<div class="music-panel" id="musicPanel">
  <div style="font-size:.8rem;color:rgba(255,255,255,.7);margin-bottom:5px;">🎧 背景音乐</div>
  <div class="vol">
    <span id="volIcon">🔊</span>
    <input type="range" id="volSlider" min="0" max="100" value="<?php echo $bg_music_volume; ?>" oninput="setVolume(this.value)">
    <span id="volVal"><?php echo $bg_music_volume; ?>%</span>
  </div>
</div>
<audio id="bgAudio" src="<?php echo htmlspecialchars($bg_music); ?>" loop preload="auto"></audio>
<?php endif; ?>

<?php if($show_link_apply!='0'): ?>
<!-- 友联申请弹窗 -->
<div id="lkm-wrap">
  <div class="lkm-box">
    <div class="lkm-hd">
      <h3>🔗 申请友链</h3>
      <button class="lkm-close" id="lkmCloseBtn">&times;</button>
    </div>
    <div class="lkm-bd" id="lkmFormBd">
      <div class="lkm-row">
        <label>网站名称</label>
        <input type="text" id="lkmName" placeholder="请输入网站名称" autocomplete="off">
      </div>
      <div class="lkm-row">
        <label>网站URL</label>
        <input type="url" id="lkmUrl" placeholder="https://your-site.com" autocomplete="off">
      </div>
      <div class="lkm-row">
        <label>网站描述</label>
        <input type="text" id="lkmDesc" placeholder="一句话介绍您的网站" autocomplete="off">
      </div>
      <div class="lkm-row">
        <label>申请分类</label>
        <select id="lkmCat">
          <?php foreach($link_cats as $c): echo '<option value="'.htmlspecialchars($c['name']).'">'.htmlspecialchars($c['name']).'</option>'; endforeach; ?>
        </select>
      </div>
      <div class="lkm-row">
        <label>通知邮箱 <small style="color:rgba(255,255,255,.4);">(审核结果通知)</small></label>
        <input type="email" id="lkmEmail" placeholder="选填，审核通过后邮件通知您" autocomplete="off">
      </div>
      <div class="lkm-tip" id="lkmTip"></div>
      <button class="lkm-submit" id="lkmSubmit">提交申请</button>
    </div>
    <div class="lkm-done" id="lkmDoneBd" style="display:none;">
      <div class="lkm-tick">✅</div>
      <p>提交成功！</p>
      <small>请等待管理员审核</small>
    </div>
  </div>
</div>
<?php endif; ?>

<script>
<?php if($show_link_apply!='0'): ?>
(function(){
  var wrap = document.getElementById('lkm-wrap');
  var btn = document.getElementById('lkmBtn');
  var closeBtn = document.getElementById('lkmCloseBtn');
  var submitBtn = document.getElementById('lkmSubmit');
  var tip = document.getElementById('lkmTip');
  var formBd = document.getElementById('lkmFormBd');
  var doneBd = document.getElementById('lkmDoneBd');

  btn.addEventListener('click', function(){
    wrap.classList.add('open');
    formBd.style.display = 'block';
    doneBd.style.display = 'none';
    tip.style.display = 'none';
    submitBtn.disabled = false;
    submitBtn.innerHTML = '提交申请';
    document.getElementById('lkmName').value = '';
    document.getElementById('lkmUrl').value = '';
    document.getElementById('lkmDesc').value = '';
    document.getElementById('lkmEmail').value = '';
  });

  closeBtn.addEventListener('click', function(){ wrap.classList.remove('open'); });
  wrap.addEventListener('click', function(e){ if(e.target===wrap) wrap.classList.remove('open'); });

  submitBtn.addEventListener('click', function(){
    var name = document.getElementById('lkmName').value.trim();
    var url = document.getElementById('lkmUrl').value.trim();
    var desc = document.getElementById('lkmDesc').value.trim();
    var cat = document.getElementById('lkmCat').value;
    var email = document.getElementById('lkmEmail').value.trim();
    if(!name || !url){
      tip.style.display='block'; tip.className='lkm-tip err'; tip.innerHTML='请填写网站名称和URL'; return;
    }
    submitBtn.disabled = true; submitBtn.innerHTML = '<div class=lkm-spin></div>';
    tip.style.display='none';
    var x = new XMLHttpRequest();
    x.open('POST','ajax_link.php',true);
    x.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
    x.onload = function(){
      submitBtn.disabled = false; submitBtn.innerHTML = '提交申请';
      try{var r = JSON.parse(x.responseText);}catch(e){
        tip.style.display='block'; tip.className='lkm-tip err'; tip.innerHTML='返回格式错误，请重试'; return;
      }
      if(r.code==1){
        formBd.style.display='none'; doneBd.style.display='block';
        setTimeout(function(){ wrap.classList.remove('open'); },2200);
      } else {
        tip.style.display='block'; tip.className='lkm-tip info'; tip.innerHTML=r.msg;
      }
    };
    x.onerror = function(){
      submitBtn.disabled = false; submitBtn.innerHTML = '提交申请';
      tip.style.display='block'; tip.className='lkm-tip err'; tip.innerHTML='网络错误，请检查连接';
    };
    x.send('name='+encodeURIComponent(name)+'&url='+encodeURIComponent(url)+'&desc='+encodeURIComponent(desc)+'&cat='+encodeURIComponent(cat)+'&email='+encodeURIComponent(email));
  });
})();
<?php endif; ?>
<?php if($bg_music): ?>
var audio=document.getElementById('bgAudio'),isPlaying=false;
function toggleMusic(){var panel=document.getElementById('musicPanel');panel.classList.toggle('active');}
function setVolume(v){audio.volume=v/100;document.getElementById('volVal').textContent=v+'%';document.getElementById('volIcon').textContent=v==0?'🔇':v<50?'🔉':'🔊';}
document.getElementById('musicBtn').addEventListener('click',function(){if(!isPlaying){audio.volume=<?php echo $bg_music_volume; ?>/100;audio.play().then(function(){isPlaying=true}).catch(function(){});}else{audio.pause();isPlaying=false;}});
<?php endif; ?>
function tick(){var n=new Date(),pad=function(v){return String(v).padStart(2,'0')},is12h='<?php echo $time_format; ?>'==='12';var h=n.getHours(),ampm=h>=12?'下午':'上午';if(is12h)h=h%12||12;var ts=is12h?ampm+' '+pad(h)+':'+pad(n.getMinutes()):pad(h)+':'+pad(n.getMinutes())+':'+pad(n.getSeconds());var ce=document.getElementById('clock');if(ce){if(ce.classList.contains('simple')){ce.textContent='现在'+ampm+' '+pad(h)+':'+pad(n.getMinutes());}else{ce.textContent=ts;}}var de=document.getElementById('date');if(de){var days=['日','一','二','三','四','五','六'];de.textContent=n.getFullYear()+' · '+pad(n.getMonth()+1)+' · '+pad(n.getDate())+' · 星期'+days[n.getDay()];}}
setInterval(tick,1000);tick();
function doSearch(){var q=document.getElementById('sinp').value.trim();if(!q)return;window.open(document.getElementById('eng').value+encodeURIComponent(q),'_blank');}
var sinpEl=document.getElementById('sinp');if(sinpEl){sinpEl.addEventListener('keydown',function(e){if(e.key==='Enter')doSearch();});}
(function(){
  var picker=document.getElementById('enginePicker'),btn=document.getElementById('engineBtn'),menu=document.getElementById('engineMenu'),eng=document.getElementById('eng'),label=document.getElementById('engineLabel'),badge=document.getElementById('engineBadge');
  if(!picker||!btn||!menu||!eng)return;
  btn.addEventListener('click',function(e){e.stopPropagation();var open=picker.classList.toggle('open');btn.setAttribute('aria-expanded',open?'true':'false');});
  menu.querySelectorAll('.engine-option').forEach(function(opt){
    opt.addEventListener('click',function(){
      menu.querySelectorAll('.engine-option').forEach(function(o){o.classList.remove('active');});
      opt.classList.add('active');eng.value=opt.getAttribute('data-url');label.textContent=opt.getAttribute('data-label');badge.textContent=opt.getAttribute('data-icon');picker.classList.remove('open');btn.setAttribute('aria-expanded','false');
    });
  });
  document.addEventListener('click',function(){picker.classList.remove('open');btn.setAttribute('aria-expanded','false');});
})();
(function(){
  function trackAd(adId,type){
    if(!adId)return;
    var body='ad_id='+encodeURIComponent(adId)+'&type='+encodeURIComponent(type);
    if(navigator.sendBeacon){
      navigator.sendBeacon('ajax_ad_track.php',new Blob([body],{type:'application/x-www-form-urlencoded'}));
    }else if(window.fetch){
      fetch('ajax_ad_track.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:body,keepalive:true}).catch(function(){});
    }
  }
  document.querySelectorAll('.ad-link[data-ad-id]').forEach(function(ad){
    trackAd(ad.getAttribute('data-ad-id'),'view');
    ad.addEventListener('click',function(){
      trackAd(ad.getAttribute('data-ad-id'),'click');
    });
  });
})();
(function(){
  document.querySelectorAll('.card[data-site-id]').forEach(function(card){
    card.addEventListener('click',function(){
      var id=card.getAttribute('data-site-id');if(!id)return;
      var body='site_id='+encodeURIComponent(id);
      if(navigator.sendBeacon){
        navigator.sendBeacon('ajax_track.php',new Blob([body],{type:'application/x-www-form-urlencoded'}));
      }else if(window.fetch){
        fetch('ajax_track.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:body,keepalive:true}).catch(function(){});
      }
    });
  });
})();
<?php if($ping_need_refresh): ?>
setTimeout(function(){
  fetch('cron_site_status.php?auto=1', {cache:'no-store'}).catch(function(){});
},1200);
<?php endif; ?>
</script>
<img src="stats.php" width="0" height="0" style="display:none" alt="">
</body>
</html>

