<?php
/* 祈福导航系统 V1.3 官方开源：https://gitee.com/qifuxitong/daohang */
include __DIR__ . "/../includes/common.php";
$title='祈福导航系统 - 数据备份';
if($islogin!=1){
    @header('Location: ./login.php');
    exit;
}
include './head.php';

$msg = '';
$action = isset($_GET['action']) ? $_GET['action'] : '';

// 备份记录表
$bk_chk = $DB->get_row("SHOW TABLES LIKE 'web_backup'");
if(empty($bk_chk)){
    $DB->query("CREATE TABLE web_backup (
        id int(11) NOT NULL AUTO_INCREMENT,
        filename varchar(100) NOT NULL,
        size int(11) NOT NULL,
        addtime int(11) NOT NULL,
        PRIMARY KEY (id)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8");
}

// 创建备份
if($action == 'create'){
    $tables = ['web_config','web_dh','web_category','web_log','web_backup'];
    $sql = "-- 祈福导航系统 V1.3 数据库备份\n-- 官方开源: https://gitee.com/qifuxitong/daohang\n-- 备份时间: ".date('Y-m-d H:i:s')."\n\n";
    foreach($tables as $table){
        $t_chk = $DB->get_row("SHOW TABLES LIKE '$table'");
        if(empty($t_chk)) continue;
        // CREATE TABLE
        $create = $DB->get_row("SHOW CREATE TABLE `$table`");
        if(!empty($create)) {
            $create_sql = array_values(get_object_vars($create))[1];
            $sql .= "DROP TABLE IF EXISTS `$table`;\n";
            $sql .= $create_sql.";\n\n";
        }
        // INSERT DATA
        $rows = $DB->get_results("SELECT * FROM `$table`");
        if(!empty($rows)){
            $fields = array_keys($rows[0]);
            $field_str = '`'.implode('`,`',$fields).'`';
            foreach($rows as $row){
                $vals = [];
                foreach($row as $v){
                    $vals[] = "'".addslashes($v)."'";
                }
                $sql .= "INSERT INTO `$table` ($field_str) VALUES (".implode(',',$vals).");\n";
            }
            $sql .= "\n";
        }
    }
    $filename = 'backup_'.date('Ymd_His').'_v12.sql';
    $bkdir = ROOT.'backup/';
    if(!is_dir($bkdir)) @mkdir($bkdir, 0755, true);
    file_put_contents($bkdir.$filename, $sql);
    $fsize = filesize($bkdir.$filename);
    $DB->query("INSERT INTO web_backup (filename,size,addtime) VALUES ('$filename','$fsize','".time()."')");
    writeLog('备份', '数据库', 0, "生成:$filename");
    header("Location: ./backup.php?created=1");
    exit;
}

// 删除备份
if($action == 'del' && isset($_GET['id'])){
    $id = intval($_GET['id']);
    $row = $DB->get_row("SELECT * FROM web_backup WHERE id='$id'");
    if($row){
        $filepath = ROOT.'backup/'.$row['filename'];
        if(file_exists($filepath)) @unlink($filepath);
        $DB->query("DELETE FROM web_backup WHERE id='$id'");
        $msg = '<div class="alert alert-success">备份文件已删除！</div>';
    }
}

// 下载备份
if($action == 'download' && isset($_GET['id'])){
    $id = intval($_GET['id']);
    $row = $DB->get_row("SELECT * FROM web_backup WHERE id='$id'");
    if($row){
        $filepath = ROOT.'backup/'.$row['filename'];
        if(file_exists($filepath)){
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename='.$row['filename']);
            readfile($filepath);
            exit;
        }
    }
}

$backups = $DB->get_results("SELECT * FROM web_backup ORDER BY id DESC");
if(isset($_GET['created'])) $msg = '<div class="alert alert-success">备份创建成功！</div>';
?>
<div class="container" style="padding-top:70px;">
<div class="col-xs-12 col-sm-10 col-lg-8 center-block" style="float: none;">
<?php echo $msg; ?>

<div class="panel panel-primary">
<div class="panel-heading">
<h3 class="panel-title">💾 数据库备份与恢复</h3>
</div>
<div class="panel-body">
<div class="alert alert-info">
<p><strong>⚠️ 备份说明：</strong></p>
<ul style="margin:0;padding-left:20px;">
<li>备份会导出 web_config、web_dh、web_category、web_log 表的数据</li>
<li>备份文件保存在 <code>backup/</code> 目录</li>
<li>建议定期备份，重要操作前先备份</li>
</ul>
</div>
<div style="margin:15px 0;">
<a href="./backup.php?action=create" class="btn btn-primary btn-lg" onclick="return confirm('确认创建数据库备份？')">
💾 创建新备份
</a>
<a href="./restore.php" class="btn btn-warning btn-lg">📥 导入/恢复数据</a>
</div>
</div>
</div>

<div class="panel panel-default">
<div class="panel-heading">
<h3 class="panel-title">📁 备份文件列表</h3>
</div>
<table class="table table-striped">
<thead><tr><th>文件名</th><th>大小</th><th>备份时间</th><th>操作</th></tr></thead>
<tbody>
<?php if(empty($backups)): ?>
<tr><td colspan="4" class="text-center text-muted">暂无备份文件</td></tr>
<?php else: foreach($backups as $bk): ?>
<tr>
<td><span class="glyphicon glyphicon-file"></span> <?php echo $bk['filename']; ?></td>
<td><?php echo round($bk['size']/1024, 1); ?> KB</td>
<td><?php echo date('Y-m-d H:i:s', $bk['addtime']); ?></td>
<td>
<a href="./backup.php?action=download&id=<?php echo $bk['id']; ?>" class="btn btn-xs btn-primary">下载</a>
<a href="./backup.php?action=del&id=<?php echo $bk['id']; ?>" class="btn btn-xs btn-danger" onclick="return confirm('确认删除该备份？')">删除</a>
</td>
</tr>
<?php endforeach; endif; ?>
</tbody>
</table>
</div>

<div class="text-center" style="margin-top:20px;">
<a href="./" class="btn btn-default">返回后台首页</a>
</div>
</div>
</div>
