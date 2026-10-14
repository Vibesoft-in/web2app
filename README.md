# WebIntoApp — Multi-Tenant SaaS Platform

Production-grade, multi-tenant SaaS converting e-commerce stores (WooCommerce, Shopify) into white-label mobile applications (Flutter Android & iOS).

---

## 1. Monorepo Structure

```
├── backend/          # Laravel 11 + PHP 8.3 + Filament 3 + Horizon + Sanctum
├── dashboard/        # Next.js 14 (App Router) + TypeScript + Tailwind CSS
├── mobile/           # Flutter white-label engine (Riverpod, Dio, GoRouter)
├── infra/            # Docker configurations (php-fpm, nginx, mysql 8, redis, mailpit)
├── docs/             # Source of truth architectural documentation
│   ├── 01_client_website_dashboard.md
│   ├── 02_backend_architecture.md
│   ├── 03_mobile_app_architecture.md
│   ├── 04_security_roadmap_keypoints.md
│   └── IDEAS.md
├── docker-compose.yml
├── .env.example
└── .github/workflows/ci.yml
```

---

## 2. Prerequisites

- **Docker & Docker Compose** (Recommended for containerized development)
- Or Local Tools:
  - **PHP 8.3+** with extensions (`pdo_mysql`, `mbstring`, `bcmath`, `sodium`, `zip`, `intl`, `gd`)
  - **Composer 2+**
  - **Node.js 20+** & **npm**

---

## 3. Quickstart with Docker Compose

1. **Clone the repository and copy environment configuration:**
   ```bash
   cp .env.example backend/.env
   ```

2. **Start the infrastructure:**
   ```bash
   docker compose up -d
   ```
   This spins up:
   - **PHP-FPM (app)**: Port 9000
   - **Nginx (web)**: `http://localhost:8000`
   - **MySQL 8**: Port 33060 (`webintoapp` database)
   - **Redis**: Port 63790
   - **Mailpit Web UI**: `http://localhost:8025` (SMTP on 1025)

3. **Initialize Laravel backend inside the container:**
   ```bash
   docker compose exec app composer install
   docker compose exec app php artisan key:generate
   docker compose exec app php artisan migrate
   ```

---

## 4. Local Development (Without Docker)

### Backend (Laravel 11)
```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve --port=8000
```
- API Base: `http://localhost:8000/api`
- Filament Admin Panel: `http://localhost:8000/admin`

### Dashboard (Next.js 14)
```bash
cd dashboard
npm install
npm run dev
```
- Dashboard runs at `http://localhost:3000`

---

## 5. Running Tests & Quality Verification

Run all code style, static analysis, and feature tests inside `backend/`:

```bash
cd backend

# 1. Code Style Check (Laravel Pint)
./vendor/bin/pint --test

# 2. Static Analysis Level 8 (Larastan)
./vendor/bin/phpstan analyse --memory-limit=512M

# 3. Feature & Unit Tests (Pest PHP)
./vendor/bin/pest
```

---

## 6. Security Guarantees

1. **Strict Tenant Isolation**: `BelongsToTenant` trait automatically applies global scope `where tenant_id = ?` based on authenticated context. Requests cannot pass or spoof `tenant_id`. Cross-tenant resource lookups yield strict **404** responses to prevent IDOR and existence leakage.
2. **Encrypted Vault**: Store API credentials (`Consumer Key`, `Consumer Secret`) and signing passwords are stored encrypted at rest using AES-256-GCM.
3. **Zero Secrets in Mobile/Frontend**: Mobile builds and client dashboards contain zero store keys or cloud service secrets.
4. **Brute-Force & Rate Limiting**: Authentication endpoints enforce strict throttling per IP and email.
