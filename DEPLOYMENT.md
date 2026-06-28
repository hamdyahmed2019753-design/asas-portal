# ASAS Investor Portal — Production Deployment & Operations Guide

## Overview

This document explains how to deploy, configure, maintain, monitor, and troubleshoot the ASAS Investor Portal in a production environment.

The project is designed to run on:

- Ubuntu Linux
- Nginx
- PHP-FPM
- PHP 8.2+
- MySQL 8+
- Redis
- Laravel Horizon
- Supervisor

This guide should be followed for every production deployment.

---

# 1. Server Requirements

| Component | Required Version |
|------------|-----------------|
| Ubuntu | 22.04+ / 24.04 |
| PHP | 8.2+ |
| Composer | 2.x |
| MySQL | 8.0+ |
| Redis | 6+ |
| Node.js | 20+ |
| NPM | 10+ |
| Git | Latest |
| Nginx | Latest |
| Supervisor | Latest |

---

# 2. Required PHP Extensions

The following PHP extensions must be installed:

```text
bcmath
ctype
curl
dom
fileinfo
gd
intl
mbstring
openssl
pcntl
pdo_mysql
posix
redis
tokenizer
xml
zip
```

Verify installed extensions:

```bash
php -m
```

---

# 3. Production Environment

Example production configuration:

```dotenv
APP_NAME="ASAS Investor Portal"

APP_ENV=production

APP_DEBUG=false

APP_URL=https://portal.example.com

LOG_CHANNEL=stack

CACHE_STORE=redis

SESSION_DRIVER=redis

QUEUE_CONNECTION=redis

FILESYSTEM_DISK=local

REDIS_CLIENT=phpredis

REDIS_HOST=127.0.0.1

REDIS_PORT=6379

REDIS_PASSWORD=your-password

MAIL_MAILER=smtp

HORIZON_PREFIX=asas_horizon:
```

Recommended:

- HTTPS only
- APP_DEBUG=false
- Strong APP_KEY
- Redis password enabled
- Database password enabled

---

# 4. Directory Structure

Example production location:

```
/var/www/asas-portal

├── app
├── bootstrap
├── config
├── database
├── deploy
├── public
├── resources
├── routes
├── storage
├── vendor
└── .env
```

---

# 5. First Installation

Clone repository

```bash
git clone <repository>

cd asas-portal
```

Install dependencies

```bash
composer install --no-dev --optimize-autoloader

npm ci

npm run build
```

Environment

```bash
cp .env.example .env

php artisan key:generate
```

Database

```bash
php artisan migrate --force

php artisan db:seed --force
```

Storage

```bash
php artisan storage:link
```

Optimization

```bash
php artisan optimize
```

Verify

```bash
php artisan about
```

The application should now be ready for production.

---
# 6. Nginx Configuration

Example production configuration:

```nginx
server {
    listen 80;
    server_name portal.example.com;

    root /var/www/asas-portal/public;

    index index.php index.html;

    charset utf-8;

    client_max_body_size 50M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;

        fastcgi_pass unix:/run/php/php8.2-fpm.sock;

        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;

        fastcgi_param DOCUMENT_ROOT $realpath_root;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Reload nginx

```bash
sudo systemctl reload nginx
```

Verify

```bash
sudo nginx -t
```

---

# 7. Apache Configuration (Optional)

If running Apache instead of Nginx:

Enable modules

```bash
sudo a2enmod rewrite

sudo a2enmod headers
```

Document Root

```
/var/www/asas-portal/public
```

Restart

```bash
sudo systemctl restart apache2
```

---

# 8. PHP-FPM

Restart PHP

```bash
sudo systemctl restart php8.2-fpm
```

Verify

```bash
sudo systemctl status php8.2-fpm
```

Recommended php.ini

```
memory_limit = 512M

upload_max_filesize = 50M

post_max_size = 50M

max_execution_time = 120

max_input_vars = 3000

opcache.enable = 1
```

---

# 9. File Permissions

Application directories

```
storage/

bootstrap/cache/

public/storage
```

Ownership

```bash
sudo chown -R www-data:www-data .

sudo chmod -R 775 storage

sudo chmod -R 775 bootstrap/cache
```

Verify

```bash
ls -la storage

ls -la bootstrap/cache
```

---

# 10. Storage

Create symbolic link

```bash
php artisan storage:link
```

Verify

```bash
ls -l public/storage
```

Expected

```
public/storage -> ../storage/app/public
```

If incorrect

```bash
rm -f public/storage

php artisan storage:link
```

---

# 11. Cache Optimization

Always cache configuration before opening production.

Configuration

```bash
php artisan config:cache
```

Routes

```bash
php artisan route:cache
```

Views

```bash
php artisan view:cache
```

Events

```bash
php artisan event:cache
```

Everything

```bash
php artisan optimize
```

Clear all caches

```bash
php artisan optimize:clear
```

---

# 12. Deployment Workflow

Recommended production deployment order

```
Git Pull
        ↓
Composer Install
        ↓
Database Migration
        ↓
NPM Build
        ↓
Config Cache
        ↓
Route Cache
        ↓
View Cache
        ↓
Event Cache
        ↓
Optimize
        ↓
Restart Horizon
        ↓
Restart Queue
        ↓
Health Check
        ↓
Open Website
```

Commands

```bash
git pull

composer install --no-dev --optimize-autoloader

php artisan migrate --force

npm ci

npm run build

php artisan optimize

php artisan horizon:terminate

php artisan queue:restart
```

---

# 13. Zero Downtime Deployment

For maintenance deployments

```bash
php artisan down --render

git pull

composer install --no-dev

php artisan migrate --force

npm run build

php artisan optimize

php artisan horizon:terminate

php artisan queue:restart

php artisan up
```

Verify

```
/health

/up
```

Both should return HTTP 200.

---
# 14. Redis

Redis is used for:

- Cache
- Sessions
- Queues
- Horizon
- Rate Limiting

Verify Redis

```bash
redis-cli ping
```

Expected

```
PONG
```

Redis information

```bash
redis-cli info
```

Monitor live commands

```bash
redis-cli monitor
```

Show memory usage

```bash
redis-cli info memory
```

Verify Laravel connection

```bash
php artisan tinker
```

```php
Cache::put('deployment-test', true);

Cache::get('deployment-test');
```

Expected

```
true
```

---

# 15. Laravel Horizon

Horizon processes all queued jobs.

Production queues

```
default

notifications
```

Start Horizon manually

```bash
php artisan horizon
```

Terminate (graceful restart)

```bash
php artisan horizon:terminate
```

Status

```bash
php artisan horizon:status
```

List supervisors

```bash
php artisan horizon:supervisors
```

List queues

```bash
php artisan horizon:list
```

Dashboard

```
https://portal.example.com/horizon
```

Access

- Admin only

Never expose Horizon publicly.

---

# 16. Supervisor

Supervisor keeps Horizon running.

Configuration file

```
/etc/supervisor/conf.d/asas-horizon.conf
```

Reload configuration

```bash
sudo supervisorctl reread

sudo supervisorctl update
```

Start

```bash
sudo supervisorctl start asas-horizon
```

Restart

```bash
sudo supervisorctl restart asas-horizon
```

Stop

```bash
sudo supervisorctl stop asas-horizon
```

Status

```bash
sudo supervisorctl status
```

Reload everything

```bash
sudo supervisorctl reload
```

---

# 17. Queue

Restart workers

```bash
php artisan queue:restart
```

Show failed jobs

```bash
php artisan queue:failed
```

Retry all failed jobs

```bash
php artisan queue:retry all
```

Retry one failed job

```bash
php artisan queue:retry ID
```

Flush failed jobs

```bash
php artisan queue:flush
```

Clear queue

```bash
php artisan queue:clear redis
```

---

# 18. Scheduler

The scheduler executes all scheduled tasks.

Current scheduler

```bash
php artisan schedule:list
```

Run manually

```bash
php artisan schedule:run
```

Cron

```cron
* * * * * cd /var/www/asas-portal && php artisan schedule:run >> /dev/null 2>&1
```

Verify cron

```bash
crontab -l
```

---

# 19. Health Monitoring

Health endpoints

```
/up

/health

/horizon
```

Expected

| Endpoint | Status |
|----------|--------|
| /up | 200 |
| /health | 200 |
| /horizon | Admin only |

Health checks include

- Application
- Database
- Cache
- Redis
- Queue
- Storage

Example response

```json
{
    "healthy": true,
    "checks": {
        "app": "ok",
        "database": "ok",
        "cache": "ok",
        "redis": "ok",
        "queue": "redis",
        "storage": "ok"
    }
}
```

---

# 20. Logging

Laravel logs

```
storage/logs/laravel.log
```

Horizon logs

```
storage/logs/horizon.log
```

Nginx

```
/var/log/nginx/error.log

/var/log/nginx/access.log
```

PHP-FPM

```
/var/log/php8.2-fpm.log
```

View Laravel log

```bash
tail -f storage/logs/laravel.log
```

View Horizon log

```bash
tail -f storage/logs/horizon.log
```

View Nginx log

```bash
sudo tail -f /var/log/nginx/error.log
```

---

# 21. Monitoring Checklist

Verify after every deployment

✓ Redis running

✓ Horizon running

✓ Queue working

✓ Scheduler working

✓ Health endpoint returns 200

✓ Images loading

✓ Notifications delivering

✓ Database connected

✓ Cache working

✓ Storage writable

✓ No failed jobs

✓ No PHP errors

✓ No Nginx errors
# 22. Backup Strategy

A complete production backup should include:

- Database
- Storage
- Uploaded files
- .env
- Supervisor configuration
- Nginx configuration

Recommended backup frequency

| Item | Frequency |
|-------|------------|
| Database | Every hour |
| Storage | Daily |
| .env | Every deployment |
| Full Server | Weekly |

Database backup

```bash
mysqldump -u USER -p DATABASE > backup.sql
```

Compress backup

```bash
tar -czf asas-backup.tar.gz backup.sql storage
```

Verify backup

```bash
ls -lh asas-backup.tar.gz
```

---

# 23. Restore

Restore database

```bash
mysql -u USER -p DATABASE < backup.sql
```

Restore storage

```bash
cp -R backup/storage storage
```

Restore .env

```bash
cp backup/.env .env
```

Rebuild caches

```bash
php artisan optimize
```

Restart Horizon

```bash
php artisan horizon:terminate
```

---

# 24. Rollback

If deployment fails

```bash
php artisan down

git checkout <previous_commit>

composer install --no-dev

npm install

npm run build

php artisan migrate --force

php artisan optimize

php artisan horizon:terminate

php artisan queue:restart

php artisan up
```

---

# 25. Emergency Recovery

If the application is completely down

Recovery order

```
Server

↓

Nginx

↓

PHP-FPM

↓

Redis

↓

MySQL

↓

Supervisor

↓

Horizon

↓

Queue

↓

Laravel

↓

Health Endpoint
```

Restart services

```bash
sudo systemctl restart nginx

sudo systemctl restart php8.2-fpm

sudo systemctl restart redis

sudo systemctl restart mysql
```

Restart Horizon

```bash
sudo supervisorctl restart asas-horizon
```

Restart Queue

```bash
php artisan queue:restart
```

Verify

```bash
php artisan about
```

---

# 26. Security Hardening

Always

✔ HTTPS only

✔ APP_DEBUG=false

✔ APP_ENV=production

✔ Strong APP_KEY

✔ Redis password

✔ MySQL password

✔ Firewall enabled

✔ SSH key authentication

✔ Disable root login

✔ Fail2Ban

✔ Automatic security updates

Never expose

```
storage/

vendor/

.env

database/

bootstrap/cache
```

Only expose

```
public/
```

---

# 27. SSL

Verify certificate

```bash
sudo certbot certificates
```

Renew

```bash
sudo certbot renew
```

Reload Nginx

```bash
sudo systemctl reload nginx
```

Verify

```bash
openssl s_client -connect portal.example.com:443
```

---

# 28. Database Verification

Connect

```bash
mysql -u root -p
```

Verify

```sql
SHOW DATABASES;

USE asas_portal;

SHOW TABLES;

SELECT COUNT(*) FROM users;

SELECT COUNT(*) FROM notifications;
```

Migration status

```bash
php artisan migrate:status
```

---

# 29. Common Problems

## Images not loading

Check

```bash
php artisan storage:link
```

Verify

```bash
ls -l public/storage
```

---

## Notifications not arriving

Verify

- Redis
- Horizon
- Queue
- Failed Jobs

```bash
php artisan queue:failed

php artisan horizon:status
```

---

## Queue stopped

```bash
php artisan queue:restart

php artisan horizon:terminate
```

---

## Redis not running

```bash
redis-cli ping
```

Restart

```bash
sudo systemctl restart redis
```

---

## Horizon stopped

```bash
sudo supervisorctl restart asas-horizon
```

---

## APP_URL incorrect

Symptoms

- Email verification fails
- Signed URLs invalid
- File uploads fail
- Storage previews fail

After changing

```bash
php artisan config:cache
```

---

## Permission denied

```bash
sudo chown -R www-data:www-data .

chmod -R 775 storage

chmod -R 775 bootstrap/cache
```

---

## HTTP 500

```bash
tail -f storage/logs/laravel.log
```

Run

```bash
php artisan optimize:clear

php artisan optimize
```

---

## White Screen

Check

- PHP-FPM
- APP_KEY
- .env
- Database
- Logs

---

# 30. Daily Operations

Daily

- Verify Horizon
- Verify Redis
- Verify Scheduler
- Check Failed Jobs
- Review Logs
- Monitor Storage
- Check Health Endpoint

Weekly

- Backup Database
- Backup Storage
- Review Server Updates
- Check Disk Usage

Monthly

- Test Restore
- Rotate Logs
- Review Security Updates
- Verify SSL

---

# 31. Production Checklist

Before opening production

✔ APP_ENV=production

✔ APP_DEBUG=false

✔ APP_URL configured

✔ SSL installed

✔ Database migrated

✔ Seeders executed

✔ Redis running

✔ Horizon running

✔ Supervisor running

✔ Queue working

✔ Scheduler working

✔ Mail configured

✔ Storage linked

✔ Images loading

✔ Documents downloadable

✔ Health endpoint returns 200

✔ Horizon dashboard working

✔ Notifications working

✔ Cache optimized

✔ Config cached

✔ Routes cached

✔ Views cached

✔ Events cached

✔ Logs writable

✔ Automatic backups configured

✔ Monitoring enabled

✔ Security verified

---

# 32. Useful Artisan Commands

General

```bash
php artisan about

php artisan inspire

php artisan list
```

Database

```bash
php artisan migrate

php artisan migrate:status

php artisan db:seed

php artisan tinker
```

Optimization

```bash
php artisan optimize

php artisan optimize:clear

php artisan config:cache

php artisan route:cache

php artisan view:cache

php artisan event:cache
```

Storage

```bash
php artisan storage:link
```

Queue

```bash
php artisan queue:restart

php artisan queue:failed

php artisan queue:retry all

php artisan queue:flush
```

Scheduler

```bash
php artisan schedule:list

php artisan schedule:run
```

Horizon

```bash
php artisan horizon

php artisan horizon:status

php artisan horizon:list

php artisan horizon:terminate
```

Health

```bash
php artisan about
```

---

# 33. Best Practices

- Never use `php artisan serve` in production.
- Always deploy behind Nginx + PHP-FPM.
- Keep Redis, Queue, and Horizon running.
- Restart Horizon after every deployment.
- Verify `/health` after every deployment.
- Monitor `storage/logs/laravel.log` regularly.
- Keep backups encrypted and stored off-site.
- Test disaster recovery periodically.
- Keep Composer, Laravel, PHP, and server packages updated.
- Review failed jobs and logs daily.
- Document every production change.
- Never deploy directly to production without version control.

---

# Document Version

Project: ASAS Investor Portal

Framework: Laravel 12

PHP: 8.2+

Document Version: 1.0

Last Updated: June 2026
