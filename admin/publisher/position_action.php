<?php
/**
 * 广告位操作 API
 */

define('IN_CRONLITE', true);
define('SYSTEM_ROOT', dirname(__FILE__).'/');
define('ROOT', dirname(dirname(SYSTEM_ROOT)).'/');

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
    $publisher = $DB->query("SELECT id FROM ad_publishers WHERE user_id = $user_id")->fetch();
    
    // 验证广告位归属
    $position = $DB->query("
        SELECT p.* FROM ad_positions p 
        JOIN ad_publisher_websites w ON p.website_id = w.id 
        WHERE p.id = $id AND w.publisher_id = {$publisher['id']}
    ")->fetch();
    if(!$position) {
        exit(json_encode(['code' => 1, 'msg' => '广告位不存在或无权操作']));
    }
    
    if($act === 'toggle') {
        $status = intval($_POST['status'] ?? 1);
        if(!in_array($status, [0, 1])) {
            exit(json_encode(['code' => 1, 'msg' => '状态参数错误']));
        }
        
        $DB->query("UPDATE ad_positions SET status = $status WHERE id = $id");
        
        // 记录日志
        $DB->query("INSERT INTO ad_operation_logs (user_id, action, module, object_type, object_id) VALUES ($user_id, 'toggle_position', 'publisher', 'position', $id)");
        
        exit(json_encode(['code' => 0, 'msg' => '操作成功']));
    }
    elseif($act === 'delete') {
        $DB->query("DELETE FROM ad_positions WHERE id = $id");
        
        // 记录日志
        $DB->query("INSERT INTO ad_operation_logs (user_id, action, module, object_type, object_id) VALUES ($user_id, 'delete', 'publisher', 'position', $id)");
        
        exit(json_encode(['code' => 0, 'msg' => '删除成功']));
    }
    elseif($act === 'update_rules') {
        // 更新屏蔽规则
        $advertiser_blacklist = $_POST['advertiser_blacklist'] ?? '[]';
        $industry_blacklist = $_POST['industry_blacklist'] ?? '[]';
        $domain_blacklist = $_POST['domain_blacklist'] ?? '[]';
        $keyword_blacklist = $_POST['keyword_blacklist'] ?? '[]';
        
        $stmt = $DB->prepare("
            UPDATE ad_positions 
            SET advertiser_blacklist = ?, industry_blacklist = ?, domain_blacklist = ?, keyword_blacklist = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $advertiser_blacklist,
            $industry_blacklist,
            $domain_blacklist,
            $keyword_blacklist,
            $id
        ]);
        
        // 记录日志
        $DB->query("INSERT INTO ad_operation_logs (user_id, action, module, object_type, object_id) VALUES ($user_id, 'update_rules', 'publisher', 'position', $id)");
        
        exit(json_encode(['code' => 0, 'msg' => '更新成功']));
    }
    else {
        exit(json_encode(['code' => 1, 'msg' => '未知操作']));
    }
}
catch(Exception $e) {
    exit(json_encode(['code' => 1, 'msg' => '操作失败：'.$e->getMessage()]));
}
