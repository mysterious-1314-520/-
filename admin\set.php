<?php
/* 祈福导航系统 V1.3 官方开源：https://gitee.com/qifuxitong/daohang */

include __DIR__ . "/../includes/common.php";
$title='祈福导航系统 - 快捷设置';
if($islogin!=1){
    @header('Location: ./login.php');
    exit;
}

// 处理提交
if($_SERVER['REQUEST_METHOD']=='POST'){
    if(isset($_POST['base_settings'])){
        saveSetting('sitename', isset($_POST['sitename']) ? $_POST['sitename'] : $conf['sitename']);
        saveSetting('title', isset($_POST['title']) ? $_POST['title'] : $conf['title']);
        saveSetting('keywords', isset($_POST['keywords']) ? $_POST['keywords'] : (isset($conf['keywords']) ? $conf['keywords'] : ''));
        saveSetting('description', isset($_POST['description']) ? $_POST['description'] : $conf['description']);
        saveSetting('modal', isset($_POST['modal']) ? $_POST['modal'] : $conf['modal']);
        saveSetting('music', isset($_POST['music']) ? $_POST['music'] : (isset($conf['music']) ? $conf['music'] : ''));
        saveSetting('kfqq', isset($_POST['kfqq']) ? $_POST['kfqq'] : $conf['kfqq']);
        saveSetting('url', isset($_POST['url']) ? $_POST['url'] : (isset($conf['url']) ? $conf['url'] : ''));
        saveSetting('qqjump', isset($_POST['qqjump']) ? $_POST['qqjump'] : $conf['qqjump']);
        $site_logo = isset($_POST['site_logo']) ? trim($_POST['site_logo']) : '';
        $upload_dir = ROOT.'images/logo/';
        if(!is_dir($upload_dir))@mkdir($upload_dir,0755,true);
        if(isset($_FILES['site_logo_upload']) && $_FILES['site_logo_upload']['error']==0){
            $ext = strtolower(pathinfo($_FILES['site_logo_upload']['name'],PATHINFO_EXTENSION));
            if(in_array($ext,array('jpg','jpeg','png','gif','webp','svg'))){
                $filename = 'logo_'.time().'.'.$ext;
                if(move_uploaded_file($_FILES['site_logo_upload']['tmp_name'],$upload_dir.$filename)){
                    $site_logo = $rooturl.'images/logo/'.$filename;
                }
            }
        }
        saveSetting('site_logo',$site_logo);
        if(!empty($_POST['pwd']))saveSetting('admin_pwd',$_POST['pwd']);
        writeLog('修改', '设置', 0, '保存基本设置');
        $CACHE->clear();
        $conf = $CACHE->update();
        $saved = true;
    }
    if(isset($_POST['bg_settings'])){
        saveSetting('bg_mode',$_POST['bg_mode']);
        saveSetting('bg_custom',$_POST['bg_custom']);
        $upload_dir = ROOT.'images/bg/';
        if(!is_dir($upload_dir))@mkdir($upload_dir,0755,true);
        if($_POST['bg_mode']=='custom' && isset($_FILES['bg_upload']) && $_FILES['bg_upload']['error']==0){
            $ext = pathinfo($_FILES['bg_upload']['name'],PATHINFO_EXTENSION);
            if(in_array(strtolower($ext),['jpg','jpeg','png','gif','webp'])){
                $filename = 'custom_'.time().'.'.$ext;
                if(move_uploaded_file($_FILES['bg_upload']['tmp_name'],$upload_dir.$filename)){
                    saveSetting('bg_custom',$siteurl.'images/bg/'.$filename);
                }
            }
        }
        writeLog('修改', '设置', 0, '保存背景设置');
        $CACHE->clear();
        $conf = $CACHE->update();
        $saved = true;
    }
    if(isset($_POST['ui_settings'])){
        saveSetting('card_size',$_POST['card_size']);
        saveSetting('columns',$_POST['columns']);
        saveSetting('time_format',$_POST['time_format']);
        saveSetting('clock_style',$_POST['clock_style']);
        saveSetting('announcement',$_POST['announcement']);
        saveSetting('show_search',$_POST['show_search']);
        saveSetting('show_clock',$_POST['show_clock']);
        saveSetting('show_tags',$_POST['show_tags']);
        saveSetting('bg_animation',$_POST['bg_animation']);
        saveSetting('card_animation',$_POST['card_animation']);
        writeLog('修改', '设置', 0, '保存界面设置');
        $CACHE->clear();
        $conf = $CACHE->update();
        $saved = true;
    }
    if(false && isset($_POST['ad_settings'])){
        $ad_enabled = isset($_POST['ad_enabled']) && $_POST['ad_enabled']=='1' ? '1' : '0';
        $ad_position = in_array($_POST['ad_position'], array('below_search','pc_side')) ? $_POST['ad_position'] : 'below_search';
        $ad_image = isset($_POST['ad_image']) ? trim($_POST['ad_image']) : '';
        $ad_link = isset($_POST['ad_link']) ? trim($_POST['ad_link']) : '';
        $ad_title = isset($_POST['ad_title']) ? trim($_POST['ad_title']) : '';
        $ad_alt = isset($_POST['ad_alt']) ? trim($_POST['ad_alt']) : '';
        $ad_new_window = isset($_POST['ad_new_window']) && $_POST['ad_new_window']=='1' ? '1' : '0';
        $upload_dir = ROOT.'images/ad/';
        if(!is_dir($upload_dir))@mkdir($upload_dir,0755,true);
        if(isset($_FILES['ad_upload']) && $_FILES['ad_upload']['error']==0){
            $ext = strtolower(pathinfo($_FILES['ad_upload']['name'],PATHINFO_EXTENSION));
            if(in_array($ext,array('jpg','jpeg','png','gif','webp'))){
                $filename = 'ad_'.time().'.'.$ext;
                if(move_uploaded_file($_FILES['ad_upload']['tmp_name'],$upload_dir.$filename)){
                    $ad_image = $siteurl.'images/ad/'.$filename;
                }
            }
        }
        saveSetting('ad_enabled',$ad_enabled);
        saveSetting('ad_position',$ad_position);
        saveSetting('ad_image',$ad_image);
        saveSetting('ad_link',$ad_link);
        saveSetting('ad_title',$ad_title);
        saveSetting('ad_alt',$ad_alt);
        saveSetting('ad_new_window',$ad_new_window);
        writeLog('修改', '设置', 0, '保存广告设置');
        $CACHE->clear();
        $conf = $CACHE->update();
        $saved = true;
    }
    if(isset($_POST['media_settings'])){
        saveSetting('bg_music',$_POST['bg_music']);
        saveSetting('bg_music_volume',$_POST['bg_music_volume']);
        saveSetting('ping_enabled',$_POST['ping_enabled']);
        $ping_alert_latency_save = max(500, min(30000, intval($_POST['ping_alert_latency'])));
        saveSetting('ping_alert_latency',$ping_alert_latency_save);
        writeLog('修改', '设置', 0, '保存音乐与延迟设置');
        $CACHE->clear();
        $conf = $CACHE->update();
        $saved = true;
    }
    if(isset($_POST['footer_settings'])){
        saveSetting('footer_text',$_POST['footer_text']);
        saveSetting('footer_link',$_POST['footer_link']);
        saveSetting('footer_link_text',$_POST['footer_link_text']);
        saveSetting('icp',$_POST['icp']);
        $footer_opacity_save = max(5, min(100, intval($_POST['footer_opacity'])));
        $footer_size_save = max(10, min(18, intval($_POST['footer_size'])));
        saveSetting('footer_opacity',$footer_opacity_save);
        saveSetting('footer_size',$footer_size_save);
        writeLog('修改', '设置', 0, '保存页脚设置');
        $CACHE->clear();
        $conf = $CACHE->update();
        $saved = true;
    }
    if(isset($_POST['mail_settings'])){
        saveSetting('mail_enabled',$_POST['mail_enabled']);
        saveSetting('mail_to',$_POST['mail_to']);
        saveSetting('mail_user',$_POST['mail_user']);
        saveSetting('mail_pass',$_POST['mail_pass']);
        saveSetting('mail_host',$_POST['mail_host']);
        saveSetting('mail_port',$_POST['mail_port']);
        saveSetting('mail_sender',$_POST['mail_sender']);
        writeLog('修改', '设置', 0, '保存邮件设置');
        $CACHE->clear();
        $conf = $CACHE->update();
        $saved = true;
    }
}

// 获取当前设置值
$conf = $CACHE->update();

// 基本设置
$site_logo = isset($conf['site_logo']) ? $conf['site_logo'] : '';
$bg_mode = isset($conf['bg_mode']) ? $conf['bg_mode'] : 'default';
$bg_custom = isset($conf['bg_custom']) ? $conf['bg_custom'] : '';

// UI设置
$card_size = isset($conf['card_size']) ? $conf['card_size'] : 'normal';
$columns = isset($conf['columns']) ? $conf['columns'] : 'auto';
$time_format = isset($conf['time_format']) ? $conf['time_format'] : '24';
$clock_style = isset($conf['clock_style']) ? $conf['clock_style'] : 'digital';
$announcement = isset($conf['announcement']) ? $conf['announcement'] : '';
$show_search = isset($conf['show_search']) ? $conf['show_search'] : '1';
$show_clock = isset($conf['show_clock']) ? $conf['show_clock'] : '1';
$show_tags = isset($conf['show_tags']) ? $conf['show_tags'] : '1';
$bg_animation = isset($conf['bg_animation']) ? $conf['bg_animation'] : '1';
$card_animation = isset($conf['card_animation']) ? $conf['card_animation'] : '1';

// 广告设置
$ad_enabled = isset($conf['ad_enabled']) ? $conf['ad_enabled'] : '0';
$ad_position = isset($conf['ad_position']) ? $conf['ad_position'] : 'below_search';
$ad_image = isset($conf['ad_image']) ? $conf['ad_image'] : '';
$ad_link = isset($conf['ad_link']) ? $conf['ad_link'] : '';
$ad_title = isset($conf['ad_title']) ? $conf['ad_title'] : '';
$ad_alt = isset($conf['ad_alt']) ? $conf['ad_alt'] : '';
$ad_new_window = isset($conf['ad_new_window']) ? $conf['ad_new_window'] : '1';

// 音乐设置
$bg_music = isset($conf['bg_music']) ? $conf['bg_music'] : '';
$bg_music_volume = isset($conf['bg_music_volume']) ? $conf['bg_music_volume'] : '50';

// Ping延迟设置
$ping_enabled = isset($conf['ping_enabled']) ? $conf['ping_enabled'] : '0';
$ping_alert_latency = isset($conf['ping_alert_latency']) ? intval($conf['ping_alert_latency']) : 3000;
$ping_alert_latency = max(500, min(30000, $ping_alert_latency));
$ping_last_run = isset($conf['ping_last_run']) ? $conf['ping_last_run'] : '';
$ping_last_time = !empty($conf['ping_last_time']) ? date('Y-m-d H:i:s', intval($conf['ping_last_time'])) : '暂未检测';

// 页脚设置
$footer_text = isset($conf['footer_text']) ? $conf['footer_text'] : '祈福导航系统 · 精选优质资源';
$footer_link = isset($conf['footer_link']) ? $conf['footer_link'] : '';
$footer_link_text = isset($conf['footer_link_text']) ? $conf['footer_link_text'] : '';
$icp = isset($conf['icp']) ? $conf['icp'] : '';
$footer_opacity = isset($conf['footer_opacity']) ? intval($conf['footer_opacity']) : 25;
$footer_size = isset($conf['footer_size']) ? intval($conf['footer_size']) : 12;
$footer_opacity = max(5, min(100, $footer_opacity));
$footer_size = max(10, min(18, $footer_size));

// 邮件设置
$mail_enabled = isset($conf['mail_enabled']) ? $conf['mail_enabled'] : '0';
$mail_to = isset($conf['mail_to']) ? $conf['mail_to'] : '';
$mail_user = isset($conf['mail_user']) ? $conf['mail_user'] : '';
$mail_pass = isset($conf['mail_pass']) ? $conf['mail_pass'] : '';
$mail_host = isset($conf['mail_host']) ? $conf['mail_host'] : 'smtp.qq.com';
$mail_port = isset($conf['mail_port']) ? $conf['mail_port'] : '587';
$mail_sender = isset($conf['mail_sender']) ? $conf['mail_sender'] : '';
include './head.php';
?>
<div class="container" style="padding-top:70px;">
<?php if(isset($saved)){ ?>
    <div class="alert alert-success" style="margin:0 0 20px 0;"><span class="glyphicon glyphicon-ok"></span> 保存成功！</div>
<?php } ?>
    <div class="col-xs-12 col-sm-10 col-lg-8 center-block" style="float: none;">

      <!-- 基本设置 -->
      <div class="panel panel-primary">
        <div class="panel-heading">
          <h3 class="panel-title">⚙️ 基本设置</h3>
        </div>
        <div class="panel-body" style="padding:20px;">
          <form action="./set.php" method="post" enctype="multipart/form-data">
            <input type="hidden" name="base_settings" value="1">
            <div class="row">
              <div class="col-sm-6">
                <div class="form-group">
                  <label>网站名称</label>
                  <input type="text" name="sitename" value="<?php echo htmlspecialchars($conf['sitename']); ?>" class="form-control" required>
                </div>
              </div>
              <div class="col-sm-6">
                <div class="form-group">
                  <label>标题栏后缀</label>
                  <input type="text" name="title" value="<?php echo htmlspecialchars($conf['title']); ?>" class="form-control">
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-sm-6">
                <div class="form-group">
                  <label>网站描述</label>
                  <input type="text" name="description" value="<?php echo htmlspecialchars($conf['description']); ?>" class="form-control">
                </div>
              </div>
              <div class="col-sm-6">
                <div class="form-group">
                  <label>客服QQ</label>
                  <input type="text" name="kfqq" value="<?php echo htmlspecialchars($conf['kfqq']); ?>" class="form-control">
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-sm-6">
                <div class="form-group">
                  <label>导航介绍语</label>
                  <input type="text" name="modal" value="<?php echo htmlspecialchars($conf['modal']); ?>" class="form-control">
                </div>
              </div>
              <div class="col-sm-3">
                <div class="form-group">
                  <label>手机QQ跳转浏览器</label>
                  <select name="qqjump" class="form-control" default="<?php echo $conf['qqjump']?>">
                    <option value="0">关闭</option>
                    <option value="1">开启</option>
                  </select>
                </div>
              </div>
              <div class="col-sm-3">
                <div class="form-group">
                  <label>密码重置(留空不变)</label>
                  <input type="text" name="pwd" value="" class="form-control" placeholder="不修改请留空">
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-sm-8">
                <div class="form-group">
                  <label>网站LOGO</label>
                  <div class="input-group">
                    <input type="text" name="site_logo" value="<?php echo htmlspecialchars($site_logo); ?>" class="form-control" placeholder="可填写图片链接，留空则显示网站名称首字">
                    <span class="input-group-btn"><label class="btn btn-default" style="margin:0;">上传<input type="file" name="site_logo_upload" accept="image/*,.svg" style="display:none;"></label></span>
                  </div>
                  <p class="help-block" style="margin-bottom:0;">后台左侧顶部会使用这里的LOGO和网站名称。</p>
                </div>
              </div>
              <div class="col-sm-4">
                <div class="form-group">
                  <label>当前预览</label>
                  <div class="qf-setting-logo-preview">
                    <?php if($site_logo){ ?>
                      <img src="<?php echo htmlspecialchars($site_logo); ?>" alt="LOGO">
                    <?php } else { ?>
                      <span><?php echo htmlspecialchars(function_exists('mb_substr') ? mb_substr($conf['sitename'],0,1,'UTF-8') : substr($conf['sitename'],0,1)); ?></span>
                    <?php } ?>
                    <strong><?php echo htmlspecialchars($conf['sitename']); ?></strong>
                  </div>
                </div>
              </div>
            </div>
            <button type="submit" class="btn btn-primary">💾 保存基本设置</button>
          </form>
        </div>
      </div>

      <!-- 背景设置 -->
      <div class="panel panel-info">
        <div class="panel-heading">
          <h3 class="panel-title">🖼️ 背景设置</h3>
        </div>
        <div class="panel-body" style="padding:20px;">
          <form action="./set.php" method="post" enctype="multipart/form-data">
            <input type="hidden" name="bg_settings" value="1">
            <div class="form-group">
              <label>背景模式</label>
              <select name="bg_mode" class="form-control" onchange="toggleBgOpts(this.value)">
                <option value="default" <?php echo $bg_mode=='default'?'selected':''; ?>>默认背景</option>
                <option value="custom" <?php echo $bg_mode=='custom'?'selected':''; ?>>自定义上传</option>
                <option value="bing" <?php echo $bg_mode=='bing'?'selected':''; ?>>必应壁纸(每日更新)</option>
              </select>
            </div>
            <div id="customBgOpts" style="display:<?php echo $bg_mode=='custom'?'block':'none'; ?>">
              <div class="form-group">
                <label>上传背景图片 / 输入图片URL</label>
                <div class="input-group">
                  <input type="text" name="bg_custom" value="<?php echo htmlspecialchars($bg_custom); ?>" class="form-control" placeholder="直接填图片链接">
                  <span class="input-group-btn"><label class="btn btn-default" style="margin:0;">上传<input type="file" name="bg_upload" accept="image/*" style="display:none;" onchange="this.form.bg_custom.value=this.files[0].name"></label></span>
                </div>
                <span class="help-block">支持 jpg、png、gif、webp 格式</span>
              </div>
            </div>
            <div id="bingOpts" style="display:<?php echo $bg_mode=='bing'?'block':'none'; ?>">
              <div class="alert alert-info" style="margin:0;">
                <span class="glyphicon glyphicon-info-sign"></span> 必应壁纸每日自动更新
              </div>
            </div>
            <button type="submit" class="btn btn-info">💾 保存背景设置</button>
          </form>
        </div>
      </div>

      <!-- 前台界面设置 -->
      <div class="panel panel-success" id="ui-settings">
        <div class="panel-heading">
          <h3 class="panel-title">🎨 前台界面设置</h3>
        </div>
        <div class="panel-body" style="padding:20px;">
          <form action="./set.php" method="post">
            <input type="hidden" name="ui_settings" value="1">

            <h4 style="margin-bottom:15px;">📐 布局设置</h4>
            <div class="row">
              <div class="col-sm-6">
                <div class="form-group">
                  <label>卡片大小</label>
                  <select name="card_size" class="form-control">
                    <option value="small" <?php echo $card_size=='small'?'selected':''; ?>>小卡片</option>
                    <option value="normal" <?php echo $card_size=='normal'?'selected':''; ?>>标准卡片</option>
                    <option value="large" <?php echo $card_size=='large'?'selected':''; ?>>大卡片</option>
                  </select>
                </div>
              </div>
              <div class="col-sm-6">
                <div class="form-group">
                  <label>网格列数</label>
                  <select name="columns" class="form-control">
                    <option value="2" <?php echo $columns=='2'?'selected':''; ?>>2列</option>
                    <option value="3" <?php echo $columns=='3'?'selected':''; ?>>3列</option>
                    <option value="4" <?php echo $columns=='4'?'selected':''; ?>>4列</option>
                    <option value="auto" <?php echo $columns=='auto'?'selected':''; ?>>自适应</option>
                  </select>
                </div>
              </div>
            </div>

            <h4 style="margin:20px 0 15px;">🕐 时钟设置</h4>
            <div class="row">
              <div class="col-sm-6">
                <div class="form-group">
                  <label>时间格式</label>
                  <select name="time_format" class="form-control">
                    <option value="24" <?php echo $time_format=='24'?'selected':''; ?>>24小时制</option>
                    <option value="12" <?php echo $time_format=='12'?'selected':''; ?>>12小时制</option>
                  </select>
                </div>
              </div>
              <div class="col-sm-6">
                <div class="form-group">
                  <label>时钟样式</label>
                  <select name="clock_style" class="form-control">
                    <option value="digital" <?php echo $clock_style=='digital'?'selected':''; ?>>数字时钟</option>
                    <option value="simple" <?php echo $clock_style=='simple'?'selected':''; ?>>简约文字钟</option>
                  </select>
                </div>
              </div>
            </div>

            <h4 style="margin:20px 0 15px;">📝 首页公告</h4>
            <div class="form-group">
              <label>公告内容（留空则不显示）</label>
              <textarea name="announcement" class="form-control" rows="2" placeholder="输入公告内容，如：🎉 新年快乐！"><?php echo htmlspecialchars($announcement); ?></textarea>
            </div>

            <h4 style="margin:20px 0 15px;">👁️ 显示控制</h4>
            <div class="row">
              <div class="col-sm-4">
                <div class="form-group">
                  <label>搜索框</label>
                  <select name="show_search" class="form-control">
                    <option value="1" <?php echo $show_search=='1'?'selected':''; ?>>显示</option>
                    <option value="0" <?php echo $show_search=='0'?'selected':''; ?>>隐藏</option>
                  </select>
                </div>
              </div>
              <div class="col-sm-4">
                <div class="form-group">
                  <label>时钟</label>
                  <select name="show_clock" class="form-control">
                    <option value="1" <?php echo $show_clock=='1'?'selected':''; ?>>显示</option>
                    <option value="0" <?php echo $show_clock=='0'?'selected':''; ?>>隐藏</option>
                  </select>
                </div>
              </div>
              <div class="col-sm-4">
                <div class="form-group">
                  <label>快捷标签</label>
                  <select name="show_tags" class="form-control">
                    <option value="1" <?php echo $show_tags=='1'?'selected':''; ?>>显示</option>
                    <option value="0" <?php echo $show_tags=='0'?'selected':''; ?>>隐藏</option>
                  </select>
                </div>
              </div>
            </div>

            <h4 style="margin:20px 0 15px;">🎭 动画设置</h4>
            <div class="row">
              <div class="col-sm-6">
                <div class="form-group">
                  <label>背景动画</label>
                  <select name="bg_animation" class="form-control">
                    <option value="1" <?php echo $bg_animation=='1'?'selected':''; ?>>开启</option>
                    <option value="0" <?php echo $bg_animation=='0'?'selected':''; ?>>关闭</option>
                  </select>
                </div>
              </div>
              <div class="col-sm-6">
                <div class="form-group">
                  <label>卡片悬浮动画</label>
                  <select name="card_animation" class="form-control">
                    <option value="1" <?php echo $card_animation=='1'?'selected':''; ?>>开启</option>
                    <option value="0" <?php echo $card_animation=='0'?'selected':''; ?>>关闭</option>
                  </select>
                </div>
              </div>
            </div>

            <button type="submit" class="btn btn-success">💾 保存界面设置</button>
          </form>
        </div>
      </div>

      <!-- 音乐与延迟检测 -->
      <div class="panel panel-info">
        <div class="panel-heading">
          <h3 class="panel-title">广告设置</h3>
        </div>
        <div class="panel-body" style="padding:20px;">
          <form action="./set.php" method="post" enctype="multipart/form-data">
            <input type="hidden" name="ad_settings" value="1">
            <div class="row">
              <div class="col-sm-4">
                <div class="form-group">
                  <label>广告状态</label>
                  <select name="ad_enabled" class="form-control">
                    <option value="0" <?php echo $ad_enabled=='0'?'selected':''; ?>>关闭</option>
                    <option value="1" <?php echo $ad_enabled=='1'?'selected':''; ?>>开启</option>
                  </select>
                </div>
              </div>
              <div class="col-sm-4">
                <div class="form-group">
                  <label>展示位置</label>
                  <select name="ad_position" class="form-control">
                    <option value="below_search" <?php echo $ad_position=='below_search'?'selected':''; ?>>搜索栏下方</option>
                    <option value="pc_side" <?php echo $ad_position=='pc_side'?'selected':''; ?>>PC端右侧悬浮</option>
                  </select>
                </div>
              </div>
              <div class="col-sm-4">
                <div class="form-group">
                  <label>打开方式</label>
                  <select name="ad_new_window" class="form-control">
                    <option value="1" <?php echo $ad_new_window=='1'?'selected':''; ?>>新窗口打开</option>
                    <option value="0" <?php echo $ad_new_window=='0'?'selected':''; ?>>当前窗口打开</option>
                  </select>
                </div>
              </div>
            </div>
            <div class="form-group">
              <label>广告图片 URL / 上传图片</label>
              <div class="input-group">
                <input type="text" name="ad_image" value="<?php echo htmlspecialchars($ad_image); ?>" class="form-control" placeholder="https://example.com/ad.jpg">
                <span class="input-group-btn"><label class="btn btn-default" style="margin:0;">上传<input type="file" name="ad_upload" accept="image/*" style="display:none;"></label></span>
              </div>
              <span class="help-block">支持 jpg、png、gif、webp。建议搜索栏下方使用横幅图，PC侧边使用竖图或方图。</span>
            </div>
            <div class="row">
              <div class="col-sm-6">
                <div class="form-group">
                  <label>广告跳转链接</label>
                  <input type="text" name="ad_link" value="<?php echo htmlspecialchars($ad_link); ?>" class="form-control" placeholder="https://example.com">
                </div>
              </div>
              <div class="col-sm-6">
                <div class="form-group">
                  <label>广告标题</label>
                  <input type="text" name="ad_title" value="<?php echo htmlspecialchars($ad_title); ?>" class="form-control" placeholder="可选，用于悬停提示">
                </div>
              </div>
            </div>
            <div class="form-group">
              <label>图片说明</label>
              <input type="text" name="ad_alt" value="<?php echo htmlspecialchars($ad_alt); ?>" class="form-control" placeholder="可选，用于图片无法加载时显示">
            </div>
            <?php if($ad_image): ?>
            <div class="form-group">
              <label>当前广告预览</label>
              <div style="max-width:520px;border:1px solid #ddd;border-radius:6px;padding:10px;background:#fafafa;">
                <img src="<?php echo htmlspecialchars($ad_image); ?>" alt="" style="max-width:100%;height:auto;border-radius:4px;">
              </div>
            </div>
            <?php endif; ?>
            <button type="submit" class="btn btn-info">保存广告设置</button>
          </form>
        </div>
      </div>

      <div class="panel panel-warning">
        <div class="panel-heading">
          <h3 class="panel-title">🎵 音乐与延迟检测</h3>
        </div>
        <div class="panel-body" style="padding:20px;">
          <form action="./set.php" method="post">
            <input type="hidden" name="media_settings" value="1">

            <h4 style="margin-bottom:15px;">🎧 背景音乐</h4>
            <div class="row">
              <div class="col-sm-8">
                <div class="form-group">
                  <label>音乐直链（MP3/OGG格式）</label>
                  <input type="text" name="bg_music" value="<?php echo htmlspecialchars($bg_music); ?>" class="form-control" placeholder="https://example.com/music.mp3">
                </div>
              </div>
              <div class="col-sm-4">
                <div class="form-group">
                  <label>音量（0-100）</label>
                  <input type="number" name="bg_music_volume" value="<?php echo $bg_music_volume; ?>" class="form-control" min="0" max="100">
                </div>
              </div>
            </div>
            <div class="alert alert-info">
              <span class="glyphicon glyphicon-info-sign"></span> 填入音乐直链后前台会自动显示音乐控制按钮，支持自动播放/暂停/调节音量
            </div>

            <h4 style="margin:25px 0 15px;">📡 站点延迟检测</h4>
            <div class="row">
              <div class="col-sm-6">
                <div class="form-group">
                  <label>显示站点Ping延迟</label>
                  <select name="ping_enabled" class="form-control">
                    <option value="0" <?php echo $ping_enabled=='0'?'selected':''; ?>>关闭</option>
                    <option value="1" <?php echo $ping_enabled=='1'?'selected':''; ?>>开启</option>
                  </select>
                </div>
              </div>
              <div class="col-sm-6" style="padding-top:25px;">
                <div class="alert alert-warning" style="margin:0;">
                  <span class="glyphicon glyphicon-warning-sign"></span> 开启后前台卡片右上角显示状态灯（绿色=正常/红色=不可访问或延迟过高）
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-sm-6">
                <div class="form-group">
                  <label>红灯延迟阈值（毫秒）</label>
                  <input type="number" name="ping_alert_latency" value="<?php echo $ping_alert_latency; ?>" class="form-control" min="500" max="30000">
                  <span class="help-block">默认 3000ms，超过该值前台显示红灯</span>
                </div>
              </div>
            </div>
            <div class="alert alert-info">
              <span class="glyphicon glyphicon-info-sign"></span>
              每天 0 点自动检测建议在宝塔/服务器计划任务中访问：
              <code><?php echo $rooturl; ?>cron_site_status.php?force=1</code>
              <br>最近检测：<?php echo htmlspecialchars($ping_last_time); ?><?php if($ping_last_run){ ?>（<?php echo htmlspecialchars($ping_last_run); ?>）<?php } ?>
            </div>
            <div class="alert alert-danger">
              <span class="glyphicon glyphicon-envelope"></span>
              红灯邮件提醒：开启本功能并在下方开启邮件通知后，每日计划任务检测到站点无法访问或延迟超过阈值，会自动发送汇总提醒到接收通知邮箱。
            </div>

            <button type="submit" class="btn btn-warning">💾 保存音乐与延迟设置</button>
          </form>
        </div>
      </div>

      <!-- 页脚设置 -->
      <div class="panel panel-default">
        <div class="panel-heading">
          <h3 class="panel-title">📝 页脚设置</h3>
        </div>
        <div class="panel-body" style="padding:20px;">
          <form action="./set.php" method="post">
            <input type="hidden" name="footer_settings" value="1">
            <div class="form-group">
              <label>版权文字</label>
              <input type="text" name="footer_text" value="<?php echo htmlspecialchars($footer_text); ?>" class="form-control" placeholder="如：祈福导航系统 · 精选优质资源">
            </div>
            <div class="row">
              <div class="col-sm-5">
                <div class="form-group">
                  <label>底部链接文字</label>
                  <input type="text" name="footer_link_text" value="<?php echo htmlspecialchars($footer_link_text); ?>" class="form-control" placeholder="如：关于本站">
                </div>
              </div>
              <div class="col-sm-7">
                <div class="form-group">
                  <label>底部链接地址</label>
                  <input type="text" name="footer_link" value="<?php echo htmlspecialchars($footer_link); ?>" class="form-control" placeholder="https://example.com">
                </div>
              </div>
            </div>
            <div class="form-group">
              <label>备案号</label>
              <input type="text" name="icp" value="<?php echo htmlspecialchars($icp); ?>" class="form-control" placeholder="如：豫ICP备2025000000号-1">
            </div>
            <div class="row">
              <div class="col-sm-6">
                <div class="form-group">
                  <label>底部透明度（5-100）</label>
                  <input type="number" name="footer_opacity" value="<?php echo $footer_opacity; ?>" class="form-control" min="5" max="100">
                  <span class="help-block">数值越小越透明，默认 25</span>
                </div>
              </div>
              <div class="col-sm-6">
                <div class="form-group">
                  <label>底部文字大小（10-18px）</label>
                  <input type="number" name="footer_size" value="<?php echo $footer_size; ?>" class="form-control" min="10" max="18">
                  <span class="help-block">默认 12px</span>
                </div>
              </div>
            </div>
            <div class="alert alert-info">
              <span class="glyphicon glyphicon-info-sign"></span> 备案号默认链接到工信部查询页面，留空则不显示
            </div>
            <button type="submit" class="btn btn-default">💾 保存页脚设置</button>
          </form>
        </div>
      </div>

      <!-- 邮件通知设置 -->
      <div class="panel panel-danger">
        <div class="panel-heading">
          <h3 class="panel-title">📧 邮件通知设置</h3>
        </div>
        <div class="panel-body" style="padding:20px;">
          <form action="./set.php" method="post">
            <input type="hidden" name="mail_settings" value="1">
            <div class="row">
              <div class="col-sm-4">
                <div class="form-group">
                  <label>开启邮件通知</label>
                  <select name="mail_enabled" class="form-control">
                    <option value="0" <?php echo $mail_enabled=='0'?'selected':''; ?>>关闭</option>
                    <option value="1" <?php echo $mail_enabled=='1'?'selected':''; ?>>开启</option>
                  </select>
                </div>
              </div>
              <div class="col-sm-8">
                <div class="form-group">
                  <label>接收通知邮箱</label>
                  <input type="email" name="mail_to" value="<?php echo htmlspecialchars($mail_to); ?>" class="form-control" placeholder="管理员工箱地址">
                </div>
              </div>
            </div>
            <div class="alert alert-info" style="margin:10px 0;">
              <h4 style="margin:0 0 8px;">📬 QQ邮箱 SMTP 设置教程</h4>
              <ol style="margin:0;padding-left:18px;font-size:13px;line-height:1.8;">
                <li>登录 <a href="https://mail.qq.com" target="_blank">mail.qq.com</a> → 设置 → 账户</li>
                <li>开启 <b>POP3/SMTP服务</b></li>
                <li>生成 <b>授权码</b>（不是QQ密码！）</li>
                <li>将授权码填入下方"邮箱密码"栏</li>
                <li>SMTP服务器填 <code>smtp.qq.com</code>，端口填 <code>587</code></li>
              </ol>
            </div>
            <div class="alert alert-warning">
              <span class="glyphicon glyphicon-bell"></span>
              此邮件配置同时用于友链审核通知和站点红灯提醒；当 Ping 检测发现站点无法访问或延迟过高变红时，系统会每日发送一次汇总邮件。
            </div>
            <div class="row">
              <div class="col-sm-6">
                <div class="form-group">
                  <label>SMTP服务器</label>
                  <input type="text" name="mail_host" value="<?php echo htmlspecialchars($mail_host); ?>" class="form-control" placeholder="smtp.qq.com">
                </div>
              </div>
              <div class="col-sm-3">
                <div class="form-group">
                  <label>SMTP端口</label>
                  <input type="number" name="mail_port" value="<?php echo htmlspecialchars($mail_port); ?>" class="form-control" placeholder="587">
                </div>
              </div>
              <div class="col-sm-3">
                <div class="form-group">
                  <label>发件人名称</label>
                  <input type="text" name="mail_sender" value="<?php echo htmlspecialchars($mail_sender); ?>" class="form-control" placeholder="友链系统">
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-sm-6">
                <div class="form-group">
                  <label>发件邮箱</label>
                  <input type="email" name="mail_user" value="<?php echo htmlspecialchars($mail_user); ?>" class="form-control" placeholder="123456@qq.com">
                </div>
              </div>
              <div class="col-sm-6">
                <div class="form-group">
                  <label>邮箱密码/授权码</label>
                  <input type="password" name="mail_pass" value="<?php echo htmlspecialchars($mail_pass); ?>" class="form-control" placeholder="填授权码，不是QQ密码">
                </div>
              </div>
            </div>
            <button type="submit" class="btn btn-danger">💾 保存邮件设置</button>
            <button type="button" class="btn btn-default" id="testMailBtn" style="margin-left:10px;">📤 发送测试邮件</button>
          </form>
          <div id="testMailMsg" style="margin-top:10px;"></div>
        </div>
      </div>

    </div>
</div>

<script>
$("input[name='ad_settings']").closest(".panel").remove();
var items = $("select[default]");
for (i = 0; i < items.length; i++) {
    $(items[i]).val($(items[i]).attr("default")||0);
}
function toggleBgOpts(mode){
    document.getElementById('customBgOpts').style.display = (mode=='custom')?'block':'none';
    document.getElementById('bingOpts').style.display = (mode=='bing')?'block':'none';
}

document.getElementById('testMailBtn').addEventListener('click', function(){
    var btn = this;
    var msg = document.getElementById('testMailMsg');
    btn.disabled = true;
    btn.textContent = '发送中...';
    msg.innerHTML = '<div class="alert alert-info" style="margin:0;">正在连接邮箱服务器...</div>';
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'ajax_test_mail.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function(){
        btn.disabled = false;
        btn.textContent = '📤 发送测试邮件';
        var res;
        try {
            res = JSON.parse(xhr.responseText);
        } catch (e) {
            var raw = xhr.responseText ? xhr.responseText.replace(/<[^>]*>/g, '').trim() : '';
            msg.innerHTML = '<div class="alert alert-danger" style="margin:0;">服务器返回格式错误：' + (raw ? raw.substring(0, 160) : '空响应') + '</div>';
            return;
        }
        if(res.code == 1){
            msg.innerHTML = '<div class="alert alert-success" style="margin:0;">' + res.msg + '</div>';
        } else {
            msg.innerHTML = '<div class="alert alert-danger" style="margin:0;">发送失败：' + res.msg + '</div>';
        }
    };
    xhr.onerror = function(){
        btn.disabled = false;
        btn.textContent = '📤 发送测试邮件';
        msg.innerHTML = '<div class="alert alert-danger" style="margin:0;">网络错误，请检查配置</div>';
    };
    var form = btn.form;
    var data = [
        'mail_enabled=' + encodeURIComponent(form.mail_enabled.value),
        'mail_to=' + encodeURIComponent(form.mail_to.value),
        'mail_host=' + encodeURIComponent(form.mail_host.value),
        'mail_port=' + encodeURIComponent(form.mail_port.value),
        'mail_sender=' + encodeURIComponent(form.mail_sender.value),
        'mail_user=' + encodeURIComponent(form.mail_user.value),
        'mail_pass=' + encodeURIComponent(form.mail_pass.value)
    ].join('&');
    xhr.send(data);
});
</script>
