<?php
/* 祈福导航系统 V1.3 官方开源：https://gitee.com/qifuxitong/daohang */

include __DIR__ . "/../includes/common.php";
$title='祈福导航系统 - 修改密码';
if($islogin!=1){
    @header('Location: ./login.php');
    exit;
}
include './head.php';

$msg = '';
if($_SERVER['REQUEST_METHOD']=='POST'){
    $old = $_POST['oldpwd'];
    $new1 = $_POST['newpwd'];
    $new2 = $_POST['newpwd2'];
    if(empty($old) || empty($new1) || empty($new2)){
        $msg = '<div class="alert alert-danger">请填写所有字段！</div>';
    }elseif($new1 !== $new2){
        $msg = '<div class="alert alert-danger">两次输入的新密码不一致！</div>';
    }elseif($old !== $conf['admin_pwd']){
        $msg = '<div class="alert alert-danger">原密码错误！</div>';
    }elseif(strlen($new1)<4){
        $msg = '<div class="alert alert-danger">新密码长度不能少于4位！</div>';
    }else{
        saveSetting('admin_pwd', $new1);
        $CACHE->clear();
        writeLog('修改', '密码', 0, '管理员修改登录密码');
        $msg = '<div class="alert alert-success">密码修改成功！请使用新密码重新登录。</div>';
        // 退出登录
        setcookie("admin_token", "", time()-3600);
    }
}
?>
<style>
.pwd-box{max-width:500px;margin:0 auto}
</style>
<div class="container" style="padding-top:70px;">
<div class="col-xs-12 col-sm-8 center-block pwd-box" style="float: none;">
<?php echo $msg; ?>

<div class="panel panel-primary">
<div class="panel-heading">
<h3 class="panel-title">🔐 修改登录密码</h3>
</div>
<div class="panel-body">
<form method="post">
<div class="form-group">
<label>原密码</label>
<input type="password" name="oldpwd" class="form-control" required>
</div>
<div class="form-group">
<label>新密码</label>
<input type="password" name="newpwd" class="form-control" required placeholder="至少4位">
</div>
<div class="form-group">
<label>确认新密码</label>
<input type="password" name="newpwd2" class="form-control" required placeholder="再次输入新密码">
</div>
<div class="form-group">
<button type="submit" class="btn btn-primary btn-block">确认修改</button>
</div>
</form>
</div>
</div>

<div class="text-center">
<a href="./" class="btn btn-default">返回后台首页</a>
</div>
</div>
</div>
