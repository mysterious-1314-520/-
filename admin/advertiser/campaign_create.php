<?php
/**
 * 创建广告活动页面
 * 广告主后台 - 活动管理
 */

define('IN_CRONLITE', true);
define('SYSTEM_ROOT', '/workspace/includes/');
define('ROOT', '/workspace/');

require_once(ROOT.'includes/common.php');
require_once(ROOT.'includes/auth.php');

// 要求登录且为广告主角色
require_login();
require_role('advertiser');

$user_id = get_current_user_id();
$error = '';
$success = '';

try {
    // 获取广告主信息
    $advertiser = $DB->fetch_result($DB->query("SELECT id, account_balance FROM ad_advertisers WHERE user_id = $user_id"));
    
    // 处理表单提交
    if(isset($_POST['submit'])) {
        $name = trim($_POST['name']);
        $objective = $_POST['objective'];
        $budget_total = floatval($_POST['budget_total']);
        $budget_daily = floatval($_POST['budget_daily']);
        $charge_type = $_POST['charge_type'];
        $bid_price = floatval($_POST['bid_price']);
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        
        // 验证
        if(empty($name)) {
            $error = '请输入活动名称';
        }
        elseif(!in_array($objective, ['brand', 'traffic', 'conversion'])) {
            $error = '请选择正确的投放目标';
        }
        elseif($budget_total < 100) {
            $error = '总预算最低为 100 元';
        }
        elseif($budget_daily < 50) {
            $error = '日预算最低为 50 元';
        }
        elseif($budget_total < $budget_daily * (strtotime($end_date) - strtotime($start_date)) / 86400) {
            $error = '总预算必须 ≥ 日预算 × 投放天数';
        }
        elseif(!in_array($charge_type, ['CPM', 'CPC', 'CPA', 'CPT'])) {
            $error = '请选择正确的计费类型';
        }
        elseif($bid_price < 0.5) {
            $error = '出价最低为 0.5 元';
        }
        elseif(strtotime($start_date) < time()) {
            $error = '开始日期不能早于当前时间';
        }
        elseif(strtotime($end_date) <= strtotime($start_date)) {
            $error = '结束日期必须晚于开始日期';
        }
        else {
            // 检查余额
            if($budget_total > $advertiser['account_balance']) {
                $error = '账户余额不足，请充值后再创建活动';
            }
            else {
                // 插入活动
                $stmt = $DB->prepare("
                    INSERT INTO ad_campaigns 
                    (advertiser_id, name, objective, budget_total, budget_daily, charge_type, bid_price, start_date, end_date, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
                ");
                $stmt->execute([
                    $advertiser['id'],
                    $name,
                    $objective,
                    $budget_total,
                    $budget_daily,
                    $charge_type,
                    $bid_price,
                    $start_date,
                    $end_date
                ]);
                
                $campaign_id = $DB->lastInsertId();
                
                // 记录日志
                $DB->query("INSERT INTO ad_operation_logs (user_id, action, module, object_type, object_id) VALUES ($user_id, 'create_campaign', 'advertiser', 'campaign', $campaign_id)");
                
                // 跳转到广告组创建
                header('Location: adgroup_create.php?campaign_id='.$campaign_id);
                exit;
            }
        }
    }
}
catch(Exception $e) {
    $error = '创建失败：'.$e->getMessage();
}

include(ROOT.'admin/admin.php');
?>

<div class="card">
    <div class="card-header">
        <h5><i class="fa fa-plus"></i> 创建广告活动</h5>
    </div>
    
    <div class="card-body">
        <?php if($error): ?>
            <div class="alert alert-danger"><?=$error?></div>
        <?php endif; ?>
        
        <form method="post">
            <div class="form-group">
                <label class="required">活动名称</label>
                <input type="text" name="name" class="form-control" placeholder="请输入活动名称，如：618 促销活动" maxlength="100" required>
                <small class="text-muted">仅用于内部管理，用户不可见</small>
            </div>
            
            <div class="form-group">
                <label class="required">投放目标</label>
                <select name="objective" class="form-control" required>
                    <option value="traffic">获取流量 - 以最小成本获取最大流量</option>
                    <option value="brand">品牌曝光 - 最大化品牌曝光度</option>
                    <option value="conversion">促进转化 - 获取更多转化</option>
                </select>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="required">计费类型</label>
                        <select name="charge_type" class="form-control" required onchange="updateBidTips()">
                            <option value="CPM">CPM - 按千次展示计费</option>
                            <option value="CPC">CPC - 按点击计费</option>
                            <option value="CPA">CPA - 按转化计费</option>
                            <option value="CPT">CPT - 按时段包段计费</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="required">出价 (元)</label>
                        <input type="number" name="bid_price" class="form-control" step="0.01" min="0.5" value="1.00" required id="bid_price">
                        <small class="text-muted" id="bid_tips">CPM 出价：1 元 = 1000 次展示</small>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="required">总预算 (元)</label>
                        <input type="number" name="budget_total" class="form-control" step="1" min="100" value="1000" required>
                        <small class="text-muted">最低 100 元，当前余额：<span class="text-success">¥<?=number_format($advertiser['account_balance'], 2)?></span></small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="required">日预算 (元)</label>
                        <input type="number" name="budget_daily" class="form-control" step="1" min="50" value="200" required>
                        <small class="text-muted">最低 50 元，预算耗尽后活动自动暂停</small>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="required">开始日期</label>
                        <input type="date" name="start_date" class="form-control" value="<?=date('Y-m-d', strtotime('+1 day'))?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="required">结束日期</label>
                        <input type="date" name="end_date" class="form-control" value="<?=date('Y-m-d', strtotime('+7 days'))?>" required>
                    </div>
                </div>
            </div>
            
            <div class="form-group mt-4">
                <button type="submit" name="submit" class="btn btn-primary btn-block">
                    <i class="fa fa-check"></i> 提交创建
                </button>
                <a href="campaign_list.php" class="btn btn-secondary btn-block mt-2">
                    <i class="fa fa-arrow-left"></i> 返回列表
                </a>
            </div>
        </form>
    </div>
</div>

<script>
function updateBidTips() {
    const type = document.querySelector('[name="charge_type"]').value;
    const tips = {
        'CPM': 'CPM 出价：1 元 = 1000 次展示，最低 0.5 元',
        'CPC': 'CPC 出价：1 元 = 1 次点击，最低 0.1 元',
        'CPA': 'CPA 出价：1 元 = 1 次转化，最低 1 元',
        'CPT': 'CPT 出价：按实际协商价格，最低 100 元/天'
    };
    document.getElementById('bid_tips').textContent = tips[type];
}
</script>
