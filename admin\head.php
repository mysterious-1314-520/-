<?php
@header('Content-Type: text/html; charset=UTF-8');

if(!function_exists('qifu_admin_current_page')){
    function qifu_admin_current_page(){
        $script = isset($_SERVER['SCRIPT_NAME']) ? basename($_SERVER['SCRIPT_NAME']) : '';
        $script = $script === '' ? 'index' : strtolower(preg_replace('/\.php$/', '', $script));
        return $script === '' ? 'index' : $script;
    }
}

if(!function_exists('qifu_admin_active')){
    function qifu_admin_active($pages){
        $current = qifu_admin_current_page();
        foreach(explode(',', (string)$pages) as $page){
            $page = trim(strtolower($page));
            if($current === 'ui_preview' && $page === 'index'){
                return 'active';
            }
            if($page !== '' && $page === $current){
                return 'active';
            }
        }
        return '';
    }
}

if(!function_exists('checkIfActive')){
    function checkIfActive($needle){
        return qifu_admin_active($needle);
    }
}

$is_admin_logged = isset($islogin) && intval($islogin) === 1;
$current_page = qifu_admin_current_page();
$admin_site_name = '祈福导航';
$admin_site_logo = '';
if(isset($conf) && is_array($conf)){
    if(isset($conf['sitename']) && trim($conf['sitename']) !== ''){
        $admin_site_name = trim($conf['sitename']);
    }
    if(isset($conf['site_logo']) && trim($conf['site_logo']) !== ''){
        $admin_site_logo = trim($conf['site_logo']);
    }
}
$admin_brand_initial = 'Q';
if($admin_site_name !== ''){
    $admin_brand_initial = function_exists('mb_substr') ? mb_substr($admin_site_name, 0, 1, 'UTF-8') : substr($admin_site_name, 0, 1);
}
$admin_site_name_html = htmlspecialchars($admin_site_name, ENT_QUOTES, 'UTF-8');
$admin_site_logo_html = htmlspecialchars($admin_site_logo, ENT_QUOTES, 'UTF-8');
$admin_brand_initial_html = htmlspecialchars($admin_brand_initial, ENT_QUOTES, 'UTF-8');
$admin_version_html = htmlspecialchars(defined('QIFU_PRODUCT_VERSION') ? QIFU_PRODUCT_VERSION : 'V1', ENT_QUOTES, 'UTF-8');
$admin_menus = array(
    array('key' => 'dashboard', 'name' => '&#24037;&#20316;&#21488;', 'icon' => 'glyphicon-dashboard', 'single' => true, 'items' => array(
        array('label' => '&#20202;&#34920;&#30424;', 'href' => './', 'pages' => 'index', 'icon' => 'glyphicon-dashboard'),
    )),
    array('key' => 'system', 'name' => '&#31995;&#32479;&#37197;&#32622;', 'icon' => 'glyphicon-cog', 'items' => array(
        array('label' => '&#24555;&#25463;&#35774;&#32622;', 'href' => './set.php', 'pages' => 'set', 'icon' => 'glyphicon-cog'),
        array('label' => '&#20462;&#25913;&#23494;&#30721;', 'href' => './password.php', 'pages' => 'password', 'icon' => 'glyphicon-lock'),
    )),
    array('key' => 'content', 'name' => '&#20869;&#23481;&#36816;&#33829;', 'icon' => 'glyphicon-th-large', 'items' => array(
        array('label' => '&#31449;&#28857;&#31649;&#29702;', 'href' => './set_dh.php', 'pages' => 'set_dh', 'icon' => 'glyphicon-globe'),
        array('label' => '&#20998;&#31867;&#31649;&#29702;', 'href' => './set_category.php', 'pages' => 'set_category', 'icon' => 'glyphicon-folder-open'),
        array('label' => '&#21451;&#38142;&#31649;&#29702;', 'href' => './links.php', 'pages' => 'links', 'icon' => 'glyphicon-link'),
        array('label' => '&#24191;&#21578;&#35774;&#32622;', 'href' => './ad.php', 'pages' => 'ad', 'icon' => 'glyphicon-picture'),
    )),
    array('key' => 'tools', 'name' => '&#32500;&#25252;&#24037;&#20855;', 'icon' => 'glyphicon-wrench', 'items' => array(
        array('label' => '&#25805;&#20316;&#26085;&#24535;', 'href' => './logs.php', 'pages' => 'logs', 'icon' => 'glyphicon-list-alt'),
        array('label' => '&#25968;&#25454;&#22791;&#20221;', 'href' => './backup.php', 'pages' => 'backup', 'icon' => 'glyphicon-floppy-disk'),
        array('label' => '&#25968;&#25454;&#24674;&#22797;', 'href' => './restore.php', 'pages' => 'restore', 'icon' => 'glyphicon-import'),
    )),
    array('key' => 'about', 'name' => '&#20851;&#20110;&#25105;&#20204;', 'icon' => 'glyphicon-copyright-mark', 'single' => true, 'items' => array(
        array('label' => '&#20851;&#20110;&#25105;&#20204;', 'href' => './about.php', 'pages' => 'about', 'icon' => 'glyphicon-copyright-mark'),
    )),
);

$admin_page_label = '';
foreach($admin_menus as $group){
    foreach($group['items'] as $item){
        if(qifu_admin_active($item['pages']) === 'active'){
            $admin_page_label = $item['label'];
            break 2;
        }
    }
}
if($admin_page_label === '' && $current_page === 'ui_preview'){
    $admin_page_label = 'UI &#39044;&#35272;';
}
$html_title = $admin_page_label !== '' ? $admin_page_label.' - '.$admin_site_name_html.'&#21518;&#21488;' : htmlspecialchars(isset($title) ? $title : 'Qifu Admin', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="zh-cn">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title><?php echo $html_title; ?></title>
  <link href="../assets/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="./saiadmin-skin.css?v=1388" rel="stylesheet"/>
  <script src="../assets/js/jquery.min.js"></script>
  <script src="../assets/js/bootstrap.min.js"></script>
  <!--[if lt IE 9]>
    <script src="//cdn.bootcss.com/html5shiv/3.7.3/html5shiv.min.js"></script>
    <script src="//cdn.bootcss.com/respond.js/1.4.2/respond.min.js"></script>
  <![endif]-->
</head>
<body class="<?php echo $is_admin_logged ? 'qf-admin' : 'qf-auth'; ?>">
<?php if($is_admin_logged){?>
  <div class="qf-progress" id="adminProgress"><span></span></div>
  <div class="qf-mobile-mask" id="adminMask"></div>

  <aside class="qf-sidebar" id="adminSidebar">
    <a class="qf-brand" href="./" aria-label="<?php echo $admin_site_name_html; ?>">
      <span class="qf-brand-logo <?php echo $admin_site_logo !== '' ? 'has-image' : ''; ?>" aria-hidden="true">
        <?php if($admin_site_logo !== ''){ ?>
          <img src="<?php echo $admin_site_logo_html; ?>" alt="">
        <?php } else { ?>
          <span><?php echo $admin_brand_initial_html; ?></span>
        <?php } ?>
      </span>
      <span class="qf-brand-copy">
        <strong><?php echo $admin_site_name_html; ?></strong>
        <em class="qf-brand-version"><i></i><?php echo $admin_version_html; ?></em>
      </span>
    </a>

    <nav class="qf-menu" aria-label="Admin navigation">
      <?php foreach($admin_menus as $group){
          $group_active = false;
          foreach($group['items'] as $item){
              if(qifu_admin_active($item['pages']) === 'active'){
                  $group_active = true;
                  break;
              }
          }
          if(!empty($group['single'])){
              $item = $group['items'][0];
              $active = qifu_admin_active($item['pages']);
      ?>
        <div class="qf-menu-group qf-menu-single <?php echo $active ? 'is-active is-open' : 'is-open'; ?>">
          <a class="qf-menu-link <?php echo $active; ?>" href="<?php echo $item['href']; ?>" title="<?php echo $item['label']; ?>">
            <span class="glyphicon <?php echo $item['icon']; ?>"></span>
            <span class="qf-menu-text"><?php echo $item['label']; ?></span>
          </a>
        </div>
      <?php } else { ?>
        <div class="qf-menu-group <?php echo $group_active ? 'is-active is-open' : ''; ?>" data-menu-group="<?php echo htmlspecialchars($group['key']); ?>">
          <button class="qf-menu-parent" type="button" aria-expanded="<?php echo $group_active ? 'true' : 'false'; ?>" title="<?php echo $group['name']; ?>">
            <span class="glyphicon <?php echo $group['icon']; ?> qf-menu-parent-icon"></span>
            <span class="qf-menu-parent-text"><?php echo $group['name']; ?></span>
            <span class="glyphicon glyphicon-menu-down qf-menu-arrow"></span>
          </button>
          <div class="qf-menu-children">
          <?php foreach($group['items'] as $item){
              $active = qifu_admin_active($item['pages']);
          ?>
            <a class="qf-menu-link <?php echo $active; ?>" href="<?php echo $item['href']; ?>" title="<?php echo $item['label']; ?>">
              <span class="glyphicon <?php echo $item['icon']; ?>"></span>
              <span class="qf-menu-text"><?php echo $item['label']; ?></span>
            </a>
          <?php } ?>
          </div>
        </div>
      <?php } ?>
      <?php } ?>
    </nav>
  </aside>

  <header class="qf-topbar">
    <div class="qf-topbar-left">
      <button type="button" class="qf-icon-btn qf-topbar-tool" id="adminCollapse" aria-label="Toggle sidebar" title="Toggle sidebar">
        <span class="glyphicon glyphicon-menu-hamburger"></span>
      </button>
      <button type="button" class="qf-icon-btn qf-topbar-tool" id="adminRefresh" aria-label="Refresh content" title="Refresh">
        <span class="glyphicon glyphicon-refresh"></span>
      </button>
      <div class="qf-page-head">
        <h1><?php echo $admin_page_label !== '' ? $admin_page_label : htmlspecialchars(isset($title) ? $title : ''); ?></h1>
      </div>
    </div>
    <div class="qf-topbar-actions">
      <a class="qf-action qf-action-primary" href="./set_dh.php?my=add">
        <span class="glyphicon glyphicon-plus"></span><span>&#26032;&#22686;&#31449;&#28857;</span>
      </a>
      <a class="qf-action" href="../" target="_blank">
        <span class="glyphicon glyphicon-new-window"></span><span>&#21069;&#21488;</span>
      </a>
      <a class="qf-action" href="./login.php?logout">
        <span class="glyphicon glyphicon-log-out"></span><span>&#36864;&#20986;</span>
      </a>
    </div>
  </header>

  <script>
    (function(){
      var body = document.body;
      var progress = document.getElementById('adminProgress');
      var mask = document.getElementById('adminMask');
      var collapse = document.getElementById('adminCollapse');
      var refresh = document.getElementById('adminRefresh');
      var sidebar = document.getElementById('adminSidebar');
      var topbar = document.querySelector('.qf-topbar');
      var collapsed = window.localStorage && localStorage.getItem('qfSidebarCollapsed') === '1';
      var menuStoreKey = 'qfMenuOpenGroups';
      var sidebarWidth = '264px';
      var sidebarCollapsedWidth = '76px';

      if(collapsed){ body.classList.add('qf-sidebar-collapsed'); }

      function isMobile(){ return window.matchMedia && window.matchMedia('(max-width: 980px)').matches; }
      function syncMobileSidebar(open){
        if(!sidebar) return;
        if(isMobile()){
          sidebar.style.setProperty('left', open ? '0px' : '-' + sidebarWidth, 'important');
          sidebar.style.setProperty('transform', 'none', 'important');
        } else {
          sidebar.style.removeProperty('left');
          sidebar.style.removeProperty('transform');
        }
      }
      function syncSidebarLayout(){
        if(isMobile()){
          body.style.removeProperty('padding-left');
          if(sidebar){ sidebar.style.removeProperty('width'); }
          if(topbar){ topbar.style.removeProperty('left'); }
          if(progress){ progress.style.removeProperty('left'); }
          return;
        }
        var width = body.classList.contains('qf-sidebar-collapsed') ? sidebarCollapsedWidth : sidebarWidth;
        body.style.setProperty('padding-left', width, 'important');
        if(sidebar){ sidebar.style.setProperty('width', width, 'important'); }
        if(topbar){ topbar.style.setProperty('left', width, 'important'); }
        if(progress){ progress.style.setProperty('left', width, 'important'); }
      }
      function closeMobile(){
        body.classList.remove('qf-sidebar-open');
        syncMobileSidebar(false);
      }
      function showProgress(){
        if(!progress) return;
        progress.classList.remove('finishing');
        progress.classList.add('loading');
        setTimeout(function(){ progress.classList.add('finishing'); }, 180);
      }

      syncMobileSidebar(body.classList.contains('qf-sidebar-open'));
      syncSidebarLayout();

      if(mask){ mask.addEventListener('click', closeMobile); }
      if(collapse){
        collapse.addEventListener('click', function(){
          if(isMobile()){
            body.classList.toggle('qf-sidebar-open');
            syncMobileSidebar(body.classList.contains('qf-sidebar-open'));
          } else {
            body.classList.toggle('qf-sidebar-collapsed');
            if(window.localStorage){
              localStorage.setItem('qfSidebarCollapsed', body.classList.contains('qf-sidebar-collapsed') ? '1' : '0');
            }
          }
          syncSidebarLayout();
        });
      }
      function readOpenGroups(){
        if(!window.localStorage) return null;
        try {
          var raw = localStorage.getItem(menuStoreKey);
          if(raw === null) return null;
          var parsed = JSON.parse(raw);
          return Array.isArray(parsed) ? parsed : [];
        } catch(err) {
          return null;
        }
      }
      function writeOpenGroups(){
        if(!window.localStorage) return;
        var openKeys = [];
        document.querySelectorAll('.qf-menu-group[data-menu-group].is-open').forEach(function(group){
          openKeys.push(group.getAttribute('data-menu-group'));
        });
        localStorage.setItem(menuStoreKey, JSON.stringify(openKeys));
      }
      function setGroupOpen(group, open, save){
        if(!group) return;
        group.classList.toggle('is-open', open);
        var parent = group.querySelector('.qf-menu-parent');
        if(parent){ parent.setAttribute('aria-expanded', open ? 'true' : 'false'); }
        if(save){ writeOpenGroups(); }
      }
      function initMenuGroups(){
        var openGroups = readOpenGroups();
        document.querySelectorAll('.qf-menu-group[data-menu-group]').forEach(function(group){
          var key = group.getAttribute('data-menu-group');
          var open = group.classList.contains('is-active') || (openGroups ? openGroups.indexOf(key) !== -1 : group.classList.contains('is-open'));
          setGroupOpen(group, open, false);
          var parent = group.querySelector('.qf-menu-parent');
          if(parent){
            parent.addEventListener('click', function(){
              if(body.classList.contains('qf-sidebar-collapsed')){
                body.classList.remove('qf-sidebar-collapsed');
                if(window.localStorage){ localStorage.setItem('qfSidebarCollapsed', '0'); }
                syncSidebarLayout();
              }
              setGroupOpen(group, !group.classList.contains('is-open'), true);
              parent.blur();
            });
          }
        });
      }

      function normalizePath(url){
        var a = document.createElement('a');
        a.href = url;
        var path = a.pathname.replace(/\/+$/, '');
        var currentDir = window.location.pathname.replace(/\/[^\/]*$/, '/').replace(/\/+$/, '');
        if(path === currentDir || path === currentDir + '/index.php') return 'index';
        var key = (path.split('/').pop() || 'index.php').replace(/\.php$/i, '').toLowerCase();
        return key === 'ui_preview' ? 'index' : key;
      }
      function pageLabelFor(url){
        var key = normalizePath(url);
        var matched = document.querySelector('.qf-sidebar a[data-page="' + key + '"] .qf-menu-text');
        return matched ? matched.textContent : '';
      }
      function setActiveMenu(url){
        var key = normalizePath(url);
        document.querySelectorAll('.qf-sidebar .qf-menu-link').forEach(function(item){
          var active = (item.getAttribute('data-page') || '') === key;
          item.classList.toggle('active', active);
          if(active){
            var group = item.closest ? item.closest('.qf-menu-group') : null;
            if(group){
              group.classList.add('is-active');
              setGroupOpen(group, true, false);
            }
          }
          item.blur();
        });
        document.querySelectorAll('.qf-sidebar .qf-menu-group').forEach(function(group){
          group.classList.toggle('is-active', !!group.querySelector('.qf-menu-link.active'));
        });
      }
      function nodesAfter(marker){
        var nodes = [];
        var node = marker ? marker.nextSibling : null;
        while(node){
          nodes.push(node);
          node = node.nextSibling;
        }
        return nodes;
      }
      function runPageScripts(nodes){
        var scripts = [];
        nodes.forEach(function(node){
          if(node.nodeType !== 1) return;
          if(node.tagName && node.tagName.toLowerCase() === 'script'){
            scripts.push(node);
          }
          node.querySelectorAll && node.querySelectorAll('script').forEach(function(script){
            scripts.push(script);
          });
        });
        scripts.forEach(function(oldScript){
          var src = oldScript.getAttribute('src') || '';
          if(src.indexOf('jquery') !== -1 || src.indexOf('bootstrap') !== -1) return;
          var script = document.createElement('script');
          Array.prototype.slice.call(oldScript.attributes).forEach(function(attr){
            script.setAttribute(attr.name, attr.value);
          });
          var inlineCode = oldScript.text || oldScript.textContent || '';
          if(!src && inlineCode){
            script.text = 'var __qfOldAdd=document.addEventListener.bind(document);document.addEventListener=function(type,fn,opt){if(type==="DOMContentLoaded"&&typeof fn==="function"){fn.call(document,new Event("DOMContentLoaded"));return;}return __qfOldAdd(type,fn,opt);};setTimeout(function(){document.addEventListener=__qfOldAdd;},0);\n' + inlineCode + '\ndocument.addEventListener=__qfOldAdd;';
          } else {
            script.text = inlineCode;
          }
          oldScript.parentNode.replaceChild(script, oldScript);
        });
      }
      function ajaxGet(url){
        var xhr = null;
        if(window.XMLHttpRequest){
          xhr = new XMLHttpRequest();
        } else if(window.ActiveXObject) {
          xhr = new ActiveXObject('Microsoft.XMLHTTP');
        }
        return xhr;
      }
      function swapAdminContent(doc, url, pushState){
        var marker = document.getElementById('qf-content-start');
        var nextMarker = doc && doc.getElementById ? doc.getElementById('qf-content-start') : null;
        if(!marker || !nextMarker) throw new Error('No content marker');
        body.classList.remove('qf-content-enter-ready', 'qf-content-enter-active');
        nodesAfter(marker).forEach(function(node){ node.parentNode.removeChild(node); });
        var importedNodes = [];
        nodesAfter(nextMarker).forEach(function(node){
          var imported = document.importNode(node, true);
          importedNodes.push(imported);
          document.body.appendChild(imported);
        });
        body.classList.add('qf-content-enter-ready');
        requestAnimationFrame(function(){
          body.classList.add('qf-content-enter-active');
          setTimeout(function(){
            body.classList.remove('qf-content-enter-ready', 'qf-content-enter-active');
          }, 520);
        });
        document.title = doc.title || document.title;
        var label = pageLabelFor(url);
        var heading = document.querySelector('.qf-page-head h1');
        if(heading && label){ heading.textContent = label; }
        setActiveMenu(url);
        runPageScripts(importedNodes);
        if(pushState && window.history && history.pushState){
          history.pushState({ qfAjax: true, url: url }, document.title, url);
        }
      }
      function finishAdminContent(){
        if(progress){
          progress.classList.remove('loading');
          progress.classList.add('finishing');
          setTimeout(function(){ progress.classList.remove('loading', 'finishing'); }, 260);
        }
        var current = document.querySelector('body.qf-admin > .container');
        if(current){ current.classList.remove('qf-content-loading'); }
      }
      function iframeGet(url, done, fail){
        var frame = document.createElement('iframe');
        frame.style.cssText = 'position:absolute;width:0;height:0;border:0;left:-9999px;top:-9999px;visibility:hidden;';
        frame.tabIndex = -1;
        frame.onload = function(){
          try {
            var doc = frame.contentDocument || (frame.contentWindow && frame.contentWindow.document);
            done(doc);
          } catch(err) {
            fail();
          }
          setTimeout(function(){
            if(frame.parentNode){ frame.parentNode.removeChild(frame); }
          }, 0);
        };
        frame.onerror = fail;
        frame.src = url;
        document.body.appendChild(frame);
      }
      function loadAdminContent(url, pushState){
        var target = document.querySelector('body.qf-admin > .container');
        if(!document.getElementById('qf-content-start') || !target) return false;
        showProgress();
        closeMobile();
        target.classList.add('qf-content-loading');
        var xhr = ajaxGet(url);
        if(xhr){
          xhr.open('GET', url, true);
          xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
          xhr.onreadystatechange = function(){
            if(xhr.readyState !== 4) return;
            if(xhr.status < 200 || xhr.status >= 300){
              window.location.href = url;
              return;
            }
            try {
              swapAdminContent(new DOMParser().parseFromString(xhr.responseText || '', 'text/html'), url, pushState);
              finishAdminContent();
            } catch(err) {
              window.location.href = url;
            }
          };
          xhr.onerror = function(){
            window.location.href = url;
          };
          xhr.send(null);
          return true;
        }
        iframeGet(url, function(doc){
          try {
            swapAdminContent(doc, url, pushState);
            finishAdminContent();
          } catch(err) {
            window.location.href = url;
          }
        }, function(){
          window.location.href = url;
        });
        return true;
      }

      document.querySelectorAll('.qf-sidebar a[href], .qf-topbar-actions a[href]').forEach(function(link){
        var linkHref = link.getAttribute('href') || '';
        if(link.classList.contains('qf-menu-link')){
          link.setAttribute('data-page', normalizePath(linkHref));
        }
      });
      initMenuGroups();
      if(refresh){
        refresh.addEventListener('click', function(){
          refresh.blur();
          refresh.classList.remove('is-spinning');
          void refresh.offsetWidth;
          refresh.classList.add('is-spinning');
          setTimeout(function(){ refresh.classList.remove('is-spinning'); }, 760);
          if(!loadAdminContent(window.location.href, false)){
            window.location.reload();
          }
        });
      }
      document.addEventListener('click', function(e){
        var node = e.target;
        while(node && node !== document){
          if(node.classList && (node.classList.contains('qf-menu-link') || node.classList.contains('qf-action'))){ break; }
          node = node.parentNode;
        }
        if(!node || node === document || !node.getAttribute) return;
        var href = node.getAttribute('href') || '';
        if(!href || href.indexOf('javascript:') === 0 || node.target === '_blank' || e.ctrlKey || e.metaKey || e.shiftKey || e.altKey) return;
        e.preventDefault();
        node.blur();
        if(node.classList.contains('qf-menu-link')){
          if(!loadAdminContent(href, true)){ window.location.href = href; }
          return;
        }
        showProgress();
        closeMobile();
        setTimeout(function(){ window.location.href = href; }, 180);
      }, true);
      setActiveMenu(window.location.href);
      window.addEventListener('popstate', function(){
        loadAdminContent(window.location.href, false);
      });
      window.addEventListener('resize', function(){
        syncMobileSidebar(body.classList.contains('qf-sidebar-open'));
        syncSidebarLayout();
      });
    })();
  </script>
  <span id="qf-content-start" hidden></span>
<?php }?>
