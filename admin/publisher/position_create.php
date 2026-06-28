<?php
/**
 * 创建广告位页面
 * 网站主后台 - 广告位管理
 */

define('IN_CRONLITE', true);
define('SYSTEM_ROOT', '/workspace/includes/');
define('ROOT', '/workspace/');

require_once(ROOT.'includes/common.php');
require_once(ROOT.'includes/auth.php');

require_login();
require_role('publisher');

$website_id = isset($_GET['website_id']) ? intval($_GET['website_id']) : 0;
$error = '';

try {
    $user_id = get_current_user_id();
    $publisher = $DB->fetch_result($DB->query("SELECT id FROM ad_publishers WHERE user_id = $user_id"));
    
    // 验证网站归属
    if($website_id > 0) {
        $website = $DB->fetch_result($DB->query("SELECT * FROM ad_publisher_websites WHERE id = $website_id AND publisher_id = {$publisher['id']}"));
        if(!$website) {
            die('网站不存在或无权操作');
        }
        if($website['status'] != 1) {
            die('网站审核通过后方可创建广告位');
        }
    }
    
    // 处理表单提交
    if(isset($_POST['submit'])) {
        $name = trim($_POST['name']);
        $type = $_POST['type'];
        $width = intval($_POST['width']);
        $height = intval($_POST['height']);
        $min_cpm = floatval($_POST['min_cpm']);
        $visibility = $_POST['visibility'];
        
        if(empty($name)) {
            $error = '请输入广告位名称';
        }
        elseif(!in_array($type, ['image', 'native', 'popup', 'floating', 'video', 'code'])) {
            $error = '请选择正确的广告类型';
        }
        elseif($width <= 0 || $height <= 0) {
            $error = '尺寸必须为正整数';
        }
        elseif($min_cpm < 0) {
            $error = '最低 CPM 不能小于 0';
        }
        else {
            // 生成唯一代码
            $code = 'pos_' . substr(md5(uniqid().mt_rand()), 0, 12);
            
            $stmt = $DB->prepare("
                INSERT INTO ad_positions 
                (website_id, name, code, type, width, height, min_cpm, visibility)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $website_id,
                $name,
                $code,
                $type,
                $width,
                $height,
                $min_cpm,
                $visibility
            ]);
            
            $position_id = $DB->lastInsertId();
            
            // 记录日志
            $DB->query("INSERT INTO ad_operation_logs (user_id, action, module, object_type, object_id) VALUES ($user_id, 'create_position', 'publisher', 'position', $position_id)");
            
            // 跳转到编辑屏蔽规则
            header('Location: position_edit.php?id='.$position_id);
            exit;
        }
    }
}
catch(Exception $e) {
    $error = '创建失败：'.$e->getMessage();
}

include(ROOT.'admin/admin.php');
?>

<div class="card">
    <div class="card-header">
        <h5><i class="fa fa-plus"></i> 创建广告位</h5>
    </div>
    
    <div class="card-body">
        <?php if($error): ?>
            <div class="alert alert-danger"><?=$error?></div>
        <?php endif; ?>
        
        <form method="post">
            <div class="form-group">
                <label class="required">广告位名称</label>
                <input type="text" name="name" class="form-control" placeholder="如：首页顶部横幅" maxlength="100" required>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="required">广告类型</label>
                        <select name="type" class="form-control" required onchange="updateSize()">
                            <option value="image">图片广告</option>
                            <option value="native">原生广告</option>
                            <option value="popup">弹窗广告</option>
                            <option value="floating">悬浮广告</option>
                            <option value="video">视频广告</option>
                            <option value="code">代码广告</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="required">可见度</label>
                        <select name="visibility" class="form-control" required>
                            <option value="public">公开 - 所有广告主均可投放</option>
                            <option value="private">私有 - 仅指定广告主可投放</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="required">宽度 (px)</label>
                        <input type="number" name="width" class="form-control" value="300" min="1" required id="width">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="required">高度 (px)</label>
                        <input type="number" name="height" class="form-control" value="250" min="1" required id="height">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>最低 CPM 出价 (元)</label>
                        <input type="number" name="min_cpm" class="form-control" value="0" step="0.01" min="0">
                        <small class="text-muted">0 表示不限制</small>
                    </div>
                </div>
            </div>
            
            <div class="alert alert-info mt-3">
                <strong>常用尺寸参考：</strong>
                <ul class="mb-0 mt-2">
                    <li>300×250 - 中型矩形 (最常用)</li>
                    <li>728×90 - 全尺寸横幅 (页面顶部)</li>
                    <li>300×600 - 半页广告 (侧边栏)</li>
                    <li>320×50 - 手机横幅 (移动端)</li>
                    <li>970×90 - 大尺寸横幅 (宽屏顶部)</li>
                </ul>
            </div>
            
            <div class="form-group mt-4">
                <button type="submit" name="submit" class="btn btn-primary btn-block">
                    <i class="fa fa-check"></i> 创建广告位
                </button>
                <a href="position_list.php?website_id=<?=$website_id?>" class="btn btn-secondary btn-block mt-2">
                    <i class="fa fa-arrow-left"></i> 返回列表
                </a>
            </div>
        </form>
    </div>
</div>

<script>
const sizeMap = {
    'image': [300, 250],
    'native': [300, 250],
    'popup': [800, 600],
    'floating': [160, 600],
    'video': [640, 360],
    'code': [300, 250]
};

function updateSize() {
    const type = document.querySelector('[name="type"]').value;
    const [w, h] = sizeMap[type];
    document.getElementById('width').value = w;
    document.getElementById('height').value = h;
}
</script>
