<?php
/**
 * 用户登录页面
 * 广告联盟系统 - 用户认证
 */

define('IN_CRONLITE', true);
define('SYSTEM_ROOT', dirname(__FILE__).'/');
define('ROOT', dirname(SYSTEM_ROOT).'/');

require_once(ROOT.'includes/common.php');
require_once(ROOT.'includes/db.class.php');

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
                    $DB->query("INSERT INTO ad_operation_logs (user_id, action, module, ip) VALUES (?, 'login', 'user', '".$clientip."')");
                    
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

/**
 * 记录登录失败尝试
 */
function record_login_attempt($phone) {
    global $_SESSION;
    $fail_key = 'login_fail_' . $phone;
    $lock_key = 'login_lock_' . $phone;
    
    // 检查是否已锁定
    if($_SESSION[$lock_key]) {
        return;
    }
    
    // 增加失败计数
    $_SESSION[$fail_key] = isset($_SESSION[$fail_key]) ? $_SESSION[$fail_key] + 1 : 1;
    
    // 达到 5 次失败，锁定 30 分钟
    if($_SESSION[$fail_key] >= 5) {
        $_SESSION[$lock_key] = time() + 30 * 60; // 30 分钟后过期
    }
}

// 加载模板
include(ROOT.'admin/admin.php');
