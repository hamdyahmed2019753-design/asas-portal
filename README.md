# ASAS Investor Portal

A production-ready Investor Portal built with **Laravel 12**, **Filament 3**, **Redis**, and **Laravel Horizon**.

The system provides a complete investment management platform including investor onboarding, KYC verification, contracts, investments, payouts, documents, notifications, and an administration panel.

---

# Features

* Investor Registration
* Email Verification
* Multi-step Onboarding
* KYC Verification Workflow
* Investment Management
* Contract Management
* Contract Interest Workflow
* Payout Scheduling
* Documents Center
* Notification Center
* News Management
* Profile & Security
* System Settings
* Health Monitoring
* Laravel Horizon Integration
* Redis Queue System
* Role & Permission Management

---

# Technology Stack

## Backend

* Laravel 12
* PHP 8.2+
* MySQL 8+
* Redis
* Laravel Horizon
* Livewire 3
* Filament 3
* Spatie Permission
* Spatie Activity Log

## Frontend

* Blade
* Alpine.js
* Tailwind CSS
* Chart.js
* ApexCharts

---

# Project Structure

```
app/
bootstrap/
config/
database/
public/
resources/
routes/
storage/
```

---

# Documentation

Project documentation is split into dedicated files.

| File          | Purpose                               |
| ------------- | ------------------------------------- |
| AGENTS.md     | AI development instructions           |
| LEAN-CTX.md   | lean-ctx project rules                |
| PROJECT.md    | Project architecture & business logic |
| DEPLOYMENT.md | Production deployment guide           |
| OPERATIONS.md | Daily operations & maintenance        |

---

# Development

Requirements

* PHP 8.2+
* Composer 2
* Node.js 20+
* MySQL 8+
* Redis 6+

Installation

```bash
git clone <repository>

cd asas-portal

composer install

npm install

cp .env.example .env

php artisan key:generate

php artisan migrate

php artisan db:seed

php artisan storage:link

npm run build
```

Run locally

```bash
php artisan serve
```

---

# Production

For production deployment, follow:

```
DEPLOYMENT.md
```

Do not use `php artisan serve` in production.

Use:

* Nginx
* PHP-FPM
* Redis
* Horizon
* Supervisor

---

# Security

The project uses:

* Laravel Policies
* Spatie Roles & Permissions
* Signed URLs
* Private Storage
* Queued Notifications
* Activity Logging

---

# Architecture

The application follows a layered architecture:

```
Controllers
        ↓
Form Requests
        ↓
Services
        ↓
Actions
        ↓
Models
```

Business logic is implemented inside Services and Actions.

Controllers remain thin.

---

# Build

Assets

```bash
npm run build
```

Optimization

```bash
php artisan optimize
```

---

# Health

Application health endpoint

```
/health
```

Laravel liveness endpoint

```
/up
```

Horizon Dashboard

```
/horizon
```

(Admin only)

---

# License

Private project.

Copyright © ASAS.

All rights reserved.
