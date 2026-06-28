<?php
/**
 * 广告请求 API
 */
define('IN_CRONLITE', true);
define('SYSTEM_ROOT', dirname(__FILE__).'/');
define('ROOT', dirname(SYSTEM_ROOT).'/');
require_once(ROOT.'includes/common.php');
require_once(ROOT.'includes/ad_engine.php');
header('Content-Type: application/json; charset=utf-8');
$input = file_get_contents('php://input');
$data = json_decode($input, true);
$position_code = $data['position_code'] ?? '';
$user_info = $data['user_info'] ?? [];
if(empty($position_code)) exit(json_encode(['code'=>1002,'msg'=>'参数错误']));
$request_id = 'req_'.uniqid().'_'.mt_rand(1000,9999);
$risk = ad_risk_check($user_info);
if($risk['is_fraud']) { ad_log_risk($request_id,$user_info,$risk); exit(json_encode(['code'=>1001,'msg'=>'Invalid traffic'])); }
$position = $DB->query("SELECT * FROM ad_positions WHERE code='$position_code' AND status=1 LIMIT 1")->fetch();
if(!$position) exit(json_encode(['code'=>0,'data'=>null]));
$ad = ad_get_best_ad($position,$user_info,$risk['score']);
if(!$ad) exit(json_encode(['code'=>0,'data'=>null]));
exit(json_encode(['code'=>0,'data'=>['request_id'=>$request_id,'creative_id'=>$ad['creative_id'],'type'=>$ad['type'],'width'=>$position['width'],'height'=>$position['height'],'image_url'=>$ad['image_url'],'click_url'=>'/api/ad_click.php?id='.$ad['creative_id'].'&rid='.$request_id,'impression_url'=>'/api/ad_impression.php?id='.$ad['creative_id'].'&rid='.$request_id,'landing_url'=>$ad['landing_url'],'title'=>$ad['title'],'timeout'=>3000]],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
