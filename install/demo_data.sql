-- 祈福导航系统 V1.3 示例数据
-- 适用于全新安装后快速填充分类和站点数据

-- 分类数据
INSERT INTO `web_category` (`id`, `name`, `icon`, `sort`, `active`, `addtime`) VALUES
(1, '常用推荐', '⭐', 1, 1, UNIX_TIMESTAMP()),
(2, '搜索引擎', '🔍', 2, 1, UNIX_TIMESTAMP()),
(3, '社交媒体', '💬', 3, 1, UNIX_TIMESTAMP()),
(4, '技术开发', '💻', 4, 1, UNIX_TIMESTAMP()),
(5, '娱乐媒体', '🎬', 5, 1, UNIX_TIMESTAMP()),
(6, '学习教育', '📚', 6, 1, UNIX_TIMESTAMP()),
(7, '生活服务', '🏠', 7, 1, UNIX_TIMESTAMP()),
(8, '购物电商', '🛒', 8, 1, UNIX_TIMESTAMP()),
(9, '新闻资讯', '📰', 9, 1, UNIX_TIMESTAMP()),
(10, '工具资源', '🛠️', 10, 1, UNIX_TIMESTAMP());

-- 站点数据 (36 个示例站点)
INSERT INTO `web_dh` (`url`, `name`, `category`, `description`, `desc_marquee`, `desc_speed`, `desc_color`, `icon`, `sort`, `active`, `clicks`) VALUES

-- 常用推荐
('https://www.baidu.com', '百度', '常用推荐', '全球最大的中文搜索引擎', 1, 'normal', 'blue', 'https://www.baidu.com/favicon.ico', 1, 1, 0),
('https://www.bing.com', 'Bing 必应', '常用推荐', '微软智能搜索引擎，支持 AI 对话', 1, 'normal', 'blue', 'https://www.bing.com/favicon.ico', 2, 1, 0),
('https://www.google.com', 'Google', '常用推荐', '全球最大的搜索引擎', 1, 'normal', 'green', 'https://www.google.com/favicon.ico', 3, 1, 0),

-- 搜索引擎
('https://www.sogou.com', '搜狗搜索', '搜索引擎', '搜狗搜索引擎', 0, 'normal', 'blue', '', 1, 1, 0),
('https://www.so.com', '360 搜索', '搜索引擎', '360 安全搜索', 0, 'normal', 'green', '', 2, 1, 0),
('https://duckduckgo.com', 'DuckDuckGo', '搜索引擎', '注重隐私的搜索引擎', 0, 'normal', 'orange', '', 3, 1, 0),

-- 社交媒体
('https://weibo.com', '微博', '社交媒体', '随时随地分享新鲜事', 1, 'normal', 'red', 'https://weibo.com/favicon.ico', 1, 1, 0),
('https://www.zhihu.com', '知乎', '社交媒体', '有问题就会有答案', 1, 'slow', 'blue', 'https://www.zhihu.com/favicon.ico', 2, 1, 0),
('https://www.douban.com', '豆瓣', '社交媒体', '书影音记录与社区', 0, 'normal', 'green', '', 3, 1, 0),
('https://t.me', 'Telegram', '社交媒体', '安全即时通讯工具', 0, 'normal', 'blue', '', 4, 1, 0),

-- 技术开发
('https://github.com', 'GitHub', '技术开发', '全球最大的代码托管平台', 1, 'normal', 'purple', 'https://github.com/favicon.ico', 1, 1, 0),
('https://stackoverflow.com', 'Stack Overflow', '技术开发', '程序员问答社区', 0, 'normal', 'orange', '', 2, 1, 0),
('https://juejin.cn', '稀土掘金', '技术开发', '技术社区与知识分享', 0, 'normal', 'orange', '', 3, 1, 0),
('https://www.v2ex.com', 'V2EX', '技术开发', '创意工作者社区', 0, 'normal', 'green', '', 4, 1, 0),
('https://cloud.tencent.com', '腾讯云', '技术开发', '云计算服务提供商', 0, 'normal', 'blue', '', 5, 1, 0),

-- 娱乐媒体
('https://www.bilibili.com', '哔哩哔哩', '娱乐媒体', '国内知名视频弹幕网站', 1, 'normal', 'pink', 'https://www.bilibili.com/favicon.ico', 1, 1, 0),
('https://www.youtube.com', 'YouTube', '娱乐媒体', '全球最大的视频网站', 1, 'normal', 'red', '', 2, 1, 0),
('https://music.163.com', '网易云音乐', '娱乐媒体', '音乐播放与分享平台', 0, 'normal', 'red', '', 3, 1, 0),
('https://www.iqiyi.com', '爱奇艺', '娱乐媒体', '视频流媒体平台', 0, 'normal', 'green', '', 4, 1, 0),

-- 学习教育
('https://www.xuexi.cn', '学习强国', '学习教育', '学习平台', 0, 'normal', 'red', '', 1, 1, 0),
('https://www.icourse163.org', '中国大学 MOOC', '学习教育', '在线学习平台', 0, 'normal', 'blue', '', 2, 1, 0),
('https://www.khanacademy.org', '可汗学院', '学习教育', '免费教育网站', 0, 'normal', 'green', '', 3, 1, 0),

-- 生活服务
('https://mail.qq.com', 'QQ 邮箱', '生活服务', '腾讯邮箱服务', 0, 'normal', 'blue', '', 1, 1, 0),
('https://www.12306.cn', '12306', '生活服务', '铁路客户服务中心', 0, 'normal', 'blue', '', 2, 1, 0),
('https://www.weather.com.cn', '中国天气', '生活服务', '天气预报服务', 0, 'normal', 'blue', '', 3, 1, 0),

-- 购物电商
('https://www.taobao.com', '淘宝', '购物电商', '阿里巴巴旗下购物平台', 1, 'normal', 'orange', 'https://www.taobao.com/favicon.ico', 1, 1, 0),
('https://www.jd.com', '京东', '购物电商', '品质电商平台', 1, 'normal', 'red', 'https://www.jd.com/favicon.ico', 2, 1, 0),
('https://www.pinduoduo.com', '拼多多', '购物电商', '社交电商平台', 0, 'normal', 'red', '', 3, 1, 0),
('https://www.amazon.com', '亚马逊', '购物电商', '全球电商平台', 0, 'normal', 'orange', '', 4, 1, 0),

-- 新闻资讯
('https://www.thepaper.cn', '澎湃新闻', '新闻资讯', '时政思想财经新闻', 0, 'normal', 'blue', '', 1, 1, 0),
('https://www.sina.com.cn', '新浪网', '新闻资讯', '综合门户网站', 0, 'normal', 'red', '', 2, 1, 0),
('https://www.163.com', '网易', '新闻资讯', '综合门户网站', 0, 'normal', 'red', '', 3, 1, 0),

-- 工具资源
('https://cli.im', '草料二维码', '工具资源', '在线二维码生成', 0, 'normal', 'green', '', 1, 1, 0),
('https://www.aigei.com', '爱给网', '工具资源', '音效配乐素材', 0, 'normal', 'orange', '', 2, 1, 0),
('https://www.remove.bg', 'Remove.bg', '工具资源', '在线抠图工具', 0, 'normal', 'blue', '', 3, 1, 0),
('https://uupoop.com', 'Uupoop', '工具资源', '在线 PDF 工具', 0, 'normal', 'red', '', 4, 1, 0);

-- 网站配置更新
UPDATE `web_config` SET v = '祈福导航 - 精选优质互联网资源' WHERE k = 'title';
UPDATE `web_config` SET v = '祈福导航，为您提供优质的互联网资源导航服务，涵盖搜索、社交、技术、娱乐、学习、生活、购物、新闻等多个领域' WHERE k = 'keywords';
UPDATE `web_config` SET v = '祈福导航是一个免费的网址导航系统，收录各类实用网站，帮您快速找到所需资源。支持分类浏览、搜索直达、站点描述跑马灯展示等功能。' WHERE k = 'description';
UPDATE `web_config` SET v = '欢迎使用祈福导航系统！本站收录了互联网上最常用的各类网站资源，帮助您高效访问。' WHERE k = 'anounce';
UPDATE `web_config` SET v = '© 2024 祈福导航系统 · 精选优质资源 | Powered by 祈福导航 V1.3' WHERE k = 'footer_text';
