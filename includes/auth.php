<?php
/**
 * 认证与权限控制中间件
 * 广告联盟系统 - 用户认证、RBAC 权限控制
 */

if(!defined('IN_CRONLITE')) exit;

/**
 * 获取系统目录 URL
 * @return string
 */
function sysdir() {
    return '/admin/';
}

/**
 * 检查用户是否已登录
 * @return bool
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * 要求用户登录，未登录则跳转
 * @param string $redirect_url 登录成功后返回的 URL
 */
function require_login($redirect_url = '') {
    if(!is_logged_in()) {
        $_SESSION['login_redirect'] = $redirect_url ?: $_SERVER['REQUEST_URI'];
        header('Location: '.sysdir().'admin/ad_login.php');
        exit;
    }
}

/**
 * 获取当前登录用户 ID
 * @return int|null
 */
function get_current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * 获取当前登录用户角色
 * @return string|null
 */
function get_current_user_role() {
    return $_SESSION['user_role'] ?? null;
}

/**
 * 检查用户角色
 * @param string|array $allowed_roles 允许的角色列表
 * @return bool
 */
function check_role($allowed_roles) {
    if(!is_logged_in()) {
        return false;
    }
    
    $current_role = get_current_user_role();
    
    if(is_array($allowed_roles)) {
        return in_array($current_role, $allowed_roles);
    }
    
    return $current_role === $allowed_roles;
}

/**
 * 要求特定角色，不符合则拒绝访问
 * @param string|array $allowed_roles 允许的角色列表
 * @throws Exception
 */
function require_role($allowed_roles) {
    if(!check_role($allowed_roles)) {
        $roles = is_array($allowed_roles) ? implode('/', $allowed_roles) : $allowed_roles;
        http_response_code(403);
        die('无权访问：需要角色 ['.$roles.']');
    }
}

/**
 * 检查用户是否有某项权限
 * @param string $permission 权限标识
 * @return bool
 */
function check_permission($permission) {
    if(!is_logged_in()) {
        return false;
    }
    
    global $DB;
    $user_id = get_current_user_id();
    
    // 管理员拥有所有权限
    if(get_current_user_role() === 'admin') {
        return true;
    }
    
    try {
        // 查询用户权限
        static $user_permissions = [];
        
        if(!isset($user_permissions[$user_id])) {
            // 获取角色权限
            $stmt = $DB->prepare("
                SELECT p.permission 
                FROM ad_role_permissions rp
                JOIN ad_permissions p ON rp.permission_id = p.id
                WHERE rp.role = ?
            ");
            $stmt->execute([get_current_user_role()]);
            $user_permissions[$user_id] = array_column($stmt->fetchAll(), 'permission');
        }
        
        return in_array($permission, $user_permissions[$user_id]);
    }
    catch(Exception $e) {
        // 出错时返回 false
        return false;
    }
}

/**
 * 要求特定权限，无权限则拒绝访问
 * @param string $permission 权限标识
 * @throws Exception
 */
function require_permission($permission) {
    if(!check_permission($permission)) {
        http_response_code(403);
        die('无权访问：缺少权限 ['.$permission.']');
    }
}

/**
 * 检查数据权限 (用户只能访问自己的数据)
 * @param string $table 表名
 * @param int $object_id 对象 ID
 * @param string $owner_field 所有者字段名
 * @return bool
 */
function check_data_permission($table, $object_id, $owner_field = 'user_id') {
    if(!is_logged_in()) {
        return false;
    }
    
    global $DB;
    $user_id = get_current_user_id();
    $role = get_current_user_role();
    
    // 管理员可以访问所有数据
    if($role === 'admin') {
        return true;
    }
    
    try {
        $stmt = $DB->prepare("SELECT $owner_field FROM $table WHERE id = ? LIMIT 1");
        $stmt->execute([$object_id]);
        $result = $stmt->fetch();
        
        if(!$result) {
            return false;
        }
        
        // 广告主和网站主只能访问自己的数据
        if($role === 'advertiser') {
            // 需要检查是否属于该广告主
            $stmt = $DB->prepare("
                SELECT a.id FROM ad_advertisers a 
                JOIN $table t ON a.id = t.{$owner_field} 
                WHERE a.user_id = ? AND t.id = ?
            ");
            $stmt->execute([$user_id, $object_id]);
            return $stmt->fetch() !== false;
        }
        elseif($role === 'publisher') {
            $stmt = $DB->prepare("
                SELECT p.id FROM ad_publishers p 
                JOIN $table t ON p.id = t.{$owner_field} 
                WHERE p.user_id = ? AND t.id = ?
            ");
            $stmt->execute([$user_id, $object_id]);
            return $stmt->fetch() !== false;
        }
        
        return intval($result[$owner_field]) === $user_id;
    }
    catch(Exception $e) {
        return false;
    }
}

/**
 * 根据用户角色生成后台菜单
 * @return array
 */
function get_user_menu() {
    $role = get_current_user_role();
    
    $menus = [
        'admin' => [
            ['name' => '控制台', 'url' => 'index.php', 'icon' => 'dashboard'],
            ['name' => '广告审核', 'url' => 'audit/ad_audit.php', 'icon' => 'audit'],
            ['name' => '网站审核', 'url' => 'audit/website_audit.php', 'icon' => 'website'],
            ['name' => '财务对账', 'url' => 'finance/reconciliation.php', 'icon' => 'finance'],
            ['name' => '系统设置', 'url' => 'system/config.php', 'icon' => 'settings'],
            ['name' => '用户管理', 'url' => 'system/users.php', 'icon' => 'users'],
        ],
        'auditor' => [
            ['name' => '审核工作台', 'url' => 'index.php', 'icon' => 'audit'],
            ['name' => '广告审核', 'url' => 'audit/ad_audit.php', 'icon' => 'ad'],
            ['name' => '网站审核', 'url' => 'audit/website_audit.php', 'icon' => 'website'],
            ['name' => '提现审核', 'url' => 'audit/withdraw_audit.php', 'icon' => 'withdraw'],
        ],
        'advertiser' => [
            ['name' => '数据概览', 'url' => 'advertiser/index.php', 'icon' => 'dashboard'],
            ['name' => '广告活动', 'url' => 'advertiser/campaign_list.php', 'icon' => 'campaign'],
            ['name' => '广告创意', 'url' => 'advertiser/creative_list.php', 'icon' => 'creative'],
            ['name' => '数据报表', 'url' => 'advertiser/report.php', 'icon' => 'report'],
            ['name' => '财务管理', 'url' => 'advertiser/finance.php', 'icon' => 'finance'],
            ['name' => '账户设置', 'url' => 'advertiser/settings.php', 'icon' => 'settings'],
        ],
        'publisher' => [
            ['name' => '数据概览', 'url' => 'publisher/index.php', 'icon' => 'dashboard'],
            ['name' => '网站管理', 'url' => 'publisher/website_list.php', 'icon' => 'website'],
            ['name' => '广告位管理', 'url' => 'publisher/position_list.php', 'icon' => 'position'],
            ['name' => '收益统计', 'url' => 'publisher/earnings.php', 'icon' => 'earnings'],
            ['name' => '提现管理', 'url' => 'publisher/withdraw.php', 'icon' => 'withdraw'],
            ['name' => '账户设置', 'url' => 'publisher/settings.php', 'icon' => 'settings'],
        ],
    ];
    
    return $menus[$role] ?? [];
}

/**
 * 退出登录
 */
function logout() {
    $_SESSION = [];
    session_destroy();
    setcookie(session_name(), '', time() - 3600, '/');
    header('Location: ad_login.php');
    exit;
}

/**
 * 发送短信验证码 (简化版，实际应调用短信服务商 API)
 * @param string $phone 手机号
 * @return bool
 */
function send_sms_code($phone) {
    // 生成 6 位验证码
    $code = sprintf('%06d', mt_rand(0, 999999));
    
    // 存入 session，5 分钟有效
    $_SESSION['sms_code_'.$phone] = $code;
    $_SESSION['sms_code_time_'.$phone] = time();
    
    // TODO: 调用短信服务商 API 发送短信
    // 示例：阿里云短信、腾讯云短信等
    
    // 开发环境直接返回成功
    return true;
}

/**
 * 验证短信验证码
 * @param string $phone 手机号
 * @param string $code 验证码
 * @return bool
 */
function verify_sms_code($phone, $code) {
    $saved_code = $_SESSION['sms_code_'.$phone] ?? null;
    $saved_time = $_SESSION['sms_code_time_'.$phone] ?? 0;
    
    // 检查是否超时 (5 分钟)
    if(time() - $saved_time > 300) {
        unset($_SESSION['sms_code_'.$phone]);
        unset($_SESSION['sms_code_time_'.$phone]);
        return false;
    }
    
    return $saved_code === $code;
}
