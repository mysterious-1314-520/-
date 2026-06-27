<?php
include __DIR__ . "/../includes/common.php";
$title = '广告设置 - 祈福导航系统';
if($islogin != 1){
    @header('Location: ./login.php');
    exit;
}

$tips = array();
$errors = array();

function ad_admin_tip(&$list, $text, $type = 'success'){
    $list[] = array('text' => $text, 'type' => $type);
}

function ad_admin_upload_error($code){
    $errors = array(
        UPLOAD_ERR_INI_SIZE => '文件超过服务器 upload_max_filesize 限制',
        UPLOAD_ERR_FORM_SIZE => '文件超过表单限制',
        UPLOAD_ERR_PARTIAL => '文件只上传了一部分',
        UPLOAD_ERR_NO_FILE => '没有选择文件',
        UPLOAD_ERR_NO_TMP_DIR => '服务器缺少临时目录',
        UPLOAD_ERR_CANT_WRITE => '服务器写入文件失败',
        UPLOAD_ERR_EXTENSION => '上传被 PHP 扩展拦截'
    );
    return isset($errors[$code]) ? $errors[$code] : '未知上传错误：'.$code;
}

function ad_admin_upload($field, &$error){
    global $rooturl;
    if(!isset($_FILES[$field]) || $_FILES[$field]['error'] == UPLOAD_ERR_NO_FILE) return '';
    if($_FILES[$field]['error'] != UPLOAD_ERR_OK){
        $error = ad_admin_upload_error(intval($_FILES[$field]['error']));
        return '';
    }
    $allowed = array('jpg','jpeg','png','gif','webp');
    $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
    if(!in_array($ext, $allowed)){
        $error = '图片格式不支持，请上传 jpg、jpeg、png、gif、webp';
        return '';
    }
    $upload_dir = ROOT.'images/ad/';
    if(!is_dir($upload_dir) && !@mkdir($upload_dir, 0755, true)){
        $error = '广告图片目录创建失败，请检查 images 目录权限';
        return '';
    }
    if(!is_writable($upload_dir)){
        $error = '广告图片目录不可写，请检查 images/ad 目录权限';
        return '';
    }
    $filename = preg_replace('/[^a-z0-9_]+/i', '_', $field).'_'.date('YmdHis').'_'.mt_rand(1000,9999).'.'.$ext;
    $target = $upload_dir.$filename;
    $saved = false;
    if(is_uploaded_file($_FILES[$field]['tmp_name'])){
        $saved = @move_uploaded_file($_FILES[$field]['tmp_name'], $target);
    }
    if(!$saved && is_readable($_FILES[$field]['tmp_name'])){
        $saved = @copy($_FILES[$field]['tmp_name'], $target);
    }
    if(!$saved){
        $error = '图片保存失败，请检查 PHP 临时目录和 images/ad 权限';
        return '';
    }
    @chmod($target, 0644);
    return rtrim($rooturl, '/').'/images/ad/'.$filename;
}

function ad_admin_datetime($value){
    $value = trim((string)$value);
    if($value === '') return '';
    $ts = strtotime($value);
    return $ts ? date('Y-m-d H:i:s', $ts) : '';
}

qifu_ad_ensure_tables();
qifu_ad_ensure_config();
qifu_ad_seed_legacy();

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    if($action == 'save_global'){
        saveSetting('ad_enabled', isset($_POST['ad_enabled']) ? '1' : '0');
        saveSetting('ad_show_below', isset($_POST['ad_show_below']) ? '1' : '0');
        saveSetting('ad_show_right', isset($_POST['ad_show_right']) ? '1' : '0');
        saveSetting('ad_show_left', isset($_POST['ad_show_left']) ? '1' : '0');
        saveSetting('ad_new_window', isset($_POST['ad_new_window']) ? '1' : '0');
        foreach(qifu_ad_positions() as $key => $label){
            $mode_key = 'ad_mode_'.$key;
            $mode = isset($_POST[$mode_key]) ? $_POST[$mode_key] : 'fixed';
            if(!isset(qifu_ad_modes()[$mode])) $mode = 'fixed';
            saveSetting($mode_key, $mode);
        }
        $CACHE->clear();
        $conf = $CACHE->update();
        writeLog('修改', '广告设置', 0, '保存广告全局设置');
        ad_admin_tip($tips, '广告全局设置已保存，前台会按新规则展示。');
    }

    if($action == 'save_ad'){
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $positions = qifu_ad_positions();
        $position = isset($_POST['position']) && isset($positions[$_POST['position']]) ? $_POST['position'] : 'below_search';
        $slot = $position == 'below_search' ? max(1, min(4, intval($_POST['slot']))) : 1;
        $upload_error = '';
        $uploaded = ad_admin_upload('ad_file', $upload_error);
        if($upload_error !== '') $errors[] = $upload_error;

        $image = isset($_POST['image']) ? qifu_ad_normalize_url($_POST['image']) : '';
        if($uploaded !== '') $image = $uploaded;

        $data = array(
            'position' => $position,
            'slot' => $slot,
            'title' => isset($_POST['title']) ? trim($_POST['title']) : '',
            'image' => $image,
            'link' => isset($_POST['link']) ? qifu_ad_normalize_url($_POST['link']) : '',
            'alt' => isset($_POST['alt']) ? trim($_POST['alt']) : '',
            'active' => isset($_POST['active']) ? 1 : 0,
            'start_at' => ad_admin_datetime(isset($_POST['start_at']) ? $_POST['start_at'] : ''),
            'end_at' => ad_admin_datetime(isset($_POST['end_at']) ? $_POST['end_at'] : ''),
            'sort' => isset($_POST['sort']) ? intval($_POST['sort']) : 100,
            'weight' => max(1, min(50, isset($_POST['weight']) ? intval($_POST['weight']) : 1)),
            'updated_at' => time(),
        );

        if(empty($errors)){
            if($id > 0){
                $DB->query("UPDATE web_ads SET
                    position='".qifu_ad_escape($data['position'])."',
                    slot='".intval($data['slot'])."',
                    title='".qifu_ad_escape($data['title'])."',
                    image='".qifu_ad_escape($data['image'])."',
                    link='".qifu_ad_escape($data['link'])."',
                    alt='".qifu_ad_escape($data['alt'])."',
                    active='".intval($data['active'])."',
                    start_at='".qifu_ad_escape($data['start_at'])."',
                    end_at='".qifu_ad_escape($data['end_at'])."',
                    sort='".intval($data['sort'])."',
                    weight='".intval($data['weight'])."',
                    updated_at='".intval($data['updated_at'])."'
                    WHERE id='$id'");
                writeLog('修改', '广告', $id, '编辑广告内容');
                ad_admin_tip($tips, '广告已更新。');
            } else {
                $DB->query("INSERT INTO web_ads (position,slot,title,image,link,alt,active,start_at,end_at,sort,weight,created_at,updated_at) VALUES (
                    '".qifu_ad_escape($data['position'])."',
                    '".intval($data['slot'])."',
                    '".qifu_ad_escape($data['title'])."',
                    '".qifu_ad_escape($data['image'])."',
                    '".qifu_ad_escape($data['link'])."',
                    '".qifu_ad_escape($data['alt'])."',
                    '".intval($data['active'])."',
                    '".qifu_ad_escape($data['start_at'])."',
                    '".qifu_ad_escape($data['end_at'])."',
                    '".intval($data['sort'])."',
                    '".intval($data['weight'])."',
                    '".time()."',
                    '".time()."'
                )");
                writeLog('添加', '广告', 0, '新增广告内容');
                ad_admin_tip($tips, '新广告已添加。');
            }
            $CACHE->clear();
            $conf = $CACHE->update();
        }
    }

    if($action == 'delete_ad'){
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if($id > 0){
            $DB->query("DELETE FROM web_ads WHERE id='$id'");
            $DB->query("DELETE FROM web_ad_stats WHERE ad_id='$id'");
            $CACHE->clear();
            writeLog('删除', '广告', $id, '删除广告及统计');
            ad_admin_tip($tips, '广告已删除。');
        }
    }

    if($action == 'clear_cache'){
        $CACHE->clear();
        $conf = $CACHE->update();
        writeLog('清理', '缓存', 0, '后台一键清缓存');
        ad_admin_tip($tips, '缓存已清理，前台会重新读取最新配置。');
    }
}

$ad_enabled = isset($conf['ad_enabled']) ? $conf['ad_enabled'] : '0';
$ad_show_below = isset($conf['ad_show_below']) ? $conf['ad_show_below'] : '1';
$ad_show_right = isset($conf['ad_show_right']) ? $conf['ad_show_right'] : '0';
$ad_show_left = isset($conf['ad_show_left']) ? $conf['ad_show_left'] : '0';
$ad_new_window = isset($conf['ad_new_window']) ? $conf['ad_new_window'] : '1';
$ads = qifu_ad_all();
$positions = qifu_ad_positions();
$modes = qifu_ad_modes();
$check_images = isset($_GET['check_images']) && $_GET['check_images'] == '1';

$stats_today = date('Y-m-d');
$ad_today_views = intval($DB->count("SELECT COALESCE(SUM(views),0) FROM web_ad_stats WHERE stat_date='$stats_today'"));
$ad_today_clicks = intval($DB->count("SELECT COALESCE(SUM(clicks),0) FROM web_ad_stats WHERE stat_date='$stats_today'"));
$ad_total_views = intval($DB->count("SELECT COALESCE(SUM(views),0) FROM web_ad_stats"));
$ad_total_clicks = intval($DB->count("SELECT COALESCE(SUM(clicks),0) FROM web_ad_stats"));

include './head.php';
?>
<style>
.ad-shell{padding-top:70px}
.ad-card{border:1px solid #e4e8ef;border-radius:14px;background:#fff;margin-bottom:18px;box-shadow:0 8px 24px rgba(30,50,80,.05);overflow:hidden}
.ad-card-hd{padding:14px 18px;background:linear-gradient(135deg,#f7fbff,#f9fafc);border-bottom:1px solid #e4e8ef;font-weight:700;color:#34495e}
.ad-list-hd{display:flex;align-items:center;justify-content:space-between;gap:14px;min-height:58px}
.ad-list-title{display:inline-flex;align-items:center;gap:7px;color:#172033}
.ad-list-actions{display:flex;align-items:center;justify-content:flex-end;gap:10px;margin-left:auto}
.ad-list-actions form{margin:0}
.ad-list-actions .btn{height:36px;display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:0 14px;border-radius:10px;font-weight:700;line-height:1;box-shadow:none}
.ad-list-actions .btn-warning{border-color:#f59e0b;background:#f59e0b;color:#fff}
.ad-list-actions .btn-default{border-color:#dbe5ef;background:#fff;color:#344054}
.ad-list-actions .btn:hover{transform:translateY(-1px)}
.ad-card-bd{padding:18px}
.ad-toast{border:0;border-radius:12px;padding:12px 16px;margin-bottom:12px;box-shadow:0 8px 22px rgba(0,0,0,.06)}
.ad-toast.success{background:#ecfdf3;color:#1f7a3a}
.ad-toast.error{background:#fff1f0;color:#b42318}
.ad-stat-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:18px}
.ad-stat{border-radius:14px;padding:16px;color:#fff;background:linear-gradient(135deg,#2563eb,#14b8a6)}
.ad-stat:nth-child(2){background:linear-gradient(135deg,#f97316,#ef4444)}
.ad-stat:nth-child(3){background:linear-gradient(135deg,#0f766e,#22c55e)}
.ad-stat:nth-child(4){background:linear-gradient(135deg,#334155,#64748b)}
.ad-stat b{display:block;font-size:24px;line-height:1}
.ad-stat span{display:block;font-size:12px;opacity:.85;margin-top:8px}
.ad-form-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px}
.ad-form-grid .wide{grid-column:span 2}
.ad-global-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:14px}
.ad-check-card{display:flex;align-items:center;gap:12px;min-height:58px;margin:0;padding:12px 15px;border:1px solid #e3edf7;border-radius:16px;background:linear-gradient(135deg,#ffffff,#f8fbff);box-shadow:0 8px 20px rgba(42,76,120,.05);cursor:pointer;transition:.22s;color:#162033}
.ad-check-card:hover{border-color:#9fd7f0;transform:translateY(-1px);box-shadow:0 12px 26px rgba(42,119,170,.10)}
.ad-check-card input{position:absolute;opacity:0;pointer-events:none}
.ad-check-card span{font-weight:700}
.ad-switch-ui{width:48px;height:26px;border-radius:999px;background:#dbe6f2;box-shadow:inset 0 2px 5px rgba(40,70,100,.16);position:relative;flex-shrink:0;transition:.24s}
.ad-switch-ui:after{content:"";position:absolute;width:20px;height:20px;left:3px;top:3px;border-radius:50%;background:#fff;box-shadow:0 3px 9px rgba(31,50,80,.22);transition:.24s}
.ad-check-card input:checked + .ad-switch-ui{background:linear-gradient(135deg,#11c7a3,#2f80ed)}
.ad-check-card input:checked + .ad-switch-ui:after{transform:translateX(22px)}
.ad-check-card input:focus + .ad-switch-ui{box-shadow:0 0 0 3px rgba(47,128,237,.18),inset 0 2px 5px rgba(40,70,100,.12)}
.ad-mode-row{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-top:10px}
.ad-mode-box label{display:block;margin-bottom:8px;color:#172033;font-weight:700}
.ad-mode-box select{height:42px;border-radius:10px;border-color:#d7e2ef;box-shadow:none}
.ad-action-row{display:flex;align-items:center;gap:12px;margin-top:18px}
.ad-primary-btn{position:relative;display:inline-flex;align-items:center;justify-content:center;gap:8px;border:0!important;border-radius:14px!important;padding:11px 22px!important;color:#fff!important;font-weight:800;letter-spacing:.02em;background:linear-gradient(135deg,#16b6d9,#2f80ed 55%,#5b6ee1)!important;box-shadow:0 12px 24px rgba(47,128,237,.26),inset 0 1px 0 rgba(255,255,255,.35);overflow:hidden;transition:transform .2s,box-shadow .2s,filter .2s}
.ad-primary-btn:before{content:"";position:absolute;inset:0;background:linear-gradient(120deg,transparent 0%,rgba(255,255,255,.36) 45%,transparent 70%);transform:translateX(-120%);transition:transform .55s}
.ad-primary-btn:hover,.ad-primary-btn:focus{color:#fff!important;transform:translateY(-2px);filter:saturate(1.08);box-shadow:0 16px 34px rgba(47,128,237,.34),inset 0 1px 0 rgba(255,255,255,.45)}
.ad-primary-btn:hover:before{transform:translateX(120%)}
.ad-primary-btn:active{transform:translateY(0);box-shadow:0 8px 18px rgba(47,128,237,.25)}
.ad-primary-btn .glyphicon{top:0}
.ad-muted-note{color:#7a8796;font-size:12px}
.ad-size-hint{margin-top:8px;padding:10px 12px;border:1px solid #dceafd;border-radius:12px;background:linear-gradient(135deg,#f7fbff,#eef7ff);color:#315579;font-size:12px;line-height:1.6}
.ad-size-hint b{color:#135ca8}
.ad-size-hint small{display:block;color:#7d8c9c}
.ad-table td{vertical-align:middle!important}
.ad-preview{width:128px;height:70px;border-radius:12px;object-fit:cover;background:#eef2f7;border:1px solid #dde5ef}
.ad-status{display:inline-block;border-radius:999px;padding:4px 9px;font-size:12px;font-weight:700}
.ad-status.on{background:#e9f9ef;color:#17823b}
.ad-status.wait{background:#fff7e6;color:#a15c00}
.ad-status.end,.ad-status.off{background:#f1f5f9;color:#64748b}
.ad-status.bad{background:#fff1f0;color:#b42318}
.ad-img-ok{color:#17823b;font-weight:700}
.ad-img-bad{color:#b42318;font-weight:700}
.ad-upload-progress{height:8px;background:#edf2f7;border-radius:999px;overflow:hidden;margin-top:8px;display:none}
.ad-upload-progress span{display:block;height:100%;width:0;background:#2f80ed;transition:width .15s}
.ad-upload-msg{font-size:12px;color:#777;margin-top:6px;min-height:18px}
@media(max-width:900px){.ad-stat-grid,.ad-form-grid,.ad-global-grid,.ad-mode-row{grid-template-columns:1fr}.ad-form-grid .wide{grid-column:span 1}.ad-preview{width:100%;height:120px}.ad-action-row{flex-direction:column;align-items:flex-start}.ad-list-hd{align-items:flex-start;flex-direction:column}.ad-list-actions{width:100%;justify-content:flex-start;flex-wrap:wrap}.ad-list-actions .btn{height:34px}}
</style>
<div class="container ad-shell">
  <div class="col-xs-12 col-sm-11 col-lg-11 center-block" style="float:none;">
    <?php foreach($tips as $tip): ?>
      <div class="ad-toast <?php echo $tip['type']; ?>"><span class="glyphicon glyphicon-ok"></span> <?php echo htmlspecialchars($tip['text']); ?></div>
    <?php endforeach; ?>
    <?php foreach($errors as $error): ?>
      <div class="ad-toast error"><span class="glyphicon glyphicon-remove"></span> <?php echo htmlspecialchars($error); ?></div>
    <?php endforeach; ?>

    <div class="ad-stat-grid">
      <div class="ad-stat"><b><?php echo $ad_today_views; ?></b><span>今日广告曝光</span></div>
      <div class="ad-stat"><b><?php echo $ad_today_clicks; ?></b><span>今日广告点击</span></div>
      <div class="ad-stat"><b><?php echo $ad_total_views; ?></b><span>累计广告曝光</span></div>
      <div class="ad-stat"><b><?php echo $ad_total_clicks; ?></b><span>累计广告点击</span></div>
    </div>

    <div class="ad-card">
      <div class="ad-card-hd"><span class="glyphicon glyphicon-cog"></span> 全局展示规则</div>
      <div class="ad-card-bd">
        <form method="post" class="form">
          <input type="hidden" name="action" value="save_global">
          <div class="ad-global-grid">
            <label class="ad-check-card"><input type="checkbox" name="ad_enabled" value="1" <?php echo $ad_enabled=='1'?'checked':''; ?>><i class="ad-switch-ui"></i><span>启用广告系统</span></label>
            <label class="ad-check-card"><input type="checkbox" name="ad_show_below" value="1" <?php echo $ad_show_below=='1'?'checked':''; ?>><i class="ad-switch-ui"></i><span>搜索栏下方四等分</span></label>
            <label class="ad-check-card"><input type="checkbox" name="ad_show_right" value="1" <?php echo $ad_show_right=='1'?'checked':''; ?>><i class="ad-switch-ui"></i><span>PC右侧悬浮</span></label>
            <label class="ad-check-card"><input type="checkbox" name="ad_show_left" value="1" <?php echo $ad_show_left=='1'?'checked':''; ?>><i class="ad-switch-ui"></i><span>PC左侧悬浮</span></label>
            <label class="ad-check-card"><input type="checkbox" name="ad_new_window" value="1" <?php echo $ad_new_window=='1'?'checked':''; ?>><i class="ad-switch-ui"></i><span>点击广告新窗口打开</span></label>
          </div>
          <div class="ad-mode-row">
            <?php foreach($positions as $key => $label): $mode_key='ad_mode_'.$key; $current=isset($conf[$mode_key])?$conf[$mode_key]:'fixed'; ?>
            <div class="ad-mode-box">
              <label><?php echo htmlspecialchars($label); ?> 展示方式</label>
              <select name="<?php echo $mode_key; ?>" class="form-control">
                <?php foreach($modes as $mk => $mv): ?><option value="<?php echo $mk; ?>" <?php echo $current==$mk?'selected':''; ?>><?php echo $mv; ?></option><?php endforeach; ?>
              </select>
            </div>
            <?php endforeach; ?>
          </div>
          <div class="ad-action-row">
            <button class="btn ad-primary-btn" type="submit"><span class="glyphicon glyphicon-ok"></span> 保存全局设置</button>
            <span class="ad-muted-note">保存后前台广告位、随机/轮播规则会立即按新设置生效。</span>
          </div>
        </form>
      </div>
    </div>

    <div class="ad-card">
      <div class="ad-card-hd"><span class="glyphicon glyphicon-plus"></span> 新增广告</div>
      <div class="ad-card-bd">
        <form method="post" enctype="multipart/form-data" class="ad-edit-form">
          <input type="hidden" name="action" value="save_ad">
          <div class="ad-form-grid">
            <div><label>展示位置</label><select name="position" class="form-control ad-position-select"><?php foreach($positions as $key=>$label): ?><option value="<?php echo $key; ?>"><?php echo $label; ?></option><?php endforeach; ?></select><div class="ad-size-hint"></div></div>
            <div><label>四等分位置</label><select name="slot" class="form-control ad-slot-select"><option value="1">位置1</option><option value="2">位置2</option><option value="3">位置3</option><option value="4">位置4</option></select></div>
            <div><label>排序</label><input type="number" name="sort" value="100" class="form-control"></div>
            <div><label>权重</label><input type="number" name="weight" value="1" min="1" max="50" class="form-control"></div>
            <div class="wide"><label>广告标题</label><input type="text" name="title" class="form-control"></div>
            <div class="wide"><label>跳转链接</label><input type="text" name="link" class="form-control" placeholder="https://example.com"></div>
            <div class="wide"><label>图片外链 / 上传后自动填入</label><input type="text" name="image" class="form-control ad-url-input" placeholder="https://example.com/ad.gif"></div>
            <div class="wide"><label>上传图片（支持 GIF 动图）</label><input type="file" name="ad_file" class="form-control ad-upload-input" accept="image/jpeg,image/png,image/gif,image/webp"><div class="ad-upload-progress"><span></span></div><div class="ad-upload-msg"></div></div>
            <div class="wide"><label>图片说明</label><input type="text" name="alt" class="form-control"></div>
            <div><label>状态</label><div style="padding-top:7px;"><label><input type="checkbox" name="active" value="1" checked> 启用</label></div></div>
            <div class="wide"><label>定时上线</label><input type="datetime-local" name="start_at" class="form-control"></div>
            <div class="wide"><label>定时下线</label><input type="datetime-local" name="end_at" class="form-control"></div>
          </div>
          <div style="margin-top:14px;"><button class="btn ad-primary-btn" type="submit"><span class="glyphicon glyphicon-plus"></span> 添加广告</button></div>
        </form>
      </div>
    </div>

    <div class="ad-card">
      <div class="ad-card-hd ad-list-hd">
        <span class="ad-list-title"><span class="glyphicon glyphicon-list"></span> 广告列表</span>
        <div class="ad-list-actions">
          <form method="post"><input type="hidden" name="action" value="clear_cache"><button class="btn btn-sm btn-warning" type="submit"><span class="glyphicon glyphicon-refresh"></span> 一键清缓存</button></form>
          <a class="btn btn-sm btn-default" href="./ad.php?check_images=1"><span class="glyphicon glyphicon-search"></span> 检测图片状态</a>
        </div>
      </div>
      <div class="table-responsive">
        <table class="table table-hover ad-table" style="margin-bottom:0;">
          <thead><tr><th>ID</th><th>预览</th><th>位置</th><th>标题 / 链接</th><th>投放</th><th>排序/权重</th><th>统计</th><th>图片检测</th><th>操作</th></tr></thead>
          <tbody>
          <?php if(empty($ads)): ?>
            <tr><td colspan="9" class="text-center text-muted" style="padding:30px;">暂无广告，请先添加。</td></tr>
          <?php else: foreach($ads as $ad):
            $status = qifu_ad_status_text($ad);
            $img_check = $check_images ? qifu_ad_check_image($ad['image']) : array(null, '未检测');
            $position_label = isset($positions[$ad['position']]) ? $positions[$ad['position']] : $ad['position'];
            $slot_label = $ad['position'] == 'below_search' ? ' / 位置'.intval($ad['slot']) : '';
            $start_value = $ad['start_at'] ? date('Y-m-d\TH:i', strtotime($ad['start_at'])) : '';
            $end_value = $ad['end_at'] ? date('Y-m-d\TH:i', strtotime($ad['end_at'])) : '';
          ?>
            <tr>
              <td><?php echo intval($ad['id']); ?></td>
              <td><?php if($ad['image']): ?><img class="ad-preview" src="<?php echo htmlspecialchars($ad['image']); ?>" alt="ad"><?php else: ?><span class="text-muted">无图</span><?php endif; ?></td>
              <td><?php echo htmlspecialchars($position_label.$slot_label); ?><br><span class="ad-status <?php echo $status[0]; ?>"><?php echo $status[1]; ?></span></td>
              <td><b><?php echo htmlspecialchars($ad['title'] ?: '未命名广告'); ?></b><br><small class="text-muted"><?php echo htmlspecialchars($ad['link'] ?: '未设置跳转链接'); ?></small></td>
              <td><small>上线：<?php echo htmlspecialchars($ad['start_at'] ?: '立即'); ?><br>下线：<?php echo htmlspecialchars($ad['end_at'] ?: '不限'); ?></small></td>
              <td><?php echo intval($ad['sort']); ?> / <?php echo intval($ad['weight']); ?></td>
              <td><span class="label label-info"><?php echo intval($ad['views']); ?> 曝光</span><br><span class="label label-success" style="margin-top:4px;display:inline-block;"><?php echo intval($ad['clicks']); ?> 点击</span></td>
              <td><?php if($img_check[0] === true): ?><span class="ad-img-ok"><?php echo $img_check[1]; ?></span><?php elseif($img_check[0] === false): ?><span class="ad-img-bad"><?php echo htmlspecialchars($img_check[1]); ?></span><?php else: ?><span class="text-muted"><?php echo htmlspecialchars($img_check[1]); ?></span><?php endif; ?></td>
              <td style="min-width:170px;">
                <button class="btn btn-xs btn-primary" type="button" data-toggle="collapse" data-target="#adEdit<?php echo intval($ad['id']); ?>">编辑</button>
                <form method="post" style="display:inline;" onsubmit="return confirm('确定删除这个广告吗？统计也会一起删除。');"><input type="hidden" name="action" value="delete_ad"><input type="hidden" name="id" value="<?php echo intval($ad['id']); ?>"><button class="btn btn-xs btn-danger" type="submit">删除</button></form>
              </td>
            </tr>
            <tr class="collapse" id="adEdit<?php echo intval($ad['id']); ?>"><td colspan="9">
              <form method="post" enctype="multipart/form-data" class="ad-edit-form">
                <input type="hidden" name="action" value="save_ad">
                <input type="hidden" name="id" value="<?php echo intval($ad['id']); ?>">
                <div class="ad-form-grid">
                  <div><label>展示位置</label><select name="position" class="form-control ad-position-select"><?php foreach($positions as $key=>$label): ?><option value="<?php echo $key; ?>" <?php echo $ad['position']==$key?'selected':''; ?>><?php echo $label; ?></option><?php endforeach; ?></select><div class="ad-size-hint"></div></div>
                  <div><label>四等分位置</label><select name="slot" class="form-control ad-slot-select"><?php for($i=1;$i<=4;$i++): ?><option value="<?php echo $i; ?>" <?php echo intval($ad['slot'])==$i?'selected':''; ?>>位置<?php echo $i; ?></option><?php endfor; ?></select></div>
                  <div><label>排序</label><input type="number" name="sort" value="<?php echo intval($ad['sort']); ?>" class="form-control"></div>
                  <div><label>权重</label><input type="number" name="weight" value="<?php echo intval($ad['weight']); ?>" min="1" max="50" class="form-control"></div>
                  <div class="wide"><label>广告标题</label><input type="text" name="title" value="<?php echo htmlspecialchars($ad['title']); ?>" class="form-control"></div>
                  <div class="wide"><label>跳转链接</label><input type="text" name="link" value="<?php echo htmlspecialchars($ad['link']); ?>" class="form-control"></div>
                  <div class="wide"><label>图片外链 / 上传后自动填入</label><input type="text" name="image" value="<?php echo htmlspecialchars($ad['image']); ?>" class="form-control ad-url-input"></div>
                  <div class="wide"><label>上传替换图片</label><input type="file" name="ad_file" class="form-control ad-upload-input" accept="image/jpeg,image/png,image/gif,image/webp"><div class="ad-upload-progress"><span></span></div><div class="ad-upload-msg"></div></div>
                  <div class="wide"><label>图片说明</label><input type="text" name="alt" value="<?php echo htmlspecialchars($ad['alt']); ?>" class="form-control"></div>
                  <div><label>状态</label><div style="padding-top:7px;"><label><input type="checkbox" name="active" value="1" <?php echo intval($ad['active'])==1?'checked':''; ?>> 启用</label></div></div>
                  <div class="wide"><label>定时上线</label><input type="datetime-local" name="start_at" value="<?php echo $start_value; ?>" class="form-control"></div>
                  <div class="wide"><label>定时下线</label><input type="datetime-local" name="end_at" value="<?php echo $end_value; ?>" class="form-control"></div>
                </div>
                <div style="margin-top:14px;"><button class="btn ad-primary-btn" type="submit"><span class="glyphicon glyphicon-ok"></span> 保存广告</button></div>
              </form>
            </td></tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<script>
(function(){
  function setMsg(box, text, ok){ box.textContent = text; box.style.color = ok ? '#1f7a3a' : '#b42318'; }
  function updateSlotState(form){
    var pos = form.querySelector('.ad-position-select');
    var slot = form.querySelector('.ad-slot-select');
    var hint = form.querySelector('.ad-size-hint');
    if(!pos) return;
    if(slot) slot.disabled = pos.value !== 'below_search';
    if(hint){
      var tips = {
        below_search: '<b>推荐尺寸：420 x 120 px</b><small>四等分广告建议 2:1 到 3.5:1 横图，GIF 动图也建议控制在这个比例。</small>',
        pc_right: '<b>推荐尺寸：300 x 400 px</b><small>PC右侧悬浮建议竖版图，比例约 3:4，内容不要贴边。</small>',
        pc_left: '<b>推荐尺寸：300 x 400 px</b><small>PC左侧悬浮建议竖版图，比例约 3:4，左右留出安全边距。</small>'
      };
      hint.innerHTML = tips[pos.value] || tips.below_search;
    }
  }
  document.querySelectorAll('.ad-edit-form').forEach(function(form){
    updateSlotState(form);
    var pos = form.querySelector('.ad-position-select');
    if(pos) pos.addEventListener('change', function(){ updateSlotState(form); });
  });
  document.querySelectorAll('.ad-upload-input').forEach(function(input){
    input.addEventListener('change', function(){
      if(!this.files || !this.files[0]) return;
      var form = this.closest('form');
      var target = form.querySelector('.ad-url-input');
      var bar = form.querySelector('.ad-upload-progress');
      var fill = bar.querySelector('span');
      var msg = form.querySelector('.ad-upload-msg');
      var data = new FormData();
      data.append('file', this.files[0]);
      data.append('slot', 'ad_admin');
      var xhr = new XMLHttpRequest();
      xhr.open('POST', './ajax_upload_ad.php', true);
      bar.style.display = 'block';
      fill.style.width = '0%';
      setMsg(msg, '准备上传...', true);
      xhr.upload.onprogress = function(e){
        if(e.lengthComputable) fill.style.width = Math.round(e.loaded / e.total * 100) + '%';
      };
      xhr.onload = function(){
        var res;
        try{ res = JSON.parse(xhr.responseText); }catch(e){ setMsg(msg, '服务器返回异常：' + xhr.responseText.substring(0, 120), false); return; }
        if(res.code == 1){
          fill.style.width = '100%';
          target.value = res.url;
          input.value = '';
          setMsg(msg, '上传成功，保存广告后生效。', true);
        }else{
          setMsg(msg, res.msg || '上传失败，可直接点保存尝试兜底上传。', false);
        }
      };
      xhr.onerror = function(){ setMsg(msg, '网络错误，可直接点保存尝试兜底上传。', false); };
      xhr.send(data);
    });
  });
})();
</script>
