<?php
include __DIR__ . "/../includes/common.php";

if(isset($_POST['user']) && isset($_POST['pass'])){
    $user = daddslashes($_POST['user']);
    $pass = daddslashes($_POST['pass']);
    if($user == $conf['admin_user'] && $pass == $conf['admin_pwd']) {
        $session = md5($user.$pass.$password_hash);
        $token = authcode("{$user}\t{$session}", 'ENCODE', SYS_KEY);
        setcookie("admin_token", $token, time() + 604800);
        @header('Content-Type: text/html; charset=UTF-8');
        exit("<script language='javascript'>alert('Login success');window.location.href='./';</script>");
    } elseif ($pass != $conf['admin_pwd']) {
        @header('Content-Type: text/html; charset=UTF-8');
        exit("<script language='javascript'>alert('Invalid username or password');history.go(-1);</script>");
    }
} elseif(isset($_GET['logout'])){
    setcookie("admin_token", "", time() - 604800);
    @header('Content-Type: text/html; charset=UTF-8');
    exit("<script language='javascript'>alert('Logged out');window.location.href='./login.php';</script>");
} elseif($islogin == 1){
    exit("<script language='javascript'>alert('Already logged in');window.location.href='./';</script>");
}

$title = 'Qifu Admin Login';
include './head.php';
?>
  <main class="qf-login-shell">
    <section class="qf-login-aside">
      <span class="qf-login-mark">&#31048;</span>
      <h1>&#31048;&#31119;&#23548;&#33322;&#31649;&#29702;&#20013;&#24515;</h1>
      <p>&#32479;&#19968;&#31649;&#29702;&#31449;&#28857;&#12289;&#20998;&#31867;&#12289;&#24191;&#21578;&#12289;&#21451;&#38142;&#21644;&#31995;&#32479;&#35774;&#32622;&#12290;&#30028;&#38754;&#24050;&#25353;&#31616;&#32422;&#30333;&#33394;&#21518;&#21488;&#39118;&#26684;&#36827;&#34892;&#20248;&#21270;&#12290;</p>
      <div class="qf-login-meta">
        <span><b>Sites</b>&#31449;&#28857;&#36816;&#33829;</span>
        <span><b>Logs</b>&#25805;&#20316;&#30041;&#30165;</span>
        <span><b>Backup</b>&#25968;&#25454;&#32500;&#25252;</span>
      </div>
    </section>
    <section class="qf-login-panel">
      <h2>&#31649;&#29702;&#21592;&#30331;&#24405;</h2>
      <p><?php echo htmlspecialchars(isset($conf['sitename']) ? $conf['sitename'] : 'Qifu Navigation'); ?></p>
      <form action="./login.php" method="post" role="form">
        <div class="input-group">
          <span class="input-group-addon"><span class="glyphicon glyphicon-user"></span></span>
          <input type="text" name="user" value="<?php echo htmlspecialchars(isset($_POST['user']) ? $_POST['user'] : ''); ?>" class="form-control" placeholder="&#31649;&#29702;&#21592;&#36134;&#21495;" required="required" autocomplete="username"/>
        </div>
        <div class="input-group">
          <span class="input-group-addon"><span class="glyphicon glyphicon-lock"></span></span>
          <input type="password" name="pass" class="form-control" placeholder="&#31649;&#29702;&#21592;&#23494;&#30721;" required="required" autocomplete="current-password"/>
        </div>
        <button type="submit" class="btn btn-primary">
          <span class="glyphicon glyphicon-log-in"></span> &#36827;&#20837;&#21518;&#21488;
        </button>
      </form>
      <div class="qf-login-links">
        <a href="../" target="_blank"><span class="glyphicon glyphicon-new-window"></span> &#26597;&#30475;&#21069;&#21488;</a>
        <a href="./login.php"><span class="glyphicon glyphicon-refresh"></span> &#21047;&#26032;&#30331;&#24405;</a>
      </div>
    </section>
  </main>
</body>
</html>
