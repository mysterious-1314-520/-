<?php
/**
 * 用户注册页面
 * 广告联盟系统 - 用户注册
 */

define('IN_CRONLITE', true);
define('SYSTEM_ROOT', dirname(__FILE__, 2).'/includes/');
define('ROOT', dirname(__FILE__, 2).'/');

require_once(ROOT.'includes/common.php');

// 检查是否已登录
if(isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

// 处理注册提交
if(isset($_POST['submit'])) {
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];
    $sms_code = trim($_POST['sms_code']);
    $role = $_POST['role']; // advertiser 或 publisher
    $company_name = trim($_POST['company_name']);
    
    // 验证手机号格式
    if(!preg_match('/^1[3-9]\d{9}$/', $phone)) {
        $error = '请输入正确的手机号码';
    }
    // 验证密码
    elseif(strlen($password) < 6 || strlen($password) > 20) {
        $error = '密码长度必须在 6-20 位之间';
    }
    // 验证密码一致性
    elseif($password !== $password_confirm) {
        $error = '两次输入的密码不一致';
    }
    // 验证角色
    elseif(!in_array($role, ['advertiser', 'publisher'])) {
        $error = '请选择正确的用户角色';
    }
    // 验证短信验证码 (简化版，实际应调用短信服务商 API)
    elseif($sms_code !== $_SESSION['sms_code_'.$phone]) {
        $error = '短信验证码错误';
    }
    else {
        try {
            // 检查手机号是否已注册
            $stmt = $DB->prepare("SELECT id FROM ad_users WHERE phone = ?");
            $stmt->execute([$phone]);
            if($stmt->fetch()) {
                $error = '该手机号已被注册';
            }
            else {
                // 生成 bcrypt 密码哈希
                $password_hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                
                // 插入用户
                $stmt = $DB->prepare("
                    INSERT INTO ad_users (phone, password_hash, role, company_name, status) 
                    VALUES (?, ?, ?, ?, 1)
                ");
                $stmt->execute([$phone, $password_hash, $role, $company_name]);
                
                $user_id = $DB->lastInsertId();
                
                // 创建对应的广告主/网站主账户
                if($role === 'advertiser') {
                    $stmt = $DB->prepare("
                        INSERT INTO ad_advertisers (id, user_id) VALUES (?, ?)
                    ");
                    $stmt->execute([$user_id, $user_id]);
                    
                    // 创建账户
                    $stmt = $DB->prepare("
                        INSERT INTO ad_accounts (user_id, account_type) VALUES (?, 'advertiser')
                    ");
                    $stmt->execute([$user_id]);
                }
                else {
                    $stmt = $DB->prepare("
                        INSERT INTO ad_publishers (id, user_id) VALUES (?, ?)
                    ");
                    $stmt->execute([$user_id, $user_id]);
                    
                    // 创建账户
                    $stmt = $DB->prepare("
                        INSERT INTO ad_accounts (user_id, account_type) VALUES (?, 'publisher')
                    ");
                    $stmt->execute([$user_id]);
                }
                
                // 记录操作日志
                $DB->query("INSERT INTO ad_operation_logs (user_id, action, module, ip) VALUES (?, 'register', 'user', '".$clientip."')");
                
                $success = '注册成功！即将跳转到登录页面...';
                header('refresh: 2; url=login.php');
            }
        }
        catch(Exception $e) {
            $error = '注册失败：'.$e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>广告联盟注册 - 祈福导航系统</title>
<link rel="stylesheet" href="https://cdn.bootcss.com/bootstrap/3.3.7/css/bootstrap.min.css">
<link rel="stylesheet" href="saiadmin-skin.css?v=1388">
<style>
.login-wrapper{display:flex;justify-content:center;align-items:center;min-height:100vh;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);padding:20px;}
.login-box{background:#fff;border-radius:8px;box-shadow:0 10px 40px rgba(0,0,0,.2);padding:40px;max-width:450px;width:100%;}
.login-title{text-align:center;margin-bottom:30px;color:#333;font-size:24px;font-weight:600;}
.login-form .form-group{margin-bottom:20px;}
.login-form .form-control{height:44px;border-radius:4px;border-color:#ddd;font-size:14px;}
.login-form .btn{height:44px;font-size:16px;border-radius:4px;}
.login-footer{text-align:center;margin-top:20px;color:#666;font-size:14px;}
.login-footer a{color:#667eea;text-decoration:none;}
.error-msg{background:#f8d7da;color:#721c24;padding:10px 15px;border-radius:4px;margin-bottom:20px;border:1px solid #f5c6cb;font-size:14px;}
.success-msg{background:#d4edda;color:#155724;padding:10px 15px;border-radius:4px;margin-bottom:20px;border:1px solid #c3e6cb;font-size:14px;}
.role-selector{display:flex;gap:15px;margin-bottom:20px;}
.role-option{flex:1;position:relative;}
.role-option input{display:none;}
.role-option label{display:block;padding:15px;text-align:center;border:2px solid #ddd;border-radius:4px;cursor:pointer;font-weight:500;transition:.2s;}
.role-option input:checked+label{border-color:#667eea;background:rgba(102,126,234,.05);color:#667eea;}
</style>
</head>
<body>
<div class="login-wrapper">
  <div class="login-box">
    <div class="login-title">广告联盟注册</div>
    <?php if(!empty($error)): ?>
    <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if(!empty($success)): ?>
    <div class="success-msg"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <form class="login-form" method="post">
      <div class="form-group">
        <input type="text" name="phone" class="form-control" placeholder="请输入手机号" required maxlength="11" pattern="1[3-9]\d{9}">
      </div>
      <div class="form-group">
        <input type="password" name="password" class="form-control" placeholder="请输入密码（6-20 位）" required minlength="6" maxlength="20">
      </div>
      <div class="form-group">
        <input type="password" name="password_confirm" class="form-control" placeholder="请确认密码" required>
      </div>
      <div class="form-group">
        <label>用户角色</label>
        <div class="role-selector">
          <div class="role-option">
            <input type="radio" name="role" value="advertiser" id="role_advertiser" checked>
            <label for="role_advertiser">广告主</label>
          </div>
          <div class="role-option">
            <input type="radio" name="role" value="publisher" id="role_publisher">
            <label for="role_publisher">网站主</label>
          </div>
        </div>
      </div>
      <div class="form-group">
        <input type="text" name="company_name" class="form-control" placeholder="公司/组织名称（选填）">
      </div>
      <button type="submit" name="submit" class="btn btn-primary btn-block">立即注册</button>
    </form>
    <div class="login-footer">
      <p>已有账号？<a href="ad_login.php">立即登录</a></p>
      <p><a href="../">返回首页</a></p>
    </div>
  </div>
</div>
</body>
</html>
