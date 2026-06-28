<?php
/**
 * 网站操作 API
 */

define('IN_CRONLITE', true);
define('SYSTEM_ROOT', '/workspace/includes/');
define('ROOT', '/workspace/');

require_once(ROOT.'includes/common.php');
require_once(ROOT.'includes/auth.php');

header('Content-Type: application/json; charset=utf-8');

try {
    require_login();
    require_role('publisher');
    
    $act = $_POST['act'] ?? '';
    $id = intval($_POST['id'] ?? 0);
    
    if($id <= 0) {
        exit(json_encode(['code' => 1, 'msg' => '参数错误']));
    }
    
    $user_id = get_current_user_id();
    $publisher = $DB->fetch_result($DB->query("SELECT id FROM ad_publishers WHERE user_id = $user_id"));
    
    // 验证网站归属
    $website = $DB->fetch_result($DB->query("SELECT * FROM ad_publisher_websites WHERE id = $id AND publisher_id = {$publisher['id']}"));
    if(!$website) {
        exit(json_encode(['code' => 1, 'msg' => '网站不存在或无权操作']));
    }
    
    if($act === 'delete') {
        // 删除网站及关联广告位
        $DB->query("DELETE FROM ad_positions WHERE website_id = $id");
        $DB->query("DELETE FROM ad_publisher_websites WHERE id = $id");
        
        // 记录日志
        $DB->query("INSERT INTO ad_operation_logs (user_id, action, module, object_type, object_id) VALUES ($user_id, 'delete', 'publisher', 'website', $id)");
        
        exit(json_encode(['code' => 0, 'msg' => '删除成功']));
    }
    else {
        exit(json_encode(['code' => 1, 'msg' => '未知操作']));
    }
}
catch(Exception $e) {
    exit(json_encode(['code' => 1, 'msg' => '操作失败：'.$e->getMessage()]));
}
