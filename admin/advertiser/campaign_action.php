<?php
/**
 * 广告活动操作 API
 * 暂停/恢复/删除
 */

define('IN_CRONLITE', true);
define('SYSTEM_ROOT', dirname(__FILE__).'/');
define('ROOT', dirname(dirname(SYSTEM_ROOT)).'/');

require_once(ROOT.'includes/common.php');
require_once(ROOT.'includes/auth.php');

header('Content-Type: application/json; charset=utf-8');

try {
    require_login();
    require_role('advertiser');
    
    $act = $_POST['act'] ?? '';
    $id = intval($_POST['id'] ?? 0);
    
    if($id <= 0) {
        exit(json_encode(['code' => 1, 'msg' => '参数错误']));
    }
    
    $user_id = get_current_user_id();
    $advertiser = $DB->query("SELECT id FROM ad_advertisers WHERE user_id = $user_id")->fetch();
    
    // 验证活动归属
    $campaign = $DB->query("SELECT * FROM ad_campaigns WHERE id = $id AND advertiser_id = {$advertiser['id']}")->fetch();
    if(!$campaign) {
        exit(json_encode(['code' => 1, 'msg' => '活动不存在或无权操作']));
    }
    
    if($act === 'toggle') {
        $status = intval($_POST['status'] ?? 0);
        if(!in_array($status, [4, 5])) {
            exit(json_encode(['code' => 1, 'msg' => '状态参数错误']));
        }
        
        $DB->query("UPDATE ad_campaigns SET status = $status WHERE id = $id");
        
        // 记录日志
        $action_name = $status == 4 ? 'resume' : 'pause';
        $DB->query("INSERT INTO ad_operation_logs (user_id, action, module, object_type, object_id) VALUES ($user_id, '$action_name', 'advertiser', 'campaign', $id)");
        
        exit(json_encode(['code' => 0, 'msg' => '操作成功']));
    }
    elseif($act === 'delete') {
        // 只能删除草稿状态的活动
        if($campaign['status'] != 0) {
            exit(json_encode(['code' => 1, 'msg' => '只能删除草稿状态的活动']));
        }
        
        $DB->query("DELETE FROM ad_campaigns WHERE id = $id");
        
        // 记录日志
        $DB->query("INSERT INTO ad_operation_logs (user_id, action, module, object_type, object_id) VALUES ($user_id, 'delete', 'advertiser', 'campaign', $id)");
        
        exit(json_encode(['code' => 0, 'msg' => '删除成功']));
    }
    else {
        exit(json_encode(['code' => 1, 'msg' => '未知操作']));
    }
}
catch(Exception $e) {
    exit(json_encode(['code' => 1, 'msg' => '操作失败：'.$e->getMessage()]));
}
