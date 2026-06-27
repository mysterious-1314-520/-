<?php
/* 祈福导航系统 V1.3 官方开源：https://gitee.com/qifuxitong/daohang */

include __DIR__ . "/../includes/common.php";
$title='祈福导航系统 - 友链管理';
if($islogin!=1){
    @header('Location: ./login.php');
    exit;
}
include './head.php';

// 确保友链表存在
$chk = $DB->get_row("SHOW TABLES LIKE 'web_links'");
if(empty($chk)){
    $DB->query("CREATE TABLE web_links (
        id int(11) NOT NULL AUTO_INCREMENT,
        name varchar(100) NOT NULL,
        url varchar(255) NOT NULL,
        description varchar(255) DEFAULT NULL,
        icon varchar(255) DEFAULT NULL,
        category varchar(50) DEFAULT NULL,
        email varchar(100) DEFAULT NULL,
        status int(11) NOT NULL DEFAULT 0,
        ip varchar(50) DEFAULT NULL,
        addtime int(11) NOT NULL,
        PRIMARY KEY (id)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8");
} else {
    $cols = $DB->get_results("SHOW COLUMNS FROM web_links");
    $col_names = [];
    foreach($cols as $col) $col_names[] = $col['Field'];
    if(!in_array('email', $col_names)){
        $DB->query("ALTER TABLE web_links ADD COLUMN email varchar(100) DEFAULT NULL");
    }
    if(!in_array('category', $col_names)){
        $DB->query("ALTER TABLE web_links ADD COLUMN category varchar(50) DEFAULT NULL");
    }
}

$msg = '';
$show_link_apply = isset($conf['show_link_apply']) ? $conf['show_link_apply'] : '1';

// 处理POST操作
if($_SERVER['REQUEST_METHOD']=='POST'){
    $action = @$_POST['action'];
    try {
        if($action == 'toggle_link_apply'){
            $show_link_apply = isset($_POST['show_link_apply']) && $_POST['show_link_apply']=='1' ? '1' : '0';
            saveSetting('show_link_apply', $show_link_apply);
            writeLog('修改', '友链', 0, $show_link_apply=='1' ? '开启前台友链申请入口' : '关闭前台友链申请入口');
            $CACHE->clear();
            $conf = $CACHE->update();
            $msg = '<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button>前台提交友联按钮已'.($show_link_apply=='1'?'开启':'关闭').'</div>';
        }
        if($action == 'audit'){
            $id = intval($_POST['id']);
            $status = intval($_POST['status']);
            $link = $DB->get_row("SELECT * FROM web_links WHERE id='$id'");
            if($link){
                $DB->query("UPDATE web_links SET status='$status' WHERE id='$id'");
                if($status == 1){
                    $lname = addslashes($link['name']);
                    $lurl = addslashes($link['url']);
                    $ldesc = addslashes($link['description']);
                    $licon = addslashes($link['icon']);
                    $lcat = addslashes($link['category']);
                    $DB->query("INSERT INTO web_dh (name,url,description,icon,category,active) VALUES ('$lname','$lurl','$ldesc','$licon','$lcat',1)");
                    writeLog('通过', '友链', $id, "添加站点:".$lname);
                } else {
                    writeLog('拒绝', '友链', $id, "名称:".$link['name']);
                }
                // 发送邮件通知
                $mail_sent = false;
                $mail_to = isset($conf['mail_to']) ? trim($conf['mail_to']) : '';
                $mail_user = isset($conf['mail_user']) ? trim($conf['mail_user']) : '';
                $mail_pass = isset($conf['mail_pass']) ? trim($conf['mail_pass']) : '';
                $mail_host = isset($conf['mail_host']) && !empty($conf['mail_host']) ? $conf['mail_host'] : 'smtp.qq.com';
                $mail_port = isset($conf['mail_port']) && intval($conf['mail_port']) > 0 ? intval($conf['mail_port']) : 587;
                $mail_sender = isset($conf['mail_sender']) && !empty($conf['mail_sender']) ? $conf['mail_sender'] : $mail_user;
                if($mail_user && $mail_pass && $mail_to && isset($conf['mail_enabled']) && $conf['mail_enabled']=='1'){
                    $sitename = isset($conf['sitename']) ? $conf['sitename'] : '祈福导航系统';
                    if($status == 1){
                        $subj = "[{$sitename}] 友链申请已通过";
                        $body = "亲爱的 {$link['name']}，您好！\n\n您的友链申请已通过审核！\n网站：{$link['url']}\n\n—— {$sitename}";
                    } else {
                        $subj = "[{$sitename}] 友链申请未通过";
                        $body = "亲爱的 {$link['name']}，您好！\n\n您的友链申请暂未通过。\n网站：{$link['url']}\n\n—— {$sitename}";
                    }
                    if(!empty($link['email'])){
                        @include ROOT."includes/mail.php";
                        if(function_exists('dh_send_mail')){
                            $mail_sent = @dh_send_mail($link['email'], $subj, $body, $mail_user, $mail_pass, $mail_host, $mail_port, $mail_sender);
                        }
                    }
                }
                $mail_txt = ($mail_sent && !empty($link['email'])) ? ' - 已发送邮件通知' : '';
                $msg = '<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button>操作成功！'.$mail_txt.'</div>';
            } else {
                $msg = '<div class="alert alert-danger">记录不存在</div>';
            }
        }
        if($action == 'delete'){
            $id = intval($_POST['id']);
            $DB->query("DELETE FROM web_links WHERE id='$id'");
            writeLog('删除', '友链', $id, '');
            $msg = '<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button>删除成功</div>';
        }
        if($action == 'save'){
            $id = intval($_POST['id']);
            $name = addslashes($_POST['name']);
            $url = addslashes($_POST['url']);
            $desc = addslashes($_POST['desc']);
            $icon = addslashes($_POST['icon']);
            $DB->query("UPDATE web_links SET name='$name',url='$url',description='$desc',icon='$icon' WHERE id='$id'");
            writeLog('修改', '友链', $id, "名称:$name");
            $msg = '<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button>保存成功</div>';
        }
    } catch(Exception $e) {
        $msg = '<div class="alert alert-danger">操作失败：'.$e->getMessage().'</div>';
    } catch(Error $e) {
        $msg = '<div class="alert alert-danger">操作失败：'.$e->getMessage().'</div>';
    }
}

// 处理GET删除
if(isset($_GET['del'])){
    $id = intval($_GET['del']);
    $DB->query("DELETE FROM web_links WHERE id='$id'");
    writeLog('删除', '友链', $id, '');
    $msg = '<div class="alert alert-success">删除成功</div>';
}

// 获取列表
$pending = $DB->get_results("SELECT * FROM web_links WHERE status=0 ORDER BY id DESC");
$approved = $DB->get_results("SELECT * FROM web_links WHERE status=1 ORDER BY id DESC");
$rejected = $DB->get_results("SELECT * FROM web_links WHERE status=2 ORDER BY id DESC");
?>
<style>
.edit-modal-overlay{display:none;position:fixed;top:0;left:0;right:0;bottom:0;z-index:9999;background:rgba(0,0,0,.6);align-items:center;justify-content:center}
.edit-modal-overlay.show{display:flex}
.edit-modal-box{background:#fff;border-radius:8px;width:90%;max-width:480px;box-shadow:0 20px 60px rgba(0,0,0,.3)}
.edit-modal-box .modal-header{padding:15px 20px;border-bottom:1px solid #eee;font-size:16px;font-weight:600;color:#333}
.edit-modal-box .modal-body{padding:20px}
.edit-modal-box .modal-footer{padding:12px 20px;border-top:1px solid #eee;text-align:right}
.edit-modal-box .modal-footer .btn{padding:8px 20px;border-radius:4px;cursor:pointer;font-size:14px;border:none}
.edit-modal-box .modal-footer .btn-cancel{background:#eee;color:#333}
.edit-modal-box .modal-footer .btn-submit{background:#5cb85c;color:#fff}
.link-switch-panel{border:1px solid #d9edf7;background:#f5fbff;border-radius:6px;padding:16px 18px;margin-bottom:18px}
.link-switch-panel h4{margin:0 0 8px;font-weight:700;color:#245269}
.link-switch-panel p{margin:0;color:#667;font-size:13px;line-height:1.7}
.link-switch-actions{margin-top:12px}
.link-switch-actions .btn{margin-right:8px}
</style>
<div class="container" style="padding-top:70px;">
<div class="col-xs-12 col-sm-10 col-lg-8 center-block" style="float: none;">
<?php echo $msg; ?>

<div class="link-switch-panel">
<h4>前台提交友联按钮：<?php echo $show_link_apply=='1' ? '<span class="label label-success">已开启</span>' : '<span class="label label-default">已关闭</span>'; ?></h4>
<p>关闭后，前台首页右上角不再显示“提交友联”按钮，申请弹窗也不会加载；后台仍可继续审核已有申请。</p>
<form method="post" class="link-switch-actions">
<input type="hidden" name="action" value="toggle_link_apply">
<?php if($show_link_apply=='1'): ?>
<input type="hidden" name="show_link_apply" value="0">
<button type="submit" class="btn btn-warning btn-sm">关闭前台按钮</button>
<?php else: ?>
<input type="hidden" name="show_link_apply" value="1">
<button type="submit" class="btn btn-success btn-sm">开启前台按钮</button>
<?php endif; ?>
<a href="../" target="_blank" class="btn btn-default btn-sm">查看前台</a>
</form>
</div>

<!-- 待审核 -->
<div class="panel panel-warning">
<div class="panel-heading"><h3 class="panel-title">📋 待审核申请 <span class="badge"><?php echo count($pending); ?></span></h3></div>
<?php if(empty($pending)): ?>
<div class="panel-body text-center text-muted">暂无待审核申请</div>
<?php else: ?>
<table class="table table-bordered">
<tr><th>名称</th><th>URL</th><th>分类</th><th>邮箱</th><th>描述</th><th>IP</th><th>时间</th><th style="width:140px;">操作</th></tr>
<?php foreach($pending as $l): ?>
<tr>
<td><?php echo htmlspecialchars($l['name']); ?></td>
<td><a href="<?php echo htmlspecialchars($l['url']); ?>" target="_blank">访问</a></td>
<td><span class="label label-info"><?php echo htmlspecialchars($l['category']); ?></span></td>
<td><?php echo htmlspecialchars($l['email']); ?></td>
<td><?php echo htmlspecialchars($l['description']); ?></td>
<td><small><?php echo htmlspecialchars($l['ip']); ?></small></td>
<td><small><?php echo date('m-d H:i',$l['addtime']); ?></small></td>
<td>
<form action="links.php" method="post" style="display:inline;" onsubmit="return confirm('确认通过？')">
<input type="hidden" name="action" value="audit">
<input type="hidden" name="id" value="<?php echo $l['id']; ?>">
<input type="hidden" name="status" value="1">
<button type="submit" class="btn btn-success btn-xs">通过</button>
</form>
<form action="links.php" method="post" style="display:inline;" onsubmit="return confirm('确认拒绝？')">
<input type="hidden" name="action" value="audit">
<input type="hidden" name="id" value="<?php echo $l['id']; ?>">
<input type="hidden" name="status" value="2">
<button type="submit" class="btn btn-danger btn-xs">拒绝</button>
</form>
</td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
</div>

<!-- 已通过 -->
<div class="panel panel-success">
<div class="panel-heading"><h3 class="panel-title">✅ 已通过友链 <span class="badge"><?php echo count($approved); ?></span></h3></div>
<?php if(empty($approved)): ?>
<div class="panel-body text-center text-muted">暂无已通过友链</div>
<?php else: ?>
<table class="table table-striped">
<tr><th>#</th><th>图标</th><th>名称</th><th>URL</th><th>分类</th><th>邮箱</th><th>操作</th></tr>
<?php foreach($approved as $l): ?>
<tr>
<td><?php echo $l['id']; ?></td>
<td style="font-size:20px;"><?php echo $l['icon'] ? htmlspecialchars($l['icon']) : '🔗'; ?></td>
<td><?php echo htmlspecialchars($l['name']); ?></td>
<td><a href="<?php echo htmlspecialchars($l['url']); ?>" target="_blank">访问</a></td>
<td><span class="label label-info"><?php echo htmlspecialchars($l['category']); ?></span></td>
<td><?php echo htmlspecialchars($l['email']); ?></td>
<td>
<button class="btn btn-xs btn-warning" onclick="editLink('<?php echo addslashes($l['name']); ?>','<?php echo addslashes($l['url']); ?>','<?php echo addslashes($l['description']); ?>','<?php echo addslashes($l['icon']); ?>',<?php echo $l['id']; ?>)">编辑</button>
<form action="links.php" method="post" style="display:inline;">
<input type="hidden" name="action" value="delete">
<input type="hidden" name="id" value="<?php echo $l['id']; ?>">
<button type="submit" class="btn btn-xs btn-danger" onclick="return confirm('删除？')">删除</button>
</form>
</td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
</div>

<!-- 已拒绝 -->
<?php if(!empty($rejected)): ?>
<div class="panel panel-danger">
<div class="panel-heading"><h3 class="panel-title">❌ 已拒绝 <span class="badge"><?php echo count($rejected); ?></span></h3></div>
<table class="table table-bordered">
<tr><th>名称</th><th>URL</th><th>时间</th><th>操作</th></tr>
<?php foreach($rejected as $l): ?>
<tr>
<td><?php echo htmlspecialchars($l['name']); ?></td>
<td><a href="<?php echo htmlspecialchars($l['url']); ?>" target="_blank">访问</a></td>
<td><small><?php echo date('m-d H:i',$l['addtime']); ?></small></td>
<td>
<form action="links.php" method="post" style="display:inline;" onsubmit="return confirm('重新通过？')">
<input type="hidden" name="action" value="audit">
<input type="hidden" name="id" value="<?php echo $l['id']; ?>">
<input type="hidden" name="status" value="1">
<button type="submit" class="btn btn-success btn-xs">重新通过</button>
</form>
<form action="links.php" method="post" style="display:inline;">
<input type="hidden" name="action" value="delete">
<input type="hidden" name="id" value="<?php echo $l['id']; ?>">
<button type="submit" class="btn btn-xs btn-danger" onclick="return confirm('删除？')">删除</button>
</form>
</td>
</tr>
<?php endforeach; ?>
</table>
</div>
<?php endif; ?>

</div>
</div>

<!-- 编辑弹窗 - 完全自实现 -->
<div class="edit-modal-overlay" id="editModalOverlay">
  <div class="edit-modal-box">
    <div class="modal-header">✏️ 编辑友链 <span style="float:right;cursor:pointer;font-size:20px;line-height:1;" onclick="closeEditModal()">&times;</span></div>
    <form action="links.php" method="post">
    <div class="modal-body">
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" id="eid">
    <div class="form-group"><label>网站名称</label><input type="text" name="name" id="ename" class="form-control" required></div>
    <div class="form-group"><label>URL</label><input type="url" name="url" id="eurl" class="form-control" required></div>
    <div class="form-group"><label>描述</label><input type="text" name="desc" id="edesc" class="form-control"></div>
    <div class="form-group"><label>图标（emoji或文字）</label><input type="text" name="icon" id="eicon" class="form-control" placeholder="如 ⭐"></div>
    </div>
    <div class="modal-footer">
    <button type="button" class="btn btn-cancel" onclick="closeEditModal()">取消</button>
    <button type="submit" class="btn btn-submit">保存</button>
    </div>
    </form>
  </div>
</div>

<script>
function editLink(name,url,desc,icon,id){
    document.getElementById('eid').value=id;
    document.getElementById('ename').value=name;
    document.getElementById('eurl').value=url;
    document.getElementById('edesc').value=desc;
    document.getElementById('eicon').value=icon;
    document.getElementById('editModalOverlay').classList.add('show');
}
function closeEditModal(){
    document.getElementById('editModalOverlay').classList.remove('show');
}
document.getElementById('editModalOverlay').addEventListener('click',function(e){
    if(e.target===this) closeEditModal();
});
</script>

</body></html>
