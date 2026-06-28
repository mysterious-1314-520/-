<?php
/**
 * 用户登录页面
 * 广告联盟系统 - 用户认证
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

// 处理登录提交
if(isset($_POST['submit'])) {
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);
    $clientip = real_ip();
    
    // 验证输入
    if(empty($phone) || empty($password)) {
        $error = '请输入手机号和密码';
    }
    else {
        try {
            // 查询用户
            $stmt = $DB->prepare("SELECT * FROM ad_users WHERE phone = ? LIMIT 1");
            $stmt->execute([$phone]);
            $user = $stmt->fetch();
            
            if(!$user) {
                $error = '用户不存在';
                // 记录失败尝试
                record_login_attempt($phone);
            }
            elseif($user['status'] == 0) {
                $error = '账户已被禁用，请联系客服';
            }
            elseif(!password_verify($password, $user['password_hash'])) {
                $error = '密码错误';
                record_login_attempt($phone);
            }
            else {
                // 登录成功
                // 检查是否被锁定
                $lock_key = 'login_lock_'.$phone;
                if($_SESSION[$lock_key] >= 5) {
                    $error = '账户已被锁定 30 分钟，请稍后再试';
                }
                else {
                    // 设置会话
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_phone'] = $user['phone'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['user_company'] = $user['company_name'];
                    
                    // 清除失败计数
                    unset($_SESSION['login_fail_'.$phone]);
                    unset($_SESSION['login_lock_'.$phone]);
                    
                    // 更新最后登录
                    $stmt = $DB->prepare("
                        UPDATE ad_users 
                        SET last_login_time = NOW(), last_login_ip = ? 
                        WHERE id = ?
                    ");
                    $stmt->execute([$clientip, $user['id']]);
                    
                    // 记录操作日志
                    $log_sql = "INSERT INTO ad_operation_logs (user_id, action, module, ip) VALUES ('".$user['id']."', 'login', 'user', '".$DB->escape($clientip)."')";
                    $DB->query($log_sql);
                    
                    // 跳转到对应后台
                    switch($user['role']) {
                        case 'advertiser':
                            header('Location: advertiser/index.php');
                            break;
                        case 'publisher':
                            header('Location: publisher/index.php');
                            break;
                        case 'admin':
                        case 'auditor':
                            header('Location: index.php');
                            break;
                        default:
                            header('Location: index.php');
                    }
                    exit;
                }
            }
        }
        catch(Exception $e) {
            $error = '登录失败：'.$e->getMessage();
        }
    }
}

// 记录登录失败尝试
function record_login_attempt($phone) {
    global $_SESSION;
    $fail_key = 'login_fail_' . $phone;
    $lock_key = 'login_lock_' . $phone;
    
    if($_SESSION[$lock_key]) {
        return;
    }
    
    $_SESSION[$fail_key] = isset($_SESSION[$fail_key]) ? $_SESSION[$fail_key] + 1 : 1;
    
    if($_SESSION[$fail_key] >= 5) {
        $_SESSION[$lock_key] = time() + 30 * 60;
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>广告联盟登录 - 祈福导航系统</title>
<link rel="stylesheet" href="https://cdn.bootcss.com/bootstrap/3.3.7/css/bootstrap.min.css">
<link rel="stylesheet" href="saiadmin-skin.css?v=1388">
<style>
.login-wrapper{display:flex;justify-content:center;align-items:center;min-height:100vh;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);padding:20px;}
.login-box{background:#fff;border-radius:8px;box-shadow:0 10px 40px rgba(0,0,0,.2);padding:40px;max-width:400px;width:100%;}
.login-title{text-align:center;margin-bottom:30px;color:#333;font-size:24px;font-weight:600;}
.login-form .form-group{margin-bottom:20px;}
.login-form .form-control{height:44px;border-radius:4px;border-color:#ddd;font-size:14px;}
.login-form .btn{height:44px;font-size:16px;border-radius:4px;}
.login-footer{text-align:center;margin-top:20px;color:#666;font-size:14px;}
.login-footer a{color:#667eea;text-decoration:none;}
.error-msg{background:#f8d7da;color:#721c24;padding:10px 15px;border-radius:4px;margin-bottom:20px;border:1px solid #f5c6cb;font-size:14px;}
</style>
</head>
<body>
<div class="login-wrapper">
  <div class="login-box">
    <div class="login-title">祈福广告联盟</div>
    <?php if(!empty($error)): ?>
    <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <form class="login-form" method="post">
      <div class="form-group">
        <input type="text" name="phone" class="form-control" placeholder="请输入手机号" required maxlength="11">
      </div>
      <div class="form-group">
        <input type="password" name="password" class="form-control" placeholder="请输入密码" required>
      </div>
      <div class="form-group">
        <label><input type="checkbox" name="remember"> 记住我</label>
      </div>
      <button type="submit" name="submit" class="btn btn-primary btn-block">登录</button>
    </form>
    <div class="login-footer">
      <p>还没有账号？<a href="ad_register.php">立即注册</a></p>
      <p><a href="../">返回首页</a></p>
    </div>
  </div>
</div>
</body>
</html>
