# ASAS Investor Portal — Production Deployment Guide

This document covers running the portal in production on **Linux** with **Redis**
and **Laravel Horizon**. (Horizon requires the `pcntl` + `posix` PHP extensions,
which are Linux-only — it cannot run on Windows dev machines.)

---

## 1. Requirements

| Component | Version / Notes |
|---|---|
| PHP | 8.2+ with `pcntl`, `posix`, `redis` (phpredis), `intl`, `pdo_mysql`, `mbstring`, `bcmath`, `gd` |
| MySQL | 8.0+ |
| Redis | 6+ (cache, session, queue) |
| Composer | 2.x |
| Node | 18+ (asset build) |
| Supervisor | for Horizon |
| Web server | Nginx + PHP-FPM (not `artisan serve`) |

---

## 2. Environment (`.env`)

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://portal.example.com

# Drive everything through Redis in production:
SESSION_DRIVER=redis
CACHE_STORE=redis
QUEUE_CONNECTION=redis

REDIS_CLIENT=phpredis        # or "predis" if the extension is unavailable
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=your-strong-password

HORIZON_PREFIX=asas_horizon:
MAIL_MAILER=smtp             # real mailer for queued mail notifications
```

> `predis/predis` is installed as a pure-PHP fallback; prefer the `phpredis`
> C-extension for performance.

---

## 3. Install & Build

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build

php artisan key:generate          # first deploy only
php artisan migrate --force
php artisan storage:link
```

### Optimize caches (every deploy)
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

---

## 4. Queue & Horizon

All notifications implement `ShouldQueue`, so a **worker must be running** in
production (otherwise notifications queue and never deliver — locally we use
`QUEUE_CONNECTION=sync` instead).

1. Install the Supervisor program:
   ```bash
   sudo cp deploy/supervisor/asas-horizon.conf /etc/supervisor/conf.d/
   sudo supervisorctl reread && sudo supervisorctl update
   sudo supervisorctl start asas-horizon
   ```
2. On each deploy, gracefully restart Horizon (drains in-flight jobs):
   ```bash
   php artisan horizon:terminate
   ```
3. Dashboard: **`/horizon`** — restricted to authenticated **admin** users
   (gate `viewHorizon` in `HorizonServiceProvider`).

Horizon supervises the `default` and `notifications` queues (see
`config/horizon.php`).

---

## 5. Scheduler (cron)

The daily payout refresh (`payouts:refresh`) and other scheduled tasks need cron:

```cron
* * * * * cd /var/www/asas-portal && php artisan schedule:run >> /dev/null 2>&1
```

---

## 6. Health & Monitoring

| Endpoint | Purpose |
|---|---|
| `/up` | Laravel's built-in liveness probe |
| `/health` | Deep check (database / cache / redis / queue) → `200` healthy, `503` if DB down |
| `/horizon` | Queue throughput, failed jobs, metrics (admin-only) |

Point the load balancer / uptime monitor at **`/health`**.

---

## 7. Security Notes

- Serve only over **HTTPS**; set `APP_URL` to the https domain (signed download
  URLs depend on it).
- `APP_DEBUG=false`, `APP_ENV=production`.
- Keep `APP_KEY` secret and stable (rotating it invalidates sessions + signed URLs).
- KYC/document files live on the **private** disk; downloads are temporary
  **signed** URLs gated by policies — never expose `storage/app` publicly.
- Restrict Redis (`bind 127.0.0.1`, password) and MySQL to the app host.
- `/horizon` and `/admin` are admin-only; verify roles after seeding.
