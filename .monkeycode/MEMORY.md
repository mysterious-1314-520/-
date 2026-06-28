
## 广告联盟系统修复
- Date: 2026-06-28
- Context: 广告联盟登录和后台访问问题修复
- Category: 排错调试
- Instructions:
  - 广告联盟登录页面：/admin/ad_login.php (测试账号：13800138000/123456)
  - 广告联盟注册页面：/admin/ad_register.php
  - 广告主后台：/admin/advertiser/index.php (需登录 advertiser 角色)
  - 网站主后台：/admin/publisher/index.php (需登录 publisher 角色)
  - 广告联盟使用独立的 ad_系列数据表 (共 18 张)
  - 数据库连接使用 mysqli，预处理语句通过 DB_Statement 包装类实现
  - 登录成功后会话保存在 $_SESSION 中，包含 user_id/phone/role/company
