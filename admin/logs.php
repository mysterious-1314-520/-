<?php
/* 祈福导航系统 V1.3 官方开源：https://gitee.com/qifuxitong/daohang */

include __DIR__ . "/../includes/common.php";
$title='祈福导航系统 - 操作日志';
if($islogin!=1){
    @header('Location: ./login.php');
    exit;
}
include './head.php';

// 确保日志表存在
$log_chk = $DB->get_row("SHOW TABLES LIKE 'web_log'");
if(empty($log_chk)){
    $DB->query("CREATE TABLE web_log (
        id int(11) NOT NULL AUTO_INCREMENT,
        action varchar(50) NOT NULL,
        target varchar(50) NOT NULL,
        target_id int(11) DEFAULT NULL,
        detail varchar(255) DEFAULT NULL,
        ip varchar(50) DEFAULT NULL,
        addtime int(11) NOT NULL,
        PRIMARY KEY (id),
        KEY action (action),
        KEY addtime (addtime)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8");
}

// 分页
$page = max(1, intval(@$_GET['page']));
$perpage = 20;
$offset = ($page-1)*$perpage;

// 筛选
$filter_action = isset($_GET['action']) ? trim($_GET['action']) : '';
$filter_target = isset($_GET['target']) ? trim($_GET['target']) : '';

$where = "1=1";
if($filter_action) $where .= " AND action='".addslashes($filter_action)."'";
if($filter_target) $where .= " AND target='".addslashes($filter_target)."'";

$total = $DB->count("SELECT count(*) FROM web_log WHERE $where");
$logs = $DB->get_results("SELECT * FROM web_log WHERE $where ORDER BY id DESC LIMIT $offset,$perpage");
$pages = ceil($total/$perpage);

// 统计
$action_stats = $DB->get_results("SELECT action, count(*) as cnt FROM web_log GROUP BY action ORDER BY cnt DESC");

// 清除日志
if(isset($_GET['clear'])){
    $DB->query("TRUNCATE TABLE web_log");
    header("Location: ./logs.php");
    exit;
}
?>
<style>
.log-action{font-weight:bold}
.action-add{color:#27ae60}
.action-edit{color:#2980b9}
.action-delete{color:#c0392b}
.action-batch{color:#8e44ad}
.log-table{font-size:13px}
.log-time{color:#999;font-size:12px}
</style>
<div class="container" style="padding-top:70px;">
<div class="col-xs-12 col-sm-10 col-lg-10 center-block" style="float: none;">

<div class="panel panel-default">
<div class="panel-heading">
<h3 class="panel-title">📋 操作日志 <a href="./logs.php?clear=1" class="btn btn-xs btn-danger pull-right" onclick="return confirm('确认清空所有日志？')">清空日志</a></h3>
</div>
<div class="panel-body">
<div class="row" style="margin-bottom:15px;">
<div class="col-sm-8">
<form method="get" class="form-inline">
<select name="action" class="form-control">
<option value="">全部操作</option>
<option value="添加" <?php echo $filter_action=='添加'?'selected':''; ?>>添加</option>
<option value="修改" <?php echo $filter_action=='修改'?'selected':''; ?>>修改</option>
<option value="删除" <?php echo $filter_action=='删除'?'selected':''; ?>>删除</option>
<option value="批量删除" <?php echo $filter_action=='批量删除'?'selected':''; ?>>批量删除</option>
<option value="批量显示" <?php echo $filter_action=='批量显示'?'selected':''; ?>>批量显示</option>
<option value="批量隐藏" <?php echo $filter_action=='批量隐藏'?'selected':''; ?>>批量隐藏</option>
<option value="批量移动" <?php echo $filter_action=='批量移动'?'selected':''; ?>>批量移动</option>
</select>
<select name="target" class="form-control">
<option value="">全部对象</option>
<option value="站点" <?php echo $filter_target=='站点'?'selected':''; ?>>站点</option>
<option value="分类" <?php echo $filter_target=='分类'?'selected':''; ?>>分类</option>
<option value="设置" <?php echo $filter_target=='设置'?'selected':''; ?>>设置</option>
</select>
<button type="submit" class="btn btn-primary">筛选</button>
<a href="./logs.php" class="btn btn-default">重置</a>
<span class="help-block" style="display:inline;margin-left:10px;">共 <?php echo $total; ?> 条记录</span>
</form>
</div>
<div class="col-sm-4 text-right">
<?php if($pages>1): ?>
<ul class="pagination" style="margin:0;">
<?php if($page>1): ?><li><a href="?page=<?php echo $page-1; ?>&action=<?php echo urlencode($filter_action); ?>&target=<?php echo urlencode($filter_target); ?>">&laquo;</a></li><?php endif; ?>
<?php for($i=max(1,$page-2); $i<=min($pages,$page+2); $i++): ?>
<li <?php echo $i==$page?'class="active"':''; ?>><a href="?page=<?php echo $i; ?>&action=<?php echo urlencode($filter_action); ?>&target=<?php echo urlencode($filter_target); ?>"><?php echo $i; ?></a></li>
<?php endfor; ?>
<?php if($page<$pages): ?><li><a href="?page=<?php echo $page+1; ?>&action=<?php echo urlencode($filter_action); ?>&target=<?php echo urlencode($filter_target); ?>">&raquo;</a></li><?php endif; ?>
</ul>
<?php endif; ?>
</div>
</div>

<table class="table table-striped table-hover log-table">
<thead><tr><th>时间</th><th>操作</th><th>对象</th><th>详情</th><th>IP</th></tr></thead>
<tbody>
<?php if(empty($logs)): ?>
<tr><td colspan="5" class="text-center text-muted">暂无日志记录</td></tr>
<?php else: foreach($logs as $log):
    $aclass = 'action-'.mb_substr($log['action'],0,2,'utf-8');
    $time = date('Y-m-d H:i', $log['addtime']);
?>
<tr>
<td class="log-time"><?php echo $time; ?></td>
<td><span class="log-action <?php echo $aclass; ?>"><?php echo $log['action']; ?></span></td>
<td><span class="label label-default"><?php echo $log['target']; ?></span><?php if($log['target_id']) echo ' #'.$log['target_id']; ?></td>
<td><?php echo htmlspecialchars($log['detail']); ?></td>
<td><small><?php echo $log['ip']; ?></small></td>
</tr>
<?php endforeach; endif; ?>
</tbody>
</table>

<?php if($pages>1): ?>
<ul class="pagination">
<?php if($page>1): ?><li><a href="?page=<?php echo $page-1; ?>&action=<?php echo urlencode($filter_action); ?>&target=<?php echo urlencode($filter_target); ?>">&laquo;</a></li><?php endif; ?>
<?php for($i=max(1,$page-2); $i<=min($pages,$page+2); $i++): ?>
<li <?php echo $i==$page?'class="active"':''; ?>><a href="?page=<?php echo $i; ?>&action=<?php echo urlencode($filter_action); ?>&target=<?php echo urlencode($filter_target); ?>"><?php echo $i; ?></a></li>
<?php endfor; ?>
<?php if($page<$pages): ?><li><a href="?page=<?php echo $page+1; ?>&action=<?php echo urlencode($filter_action); ?>&target=<?php echo urlencode($filter_target); ?>">&raquo;</a></li><?php endif; ?>
</ul>
<?php endif; ?>
</div>
</div>

</div>
</div>
