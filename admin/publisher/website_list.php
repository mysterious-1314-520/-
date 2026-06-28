<?php
/**
 * 网站列表页面
 * 网站主后台 - 网站管理
 */

define('IN_CRONLITE', true);
define('SYSTEM_ROOT', dirname(__FILE__).'/');
define('ROOT', dirname(dirname(SYSTEM_ROOT)).'/');

require_once(ROOT.'includes/common.php');
require_once(ROOT.'includes/auth.php');

require_login();
require_role('publisher');

$user_id = get_current_user_id();

try {
    // 获取网站主 ID
    $publisher = $DB->query("SELECT id FROM ad_publishers WHERE user_id = $user_id")->fetch();
    $publisher_id = $publisher['id'];
    
    // 分页参数
    $page = max(1, isset($_GET['page']) ? intval($_GET['page']) : 1);
    $pagesize = 20;
    $offset = ($page - 1) * $pagesize;
    
    // 搜索参数
    $status_filter = isset($_GET['status']) ? intval($_GET['status']) : -1;
    $keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
    
    // 查询条件
    $where = "WHERE publisher_id = $publisher_id";
    if($status_filter >= 0) {
        $where .= " AND status = $status_filter";
    }
    if($keyword) {
        $where .= " AND (name LIKE '%$keyword%' OR domain LIKE '%$keyword%')";
    }
    
    // 总数
    $total = $DB->query("SELECT COUNT(*) FROM ad_publisher_websites $where")->fetchColumn();
    
    // 网站列表
    $websites = $DB->query("
        SELECT * FROM ad_publisher_websites 
        $where 
        ORDER BY created_at DESC 
        LIMIT $offset, $pagesize
    ")->fetchAll();
    
    // 状态映射
    $status_map = [
        0 => ['text' => '待审核', 'class' => 'warning'],
        1 => ['text' => '已接入', 'class' => 'success'],
        2 => ['text' => '审核驳回', 'class' => 'danger'],
        3 => ['text' => '已禁用', 'class' => 'secondary']
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
                <h5 class="mb-0"><i class="fa fa-globe"></i> 网站管理</h5>
            </div>
            <div class="col-md-6 text-right">
                <a href="website_add.php" class="btn btn-primary btn-sm">
                    <i class="fa fa-plus"></i> 添加网站
                </a>
            </div>
        </div>
    </div>
    
    <div class="card-body">
        <!-- 筛选栏 -->
        <form method="get" class="row mb-4">
            <div class="col-md-4">
                <input type="text" name="keyword" class="form-control" placeholder="搜索网站名称或域名" value="<?=$keyword?>">
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
                <a href="website_list.php" class="btn btn-secondary btn-block">
                    <i class="fa fa-redo"></i> 重置
                </a>
            </div>
        </form>
        
        <!-- 网站列表 -->
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>网站名称</th>
                        <th>域名</th>
                        <th>ICP 备案</th>
                        <th>日 UV</th>
                        <th>日 PV</th>
                        <th>状态</th>
                        <th>审核意见</th>
                        <th>创建时间</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($websites)): ?>
                        <tr>
                            <td colspan="10" class="text-center text-muted py-5">
                                <i class="fa fa-inbox fa-3x mb-3"></i>
                                <p>暂无网站，<a href="website_add.php">立即添加</a></p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($websites as $w): ?>
                            <tr>
                                <td><?=$w['id']?></td>
                                <td>
                                    <a href="website_edit.php?id=<?=$w['id']?>" class="font-weight-bold">
                                        <?=htmlspecialchars($w['name'])?>
                                    </a>
                                </td>
                                <td><a href="http://<?=$w['domain']?>" target="_blank" class="text-info"><?=htmlspecialchars($w['domain'])?></a></td>
                                <td><?=htmlspecialchars($w['icp_number'] ?: '-')?></td>
                                <td><?=number_format($w['daily_uv'])?></td>
                                <td><?=number_format($w['daily_pv'])?></td>
                                <td>
                                    <span class="badge badge-<?=$status_map[$w['status']]['class']?>">
                                        <?=$status_map[$w['status']]['text']?>
                                    </span>
                                </td>
                                <td>
                                    <small class="text-muted"><?=htmlspecialchars($w['audit_remark'] ?: '-')?></small>
                                </td>
                                <td><small><?=date('Y-m-d', strtotime($w['created_at']))?></small></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="website_edit.php?id=<?=$w['id']?>" class="btn btn-info" title="编辑">
                                            <i class="fa fa-edit"></i>
                                        </a>
                                        <a href="position_list.php?website_id=<?=$w['id']?>" class="btn btn-success" title="广告位">
                                            <i class="fa fa-th"></i>
                                        </a>
                                        <button class="btn btn-danger" onclick="deleteWebsite(<?=$w['id']?>)" title="删除">
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
                <?=$db->query("SELECT COUNT(*) as c FROM ad_publisher_websites $where")->fetch()['c'] ? 
                    \lib\plugins\page::div($total, $page, "?keyword=$keyword&status=$status_filter&") : ''?>
            </nav>
        <?php endif; ?>
    </div>
</div>

<script src="<?=cdn()?>layer/layer.js"></script>
<script>
// 删除网站
function deleteWebsite(id) {
    layer.confirm('确定要删除该网站吗？删除后该网站下的广告位也将被删除！', { icon: 3, title: '警告' }, function(index) {
        $.ajax({
            url: 'website_action.php',
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
