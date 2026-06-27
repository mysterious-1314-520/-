-- 祈福导航系统 V1.3 官方开源：https://gitee.com/qifuxitong/daohang
DROP TABLE IF EXISTS `web_config`;
create table `web_config` (
`k` varchar(32) NOT NULL,
`v` text NULL,
PRIMARY KEY  (`k`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

INSERT INTO `web_config` VALUES ('cache', '');
INSERT INTO `web_config` VALUES ('version', '1300');
INSERT INTO `web_config` VALUES ('admin_user', 'admin');
INSERT INTO `web_config` VALUES ('admin_pwd', '123456');
INSERT INTO `web_config` VALUES ('style', '1');
INSERT INTO `web_config` VALUES ('sitename', '祈福导航系统');
INSERT INTO `web_config` VALUES ('title', '导航从这里开始');
INSERT INTO `web_config` VALUES ('keywords', '祈福导航系统,导航系统,网址导航,友链管理,站点收录');
INSERT INTO `web_config` VALUES ('description', '祈福导航系统是一套简洁高效的网址导航与友链管理程序，适合快速部署和二次定制。');
INSERT INTO `web_config` VALUES ('kfqq', '');
INSERT INTO `web_config` VALUES ('anounce', '');
INSERT INTO `web_config` VALUES ('modal', '');
INSERT INTO `web_config` VALUES ('music', '');
INSERT INTO `web_config` VALUES ('url', '');
INSERT INTO `web_config` VALUES ('bottom', '');
INSERT INTO `web_config` VALUES ('footer_text', '祈福导航系统 · 精选优质资源');
INSERT INTO `web_config` VALUES ('footer_link', '');
INSERT INTO `web_config` VALUES ('footer_link_text', '');
INSERT INTO `web_config` VALUES ('footer_opacity', '25');
INSERT INTO `web_config` VALUES ('footer_size', '12');
INSERT INTO `web_config` VALUES ('icp', '');
INSERT INTO `web_config` VALUES ('mail_enabled', '0');
INSERT INTO `web_config` VALUES ('mail_to', '');
INSERT INTO `web_config` VALUES ('mail_user', '');
INSERT INTO `web_config` VALUES ('mail_pass', '');
INSERT INTO `web_config` VALUES ('mail_host', 'smtp.qq.com');
INSERT INTO `web_config` VALUES ('mail_port', '587');
INSERT INTO `web_config` VALUES ('mail_sender', '');
INSERT INTO `web_config` VALUES ('bg_mode', 'default');
INSERT INTO `web_config` VALUES ('bg_custom', '');
INSERT INTO `web_config` VALUES ('card_size', 'normal');
INSERT INTO `web_config` VALUES ('columns', 'auto');
INSERT INTO `web_config` VALUES ('time_format', '24');
INSERT INTO `web_config` VALUES ('clock_style', 'digital');
INSERT INTO `web_config` VALUES ('announcement', '');
INSERT INTO `web_config` VALUES ('show_search', '1');
INSERT INTO `web_config` VALUES ('show_clock', '1');
INSERT INTO `web_config` VALUES ('show_tags', '1');
INSERT INTO `web_config` VALUES ('show_link_apply', '1');
INSERT INTO `web_config` VALUES ('ad_enabled', '0');
INSERT INTO `web_config` VALUES ('ad_position', 'below_search');
INSERT INTO `web_config` VALUES ('ad_show_below', '1');
INSERT INTO `web_config` VALUES ('ad_show_right', '0');
INSERT INTO `web_config` VALUES ('ad_show_left', '0');
INSERT INTO `web_config` VALUES ('ad_image', '');
INSERT INTO `web_config` VALUES ('ad_link', '');
INSERT INTO `web_config` VALUES ('ad_title', '');
INSERT INTO `web_config` VALUES ('ad_alt', '');
INSERT INTO `web_config` VALUES ('ad_image2', '');
INSERT INTO `web_config` VALUES ('ad_link2', '');
INSERT INTO `web_config` VALUES ('ad_title2', '');
INSERT INTO `web_config` VALUES ('ad_alt2', '');
INSERT INTO `web_config` VALUES ('ad_image3', '');
INSERT INTO `web_config` VALUES ('ad_link3', '');
INSERT INTO `web_config` VALUES ('ad_title3', '');
INSERT INTO `web_config` VALUES ('ad_alt3', '');
INSERT INTO `web_config` VALUES ('ad_image4', '');
INSERT INTO `web_config` VALUES ('ad_link4', '');
INSERT INTO `web_config` VALUES ('ad_title4', '');
INSERT INTO `web_config` VALUES ('ad_alt4', '');
INSERT INTO `web_config` VALUES ('ad_right_image', '');
INSERT INTO `web_config` VALUES ('ad_right_link', '');
INSERT INTO `web_config` VALUES ('ad_right_title', '');
INSERT INTO `web_config` VALUES ('ad_right_alt', '');
INSERT INTO `web_config` VALUES ('ad_left_image', '');
INSERT INTO `web_config` VALUES ('ad_left_link', '');
INSERT INTO `web_config` VALUES ('ad_left_title', '');
INSERT INTO `web_config` VALUES ('ad_left_alt', '');
INSERT INTO `web_config` VALUES ('ad_new_window', '1');
INSERT INTO `web_config` VALUES ('ad_mode_below_search', 'fixed');
INSERT INTO `web_config` VALUES ('ad_mode_pc_right', 'fixed');
INSERT INTO `web_config` VALUES ('ad_mode_pc_left', 'fixed');
INSERT INTO `web_config` VALUES ('ad_stat_enabled', '1');
INSERT INTO `web_config` VALUES ('ad_legacy_seeded', '1');
INSERT INTO `web_config` VALUES ('bg_animation', '1');
INSERT INTO `web_config` VALUES ('card_animation', '1');
INSERT INTO `web_config` VALUES ('bg_music', '');
INSERT INTO `web_config` VALUES ('bg_music_volume', '50');
INSERT INTO `web_config` VALUES ('ping_enabled', '0');
INSERT INTO `web_config` VALUES ('ping_alert_latency', '3000');
INSERT INTO `web_config` VALUES ('ping_alert_last_date', '');
INSERT INTO `web_config` VALUES ('ping_last_run', '');
INSERT INTO `web_config` VALUES ('ping_last_time', '0');

DROP TABLE IF EXISTS `web_dh`;
create table `web_dh` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`url` varchar(255) NULL,
`name` text NULL,
`category` varchar(50) NOT NULL DEFAULT '常用推荐',
`description` varchar(255) NOT NULL DEFAULT '',
`desc_marquee` tinyint(1) NOT NULL DEFAULT 0,
`desc_speed` varchar(20) NOT NULL DEFAULT 'normal',
`desc_color` varchar(20) NOT NULL DEFAULT 'default',
`icon` varchar(50) NOT NULL DEFAULT '',
`sort` int(11) NOT NULL DEFAULT 100,
`clicks` int(11) NOT NULL DEFAULT 0,
`ping_status` tinyint(1) NOT NULL DEFAULT -1,
`ping_checked_at` int(11) NOT NULL DEFAULT 0,
`ping_http_code` int(11) NOT NULL DEFAULT 0,
`ping_latency` int(11) NOT NULL DEFAULT 0,
`active` int(11) NOT NULL DEFAULT 1,
 PRIMARY KEY (`id`),
 KEY `category` (`category`),
 KEY `active_sort` (`active`,`sort`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `web_category`;
create table `web_category` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`name` varchar(50) NOT NULL,
`icon` varchar(50) NOT NULL DEFAULT '',
`sort` int(11) NOT NULL DEFAULT 100,
`active` int(11) NOT NULL DEFAULT 1,
`addtime` int(11) NOT NULL,
PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

INSERT INTO `web_category` VALUES (NULL, '常用推荐', '⭐', 100, 1, UNIX_TIMESTAMP());

DROP TABLE IF EXISTS `web_log`;
create table `web_log` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`action` varchar(50) NOT NULL,
`target` varchar(50) NOT NULL,
`target_id` int(11) DEFAULT NULL,
`detail` varchar(255) DEFAULT NULL,
`ip` varchar(50) DEFAULT NULL,
`addtime` int(11) NOT NULL,
PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `web_backup`;
create table `web_backup` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`filename` varchar(100) NOT NULL,
`size` int(11) NOT NULL,
`addtime` int(11) NOT NULL,
PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `web_site_stats`;
create table `web_site_stats` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`site_id` int(11) NOT NULL,
`stat_date` date NOT NULL,
`views` int(11) NOT NULL DEFAULT 0,
PRIMARY KEY (`id`),
KEY `site_date` (`site_id`,`stat_date`),
UNIQUE KEY `site_date_unique` (`site_id`,`stat_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `web_stats`;
create table `web_stats` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`stat_date` date NOT NULL,
`views` int(11) NOT NULL DEFAULT 0,
`unique_visitors` int(11) NOT NULL DEFAULT 0,
PRIMARY KEY (`id`),
UNIQUE KEY `stat_date` (`stat_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `web_links`;
create table `web_links` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`name` varchar(100) NOT NULL,
`url` varchar(255) NOT NULL,
`description` varchar(255) DEFAULT NULL,
`icon` varchar(255) DEFAULT NULL,
`category` varchar(50) DEFAULT NULL,
`email` varchar(100) DEFAULT NULL,
`status` int(11) NOT NULL DEFAULT 0,
`ip` varchar(50) DEFAULT NULL,
`addtime` int(11) NOT NULL,
PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `web_ads`;
create table `web_ads` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`position` varchar(30) NOT NULL DEFAULT 'below_search',
`slot` int(11) NOT NULL DEFAULT 1,
`title` varchar(100) NOT NULL DEFAULT '',
`image` varchar(255) NOT NULL DEFAULT '',
`link` varchar(255) NOT NULL DEFAULT '',
`alt` varchar(255) NOT NULL DEFAULT '',
`active` tinyint(1) NOT NULL DEFAULT 1,
`start_at` varchar(19) NOT NULL DEFAULT '',
`end_at` varchar(19) NOT NULL DEFAULT '',
`sort` int(11) NOT NULL DEFAULT 100,
`weight` int(11) NOT NULL DEFAULT 1,
`created_at` int(11) NOT NULL DEFAULT 0,
`updated_at` int(11) NOT NULL DEFAULT 0,
PRIMARY KEY (`id`),
KEY `position_slot` (`position`,`slot`),
KEY `active_time` (`active`,`start_at`,`end_at`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `web_ad_stats`;
create table `web_ad_stats` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`ad_id` int(11) NOT NULL,
`stat_date` date NOT NULL,
`views` int(11) NOT NULL DEFAULT 0,
`clicks` int(11) NOT NULL DEFAULT 0,
PRIMARY KEY (`id`),
KEY `ad_date` (`ad_id`,`stat_date`),
UNIQUE KEY `ad_date_unique` (`ad_id`,`stat_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;
