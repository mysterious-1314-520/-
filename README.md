# 祈福导航系统 + 广告联盟系统

<div align="center">

**版本:** 1.5.0  
**更新日期:** 2026-06-28  
**语言:** PHP 8.2+  
**许可证:** MIT

[功能特性](#功能特性) • [快速开始](#快速开始) • [文档](#文档) • [更新日志](CHANGELOG.md)

</div>

---

## 📦 项目简介

本项目包含两个核心系统:

### 1. 祈福导航系统 (V1.3)
一个美观、响应式的自建导航网站系统，支持站点管理、分类管理、友链申请等功能。

### 2. 广告联盟系统 (V1.5.0 - 新增)
完整的商业化广告投放平台，连接广告主与网站主，实现广告投放、计费结算、数据统计的全流程管理。

---

## ✨ 功能特性

### 导航系统
- ✅ 前台导航首页 (分类展示/搜索引擎切换/快捷标签)
- ✅ 站点管理 (添加/编辑/删除/启用隐藏)
- ✅ 描述跑马灯 (4 档速度/8 种颜色)
- ✅ 分类管理
- ✅ 友链申请
- ✅ 背景设置
- ✅ 响应式适配 (手机/平板/PC)

### 广告联盟系统 (NEW 🎉)

#### 用户体系
- ✅ 四角色权限 (admin/auditor/advertiser/publisher)
- ✅ 用户注册/登录 (bcrypt 加密)
- ✅ RBAC 权限控制

#### 广告主后台
- ✅ 广告活动管理 (CRUD + 状态流转)
- ✅ 定向设置 (性别/年龄/地域/设备/OS/ 浏览器/时段)
- ✅ 广告创意管理 (图片/视频/代码)
- ✅ 数据报表 (Chart.js 可视化)
- ✅ 在线充值

#### 网站主后台
- ✅ 网站接入管理
- ✅ 广告位管理 (6 种类型)
- ✅ 屏蔽规则 (黑名单)
- ✅ 收益统计

#### 投放引擎
- ✅ eCPM 竞价排序
- ✅ 定向匹配过滤
- ✅ 频控机制
- ✅ 广告请求 API

#### 数据追踪
- ✅ 展示日志 (ClickHouse)
- ✅ 点击日志 (ClickHouse)
- ✅ 转化追踪

#### 财务系统
- ✅ 账户管理
- ✅ 在线充值
- ✅ 交易明细

#### 审核系统
- ✅ 广告审核
- ✅ 网站审核

#### 反作弊
- ✅ IP 频控检测
- ✅ UA 检测
- ✅ 黑名单系统

#### JS SDK
- ✅ 广告请求
- ✅ 自动渲染
- ✅ 数据上报

---

## 🚀 快速开始

### 环境要求

- PHP >= 8.2
- MySQL 5.7+ / MariaDB 10.x
- Redis (可选，用于缓存)
- ClickHouse 20.x+ (可选，用于大数据分析)

### 安装步骤

1. **克隆项目**
   ```bash
   git clone https://github.com/mysterious-1314-520/-.git
   cd -
   ```

2. **导入数据库**
   ```bash
   mysql -u root -e "CREATE DATABASE daohang DEFAULT CHARACTER SET utf8mb4"
   mysql -u root daohang < install/install.sql
   mysql -u root daohang < install/ad_network_schema.sql
   ```

3. **导入 ClickHouse 表 (可选)**
   ```bash
   clickhouse-client --database daohang < install/ad_network_clickhouse.sql
   ```

4. **配置数据库连接**
   编辑 `config.php`，修改数据库配置

5. **访问网站**
   - 导航首页：`/`
   - 导航后台：`/admin/`
   - 广告联盟注册：`/admin/ad_register.php`
   - 广告联盟登录：`/admin/ad_login.php`

### Docker 部署 (规划中)

```bash
docker-compose up -d
```

---

## 📁 目录结构

```
.
├── admin/                      # 后台管理
│   ├── ad_register.php        # 用户注册
│   ├── ad_login.php           # 用户登录
│   ├── advertiser/            # 广告主后台
│   ├── publisher/             # 网站主后台
│   ├── audit/                 # 审核后台
│   └── finance/               # 财务后台
├── api/                        # API 接口
│   ├── ad_request.php         # 广告请求
│   ├── ad_impression.php      # 展示追踪
│   ├── ad_click.php           # 点击追踪
│   └── audit_ad.php           # 审核 API
├── assets/                     # 静态资源
│   ├── js/
│   │   ├── ad-sdk.js          # 广告 SDK
│   │   └── ad-sdk.min.js      # 压缩版 SDK
│   └── css/
├── includes/                   # 核心模块
│   ├── auth.php               # 认证中间件
│   ├── ad_engine.php          # 投放引擎
│   └── version.php            # 版本信息
├── install/                    # 安装文件
│   ├── install.sql            # 导航系统 SQL
│   ├── ad_network_schema.sql  # 广告联盟 MySQL 表
│   └── ad_network_clickhouse.sql # 广告联盟 ClickHouse 表
├── config.php                  # 数据库配置
├── index.php                   # 导航首页
├── README.md                   # 项目说明
└── CHANGELOG.md                # 更新日志
```

---

## 📖 文档

### 技术文档
- [需求文档](.monkeycode/specs/ad-network/requirements.md)
- [设计文档](.monkeycode/specs/ad-network/design.md)
- [实施任务](.monkeycode/specs/ad-network/tasklist.md)
- [完成总结](.monkeycode/specs/ad-network/README.md)

### API 文档

#### 广告请求 API
```bash
POST /api/ad_request.php
Content-Type: application/json

{
  "position_code": "pos_xxxxx",
  "website_id": 123,
  "user_info": {
    "ip": "1.2.3.4",
    "device": "mobile",
    "os": "iOS",
    "browser": "Safari"
  }
}
```

#### JS SDK 使用
```html
<script async src="/assets/js/ad-sdk.js" 
        data-pid="PUBLISHER_ID" 
        data-wid="WEBSITE_ID"></script>
<div data-ad="pos_xxxxxxx"></div>
```

---

## 📊 数据统计

| 指标 | 数量 |
|------|------|
| PHP 文件 | 30+ |
| 数据表 (MySQL) | 18 |
| 数据表 (ClickHouse) | 9 |
| API 接口 | 6 |
| 后台页面 | 20+ |
| 总代码行数 | ~6000 |

---

## 🛠️ 技术栈

- **后端**: PHP 8.2+
- **数据库**: MySQL 8.0+ / MariaDB 10.x
- **分析**: ClickHouse 20.x+
- **缓存**: Redis
- **前端**: jQuery + Bootstrap
- **图表**: Chart.js 3.9.1
- **SDK**: 原生 JavaScript

---

## 📝 更新日志

详细更新内容请查看 [CHANGELOG.md](CHANGELOG.md)

### v1.5.0 (2026-06-28)
- 🎉 新增完整广告联盟系统
- 新增 18 张 MySQL 数据表
- 新增 eCPM 竞价投放引擎
- 新增四角色权限系统
- 新增数据追踪与反作弊

### v1.3.0 (2026-06-27)
- 祈福导航系统基础功能
- 响应式适配
- 描述跑马灯

---

## 🤝 贡献指南

1. Fork 本仓库
2. 创建特性分支 (`git checkout -b feature/AmazingFeature`)
3. 提交更改 (`git commit -m 'Add some AmazingFeature'`)
4. 推送到分支 (`git push origin feature/AmazingFeature`)
5. 开启 Pull Request

---

## 📄 许可证

本项目采用 MIT 许可证 - 查看 [LICENSE](LICENSE) 文件了解详情

---

## 📞 联系方式

- **项目地址**: https://github.com/mysterious-1314-520/-
- **官方演示**: 待添加

---

<div align="center">

**祈祈福导航系统 + 广告联盟系统** • 让导航更高效，让流量更有价值

Made with ❤️ by MonkeyCode-AI

</div>
