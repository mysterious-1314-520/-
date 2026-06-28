-- 广告联盟系统 V1.0 数据库表结构
-- MySQL 8.0+, MariaDB 10.x

-- ============================================
-- 1. 用户体系
-- ============================================

-- 用户基础表
DROP TABLE IF EXISTS `ad_users`;
CREATE TABLE `ad_users` (
    `id` BIGINT PRIMARY KEY AUTO_INCREMENT,
    `phone` VARCHAR(20) NOT NULL UNIQUE COMMENT '手机号',
    `password_hash` VARCHAR(255) NOT NULL COMMENT 'bcrypt 加密密码',
    `email` VARCHAR(100) DEFAULT NULL COMMENT '邮箱',
    `role` ENUM('admin', 'auditor', 'advertiser', 'publisher') NOT NULL DEFAULT 'advertiser' COMMENT '角色',
    `status` TINYINT DEFAULT 1 COMMENT '1 正常 0 禁用',
    `company_name` VARCHAR(200) DEFAULT NULL COMMENT '企业名称',
    `company_license` VARCHAR(100) DEFAULT NULL COMMENT '营业执照号',
    `last_login_time` DATETIME DEFAULT NULL COMMENT '最后登录时间',
    `last_login_ip` VARCHAR(50) DEFAULT NULL COMMENT '最后登录 IP',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_phone` (`phone`),
    INDEX `idx_role` (`role`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户基础表';

-- 广告主信息表
DROP TABLE IF EXISTS `ad_advertisers`;
CREATE TABLE `ad_advertisers` (
    `id` BIGINT PRIMARY KEY,
    `user_id` BIGINT NOT NULL UNIQUE,
    `account_balance` DECIMAL(20,2) DEFAULT 0.00 COMMENT '账户余额',
    `frozen_balance` DECIMAL(20,2) DEFAULT 0.00 COMMENT '冻结金额',
    `coupon_balance` DECIMAL(20,2) DEFAULT 0.00 COMMENT '优惠券余额',
    `total_recharge` DECIMAL(20,2) DEFAULT 0.00 COMMENT '累计充值',
    `total_spend` DECIMAL(20,2) DEFAULT 0.00 COMMENT '累计消耗',
    `industry` VARCHAR(50) DEFAULT NULL COMMENT '所属行业',
    `contact_name` VARCHAR(50) DEFAULT NULL COMMENT '联系人',
    `contact_phone` VARCHAR(20) DEFAULT NULL COMMENT '联系电话',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_balance` (`account_balance`),
    CONSTRAINT `fk_advertiser_user` FOREIGN KEY (`user_id`) REFERENCES `ad_users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='广告主信息表';

-- 网站主信息表
DROP TABLE IF EXISTS `ad_publishers`;
CREATE TABLE `ad_publishers` (
    `id` BIGINT PRIMARY KEY,
    `user_id` BIGINT NOT NULL UNIQUE,
    `account_balance` DECIMAL(20,2) DEFAULT 0.00 COMMENT '账户余额',
    `total_earning` DECIMAL(20,2) DEFAULT 0.00 COMMENT '累计收益',
    `total_withdraw` DECIMAL(20,2) DEFAULT 0.00 COMMENT '累计提现',
    `frozen_earning` DECIMAL(20,2) DEFAULT 0.00 COMMENT '冻结收益 (待结算)',
    `min_withdraw` DECIMAL(10,2) DEFAULT 100.00 COMMENT '最低提现门槛',
    `withdraw_account` VARCHAR(100) DEFAULT NULL COMMENT '提现账号',
    `withdraw_type` ENUM('alipay', 'wechat', 'bank') DEFAULT 'alipay' COMMENT '提现方式',
    `tax_rate` DECIMAL(5,4) DEFAULT 0.0100 COMMENT '税率',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_publisher_user` FOREIGN KEY (`user_id`) REFERENCES `ad_users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='网站主信息表';

-- ============================================
-- 2. 广告系统
-- ============================================

-- 广告活动表
DROP TABLE IF EXISTS `ad_campaigns`;
CREATE TABLE `ad_campaigns` (
    `id` BIGINT PRIMARY KEY AUTO_INCREMENT,
    `advertiser_id` BIGINT NOT NULL,
    `name` VARCHAR(100) NOT NULL COMMENT '活动名称',
    `objective` ENUM('brand', 'traffic', 'conversion') DEFAULT 'traffic' COMMENT '投放目标',
    `budget_total` DECIMAL(20,2) NOT NULL COMMENT '总预算',
    `budget_daily` DECIMAL(20,2) NOT NULL COMMENT '日预算',
    `budget_used` DECIMAL(20,2) DEFAULT 0.00 COMMENT '已用预算',
    `charge_type` ENUM('CPM', 'CPC', 'CPA', 'CPT') NOT NULL COMMENT '计费类型',
    `bid_price` DECIMAL(10,4) NOT NULL COMMENT '出价',
    `start_date` DATE NOT NULL COMMENT '开始日期',
    `end_date` DATE NOT NULL COMMENT '结束日期',
    `status` TINYINT DEFAULT 0 COMMENT '0 草稿 1 待审核 2 审核通过 3 审核驳回 4 投放中 5 已暂停 6 已结束',
    `audit_remark` VARCHAR(500) DEFAULT NULL COMMENT '审核意见',
    `audit_user_id` BIGINT DEFAULT NULL COMMENT '审核员 ID',
    `audit_time` DATETIME DEFAULT NULL COMMENT '审核时间',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_advertiser` (`advertiser_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_date` (`start_date`, `end_date`),
    CONSTRAINT `fk_campaign_advertiser` FOREIGN KEY (`advertiser_id`) REFERENCES `ad_advertisers`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='广告活动表';

-- 广告组表
DROP TABLE IF EXISTS `ad_groups`;
CREATE TABLE `ad_groups` (
    `id` BIGINT PRIMARY KEY AUTO_INCREMENT,
    `campaign_id` BIGINT NOT NULL,
    `name` VARCHAR(100) NOT NULL COMMENT '广告组名称',
    -- 定向条件
    `gender` TINYINT DEFAULT 0 COMMENT '0 不限 1 男 2 女',
    `age_range` VARCHAR(50) DEFAULT NULL COMMENT '年龄范围，如 18-24',
    `regions` TEXT COMMENT '地域定向 JSON',
    `schedule` TEXT COMMENT '投放时段 JSON',
    `devices` TEXT COMMENT '设备定向 JSON',
    `os_list` TEXT COMMENT '操作系统定向 JSON',
    `browser_list` TEXT COMMENT '浏览器定向 JSON',
    `media_whitelist` TEXT COMMENT '媒体白名单 JSON',
    `audience_packages` TEXT COMMENT '人群包 JSON',
    -- 排除定向
    `age_exclude` VARCHAR(50) DEFAULT NULL COMMENT '排除年龄',
    `region_exclude` TEXT COMMENT '排除地域 JSON',
    -- 频控
    `frequency_cap` INT DEFAULT 0 COMMENT '单用户每日最多展示次数',
    -- 状态
    `status` TINYINT DEFAULT 1 COMMENT '1 启用 0 禁用',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_campaign` (`campaign_id`),
    INDEX `idx_status` (`status`),
    CONSTRAINT `fk_group_campaign` FOREIGN KEY (`campaign_id`) REFERENCES `ad_campaigns`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='广告组表';

-- 广告创意表
DROP TABLE IF EXISTS `ad_creatives`;
CREATE TABLE `ad_creatives` (
    `id` BIGINT PRIMARY KEY AUTO_INCREMENT,
    `ad_group_id` BIGINT NOT NULL,
    `type` ENUM('image', 'video', 'native', 'popup', 'floating', 'code') NOT NULL COMMENT '创意类型',
    `title` VARCHAR(60) DEFAULT NULL COMMENT '标题',
    `subtitle` VARCHAR(120) DEFAULT NULL COMMENT '副标题',
    `description` VARCHAR(500) DEFAULT NULL COMMENT '描述',
    `landing_url` VARCHAR(500) DEFAULT NULL COMMENT '落地页链接',
    -- 图片素材
    `image_url` VARCHAR(500) DEFAULT NULL COMMENT '图片 URL',
    `image_width` INT DEFAULT NULL COMMENT '图片宽度',
    `image_height` INT DEFAULT NULL COMMENT '图片高度',
    `image_md5` VARCHAR(32) DEFAULT NULL COMMENT '图片 MD5',
    -- 视频素材
    `video_url` VARCHAR(500) DEFAULT NULL COMMENT '视频 URL',
    `video_duration` INT DEFAULT NULL COMMENT '视频时长 (秒)',
    -- 代码广告
    `ad_code` MEDIUMTEXT COMMENT '广告代码',
    -- 状态
    `status` TINYINT DEFAULT 0 COMMENT '0 草稿 1 待审核 2 审核通过 3 审核驳回',
    `audit_remark` VARCHAR(500) DEFAULT NULL COMMENT '审核意见',
    `audit_user_id` BIGINT DEFAULT NULL COMMENT '审核员 ID',
    `audit_time` DATETIME DEFAULT NULL COMMENT '审核时间',
    `click_count` BIGINT DEFAULT 0 COMMENT '点击量',
    `impression_count` BIGINT DEFAULT 0 COMMENT '展示量',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_ad_group` (`ad_group_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_type` (`type`),
    CONSTRAINT `fk_creative_group` FOREIGN KEY (`ad_group_id`) REFERENCES `ad_groups`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='广告创意表';

-- ============================================
-- 3. 广告位系统
-- ============================================

-- 网站信息表
DROP TABLE IF EXISTS `ad_publisher_websites`;
CREATE TABLE `ad_publisher_websites` (
    `id` BIGINT PRIMARY KEY AUTO_INCREMENT,
    `publisher_id` BIGINT NOT NULL,
    `name` VARCHAR(100) NOT NULL COMMENT '网站名称',
    `domain` VARCHAR(100) NOT NULL UNIQUE COMMENT '网站域名',
    `icp_number` VARCHAR(50) DEFAULT NULL COMMENT 'ICP 备案号',
    `category_id` INT DEFAULT NULL COMMENT '网站分类 ID',
    `screenshot_url` VARCHAR(500) DEFAULT NULL COMMENT '网站首页截图',
    `alexa_rank` INT DEFAULT NULL COMMENT 'Alexa 排名',
    `daily_uv` INT DEFAULT 0 COMMENT '日 UV',
    `daily_pv` INT DEFAULT 0 COMMENT '日 PV',
    `status` TINYINT DEFAULT 0 COMMENT '0 待审核 1 审核通过 2 审核驳回 3 禁用',
    `audit_remark` VARCHAR(500) DEFAULT NULL COMMENT '审核意见',
    `audit_user_id` BIGINT DEFAULT NULL COMMENT '审核员 ID',
    `audit_time` DATETIME DEFAULT NULL COMMENT '审核时间',
    `sdk_code` TEXT COMMENT '广告 SDK 嵌入代码',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_publisher` (`publisher_id`),
    INDEX `idx_domain` (`domain`),
    INDEX `idx_status` (`status`),
    CONSTRAINT `fk_website_publisher` FOREIGN KEY (`publisher_id`) REFERENCES `ad_publishers`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='网站信息表';

-- 广告位表
DROP TABLE IF EXISTS `ad_positions`;
CREATE TABLE `ad_positions` (
    `id` BIGINT PRIMARY KEY AUTO_INCREMENT,
    `website_id` BIGINT NOT NULL,
    `name` VARCHAR(100) NOT NULL COMMENT '广告位名称',
    `code` VARCHAR(32) NOT NULL UNIQUE COMMENT '广告位唯一标识',
    `type` ENUM('image', 'native', 'popup', 'floating', 'video', 'code') NOT NULL COMMENT '广告类型',
    `width` INT NOT NULL COMMENT '宽度',
    `height` INT NOT NULL COMMENT '高度',
    `min_cpm` DECIMAL(10,4) DEFAULT 0.0000 COMMENT '最低 CPM 出价',
    `visibility` ENUM('public', 'private') DEFAULT 'public' COMMENT '可见度',
    `allowed_advertisers` JSON COMMENT '私有广告位允许的广告主列表',
    -- 屏蔽规则
    `advertiser_blacklist` JSON COMMENT '广告主黑名单',
    `industry_blacklist` JSON COMMENT '行业黑名单',
    `domain_blacklist` JSON COMMENT '竞品域名黑名单',
    `keyword_blacklist` JSON COMMENT '敏感词黑名单',
    -- 状态
    `status` TINYINT DEFAULT 1 COMMENT '1 启用 0 暂停',
    `impression_count` BIGINT DEFAULT 0 COMMENT '累计展示量',
    `click_count` BIGINT DEFAULT 0 COMMENT '累计点击量',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_website` (`website_id`),
    INDEX `idx_code` (`code`),
    INDEX `idx_status` (`status`),
    CONSTRAINT `fk_position_website` FOREIGN KEY (`website_id`) REFERENCES `ad_publisher_websites`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='广告位表';

-- ============================================
-- 4. 财务系统
-- ============================================

-- 账户表
DROP TABLE IF EXISTS `ad_accounts`;
CREATE TABLE `ad_accounts` (
    `id` BIGINT PRIMARY KEY AUTO_INCREMENT,
    `user_id` BIGINT NOT NULL UNIQUE,
    `account_type` ENUM('advertiser', 'publisher') NOT NULL COMMENT '账户类型',
    `balance` DECIMAL(20,2) DEFAULT 0.00 COMMENT '可用余额',
    `frozen_balance` DECIMAL(20,2) DEFAULT 0.00 COMMENT '冻结金额',
    `coupon_balance` DECIMAL(20,2) DEFAULT 0.00 COMMENT '优惠券余额',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_user` (`user_id`),
    INDEX `idx_type` (`account_type`),
    CONSTRAINT `fk_account_user` FOREIGN KEY (`user_id`) REFERENCES `ad_users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='账户表';

-- 交易流水表
DROP TABLE IF EXISTS `ad_transactions`;
CREATE TABLE `ad_transactions` (
    `id` BIGINT PRIMARY KEY AUTO_INCREMENT,
    `account_id` BIGINT NOT NULL,
    `type` ENUM('recharge', 'spend', 'earning', 'withdraw', 'refund', 'adjust') NOT NULL COMMENT '交易类型',
    `amount` DECIMAL(20,2) NOT NULL COMMENT '金额 (正数收入，负数支出)',
    `balance_before` DECIMAL(20,2) NOT NULL COMMENT '变更前余额',
    `balance_after` DECIMAL(20,2) NOT NULL COMMENT '变更后余额',
    `related_id` BIGINT DEFAULT NULL COMMENT '关联订单/活动 ID',
    `description` VARCHAR(500) DEFAULT NULL COMMENT '描述',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_account` (`account_id`),
    INDEX `idx_type` (`type`),
    INDEX `idx_created` (`created_at`),
    CONSTRAINT `fk_transaction_account` FOREIGN KEY (`account_id`) REFERENCES `ad_accounts`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='交易流水表';

-- 充值订单表
DROP TABLE IF EXISTS `ad_recharge_orders`;
CREATE TABLE `ad_recharge_orders` (
    `id` BIGINT PRIMARY KEY AUTO_INCREMENT,
    `advertiser_id` BIGINT NOT NULL,
    `order_no` VARCHAR(32) NOT NULL UNIQUE COMMENT '订单号',
    `amount` DECIMAL(20,2) NOT NULL COMMENT '充值金额',
    `bonus_amount` DECIMAL(20,2) DEFAULT 0.00 COMMENT '赠送金额',
    `channel` ENUM('alipay', 'wechat', 'bank', 'offline') NOT NULL COMMENT '支付渠道',
    `status` TINYINT DEFAULT 0 COMMENT '0 待支付 1 已支付 2 已取消 3 支付失败',
    `payment_time` DATETIME DEFAULT NULL COMMENT '支付时间',
    `notify_url` VARCHAR(500) DEFAULT NULL COMMENT '回调通知地址',
    `return_url` VARCHAR(500) DEFAULT NULL COMMENT '返回地址',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_order_no` (`order_no`),
    INDEX `idx_status` (`status`),
    INDEX `idx_advertiser` (`advertiser_id`),
    CONSTRAINT `fk_recharge_advertiser` FOREIGN KEY (`advertiser_id`) REFERENCES `ad_advertisers`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='充值订单表';

-- 提现记录表
DROP TABLE IF EXISTS `ad_withdrawals`;
CREATE TABLE `ad_withdrawals` (
    `id` BIGINT PRIMARY KEY AUTO_INCREMENT,
    `publisher_id` BIGINT NOT NULL,
    `order_no` VARCHAR(32) NOT NULL UNIQUE COMMENT '订单号',
    `amount` DECIMAL(20,2) NOT NULL COMMENT '提现金额',
    `fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '手续费',
    `actual_amount` DECIMAL(20,2) NOT NULL COMMENT '实际到账金额',
    `account_type` ENUM('alipay', 'wechat', 'bank') NOT NULL COMMENT '提现方式',
    `account_number` VARCHAR(100) NOT NULL COMMENT '提现账号',
    `account_name` VARCHAR(100) NOT NULL COMMENT '账户姓名',
    `status` TINYINT DEFAULT 0 COMMENT '0 待审核 1 审核通过 2 审核驳回 3 打款中 4 已完成',
    `audit_remark` VARCHAR(500) DEFAULT NULL COMMENT '审核意见',
    `audit_user_id` BIGINT DEFAULT NULL COMMENT '审核员 ID',
    `audit_time` DATETIME DEFAULT NULL COMMENT '审核时间',
    `payment_time` DATETIME DEFAULT NULL COMMENT '打款时间',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_order_no` (`order_no`),
    INDEX `idx_status` (`status`),
    INDEX `idx_publisher` (`publisher_id`),
    CONSTRAINT `fk_withdrawal_publisher` FOREIGN KEY (`publisher_id`) REFERENCES `ad_publishers`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='提现记录表';

-- ============================================
-- 5. 审核系统
-- ============================================

-- 审核日志表
DROP TABLE IF EXISTS `ad_audit_logs`;
CREATE TABLE `ad_audit_logs` (
    `id` BIGINT PRIMARY KEY AUTO_INCREMENT,
    `audit_type` ENUM('campaign', 'creative', 'website', 'withdrawal') NOT NULL COMMENT '审核类型',
    `audit_object_id` BIGINT NOT NULL COMMENT '审核对象 ID',
    `auditor_id` BIGINT NOT NULL COMMENT '审核员 ID',
    `action` ENUM('pass', 'reject', 'pending') NOT NULL COMMENT '审核动作',
    `remark` VARCHAR(1000) DEFAULT NULL COMMENT '审核意见',
    `previous_status` TINYINT DEFAULT NULL COMMENT '审核前状态',
    `current_status` TINYINT DEFAULT NULL COMMENT '审核后状态',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_audit_type` (`audit_type`, `audit_object_id`),
    INDEX `idx_auditor` (`auditor_id`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='审核日志表';

-- 审核队列表
DROP TABLE IF EXISTS `ad_audit_queue`;
CREATE TABLE `ad_audit_queue` (
    `id` BIGINT PRIMARY KEY AUTO_INCREMENT,
    `audit_type` ENUM('campaign', 'creative', 'website', 'withdrawal') NOT NULL COMMENT '审核类型',
    `object_id` BIGINT NOT NULL COMMENT '对象 ID',
    `priority` TINYINT DEFAULT 5 COMMENT '优先级 1-10，数字越大优先级越高',
    `assigned_auditor` BIGINT DEFAULT NULL COMMENT '分配审核员',
    `status` TINYINT DEFAULT 0 COMMENT '0 待分配 1 审核中 2 已完成',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_status` (`status`),
    INDEX `idx_type` (`audit_type`),
    INDEX `idx_priority` (`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='审核队列表';

-- ============================================
-- 6. 风控与反作弊
-- ============================================

-- 黑名单表
DROP TABLE IF EXISTS `ad_blacklist`;
CREATE TABLE `ad_blacklist` (
    `id` BIGINT PRIMARY KEY AUTO_INCREMENT,
    `type` ENUM('ip', 'device', 'user', 'domain') NOT NULL COMMENT '黑名单类型',
    `value` VARCHAR(200) NOT NULL COMMENT '黑名单值',
    `reason` VARCHAR(500) DEFAULT NULL COMMENT '拉黑原因',
    `expires_at` DATETIME DEFAULT NULL COMMENT '过期时间',
    `status` TINYINT DEFAULT 1 COMMENT '1 启用 0 禁用',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_type_value` (`type`, `value`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='黑名单表';

-- 风控日志表
DROP TABLE IF EXISTS `ad_risk_logs`;
CREATE TABLE `ad_risk_logs` (
    `id` BIGINT PRIMARY KEY AUTO_INCREMENT,
    `event_type` ENUM('impression', 'click', 'conversion') NOT NULL COMMENT '事件类型',
    `request_id` VARCHAR(64) NOT NULL COMMENT '请求 ID',
    `user_id` VARCHAR(100) DEFAULT NULL COMMENT '用户 ID',
    `ip` VARCHAR(50) DEFAULT NULL COMMENT 'IP 地址',
    `device_id` VARCHAR(100) DEFAULT NULL COMMENT '设备 ID',
    `risk_score` TINYINT DEFAULT 0 COMMENT '风险评分 0-100',
    `is_fraud` TINYINT DEFAULT 0 COMMENT '是否作弊',
    `fraud_type` VARCHAR(50) DEFAULT NULL COMMENT '作弊类型',
    `risk_rules` JSON COMMENT '命中的风控规则',
    `extra_data` JSON COMMENT '额外数据',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_request` (`request_id`),
    INDEX `idx_ip` (`ip`),
    INDEX `idx_fraud` (`is_fraud`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='风控日志表';

-- ============================================
-- 7. 系统配置
-- ============================================

-- 系统配置表
DROP TABLE IF EXISTS `ad_system_config`;
CREATE TABLE `ad_system_config` (
    `k` VARCHAR(50) PRIMARY KEY COMMENT '配置键',
    `v` TEXT COMMENT '配置值',
    `description` VARCHAR(200) DEFAULT NULL COMMENT '配置说明',
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统配置表';

-- 初始化系统配置
INSERT INTO `ad_system_config` (`k`, `v`, `description`) VALUES
('min_cpm_bid', '0.5000', '最低 CPM 出价'),
('min_cpc_bid', '0.1000', '最低 CPC 出价'),
('min_cpa_bid', '1.0000', '最低 CPA 出价'),
('default_tax_rate', '0.0100', '默认税率'),
('min_withdraw_amount', '100.00', '最低提现金额'),
('withdraw_fee_rate', '0.0100', '提现手续费率'),
('audit_timeout_hours', '4', '审核超时时间 (小时)'),
('session_timeout_minutes', '120', '会话超时时间 (分钟)'),
('max_login_attempts', '5', '最大登录尝试次数'),
('lockout_duration_minutes', '30', '账户锁定时长 (分钟)');

-- ============================================
-- 8. 操作日志
-- ============================================

-- 操作日志表
DROP TABLE IF EXISTS `ad_operation_logs`;
CREATE TABLE `ad_operation_logs` (
    `id` BIGINT PRIMARY KEY AUTO_INCREMENT,
    `user_id` BIGINT DEFAULT NULL COMMENT '操作用户 ID',
    `action` VARCHAR(100) NOT NULL COMMENT '操作动作',
    `module` VARCHAR(50) DEFAULT NULL COMMENT '模块',
    `object_type` VARCHAR(50) DEFAULT NULL COMMENT '对象类型',
    `object_id` BIGINT DEFAULT NULL COMMENT '对象 ID',
    `request_data` JSON COMMENT '请求数据',
    `response_data` JSON COMMENT '响应数据',
    `ip` VARCHAR(50) DEFAULT NULL COMMENT 'IP 地址',
    `user_agent` VARCHAR(500) DEFAULT NULL COMMENT 'User-Agent',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user` (`user_id`),
    INDEX `idx_action` (`action`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='操作日志表';
