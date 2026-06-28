<?php
/**
 * 添加网站页面
 * 网站主后台 - 网站接入
 */

define('IN_CRONLITE', true);
define('SYSTEM_ROOT', dirname(__FILE__).'/');
define('ROOT', dirname(dirname(SYSTEM_ROOT)).'/');

require_once(ROOT.'includes/common.php');
require_once(ROOT.'includes/auth.php');

require_login();
require_role('publisher');

$user_id = get_current_user_id();
$error = '';
$success = '';

try {
    // 获取网站主 ID
    $publisher = $DB->query("SELECT id FROM ad_publishers WHERE user_id = $user_id")->fetch();
    
    // 处理表单提交
    if(isset($_POST['submit'])) {
        $name = trim($_POST['name']);
        $domain = trim($_POST['domain']);
        $icp_number = trim($_POST['icp_number']);
        $category_id = intval($_POST['category_id']);
        $daily_uv = intval($_POST['daily_uv']);
        $daily_pv = intval($_POST['daily_pv']);
        
        // 验证
        if(empty($name)) {
            $error = '请输入网站名称';
        }
        elseif(empty($domain)) {
            $error = '请输入网站域名';
        }
        elseif(!preg_match('/^[a-zA-Z0-9][-a-zA-Z0-9]*(\.[a-zA-Z0-9][-a-zA-Z0-9]*)+$/', $domain)) {
            $error = '域名格式不正确';
        }
        elseif($daily_uv < 0 || $daily_pv < 0) {
            $error = 'UV 和 PV 必须为正整数';
        }
        else {
            // 检查域名是否已存在
            $exist = $DB->query("SELECT id FROM ad_publisher_websites WHERE domain = '$domain'")->fetch();
            if($exist) {
                $error = '该域名已被其他网站使用';
            }
            else {
                // 生成 SDK 代码
                $sdk_code = '<script async src="https://cdn.example.com/ad-sdk.js" data-pid="'.$publisher['id'].'" data-wid="__WEBSITE_ID__"></script>';
                
                // 插入网站
                $stmt = $DB->prepare("
                    INSERT INTO ad_publisher_websites 
                    (publisher_id, name, domain, icp_number, category_id, daily_uv, daily_pv, sdk_code, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)
                ");
                $stmt->execute([
                    $publisher['id'],
                    $name,
                    $domain,
                    $icp_number,
                    $category_id,
                    $daily_uv,
                    $daily_pv,
                    $sdk_code
                ]);
                
                $website_id = $DB->lastInsertId();
                
                // 记录日志
                $DB->query("INSERT INTO ad_operation_logs (user_id, action, module, object_type, object_id) VALUES ($user_id, 'add_website', 'publisher', 'website', $website_id)");
                
                $success = '网站添加成功，请等待审核！审核通过后方可创建广告位。';
                
                // 清空表单
                $_POST = [];
            }
        }
    }
}
catch(Exception $e) {
    $error = '添加失败：'.$e->getMessage();
}

include(ROOT.'admin/admin.php');
?>

<div class="card">
    <div class="card-header">
        <h5><i class="fa fa-plus"></i> 添加网站</h5>
    </div>
    
    <div class="card-body">
        <?php if($error): ?>
            <div class="alert alert-danger"><?=$error?></div>
        <?php endif; ?>
        <?php if($success): ?>
            <div class="alert alert-success"><?=$success?></div>
        <?php endif; ?>
        
        <form method="post">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="required">网站名称</label>
                        <input type="text" name="name" class="form-control" placeholder="如：某某技术博客" maxlength="100" value="<?=htmlspecialchars($_POST['name'] ?? '')?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="required">网站域名</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">https://</span>
                            </div>
                            <input type="text" name="domain" class="form-control" placeholder="www.example.com" value="<?=htmlspecialchars($_POST['domain'] ?? '')?>" required>
                        </div>
                        <small class="text-muted">不含 http:// 或 https:// 前缀</small>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>ICP 备案号</label>
                        <input type="text" name="icp_number" class="form-control" placeholder="如：京 ICP 备 12345678 号" value="<?=htmlspecialchars($_POST['icp_number'] ?? '')?>">
                        <small class="text-muted">选填，建议填写以提高审核通过率</small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>网站分类</label>
                        <select name="category_id" class="form-control">
                            <option value="0">请选择分类</option>
                            <option value="1">新闻资讯</option>
                            <option value="2">技术博客</option>
                            <option value="3">论坛社区</option>
                            <option value="4">电子商务</option>
                            <option value="5">企业官网</option>
                            <option value="6">娱乐媒体</option>
                            <option value="7">生活服务</option>
                            <option value="8">其他</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>预估日 UV</label>
                        <input type="number" name="daily_uv" class="form-control" value="<?=htmlspecialchars($_POST['daily_uv'] ?? '1000')?>" min="0">
                        <small class="text-muted">独立访客数，用于审核评估</small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>预估日 PV</label>
                        <input type="number" name="daily_pv" class="form-control" value="<?=htmlspecialchars($_POST['daily_pv'] ?? '3000')?>" min="0">
                        <small class="text-muted">页面浏览量，用于审核评估</small>
                    </div>
                </div>
            </div>
            
            <div class="alert alert-info mt-3">
                <i class="fa fa-info-circle"></i> <strong>温馨提示：</strong>
                <ul class="mb-0 mt-2">
                    <li>提交后需要 1-3 个工作日审核</li>
                    <li>审核通过后将生成专属 SDK 代码</li>
                    <li>将 SDK 代码嵌入网站即可开始展示广告</li>
                    <li>网站内容必须合法合规，不得包含违法违规信息</li>
                </ul>
            </div>
            
            <div class="form-group mt-4">
                <button type="submit" name="submit" class="btn btn-primary btn-block">
                    <i class="fa fa-check"></i> 提交审核
                </button>
                <a href="website_list.php" class="btn btn-secondary btn-block mt-2">
                    <i class="fa fa-arrow-left"></i> 返回列表
                </a>
            </div>
        </form>
    </div>
</div>
