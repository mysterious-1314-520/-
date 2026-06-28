-- 广告联盟系统 ClickHouse 表结构
-- ClickHouse 20.x+

-- ============================================
-- 1. 广告日志表
-- ============================================

-- 展示日志表
DROP TABLE IF EXISTS ad_impressions;
CREATE TABLE ad_impressions
(
    event_time DateTime64(3) COMMENT '事件时间',
    request_id String COMMENT '请求 ID',
    ad_position_id UInt64 COMMENT '广告位 ID',
    website_id UInt64 COMMENT '网站 ID',
    publisher_id UInt64 COMMENT '网站主 ID',
    campaign_id UInt64 COMMENT '广告活动 ID',
    ad_group_id UInt64 COMMENT '广告组 ID',
    creative_id UInt64 COMMENT '创意 ID',
    advertiser_id UInt64 COMMENT '广告主 ID',
    user_id String COMMENT '用户 ID (Cookie/DeviceID)',
    ip String COMMENT 'IP 地址',
    region String COMMENT '省份',
    country String COMMENT '国家',
    city String COMMENT '城市',
    isp String COMMENT '运营商',
    device String COMMENT '设备类型 (PC/Mobile/Tablet)',
    os String COMMENT '操作系统',
    browser String COMMENT '浏览器',
    referer String COMMENT '来源页面',
    user_agent String COMMENT 'UA',
    ecpm Decimal64(6) COMMENT 'eCPM 出价',
    bid_price Decimal64(6) COMMENT '实际出价',
    charge_type Enum8('CPM' = 1, 'CPC' = 2, 'CPA' = 3, 'CPT' = 4) COMMENT '计费类型',
    is_valid UInt8 COMMENT '是否有效流量',
    risk_score UInt8 COMMENT '风险评分 (0-100)',
    fraud_type String COMMENT '作弊类型',
    page_url String COMMENT '页面 URL',
    ad_width UInt16 COMMENT '广告宽度',
    ad_height UInt16 COMMENT '广告高度',
    load_time_ms UInt32 COMMENT '加载耗时 (毫秒)'
)
ENGINE = MergeTree()
PARTITION BY toYYYYMMDD(event_time)
ORDER BY (event_time, ad_position_id, website_id)
TTL event_time + INTERVAL 180 DAY
SETTINGS index_granularity = 8192;

-- 点击日志表
DROP TABLE IF EXISTS ad_clicks;
CREATE TABLE ad_clicks
(
    event_time DateTime64(3) COMMENT '事件时间',
    request_id String COMMENT '请求 ID',
    impression_id String COMMENT '关联展示 ID',
    ad_position_id UInt64 COMMENT '广告位 ID',
    website_id UInt64 COMMENT '网站 ID',
    publisher_id UInt64 COMMENT '网站主 ID',
    campaign_id UInt64 COMMENT '广告活动 ID',
    ad_group_id UInt64 COMMENT '广告组 ID',
    creative_id UInt64 COMMENT '创意 ID',
    advertiser_id UInt64 COMMENT '广告主 ID',
    user_id String COMMENT '用户 ID',
    ip String COMMENT 'IP 地址',
    region String COMMENT '省份',
    country String COMMENT '国家',
    device String COMMENT '设备类型',
    os String COMMENT '操作系统',
    browser String COMMENT '浏览器',
    landing_url String COMMENT '落地页 URL',
    referer String COMMENT '来源页面',
    ecpm Decimal64(6) COMMENT 'eCPM 出价',
    bid_price Decimal64(6) COMMENT '实际出价',
    charge_type Enum8('CPM' = 1, 'CPC' = 1, 'CPA' = 1, 'CPT' = 4) COMMENT '计费类型',
    is_fraud UInt8 COMMENT '是否作弊',
    fraud_reason String COMMENT '作弊原因',
    click_x UInt16 COMMENT '点击 X 坐标',
    click_y UInt16 COMMENT '点击 Y 坐标',
    page_url String COMMENT '页面 URL',
    user_agent String COMMENT 'UA',
    click_delay_ms UInt32 COMMENT '点击延迟 (毫秒)'
)
ENGINE = MergeTree()
PARTITION BY toYYYYMMDD(event_time)
ORDER BY (event_time, creative_id, advertiser_id)
TTL event_time + INTERVAL 180 DAY
SETTINGS index_granularity = 8192;

-- 转化日志表
DROP TABLE IF EXISTS ad_conversions;
CREATE TABLE ad_conversions
(
    event_time DateTime64(3) COMMENT '事件时间',
    request_id String COMMENT '请求 ID',
    click_id String COMMENT '关联点击 ID',
    impression_id String COMMENT '关联展示 ID',
    campaign_id UInt64 COMMENT '广告活动 ID',
    ad_group_id UInt64 COMMENT '广告组 ID',
    creative_id UInt64 COMMENT '创意 ID',
    advertiser_id UInt64 COMMENT '广告主 ID',
    conversion_type Enum8('form' = 1, 'purchase' = 2, 'download' = 3, 'call' = 4, 'register' = 5, 'other' = 6) COMMENT '转化类型',
    conversion_value Decimal64(2) COMMENT '转化价值',
    conversion_id String COMMENT '转化 ID (第三方)',
    attribution_window_hours UInt8 COMMENT '归因窗口 (小时)',
    click_to_convert_hours UInt16 COMMENT '点击到转化时长 (小时)',
    impression_to_convert_hours UInt16 COMMENT '展示到转化时长 (小时)',
    touch_point_count UInt8 COMMENT '触点数',
    first_touch_creative_id UInt64 COMMENT '首次触点创意 ID',
    last_touch_creative_id UInt64 COMMENT '最后触点创意 ID',
    created_at DateTime64(3) DEFAULT now() COMMENT '创建时间'
)
ENGINE = MergeTree()
PARTITION BY toYYYYMMDD(event_time)
ORDER BY (event_time, advertiser_id, conversion_type)
TTL event_time + INTERVAL 365 DAY
SETTINGS index_granularity = 8192;

-- ============================================
-- 2. 数据聚合表
-- ============================================

-- 小时级聚合表 (广告位维度)
DROP TABLE IF EXISTS ad_hourly_position;
CREATE TABLE ad_hourly_position
(
    hour DateTime COMMENT '小时',
    ad_position_id UInt64 COMMENT '广告位 ID',
    website_id UInt64 COMMENT '网站 ID',
    publisher_id UInt64 COMMENT '网站主 ID',
    impressions UInt64 COMMENT '展示量',
    clicks UInt64 COMMENT '点击量',
    ctr Float32 COMMENT '点击率',
    unique_users UInt64 COMMENT '独立用户数',
    total_ecpm Decimal64(6) COMMENT '总 eCPM',
    avg_ecpm Decimal64(6) COMMENT '平均 eCPM',
    total_cost Decimal64(2) COMMENT '总消耗',
    total_earning Decimal64(2) COMMENT '网站主总收益',
    fraud_impressions UInt64 COMMENT '作弊展示量',
    fraud_clicks UInt64 COMMENT '作弊点击量',
    valid_impressions UInt64 COMMENT '有效展示量',
    valid_clicks UInt64 COMMENT '有效点击量'
)
ENGINE = SummingMergeTree()
PARTITION BY toYYYYMMDD(hour)
ORDER BY (hour, ad_position_id, website_id)
TTL hour + INTERVAL 30 DAY;

-- 小时级聚合表 (广告主维度)
DROP TABLE IF EXISTS ad_hourly_advertiser;
CREATE TABLE ad_hourly_advertiser
(
    hour DateTime COMMENT '小时',
    advertiser_id UInt64 COMMENT '广告主 ID',
    campaign_id UInt64 COMMENT '广告活动 ID',
    ad_group_id UInt64 COMMENT '广告组 ID',
    creative_id UInt64 COMMENT '创意 ID',
    charge_type Enum8('CPM' = 1, 'CPC' = 2, 'CPA' = 3, 'CPT' = 4) COMMENT '计费类型',
    impressions UInt64 COMMENT '展示量',
    clicks UInt64 COMMENT '点击量',
    conversions UInt64 COMMENT '转化量',
    ctr Float32 COMMENT '点击率',
    cvr Float32 COMMENT '转化率',
    avg_cpc Decimal64(4) COMMENT '平均点击价格',
    total_cost Decimal64(2) COMMENT '总消耗',
    unique_users UInt64 COMMENT '独立用户数',
    fraud_clicks UInt64 COMMENT '作弊点击量'
)
ENGINE = SummingMergeTree()
PARTITION BY toYYYYMMDD(hour)
ORDER BY (hour, advertiser_id, campaign_id, ad_group_id, creative_id)
TTL hour + INTERVAL 30 DAY;

-- 日级聚合表 (网站主维度)
DROP TABLE IF EXISTS ad_daily_publisher;
CREATE TABLE ad_daily_publisher
(
    day Date COMMENT '日期',
    publisher_id UInt64 COMMENT '网站主 ID',
    website_id UInt64 COMMENT '网站 ID',
    ad_position_id UInt64 COMMENT '广告位 ID',
    impressions UInt64 COMMENT '展示量',
    clicks UInt64 COMMENT '点击量',
    ctr Float32 COMMENT '点击率',
    total_earning Decimal64(2) COMMENT '总收益',
    valid_impressions UInt64 COMMENT '有效展示量',
    valid_clicks UInt64 COMMENT '有效点击量',
    fraud_rate Float32 COMMENT '作弊率'
)
ENGINE = SummingMergeTree()
PARTITION BY toYYYYMM(day)
ORDER BY (day, publisher_id, website_id, ad_position_id)
TTL day + INTERVAL 90 DAY;

-- ============================================
-- 3. 物化视图 (自动聚合)
-- ============================================

-- 从展示日志到小时聚合的物化视图
DROP MATERIALIZED VIEW IF EXISTS ad_hourly_position_mv;
CREATE MATERIALIZED VIEW ad_hourly_position_mv
TO ad_hourly_position
AS SELECT
    toStartOfHour(event_time) AS hour,
    ad_position_id,
    website_id,
    publisher_id,
    count() AS impressions,
    0 AS clicks,
    0 AS ctr,
    uniq(user_id) AS unique_users,
    sum(ecpm) AS total_ecpm,
    avg(ecpm) AS avg_ecpm,
    0 AS total_cost,
    0 AS total_earning,
    sum(is_fraud) AS fraud_impressions,
    0 AS fraud_clicks,
    sum(1 - is_fraud) AS valid_impressions,
    0 AS valid_clicks
FROM ad_impressions
GROUP BY hour, ad_position_id, website_id, publisher_id;

-- 从点击日志到小时聚合的物化视图
DROP MATERIALIZED VIEW IF EXISTS ad_hourly_position_click_mv;
CREATE MATERIALIZED VIEW ad_hourly_position_click_mv
TO ad_hourly_position
AS SELECT
    toStartOfHour(event_time) AS hour,
    ad_position_id,
    website_id,
    publisher_id,
    0 AS impressions,
    count() AS clicks,
    0 AS ctr,
    0 AS unique_users,
    0 AS total_ecpm,
    0 AS avg_ecpm,
    0 AS total_cost,
    0 AS total_earning,
    0 AS fraud_impressions,
    sum(is_fraud) AS fraud_clicks,
    0 AS valid_impressions,
    sum(1 - is_fraud) AS valid_clicks
FROM ad_clicks
GROUP BY hour, ad_position_id, website_id, publisher_id;

-- ============================================
-- 4. 分布式表 (多节点集群时使用)
-- ============================================

-- 如果有多节点 ClickHouse 集群，创建分布式表
-- 单机部署可跳过此部分

-- DROP TABLE IF EXISTS ad_impressions_dist;
-- CREATE TABLE ad_impressions_dist AS ad_impressions
-- ENGINE = Distributed(ad_cluster, default, ad_impressions, rand());

-- ============================================
-- 5. 字典表 (用于维度关联查询)
-- ============================================

-- 广告主字典
DROP DICTIONARY IF EXISTS advertiser_dict;
CREATE DICTIONARY advertiser_dict
(
    advertiser_id UInt64,
    advertiser_name String
)
PRIMARY KEY advertiser_id
SOURCE(CLICKHOUSE(TABLE 'ad_advertisers' DB 'default'))
LIFETIME(MIN 300 MAX 360)
LAYOUT(HASHED());

-- 网站字典
DROP DICTIONARY IF EXISTS website_dict;
CREATE DICTIONARY website_dict
(
    website_id UInt64,
    website_name String,
    domain String
)
PRIMARY KEY website_id
SOURCE(CLICKHOUSE(TABLE 'ad_publisher_websites' DB 'default'))
LIFETIME(MIN 300 MAX 360)
LAYOUT(HASHED());

-- ============================================
-- 6. 查询示例
-- ============================================

-- 查询今日各广告位展示量
-- SELECT ad_position_id, count() FROM ad_impressions 
-- WHERE toYYYYMMDD(event_time) = today() GROUP BY ad_position_id;

-- 查询某广告主昨日消耗
-- SELECT advertiser_id, sum(total_cost) FROM ad_hourly_advertiser 
-- WHERE toYYYYMMDD(hour) = yesterday() GROUP BY advertiser_id;

-- 查询实时点击量 (最近 1 小时)
-- SELECT count() FROM ad_clicks WHERE event_time >= now() - INTERVAL 1 HOUR;
