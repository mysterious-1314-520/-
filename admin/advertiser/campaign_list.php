<?php
/**
 * 广告活动列表页面
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

try {
    // 获取广告主 ID
    $advertiser = $DB->fetch_result($DB->query("SELECT id FROM ad_advertisers WHERE user_id = $user_id"));
    $advertiser_id = $advertiser['id'];
    
    // 分页参数
    $page = max(1, isset($_GET['page']) ? intval($_GET['page']) : 1);
    $pagesize = 20;
    $offset = ($page - 1) * $pagesize;
    
    // 搜索参数
    $status_filter = isset($_GET['status']) ? intval($_GET['status']) : -1;
    $keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
    
    // 查询条件
    $where = "WHERE advertiser_id = $advertiser_id";
    if($status_filter >= 0) {
        $where .= " AND status = $status_filter";
    }
    if($keyword) {
        $where .= " AND name LIKE '%$keyword%'";
    }
    
    // 总数
    $total = $DB->query("SELECT COUNT(*) FROM ad_campaigns $where")->fetchColumn();
    
    // 活动列表
    $campaigns = $DB->query("
        SELECT * FROM ad_campaigns 
        $where 
        ORDER BY created_at DESC 
        LIMIT $offset, $pagesize
    ")->fetchAll();
    
    // 状态映射
    $status_map = [
        0 => ['text' => '草稿', 'class' => 'secondary'],
        1 => ['text' => '待审核', 'class' => 'warning'],
        2 => ['text' => '审核通过', 'class' => 'info'],
        3 => ['text' => '审核驳回', 'class' => 'danger'],
        4 => ['text' => '投放中', 'class' => 'success'],
        5 => ['text' => '已暂停', 'class' => 'danger'],
        6 => ['text' => '已结束', 'class' => 'secondary']
    ];
    
    // 计费类型映射
    $charge_type_map = [
        'CPM' => '千次展示',
        'CPC' => '单次点击',
        'CPA' => '单次转化',
        'CPT' => '时段包段'
    ];
}
catch(Exception $e) {
    $error = '数据加载失败：'.$e->getMessage();
}

include(ROOT.'admin/admin.php');
?>

<div class="card">
    <div class="card-header">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h5 class="mb-0"><i class="fa fa-bullhorn"></i> 广告活动管理</h5>
            </div>
            <div class="col-md-6 text-right">
                <a href="campaign_create.php" class="btn btn-primary btn-sm">
                    <i class="fa fa-plus"></i> 新建活动
                </a>
            </div>
        </div>
    </div>
    
    <div class="card-body">
        <!-- 筛选栏 -->
        <form method="get" class="row mb-4">
            <div class="col-md-4">
                <input type="text" name="keyword" class="form-control" placeholder="搜索活动名称" value="<?=$keyword?>">
            </div>
            <div class="col-md-3">
                <select name="status" class="form-control">
                    <option value="-1">全部状态</option>
                    <?php foreach($status_map as $k => $v): ?>
                        <option value="<?=$k?>" <?=$status_filter == $k ? 'selected' : ''?>><?=$v['text']?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-info btn-block">
                    <i class="fa fa-search"></i> 搜索
                </button>
            </div>
            <div class="col-md-2">
                <a href="campaign_list.php" class="btn btn-secondary btn-block">
                    <i class="fa fa-redo"></i> 重置
                </a>
            </div>
        </form>
        
        <!-- 活动列表 -->
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>活动名称</th>
                        <th>计费类型</th>
                        <th>出价</th>
                        <th>预算</th>
                        <th>已用</th>
                        <th>投放日期</th>
                        <th>状态</th>
                        <th>创建时间</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($campaigns)): ?>
                        <tr>
                            <td colspan="10" class="text-center text-muted py-5">
                                <i class="fa fa-inbox fa-3x mb-3"></i>
                                <p>暂无广告活动，<a href="campaign_create.php">立即创建</a></p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($campaigns as $c): ?>
                            <tr>
                                <td><?=$c['id']?></td>
                                <td>
                                    <a href="campaign_edit.php?id=<?=$c['id']?>" class="font-weight-bold">
                                        <?=htmlspecialchars($c['name'])?>
                                    </a>
                                </td>
                                <td><span class="badge badge-info"><?=$charge_type_map[$c['charge_type']]?></span></td>
                                <td>¥<?=number_format($c['bid_price'], 2)?></td>
                                <td>¥<?=number_format($c['budget_total'], 2)?></td>
                                <td>
                                    <div class="progress" style="height: 6px; width: 100px;">
                                        <?php $percent = $c['budget_total'] > 0 ? min(100, ($c['budget_used'] / $c['budget_total']) * 100) : 0; ?>
                                        <div class="progress-bar bg-<?= $percent >= 80 ? 'warning' : 'success' ?>" 
                                             style="width: <?=$percent?>%"></div>
                                    </div>
                                    <small class="text-muted">¥<?=number_format($c['budget_used'], 2)?></small>
                                </td>
                                <td>
                                    <small><?=date('m-d', strtotime($c['start_date']))?> ~ <?=date('m-d', strtotime($c['end_date']))?></small>
                                </td>
                                <td>
                                    <span class="badge badge-<?=$status_map[$c['status']]['class']?>">
                                        <?=$status_map[$c['status']]['text']?>
                                    </span>
                                </td>
                                <td><small><?=date('m-d H:i', strtotime($c['created_at']))?></small></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="campaign_edit.php?id=<?=$c['id']?>" class="btn btn-info" title="编辑">
                                            <i class="fa fa-edit"></i>
                                        </a>
                                        <?php if($c['status'] == 4): ?>
                                            <button class="btn btn-warning" onclick="toggleCampaign(<?=$c['id']?>, 5)" title="暂停">
                                                <i class="fa fa-pause"></i>
                                            </button>
                                        <?php elseif($c['status'] == 5): ?>
                                            <button class="btn btn-success" onclick="toggleCampaign(<?=$c['id']?>, 4)" title="恢复">
                                                <i class="fa fa-play"></i>
                                            </button>
                                        <?php endif; ?>
                                        <button class="btn btn-danger" onclick="deleteCampaign(<?=$c['id']?>)" title="删除">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- 分页 -->
        <?php if($total > $pagesize): ?>
            <nav>
                <?=$db->query("SELECT COUNT(*) as c FROM ad_campaigns $where")->fetch()['c'] ? 
                    \lib\plugins\page::div($total, $page, "?keyword=$keyword&status=$status_filter&") : ''?>
            </nav>
        <?php endif; ?>
    </div>
</div>

<script src="<?=cdn()?>layer/layer.js"></script>
<script>
// 暂停/恢复活动
function toggleCampaign(id, status) {
    const action = status == 4 ? '恢复' : '暂停';
    layer.confirm('确定要'+action+'该活动吗？', function(index) {
        $.ajax({
            url: 'campaign_action.php',
            type: 'POST',
            data: { act: 'toggle', id: id, status: status },
            success: function(res) {
                if(res.code == 0) {
                    layer.msg(action+'成功', { icon: 1 });
                    setTimeout(() => location.reload(), 1000);
                } else {
                    layer.msg(res.msg, { icon: 2 });
                }
            },
            error: function() {
                layer.msg('操作失败', { icon: 2 });
            }
        });
        layer.close(index);
    });
}

// 删除活动
function deleteCampaign(id) {
    layer.confirm('确定要删除该活动吗？删除后不可恢复！', { icon: 3, title: '警告' }, function(index) {
        $.ajax({
            url: 'campaign_action.php',
            type: 'POST',
            data: { act: 'delete', id: id },
            success: function(res) {
                if(res.code == 0) {
                    layer.msg('删除成功', { icon: 1 });
                    setTimeout(() => location.reload(), 1000);
                } else {
                    layer.msg(res.msg, { icon: 2 });
                }
            },
            error: function() {
                layer.msg('操作失败', { icon: 2 });
            }
        });
        layer.close(index);
    });
}
</script>
