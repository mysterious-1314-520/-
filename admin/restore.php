<?php
/* 祈福导航系统 V1.3 官方开源：https://gitee.com/qifuxitong/daohang */

include __DIR__ . "/../includes/common.php";
$title='祈福导航系统 - 恢复数据';
if($islogin!=1){
    @header('Location: ./login.php');
    exit;
}
include './head.php';

$msg = '';
$error = '';

if($_SERVER['REQUEST_METHOD']=='POST' && isset($_FILES['sqlfile'])){
    $file = $_FILES['sqlfile'];
    if($file['error']!=0){
        $error = '文件上传失败，错误码：'.$file['error'];
    }elseif($file['size']>10*1024*1024){
        $error = '文件大小不能超过10MB';
    }else{
        $content = file_get_contents($file['tmp_name']);
        if(strlen($content)<10){
            $error = '文件内容为空或读取失败';
        }else{
            // 逐条执行SQL
            $sqls = preg_split('/;\s*[\r\n]/', $content);
            $success = 0; $failed = 0; $err_log = '';
            foreach($sqls as $sql){
                $sql = trim($sql);
                if(empty($sql) || substr($sql,0,2)=='--') continue;
                if($DB->query($sql)){
                    $success++;
                }else{
                    $failed++;
                    if($failed<=5) $err_log .= '<div class="text-danger">执行失败: '.htmlspecialchars(mb_substr($sql,0,80)).'</div>';
                }
            }
            $CACHE->clear();
            if($failed==0){
                $msg = '<div class="alert alert-success">恢复成功！共执行 '.$success.' 条SQL语句。</div>';
                writeLog('恢复', '数据库', 0, "成功:$success条");
            }else{
                $msg = '<div class="alert alert-warning">部分恢复完成。成功:'.$success.'条，失败:'.$failed.'条<br>'.$err_log.'</div>';
            }
        }
    }
}
?>
<div class="container" style="padding-top:70px;">
<div class="col-xs-12 col-sm-10 col-lg-8 center-block" style="float: none;">

<?php if($error) echo '<div class="alert alert-danger">'.$error.'</div>'; ?>
<?php echo $msg; ?>

<div class="panel panel-danger">
<div class="panel-heading"><h3 class="panel-title">⚠️ 恢复数据</h3></div>
<div class="panel-body">
<div class="alert alert-warning">
恢复操作会覆盖现有数据！建议恢复前先 <a href="./backup.php?action=create" class="alert-link">创建备份</a>。
</div>
<form method="post" enctype="multipart/form-data">
<div class="form-group">
<label>上传 SQL 备份文件</label>
<input type="file" name="sqlfile" accept=".sql" class="form-control" required>
</div>
<div class="form-group">
<label>或者直接粘贴 SQL 内容</label>
<textarea name="sql_content" class="form-control" rows="10" placeholder="将SQL文件内容粘贴在此处（可选）"></textarea>
</div>
<button type="submit" class="btn btn-danger btn-lg" onclick="return confirm('警告：恢复操作会覆盖现有数据！是否继续？')">
⚠️ 执行恢复
</button>
<a href="./backup.php" class="btn btn-default">返回备份管理</a>
</form>
</div>
</div>

<div class="panel panel-info">
<div class="panel-heading"><h3 class="panel-title">📝 恢复说明</h3></div>
<div class="panel-body">
<ol style="padding-left:20px;">
<li>恢复前请先创建新备份，防止数据丢失</li>
<li>上传的 .sql 文件必须是本系统导出的备份格式</li>
<li>恢复过程可能需要较长时间，请耐心等待页面加载完成</li>
<li>如果恢复后出现异常，请重新上传备份文件再次恢复</li>
</ol>
</div>
</div>

</div>
</div>
