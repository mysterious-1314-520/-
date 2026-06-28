# 部署指南 / Deployment Guide

**版本:** 1.5.0  
**更新日期:** 2026-06-28

---

## 📋 目录

1. [环境要求](#环境要求)
2. [本地开发部署](#本地开发部署)
3. [生产环境部署](#生产环境部署)
4. [Docker 部署](#docker 部署)
5. [配置说明](#配置说明)
6. [常见问题](#常见问题)

---

## 环境要求

### 基础环境

| 组件 | 最低版本 | 推荐版本 |
|------|----------|----------|
| PHP | 8.0 | 8.2+ |
| MySQL | 5.7 | 8.0+ |
| MariaDB | 10.2 | 10.6+ |
| Nginx | 1.14 | 1.22+ |
| Apache | 2.4 | 2.4.50+ |

### PHP 扩展要求

**必需扩展:**
- `pdo_mysql` 或 `mysqli`
- `mbstring`
- `curl`
- `gd`
- `json`
- `session`

**可选扩展:**
- `redis` (缓存加速)
- `opcache` (性能优化)
- `intl` (国际化支持)

### ClickHouse (可选，用于大数据分析)

| 组件 | 推荐版本 |
|------|----------|
| ClickHouse Server | 20.x+ |
| 内存 | ≥4GB |
| 磁盘 | ≥100GB SSD |

---

## 本地开发部署

### 步骤 1: 安装运行环境

#### macOS (使用 Homebrew)

```bash
# 安装 PHP
brew install php@8.2

# 安装 MySQL
brew install mysql@8.0

# 安装 Composer
brew install composer

# 安装 Redis (可选)
brew install redis
```

#### Linux (Ubuntu/Debian)

```bash
# 更新包索引
sudo apt update

# 安装 PHP 8.2
sudo apt install -y php8.2 php8.2-mysql php8.2-mbstring php8.2-curl php8.2-gd

# 安装 MySQL
sudo apt install -y mysql-server-8.0

# 安装 Nginx
sudo apt install -y nginx

# 安装 Composer
sudo apt install -y composer

# 安装 Redis (可选)
sudo apt install -y redis-server
```

#### Windows (使用 XAMPP)

1. 下载 [XAMPP](https://www.apachefriends.org/)
2. 运行安装程序
3. 启动 Apache 和 MySQL 服务

### 步骤 2: 克隆项目代码

```bash
# 克隆代码
git clone https://github.com/mysterious-1314-520/-.git /var/www/ad-network
cd /var/www/ad-network

# 或使用下载方式
wget https://github.com/mysterious-1314-520/-/archive/v1.5.0/ad-network-1.5.0.tar.gz
tar -xzf ad-network-1.5.0.tar.gz
```

### 步骤 3: 创建数据库

```bash
# 登录 MySQL
mysql -u root -p

# 创建数据库
CREATE DATABASE daohang DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# 创建数据库用户 (推荐)
CREATE USER 'adnetwork'@'localhost' IDENTIFIED BY 'YourPassword123!';
GRANT ALL PRIVILEGES ON daohang.* TO 'adnetwork'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 步骤 4: 导入数据库结构

```bash
# 导入导航系统表
mysql -u adnetwork -p daohang < install/install.sql

# 导入广告联盟系统表
mysql -u adnetwork -p daohang < install/ad_network_schema.sql

# (可选) 导入 ClickHouse 表
clickhouse-client --query "CREATE DATABASE IF NOT EXISTS daohang"
clickhouse-client --database daohang < install/ad_network_clickhouse.sql
```

### 步骤 5: 配置数据库连接

编辑 `config.php` 文件:

```php
<?php
$dbconfig = [
    'host' => 'localhost',
    'port' => 3306,
    'user' => 'adnetwork',      // MySQL 用户名
    'pwd' => 'YourPassword123!', // MySQL 密码
    'dbname' => 'daohang',      // 数据库名
];
```

### 步骤 6: 设置目录权限

```bash
# 设置 Web 目录所有者
sudo chown -R www-data:www-data /var/www/ad-network

# 设置可写目录权限
sudo chmod -R 755 /var/www/ad-network
sudo chmod 777 /var/www/ad-network/config.php
sudo chmod -R 777 /var/www/ad-network/install/
sudo chmod -R 777 /var/www/ad-network/images/bg/
sudo chmod -R 777 /var/www/ad-network/images/ad/
```

### 步骤 7: 配置 Web 服务器

#### Nginx 配置

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/ad-network;
    index index.php index.html;
    
    # 禁止访问敏感目录
    location ~ ^/(includes|install|\.git|\.monkeycode)/ {
        deny all;
        return 404;
    }
    
    # PHP 处理
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_buffer_size 128k;
        fastcgi_buffers 4 256k;
        fastcgi_busy_buffers_size 256k;
    }
    
    # 静态资源缓存
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 7d;
        access_log off;
    }
    
    # 日志
    access_log /var/log/nginx/ad-network-access.log;
    error_log /var/log/nginx/ad-network-error.log;
}
```

#### Apache 配置

```apache
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /var/www/ad-network
    
    <Directory /var/www/ad-network>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    # 禁止访问敏感目录
    <DirectoryMatch "^/var/www/ad-network/(includes|install|\.git|\.monkeycode)/">
        Order allow,deny
        Deny from all
    </DirectoryMatch>
    
    ErrorLog ${APACHE_LOG_DIR}/ad-network-error.log
    CustomLog ${APACHE_LOG_DIR}/ad-network-access.log combined
</VirtualHost>
```

### 步骤 8: 启动服务

```bash
# 重启 Nginx
sudo systemctl restart nginx

# 重启 PHP-FPM
sudo systemctl restart php8.2-fpm

# 重启 Apache
sudo systemctl restart apache2
```

### 步骤 9: 访问网站

浏览器访问 `http://your-domain.com/`

- **导航首页:** `http://your-domain.com/`
- **导航后台:** `http://your-domain.com/admin/`
- **广告联盟注册:** `http://your-domain.com/admin/ad_register.php`
- **广告联盟登录:** `http://your-domain.com/admin/ad_login.php`

---

## 生产环境部署

### PHP 优化

```bash
# 安装 OPcache
sudo apt install php8.2-opcache

# 编辑 php.ini
sudo nano /etc/php/8.2/fpm/php.ini

# 添加以下配置
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.revalidate_freq=60
opcache.fast_shutdown=1
```

### 数据库优化

```sql
-- MySQL 优化建议 (my.cnf)
[mysqld]
# 连接数
max_connections = 500

# 缓冲区
innodb_buffer_pool_size = 2G
innodb_log_buffer_size = 256M

# 查询缓存
query_cache_size = 128M
query_cache_type = 1

# 慢查询日志
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow.log
long_query_time = 2
```

### Redis 缓存配置

```php
// includes/cache.php (示例)
<?php
$redis = new Redis();
$redis->connect('127.0.0.1', 6379);
$redis->auth('your_redis_password');

// 缓存广告数据
function cache_ad_data($id, $data, $ttl = 300) {
    global $redis;
    $redis->setex('ad:'.$id, $ttl, json_encode($data));
}

// 读取缓存
function get_cached_ad($id) {
    global $redis;
    $data = $redis->get('ad:'.$id);
    return $data ? json_decode($data, true) : null;
}
?>
```

### SSL/HTTPS 配置

```bash
# 使用 Let's Encrypt 免费证书
sudo apt install certbot python3-certbot-nginx

# 获取证书
sudo certbot --nginx -d your-domain.com -d www.your-domain.com

# 自动续期
sudo crontab -e
# 添加以下行
0 3 * * * certbot renew --quiet
```

---

## Docker 部署

### 步骤 1: 创建 Docker Compose 配置

```yaml
# docker-compose.yml
version: '3.8'

services:
  # Web 服务 (Nginx + PHP)
  web:
    image: nginx:alpine
    container_name: ad-network-web
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./nginx.conf:/etc/nginx/conf.d/default.conf
      - .:/var/www/html
      - ./ssl:/etc/nginx/ssl:ro
    depends_on:
      - php
      - mysql
    networks:
      - ad-network

  # PHP-FPM
  php:
    image: php:8.2-fpm
    container_name: ad-network-php
    volumes:
      - .:/var/www/html
    depends_on:
      - mysql
    networks:
      - ad-network

  # MySQL
  mysql:
    image: mysql:8.0
    container_name: ad-network-mysql
    environment:
      MYSQL_ROOT_PASSWORD: ${MYSQL_ROOT_PASSWORD}
      MYSQL_DATABASE: daohang
      MYSQL_USER: adnetwork
      MYSQL_PASSWORD: ${MYSQL_PASSWORD}
    volumes:
      - mysql_data:/var/lib/mysql
      - ./install:/docker-entrypoint-initdb.d
    networks:
      - ad-network

  # Redis (可选)
  redis:
    image: redis:alpine
    container_name: ad-network-redis
    command: redis-server --requirepass ${REDIS_PASSWORD}
    volumes:
      - redis_data:/data
    networks:
      - ad-network

  # ClickHouse (可选)
  clickhouse:
    image: clickhouse/clickhouse-server:latest
    container_name: ad-network-clickhouse
    environment:
      CLICKHOUSE_DB: daohang
      CLICKHOUSE_USER: adnetwork
      CLICKHOUSE_PASSWORD: ${CLICKHOUSE_PASSWORD}
    volumes:
      - clickhouse_data:/var/lib/clickhouse
      - ./install/ad_network_clickhouse.sql:/docker-entrypoint-initdb.d/init.sql
    networks:
      - ad-network

volumes:
  mysql_data:
  redis_data:
  clickhouse_data:

networks:
  ad-network:
    driver: bridge
```

### 步骤 2: 创建环境变量文件

```bash
# .env 文件
MYSQL_ROOT_PASSWORD=YourRootPassword123!
MYSQL_PASSWORD=YourPassword123!
REDIS_PASSWORD=YourRedisPassword123!
CLICKHOUSE_PASSWORD=YourClickHousePassword123!
```

### 步骤 3: 创建 Nginx 配置

```nginx
# nginx.conf
server {
    listen 80;
    server_name localhost;
    root /var/www/html;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass php:9000;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    location ~ /\.(htaccess|htpasswd|git|env)$ {
        deny all;
    }

    location ~ ^/(includes|install|\.git|\.monkeycode)/ {
        deny all;
        return 404;
    }
}
```

### 步骤 4: 启动容器

```bash
# 启动所有服务
docker-compose up -d

# 查看日志
docker-compose logs -f

# 停止服务
docker-compose down

# 重启服务
docker-compose restart
```

### 步骤 5: 导入数据库

```bash
# 进入 MySQL 容器
docker-compose exec mysql bash

# 导入数据库
mysql -u adnetwork -p daohang < /docker-entrypoint-initdb.d/install.sql
mysql -u adnetwork -p daohang < /docker-entrypoint-initdb.d/ad_network_schema.sql
```

---

## 配置说明

### 系统配置文件

| 文件 | 说明 | 敏感信息 |
|------|------|----------|
| `config.php` | 数据库连接配置 | 数据库密码 |
| `includes/version.php` | 版本信息 | 无 |
| `api/api_config.php` | API 配置 | API 密钥 (未来) |

### 环境变量

推荐在生产环境使用环境变量配置敏感信息:

```bash
# /etc/environment
AD_NETWORK_DB_HOST=localhost
AD_NETWORK_DB_PORT=3306
AD_NETWORK_DB_USER=adnetwork
AD_NETWORK_DB_PASS=YourPassword123!
AD_NETWORK_DB_NAME=daohang
```

### 安全配置

```php
// includes/safety.php
<?php
// 防止 SQL 注入
define('USE_PREPARED_STATEMENTS', true);

// XSS 防护
function xss_clean($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// CSRF Token
function generate_csrf_token() {
    return bin2hex(random_bytes(32));
}

// 速率限制
function rate_limit($ip, $limit = 100, $window = 3600) {
    // Redis 实现
}
?>
```

---

## 常见问题

### 1. 无法连接数据库

**错误信息:** `Connection refused`

**解决方案:**
```bash
# 检查 MySQL 是否运行
sudo systemctl status mysql

# 检查数据库连接配置
cat config.php

# 测试数据库连接
mysql -u adnetwork -p -h localhost daohang
```

### 2. 403 Forbidden

**原因:** 目录权限不足

**解决方案:**
```bash
sudo chown -R www-data:www-data /var/www/ad-network
sudo chmod -R 755 /var/www/ad-network
```

### 3. 页面空白或 500 错误

**排查步骤:**
```bash
# 查看 PHP 错误日志
sudo tail -f /var/log/php/php-fpm.log
sudo tail -f /var/log/nginx/error.log

# 开启 PHP 错误显示 (开发环境)
php_flag display_errors On
```

### 4. 广告请求响应慢

**优化建议:**
1. 启用 Redis 缓存广告数据
2. 优化 ClickHouse 查询索引
3. 使用 CDN 静态资源
4. 启用 PHP OPcache

### 5. ClickHouse 表导入失败

**解决方案:**
```bash
# 检查 ClickHouse 连接
clickhouse-client --query "SELECT 1"

# 手动创建表
clickhouse-client --database daohang < install/ad_network_clickhouse.sql

# 查看错误日志
sudo tail -f /var/log/clickhouse-server/clickhouse-server.log
```

---

## 备份与恢复

### 数据库备份

```bash
# 备份整个数据库
mysqldump -u adnetwork -p daohang > backup_$(date +%Y%m%d).sql

# 压缩备份
mysqldump -u adnetwork -p daohang | gzip > backup_$(date +%Y%m%d).sql.gz

# 定时备份 (crontab)
0 2 * * * mysqldump -u adnetwork -p daohang | gzip > /backup/daohang_$(date +\%Y\%m\%d).sql.gz
```

### 数据库恢复

```bash
# 从备份恢复
mysql -u adnetwork -p daohang < backup_20260628.sql

# 从压缩文件恢复
gunzip < backup_20260628.sql.gz | mysql -u adnetwork -p daohang
```

### ClickHouse 备份

```bash
# 备份 ClickHouse 表数据
clickhouse-client --query "SELECT * FROM ad_impressions" > ad_impressions.csv

# 恢复
clickhouse-client --query "INSERT INTO ad_impressions FORMAT CSV" < ad_impressions.csv
```

---

## 监控与告警

### 系统监控

```bash
# 安装监控工具
sudo apt install htop iotop nethogs

# 查看 PHP 进程
ps aux | grep php-fpm

# 查看数据库连接
mysql -e "SHOW STATUS LIKE 'Threads_connected';"
```

### 健康检查脚本

```bash
#!/bin/bash
# health_check.sh

#!/bin/bash
# health_check.sh

# 检查 Web 服务
curl -f http://localhost/ || exit 1

# 检查数据库
mysql -u adnetwork -p'password' -e "SELECT 1" daohang || exit 1

# 检查 Redis
redis-cli ping || exit 1

echo "All services running!"
exit 0
```

### Prometheus 监控 (规划中)

```yaml
# prometheus.yml
scrape_configs:
  - job_name: 'php_fpm'
    static_configs:
      - targets: ['localhost:9101']
  
  - job_name: 'mysql'
    static_configs:
      - targets: ['localhost:9104']
```

---

**部署完成！** 🎉

如有问题请提交 Issue 或查看 [GitHub Issues](https://github.com/mysterious-1314-520/-/issues)
