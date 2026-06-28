<?php
/**
 * 用户注册页面
 * 广告联盟系统 - 用户注册
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

// 加载模板
include(ROOT.'admin/admin.php');
