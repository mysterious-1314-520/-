<?php
/**
 * 创建广告组页面
 * 广告主后台 - 定向设置
 */

define('IN_CRONLITE', true);
define('SYSTEM_ROOT', dirname(__FILE__).'/');
define('ROOT', dirname(dirname(SYSTEM_ROOT)).'/');

require_once(ROOT.'includes/common.php');
require_once(ROOT.'includes/auth.php');

require_login();
require_role('advertiser');

$campaign_id = isset($_GET['campaign_id']) ? intval($_GET['campaign_id']) : 0;
$error = '';

try {
    // 获取广告主 ID
    $user_id = get_current_user_id();
    $advertiser = $DB->query("SELECT id FROM ad_advertisers WHERE user_id = $user_id")->fetch();
    
    // 验证活动归属
    if($campaign_id > 0) {
        $campaign = $DB->query("SELECT * FROM ad_campaigns WHERE id = $campaign_id AND advertiser_id = {$advertiser['id']}")->fetch();
        if(!$campaign) {
            die('活动不存在或无权操作');
        }
    }
    
    // 处理表单提交
    if(isset($_POST['submit'])) {
        $name = trim($_POST['name']);
        $gender = intval($_POST['gender']);
        $age_range = trim($_POST['age_range']);
        $regions = $_POST['regions'] ?? [];
        $devices = $_POST['devices'] ?? [];
        $os_list = $_POST['os_list'] ?? [];
        $browser_list = $_POST['browser_list'] ?? [];
        $frequency_cap = intval($_POST['frequency_cap']);
        $schedule = $_POST['schedule'] ?? [];
        
        if(empty($name)) {
            $error = '请输入广告组名称';
        }
        else {
            // 构建定向 JSON
            $regions_json = json_encode($regions, JSON_UNESCAPED_UNICODE);
            $devices_json = json_encode($devices, JSON_UNESCAPED_UNICODE);
            $os_json = json_encode($os_list, JSON_UNESCAPED_UNICODE);
            $browser_json = json_encode($browser_list, JSON_UNESCAPED_UNICODE);
            $schedule_json = json_encode($schedule, JSON_UNESCAPED_UNICODE);
            
            $stmt = $DB->prepare("
                INSERT INTO ad_groups 
                (campaign_id, name, gender, age_range, regions, devices, os_list, browser_list, schedule, frequency_cap)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $campaign_id,
                $name,
                $gender,
                $age_range,
                $regions_json,
                $devices_json,
                $os_json,
                $browser_json,
                $schedule_json,
                $frequency_cap
            ]);
            
            $group_id = $DB->lastInsertId();
            
            // 记录日志
            $DB->query("INSERT INTO ad_operation_logs (user_id, action, module, object_type, object_id) VALUES ($user_id, 'create_adgroup', 'advertiser', 'adgroup', $group_id)");
            
            // 跳转到创意创建
            header('Location: creative_create.php?group_id='.$group_id);
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
        <h5><i class="fa fa-bullhorn"></i> 创建广告组 - 定向设置</h5>
    </div>
    
    <div class="card-body">
        <?php if($error): ?>
            <div class="alert alert-danger"><?=$error?></div>
        <?php endif; ?>
        
        <form method="post">
            <div class="form-group">
                <label class="required">广告组名称</label>
                <input type="text" name="name" class="form-control" placeholder="如：一线城市 - 男性 -25-34 岁" required>
            </div>
            
            <!-- 基础定向 -->
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label><i class="fa fa-venus-mars"></i> 性别定向</label>
                        <select name="gender" class="form-control">
                            <option value="0">不限</option>
                            <option value="1">男</option>
                            <option value="2">女</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label><i class="fa fa-user"></i> 年龄定向</label>
                        <select name="age_range" class="form-control">
                            <option value="">不限</option>
                            <option value="18-">18 岁以下</option>
                            <option value="18-24">18-24 岁</option>
                            <option value="25-34">25-34 岁</option>
                            <option value="35-44">35-44 岁</option>
                            <option value="45-54">45-54 岁</option>
                            <option value="55+">55 岁以上</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- 地域定向 -->
            <div class="form-group">
                <label><i class="fa fa-map-marker"></i> 地域定向</label>
                <div class="row">
                    <div class="col-md-4">
                        <select class="form-control" id="province" onchange="loadCity()">
                            <option value="">选择省份</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <select class="form-control" id="city">
                            <option value="">选择城市</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="button" class="btn btn-info btn-block" onclick="addRegion()">
                            <i class="fa fa-plus"></i> 添加
                        </button>
                    </div>
                </div>
                <div class="mt-2" id="selected_regions"></div>
                <input type="hidden" name="regions" id="regions_input">
            </div>
            
            <!-- 设备定向 -->
            <div class="form-group">
                <label><i class="fa fa-mobile"></i> 设备定向</label>
                <div class="row">
                    <div class="col-md-4">
                        <label class="checkbox-inline">
                            <input type="checkbox" name="devices[]" value="mobile"> 手机
                        </label>
                    </div>
                    <div class="col-md-4">
                        <label class="checkbox-inline">
                            <input type="checkbox" name="devices[]" value="pc"> 电脑
                        </label>
                    </div>
                    <div class="col-md-4">
                        <label class="checkbox-inline">
                            <input type="checkbox" name="devices[]" value="tablet"> 平板
                        </label>
                    </div>
                </div>
            </div>
            
            <!-- 操作系统定向 -->
            <div class="form-group">
                <label><i class="fa fa-desktop"></i> 操作系统定向</label>
                <div class="row">
                    <div class="col-md-3">
                        <label class="checkbox-inline">
                            <input type="checkbox" name="os_list[]" value="windows"> Windows
                        </label>
                    </div>
                    <div class="col-md-3">
                        <label class="checkbox-inline">
                            <input type="checkbox" name="os_list[]" value="macos"> macOS
                        </label>
                    </div>
                    <div class="col-md-3">
                        <label class="checkbox-inline">
                            <input type="checkbox" name="os_list[]" value="ios"> iOS
                        </label>
                    </div>
                    <div class="col-md-3">
                        <label class="checkbox-inline">
                            <input type="checkbox" name="os_list[]" value="android"> Android
                        </label>
                    </div>
                </div>
            </div>
            
            <!-- 浏览器定向 -->
            <div class="form-group">
                <label><i class="fa fa-chrome"></i> 浏览器定向</label>
                <div class="row">
                    <div class="col-md-4">
                        <label class="checkbox-inline">
                            <input type="checkbox" name="browser_list[]" value="chrome"> Chrome
                        </label>
                    </div>
                    <div class="col-md-4">
                        <label class="checkbox-inline">
                            <input type="checkbox" name="browser_list[]" value="firefox"> Firefox
                        </label>
                    </div>
                    <div class="col-md-4">
                        <label class="checkbox-inline">
                            <input type="checkbox" name="browser_list[]" value="safari"> Safari
                        </label>
                    </div>
                    <div class="col-md-4">
                        <label class="checkbox-inline">
                            <input type="checkbox" name="browser_list[]" value="edge"> Edge
                        </label>
                    </div>
                    <div class="col-md-4">
                        <label class="checkbox-inline">
                            <input type="checkbox" name="browser_list[]" value="ie"> IE
                        </label>
                    </div>
                    <div class="col-md-4">
                        <label class="checkbox-inline">
                            <input type="checkbox" name="browser_list[]" value="other"> 其他
                        </label>
                    </div>
                </div>
            </div>
            
            <!-- 投放时段 -->
            <div class="form-group">
                <label><i class="fa fa-clock"></i> 投放时段</label>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm" id="schedule_table">
                        <thead>
                            <tr>
                                <th>时段</th>
                                <?php for($i = 0; $i < 7; $i++): ?>
                                    <th><?=['周一','周二','周三','周四','周五','周六','周日'][$i]?></th>
                                <?php endfor; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php for($h = 0; $h < 24; $h++): ?>
                                <tr>
                                    <td><?=str_pad($h, 2, '0', STR_PAD_LEFT)?>:00</td>
                                    <?php for($d = 0; $d < 7; $d++): ?>
                                        <td class="text-center">
                                            <input type="checkbox" name="schedule[<?=$d?>][]" value="<?=$h?>" checked>
                                        </td>
                                    <?php endfor; ?>
                                </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                </div>
                <small class="text-muted">取消勾选以排除该时段，默认全时段投放</small>
            </div>
            
            <!-- 频控设置 -->
            <div class="form-group">
                <label><i class="fa fa-repeat"></i> 频控设置</label>
                <div class="input-group" style="max-width: 300px;">
                    <input type="number" name="frequency_cap" class="form-control" value="0" min="0">
                    <div class="input-group-append">
                        <span class="input-group-text">次/天</span>
                    </div>
                </div>
                <small class="text-muted">0 表示不限制，建议设置 10-20 次避免打扰用户</small>
            </div>
            
            <div class="form-group mt-4">
                <button type="submit" name="submit" class="btn btn-primary btn-block">
                    <i class="fa fa-check"></i> 下一步：创建创意
                </button>
                <a href="campaign_list.php" class="btn btn-secondary btn-block mt-2">
                    <i class="fa fa-arrow-left"></i> 返回列表
                </a>
            </div>
        </form>
    </div>
</div>

<script>
// 省份数据
const provinces = [
    '北京','天津','上海','重庆','河北','山西','辽宁','吉林','黑龙江',
    '江苏','浙江','安徽','福建','江西','山东','河南','湖北','湖南',
    '广东','海南','四川','贵州','云南','陕西','甘肃','青海',
    '内蒙古','广西','西藏','宁夏','新疆'
];

// 初始化省份选择器
const provinceSelect = document.getElementById('province');
provinces.forEach(p => {
    const opt = document.createElement('option');
    opt.value = p;
    opt.textContent = p;
    provinceSelect.appendChild(opt);
});

let selectedRegions = [];

function addRegion() {
    const province = document.getElementById('province').value;
    const city = document.getElementById('city').value;
    const region = city ? province + '-' + city : province;
    
    if(region && !selectedRegions.includes(region)) {
        selectedRegions.push(region);
        updateRegionDisplay();
    }
}

function updateRegionDisplay() {
    const container = document.getElementById('selected_regions');
    document.getElementById('regions_input').value = JSON.stringify(selectedRegions);
    
    container.innerHTML = selectedRegions.map((r, i) => 
        `<span class="badge badge-info mr-2 mb-2">${r} <i class="fa fa-times ml-1" style="cursor:pointer" onclick="removeRegion(${i})"></i></span>`
    ).join('');
}

function removeRegion(index) {
    selectedRegions.splice(index, 1);
    updateRegionDisplay();
}
</script>
