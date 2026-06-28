<?php define('IN_CRONLITE',true);define('SYSTEM_ROOT',dirname(__FILE__).'/');define('ROOT',dirname(dirname(SYSTEM_ROOT)).'/');require_once(ROOT.'includes/common.php');require_once(ROOT.'includes/auth.php');require_login();require_role('publisher');$user_id=get_current_user_id();$publisher=$DB->query("SELECT id,account_balance,total_earning FROM ad_publishers WHERE user_id=$user_id")->fetch();$earnings=$DB->query("SELECT day,SUM(total_earning) as earning FROM ad_daily_publisher WHERE publisher_id={$publisher['id']} AND day>=DATE_SUB(CURDATE(),INTERVAL 7 DAY) GROUP BY day ORDER BY day ASC")->fetchAll();include(ROOT.'admin/admin.php');?>
<div class="row"><div class="col-md-6"><div class="card"><div class="card-body"><h3>账户余额</h3><h1 class="text-success">¥<?=number_format($publisher['account_balance'],2)?></h1></div></div></div><div class="col-md-6"><div class="card"><div class="card-body"><h3>累计收益</h3><h1 class="text-primary">¥<?=number_format($publisher['total_earning'],2)?></h1></div></div></div></div>
<div class="card mt-4"><div class="card-body"><canvas id="earningChart" height="80"></canvas></div></div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script><script>
new Chart(document.getElementById('earningChart').getContext('2d'),{
type:'bar',data:{labels:<?=json_encode(array_column($earnings,'day'))?>,datasets:[{label:'每日收益',data:<?=json_encode(array_column($earnings,'earning'))?>,backgroundColor:'#20c997'}]},options:{responsive:true}
});
</script>
