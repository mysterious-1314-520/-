<?php
define('DH_JSON_RESPONSE', true);
include __DIR__ . "/../includes/common.php";

function ad_json($code, $msg, $url = ''){
    while(ob_get_level() > 0) @ob_end_clean();
    @header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(array('code'=>$code, 'msg'=>$msg, 'url'=>$url), JSON_UNESCAPED_UNICODE);
    exit;
}

function ad_upload_error($code){
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

if($islogin!=1){
    ad_json(0, '请先登录后台');
}
if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    ad_json(0, '请求方式错误');
}
if(!isset($_FILES['file'])){
    ad_json(0, '没有收到上传文件');
}

$file = $_FILES['file'];
if($file['error'] != UPLOAD_ERR_OK){
    ad_json(0, ad_upload_error(intval($file['error'])));
}

$allowed = array('jpg','jpeg','png','gif','webp');
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if(!in_array($ext, $allowed)){
    ad_json(0, '图片格式不支持，请上传 jpg、jpeg、png、gif、webp');
}

$upload_dir = ROOT.'images/ad/';
if(!is_dir($upload_dir) && !@mkdir($upload_dir, 0755, true)){
    ad_json(0, '广告图片目录创建失败，请检查 images 目录权限');
}
if(!is_writable($upload_dir)){
    ad_json(0, '广告图片目录不可写，请检查 images/ad 目录权限');
}

$slot = isset($_POST['slot']) ? preg_replace('/[^a-z0-9_]+/i', '_', $_POST['slot']) : 'ad';
$filename = $slot.'_'.date('YmdHis').'_'.mt_rand(1000,9999).'.'.$ext;
$target = $upload_dir.$filename;
$saved = false;

if(is_uploaded_file($file['tmp_name'])){
    $saved = @move_uploaded_file($file['tmp_name'], $target);
}
if(!$saved && is_readable($file['tmp_name'])){
    $saved = @copy($file['tmp_name'], $target);
}
if(!$saved){
    ad_json(0, '图片保存失败，请检查 PHP 临时目录和 images/ad 权限');
}

@chmod($target, 0644);
ad_json(1, '上传成功', rtrim($rooturl, '/').'/images/ad/'.$filename);
?>
