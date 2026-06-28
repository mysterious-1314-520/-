<?php
/**
 * 广告主后台 - 首页
 * 数据概览
 */

define('IN_CRONLITE', true);
define('SYSTEM_ROOT', dirname(__FILE__).'/');
define('ROOT', dirname(dirname(SYSTEM_ROOT)).'/');

require_once(ROOT.'includes/common.php');
require_once(ROOT.'includes/auth.php');

// 要求登录且为广告主角色
require_login();
require_role('advertiser');

$user_id = get_current_user_id();

try {
    // 获取广告主信息
    $stmt = $DB->prepare("SELECT * FROM ad_advertisers WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $advertiser = $stmt->fetch();
    
    // 获取账户余额
    $account = $DB->query("SELECT * FROM ad_accounts WHERE user_id = $user_id")->fetch();
    
    // 今日数据
    $today_start = date('Y-m-d 00:00:00');
    $stats_today = $DB->query("
        SELECT 
            SUM(i.impressions) as impressions,
            SUM(i.clicks) as clicks,
            SUM(i.total_cost) as cost,
            SUM(i.fraud_clicks) as fraud_clicks
        FROM ad_hourly_advertiser i
        WHERE i.advertiser_id = {$advertiser['id']}
        AND i.hour >= '$today_start'
    ")->fetch();
    
    // 昨日数据
    $yesterday_start = date('Y-m-d 00:00:00', strtotime('-1 day'));
    $yesterday_end = date('Y-m-d 23:59:59', strtotime('-1 day'));
    $stats_yesterday = $DB->query("
        SELECT 
            SUM(i.impressions) as impressions,
            SUM(i.clicks) as clicks,
            SUM(i.total_cost) as cost
        FROM ad_hourly_advertiser i
        WHERE i.advertiser_id = {$advertiser['id']}
        AND i.hour BETWEEN '$yesterday_start' AND '$yesterday_end'
    ")->fetch();
    
    // 近 7 天趋势
    $stats_7days = $DB->query("
        SELECT 
            DATE(i.hour) as date,
            SUM(i.impressions) as impressions,
            SUM(i.clicks) as clicks,
            SUM(i.total_cost) as cost
        FROM ad_hourly_advertiser i
        WHERE i.advertiser_id = {$advertiser['id']}
        AND i.hour >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(i.hour)
        ORDER BY date ASC
    ")->fetchAll();
    
    // 活动状态统计
    $campaign_stats = $DB->query("
        SELECT status, COUNT(*) as count 
        FROM ad_campaigns 
        WHERE advertiser_id = {$advertiser['id']}
        GROUP BY status
    ")->fetchAll();
}
catch(Exception $e) {
    $error = '数据加载失败：'.$e->getMessage();
}

// 加载模板
include(ROOT.'admin/admin.php');

?>

<div class="row">
    <!-- 账户概览 -->
    <div class="col-md-3">
        <div class="card">
            <div class="card-header">
                <i class="fa fa-wallet"></i> 账户余额
            </div>
            <div class="card-body">
                <h3 class="text-primary">¥<?=number_format($account['balance'], 2)?></h3>
                <p class="text-muted">
                    冻结：¥<?=number_format($account['frozen_balance'], 2)?> | 
                    优惠券：¥<?=number_format($account['coupon_balance'], 2)?>
                </p>
                <a href="finance.php" class="btn btn-sm btn-primary">立即充值</a>
            </div>
        </div>
    </div>
    
    <!-- 今日消耗 -->
    <div class="col-md-3">
        <div class="card">
            <div class="card-header">
                <i class="fa fa-shopping-cart"></i> 今日消耗
            </div>
            <div class="card-body">
                <h3 class="text-success">¥<?=number_format($stats_today['cost'] ?: 0, 2)?></h3>
                <p class="text-muted">
                    昨日：¥<?=number_format($stats_yesterday['cost'] ?: 0, 2)?>
                    <?php if($stats_yesterday['cost'] > 0): ?>
                        <span class="text-<?=($stats_today['cost'] >= $stats_yesterday['cost']) ? 'success' : 'danger'?>">
                            <?=round((($stats_today['cost'] ?: 0) / $stats_yesterday['cost'] - 1) * 100, 1)?>%
                        </span>
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </div>
    
    <!-- 今日展示 -->
    <div class="col-md-3">
        <div class="card">
            <div class="card-header">
                <i class="fa fa-eye"></i> 今日展示
            </div>
            <div class="card-body">
                <h3><?=number_format($stats_today['impressions'] ?: 0)?></h3>
                <p class="text-muted">点击：<?=number_format($stats_today['clicks'] ?: 0)?></p>
                <?php if($stats_today['impressions'] > 0): ?>
                    <span class="badge badge-info">CTR: <?=round(($stats_today['clicks'] / $stats_today['impressions']) * 100, 2)?>%</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- 活动状态 -->
    <div class="col-md-3">
        <div class="card">
            <div class="card-header">
                <i class="fa fa-bullhorn"></i> 广告活动
            </div>
            <div class="card-body">
                <?php
                $status_map = [
                    0 => '草稿',
                    1 => '待审核',
                    2 => '审核通过',
                    3 => '审核驳回',
                    4 => '投放中',
                    5 => '已暂停',
                    6 => '已结束'
                ];
                $all_stats = [];
                foreach($campaign_stats as $s) {
                    $all_stats[$s['status']] = $s['count'];
                }
                ?>
                <div class="row">
                    <div class="col-6">
                        <span class="text-muted">投放中:</span>
                        <span class="text-success float-right"><?= $all_stats[4] ?? 0 ?></span>
                    </div>
                    <div class="col-6">
                        <span class="text-muted">待审核:</span>
                        <span class="text-warning float-right"><?= $all_stats[1] ?? 0 ?></span>
                    </div>
                    <div class="col-6">
                        <span class="text-muted">已暂停:</span>
                        <span class="text-danger float-right"><?= $all_stats[5] ?? 0 ?></span>
                    </div>
                    <div class="col-6">
                        <span class="text-muted">草稿:</span>
                        <span class="text-secondary float-right"><?= $all_stats[0] ?? 0 ?></span>
                    </div>
                </div>
                <a href="campaign_list.php" class="btn btn-sm btn-outline-primary mt-2">管理活动</a>
            </div>
        </div>
    </div>
</div>

<!-- 近 7 天趋势图表 -->
<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <i class="fa fa-chart-line"></i> 近 7 天数据趋势
            </div>
            <div class="card-body">
                <canvas id="trendChart" height="80"></canvas>
            </div>
        </div>
    </div>
</div>

<script src="<?=cdn()?>layer/layer.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
$(document).ready(function() {
    // 渲染趋势图表
    const ctx = document.getElementById('trendChart').getContext('2d');
    const trendData = <?=json_encode($stats_7days)?>;
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: trendData.map(d => d.date),
            datasets: [{
                label: '展示量',
                data: trendData.map(d => d.impressions),
                borderColor: 'rgba(54, 162, 235, 1)',
                backgroundColor: 'rgba(54, 162, 235, 0.1)',
                tension: 0.3,
                yAxisID: 'y'
            }, {
                label: '点击量',
                data: trendData.map(d => d.clicks),
                borderColor: 'rgba(255, 99, 132, 1)',
                backgroundColor: 'rgba(255, 99, 132, 0.1)',
                tension: 0.3,
                yAxisID: 'y'
            }, {
                label: '消耗 (元)',
                data: trendData.map(d => d.cost),
                borderColor: 'rgba(75, 192, 75, 1)',
                backgroundColor: 'rgba(75, 192, 75, 0.1)',
                tension: 0.3,
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
            interaction: {
                mode: 'index',
                intersect: false
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: { display: true, text: '数量' }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    grid: { drawOnChartArea: false },
                    title: { display: true, text: '金额 (元)' }
                }
            }
        }
    });
});
</script>
