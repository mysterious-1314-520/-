<?php
/**
 * 广告联盟系统 版本信息
 */

if(!defined('IN_CRONLITE')) exit;

define('AD_NETWORK_VERSION', '1.5.0');
define('AD_NETWORK_BUILD', '20260628');
define('AD_NETWORK_NAME', '祈福广告联盟系统');

/**
 * 版本更新日志
 */
$version_changelog = [
    '1.5.0' => [
        'date' => '2026-06-28',
        'changes' => [
            '【新增】广告联盟系统完整功能',
            '【新增】用户认证系统 (4 角色：admin/auditor/advertiser/publisher)',
            '【新增】广告主后台 (活动管理/定向设置/创意管理/数据报表)',
            '【新增】网站主后台 (网站接入/广告位管理/收益统计)',
            '【新增】广告投放引擎 (eCPM 竞价排序/定向过滤/频控机制)',
            '【新增】数据追踪系统 (展示/点击/转化日志)',
            '【新增】财务系统 (在线充值/账户管理/交易明细)',
            '【新增】审核系统 (广告审核/网站审核)',
            '【新增】反作弊系统 (IP 频控/UA 检测/黑名单)',
            '【新增】JS SDK (广告请求/自动渲染/数据上报)',
            '【新增】18 张 MySQL 数据表',
            '【优化】bcrypt 密码加密 (cost=12)',
            '【优化】RBAC 权限控制',
            '【优化】Chart.js 数据可视化'
        ]
    ],
    '1.3.0' => [
        'date' => '2026-06-27',
        'changes' => [
            '【原有】祈福导航系统基础功能'
        ]
    ]
];

/**
 * 检查版本更新
 */
function check_version_update() {
    global $DB;
    
    try {
        $current_version = $DB->query("SELECT v FROM ad_system_config WHERE k='version'")->fetchColumn();
        
        if(!$current_version || version_compare($current_version, AD_NETWORK_VERSION, '<')) {
            // 需要升级
            $DB->query("UPDATE ad_system_config SET v='".AD_NETWORK_VERSION."' WHERE k='version'");
            return true;
        }
        
        return false;
    }
    catch(Exception $e) {
        return false;
    }
}
