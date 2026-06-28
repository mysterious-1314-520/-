<?php
/**
 * 广告位列表页面
 * 网站主后台 - 广告位管理
 */

define('IN_CRONLITE', true);
define('SYSTEM_ROOT', dirname(__FILE__).'/');
define('ROOT', dirname(dirname(SYSTEM_ROOT)).'/');

require_once(ROOT.'includes/common.php');
require_once(ROOT.'includes/auth.php');

require_login();
require_role('publisher');

$user_id = get_current_user_id();
$website_id = isset($_GET['website_id']) ? intval($_GET['website_id']) : 0;

try {
    // 获取网站主 ID
    $publisher = $DB->query("SELECT id FROM ad_publishers WHERE user_id = $user_id")->fetch();
    
    // 验证网站归属
    if($website_id > 0) {
        $website = $DB->query("SELECT * FROM ad_publisher_websites WHERE id = $website_id AND publisher_id = {$publisher['id']}")->fetch();
        if(!$website) {
            die('网站不存在或无权操作');
        }
    }
    
    // 广告位列表
    $positions = [];
    if($website_id > 0) {
        $positions = $DB->query("SELECT * FROM ad_positions WHERE website_id = $website_id ORDER BY created_at DESC")->fetchAll();
    }
    
    // 类型映射
    $type_map = [
        'image' => '图片广告',
        'native' => '原生广告',
        'popup' => '弹窗广告',
        'floating' => '悬浮广告',
        'video' => '视频广告',
        'code' => '代码广告'
    ];
    
    // 尺寸映射
    $size_names = [
        '300x250' => '中型矩形',
        '728x90' => '全尺寸横幅',
        '300x600' => '半页广告',
        '970x90' => '大尺寸横幅',
        '160x600' => '宽 skyscraper',
        '320x50' => '手机横幅',
        '320x100' => '手机_large 横幅'
    ];
}
catch(Exception $e) {
    $error = '数据加载失败：'.$e->getMessage();
}

include(ROOT.'admin/admin.php');
?>

<div class="card">
    <div class="card-header">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h5 class="mb-0">
                    <i class="fa fa-th"></i> 广告位管理
                    <?php if($website): ?>
                        <small class="text-muted">- <?=htmlspecialchars($website['name'])?></small>
                    <?php endif; ?>
                </h5>
            </div>
            <?php if($website && $website['status'] == 1): ?>
            <div class="col-md-6 text-right">
                <a href="position_create.php?website_id=<?=$website_id?>" class="btn btn-primary btn-sm">
                    <i class="fa fa-plus"></i> 创建广告位
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="card-body">
        <?php if(!$website): ?>
            <div class="alert alert-info">
                <i class="fa fa-info-circle"></i> 请先选择要管理的网站
                <a href="website_list.php" class="btn btn-sm btn-primary ml-2">选择网站</a>
            </div>
        <?php elseif($website['status'] != 1): ?>
            <div class="alert alert-warning">
                <i class="fa fa-exclamation-triangle"></i> 
                该网站状态为 <strong><?=$website['status'] == 0 ? '待审核' : ($website['status'] == 2 ? '审核驳回' : '已禁用')?></strong>，
                <?=($website['status'] == 2 && $website['audit_remark']) ? '原因：'.htmlspecialchars($website['audit_remark']) : '审核通过后方可创建广告位'?>
            </div>
        <?php else: ?>
            
            <?php if(empty($positions)): ?>
                <div class="text-center text-muted py-5">
                    <i class="fa fa-inbox fa-3x mb-3"></i>
                    <p>暂无广告位，<a href="position_create.php?website_id=<?=$website_id?>">立即创建</a></p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>广告位名称</th>
                                <th>类型</th>
                                <th>尺寸</th>
                                <th>最低 CPM</th>
                                <th>可见度</th>
                                <th>展示量</th>
                                <th>点击量</th>
                                <th>状态</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($positions as $p): ?>
                                <tr>
                                    <td><?=$p['id']?></td>
                                    <td class="font-weight-bold"><?=htmlspecialchars($p['name'])?></td>
                                    <td><span class="badge badge-info"><?=$type_map[$p['type']]?></span></td>
                                    <td><?=$p['width']?>×<?=$p['height']?> <?=isset($size_names[$p['width'].'x'.$p['height']]) ? '('.$size_names[$p['width'].'x'.$p['height']].')' : ''?></td>
                                    <td>¥<?=number_format($p['min_cpm'], 2)?></td>
                                    <td>
                                        <span class="badge badge-<?=$p['visibility'] == 'public' ? 'success' : 'warning'?>">
                                            <?=$p['visibility'] == 'public' ? '公开' : '私有'?>
                                        </span>
                                    </td>
                                    <td><?=number_format($p['impression_count'])?></td>
                                    <td><?=number_format($p['click_count'])?></td>
                                    <td>
                                        <span class="badge badge-<?=$p['status'] == 1 ? 'success' : 'secondary'?>">
                                            <?=$p['status'] == 1 ? '启用' : '暂停'?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="position_edit.php?id=<?=$p['id']?>" class="btn btn-info" title="编辑">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                            <button class="btn btn-<?= $p['status'] == 1 ? 'warning' : 'success' ?>" 
                                                    onclick="togglePosition(<?=$p['id']?>, <?=$p['status'] == 1 ? 0 : 1?>)" 
                                                    title="<?=$p['status'] == 1 ? '暂停' : '启用'?>">
                                                <i class="fa fa-<?=$p['status'] == 1 ? 'pause' : 'play'?>"></i>
                                            </button>
                                            <button class="btn btn-secondary" onclick="showCode(<?=$p['id']?>)" title="代码">
                                                <i class="fa fa-code"></i>
                                            </button>
                                            <button class="btn btn-danger" onclick="deletePosition(<?=$p['id']?>)" title="删除">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            
        <?php endif; ?>
    </div>
</div>

<script src="<?=cdn()?>layer/layer.js"></script>
<script>
// 切换状态
function togglePosition(id, status) {
    const action = status == 1 ? '启用' : '暂停';
    layer.confirm('确定要'+action+'该广告位吗？', function(index) {
        $.ajax({
            url: 'position_action.php',
            type: 'POST',
            data: { act: 'toggle', id: id, status: status },
            success: function(res) {
                if(res.code == 0) {
                    layer.msg(action+'成功', { icon: 1 });
                    setTimeout(() => location.reload(), 1000);
                } else {
                    layer.msg(res.msg, { icon: 2 });
                }
            }
        });
        layer.close(index);
    });
}

// 显示广告代码
function showCode(id) {
    layer.prompt({
        formType: 0,
        value: '<ad data-pos="pos_' + id + '"></ad>',
        title: '广告位调用代码',
        area: ['400px', 'auto']
    }, function(code, index) {
        layer.msg('已复制', { icon: 1 });
        layer.close(index);
    });
}

// 删除广告位
function deletePosition(id) {
    layer.confirm('确定要删除该广告位吗？删除后不可恢复！', { icon: 3, title: '警告' }, function(index) {
        $.ajax({
            url: 'position_action.php',
            type: 'POST',
            data: { act: 'delete', id: id },
            success: function(res) {
                if(res.code == 0) {
                    layer.msg('删除成功', { icon: 1 });
                    setTimeout(() => location.reload(), 1000);
                } else {
                    layer.msg(res.msg, { icon: 2 });
                }
            }
        });
        layer.close(index);
    });
}
</script>
