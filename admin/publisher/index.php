<?php
/**
 * 网站主后台 - 首页
 * 数据概览
 */

define('IN_CRONLITE', true);
define('SYSTEM_ROOT', dirname(__FILE__).'/');
define('ROOT', dirname(dirname(SYSTEM_ROOT)).'/');

require_once(ROOT.'includes/common.php');
require_once(ROOT.'includes/auth.php');

// 要求登录且为网站主角色
require_login();
require_role('publisher');

$user_id = get_current_user_id();

try {
    // 获取网站主信息
    $stmt = $DB->prepare("SELECT * FROM ad_publishers WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $publisher = $stmt->fetch();
    
    // 获取账户余额
    $account = $DB->query("SELECT * FROM ad_accounts WHERE user_id = $user_id")->fetch();
    
    // 今日数据
    $today_start = date('Y-m-d 00:00:00');
    $stats_today = $DB->query("
        SELECT 
            SUM(p.impressions) as impressions,
            SUM(p.clicks) as clicks,
            SUM(p.total_earning) as earning,
            SUM(p.fraud_impressions) as fraud_impressions
        FROM ad_daily_publisher p
        WHERE p.publisher_id = {$publisher['id']}
        AND p.day = CURDATE()
    ")->fetch();
    
    // 昨日数据
    $stats_yesterday = $DB->query("
        SELECT 
            SUM(total_earning) as earning
        FROM ad_daily_publisher 
        WHERE publisher_id = {$publisher['id']}
        AND day = DATE_SUB(CURDATE(), INTERVAL 1 DAY)
    ")->fetch();
    
    // 近 7 天收益趋势
    $stats_7days = $DB->query("
        SELECT 
            day,
            SUM(impressions) as impressions,
            SUM(clicks) as clicks,
            SUM(total_earning) as earning
        FROM ad_daily_publisher 
        WHERE publisher_id = {$publisher['id']}
        AND day >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY day
        ORDER BY day ASC
    ")->fetchAll();
    
    // 网站状态统计
    $website_stats = $DB->query("
        SELECT status, COUNT(*) as count 
        FROM ad_publisher_websites 
        WHERE publisher_id = {$publisher['id']}
        GROUP BY status
    ")->fetchAll();
    
    // 广告位状态统计
    $position_stats = $DB->query("
        SELECT status, COUNT(*) as count 
        FROM ad_positions 
        WHERE website_id IN (
            SELECT id FROM ad_publisher_websites WHERE publisher_id = {$publisher['id']}
        )
        GROUP BY status
    ")->fetch();
}
catch(Exception $e) {
    $error = '数据加载失败：'.$e->getMessage();
}

// 加载模板
include(ROOT.'admin/admin.php');

?>

<div class="row">
    <!-- 账户余额 -->
    <div class="col-md-3">
        <div class="card">
            <div class="card-header">
                <i class="fa fa-wallet"></i> 账户余额
            </div>
            <div class="card-body">
                <h3 class="text-success">¥<?=number_format($account['balance'], 2)?></h3>
                <p class="text-muted">
                    冻结：¥<?=number_format($account['frozen_balance'], 2)?> | 
                    待结算：¥<?=number_format($publisher['frozen_earning'] ?? 0, 2)?>
                </p>
                <a href="withdraw.php" class="btn btn-sm btn-success">申请提现</a>
            </div>
        </div>
    </div>
    
    <!-- 今日收益 -->
    <div class="col-md-3">
        <div class="card">
            <div class="card-header">
                <i class="fa fa-yen-sign"></i> 今日收益
            </div>
            <div class="card-body">
                <h3 class="text-primary">¥<?=number_format($stats_today['earning'] ?: 0, 2)?></h3>
                <p class="text-muted">
                    昨日：¥<?=number_format($stats_yesterday['earning'] ?: 0, 2)?>
                    <?php if($stats_yesterday['earning'] > 0): ?>
                        <span class="text-<?=($stats_today['earning'] >= $stats_yesterday['earning']) ? 'success' : 'danger'?>">
                            <?=round((($stats_today['earning'] ?: 0) / $stats_yesterday['earning'] - 1) * 100, 1)?>%
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
    
    <!-- 网站统计 -->
    <div class="col-md-3">
        <div class="card">
            <div class="card-header">
                <i class="fa fa-globe"></i> 网站与广告位
            </div>
            <div class="card-body">
                <?php
                $status_map = [
                    0 => '待审核',
                    1 => '审核通过',
                    2 => '审核驳回',
                    3 => '禁用'
                ];
                $website_all = [];
                foreach($website_stats as $s) {
                    $website_all[$s['status']] = $s['count'];
                }
                ?>
                <div class="row">
                    <div class="col-6">
                        <span class="text-muted">已通过:</span>
                        <span class="text-success float-right"><?= $website_all[1] ?? 0 ?></span>
                    </div>
                    <div class="col-6">
                        <span class="text-muted">待审核:</span>
                        <span class="text-warning float-right"><?= $website_all[0] ?? 0 ?></span>
                    </div>
                </div>
                <hr class="my-2">
                <div class="row">
                    <div class="col-6">
                        <span class="text-muted">广告位总数:</span>
                        <span class="text-primary float-right"><?= $position_stats['count'] ?? 0 ?></span>
                    </div>
                    <div class="col-6">
                        <span class="text-muted">启用的:</span>
                        <span class="text-success float-right"><?= $position_stats[1] ?? 0 ?></span>
                    </div>
                </div>
                <a href="website_list.php" class="btn btn-sm btn-outline-primary mt-2">管理网站</a>
            </div>
        </div>
    </div>
</div>

<!-- 近 7 天趋势图表 -->
<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <i class="fa fa-chart-line"></i> 近 7 天收益趋势
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
            labels: trendData.map(d => d.day),
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
                label: '收益 (元)',
                data: trendData.map(d => d.earning),
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
