<?php if(!defined('IN_CRONLITE'))exit;
$api_config=[
    'allowed_domains'=>['*'],
    'rate_limit'=>['ip'=>1000,'user'=>100],
    'cache_ttl'=>300,
    'log_level'=>'info'
];
