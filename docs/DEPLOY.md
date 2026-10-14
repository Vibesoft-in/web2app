# Production Deployment & Infrastructure Guide

Comprehensive production deployment guide for the **WebIntoApp** multi-tenant platform. This document covers architecture, containerization, security hardening, supervisor workers, health monitoring, zero-downtime deployment, backups, disaster recovery, and operational procedures.

---

## 1. Production Architecture Overview

The production deployment runs on a hardened Linux host (Ubuntu 22.04 LTS / 24.04 LTS) using isolated containerized services connected via private Docker bridge networks:

```
                          Internet (Clients & Browsers)
                                       │
                                       ▼ (Ports 80/443)
                    ┌─────────────────────────────────────┐
                    │       Nginx Reverse Proxy           │
                    │   TLS 1.2/1.3, Rate Limits, CSP,    │
                    │    HSTS, Permissions-Policy, Gzip   │
                    └──────────────────┬──────────────────┘
                                       │
                ┌──────────────────────┴──────────────────────┐
                │                                             │
                ▼ (FastCGI :9000)                             ▼ (HTTP :3000)
    ┌───────────────────────────┐                 ┌───────────────────────────┐
    │    Laravel Backend        │                 │    Next.js Dashboard      │
    │   PHP 8.3 FPM (Alpine)    │                 │    Node 20 (Standalone)   │
    │   User: www (UID 1000)    │                 │   User: nextjs (UID 1001) │
    │   OPcache Pre-compiled    │                 │   Prerendered & Optimized │
    └─────────────┬─────────────┘                 └───────────────────────────┘
                  │
                  ├──────────────────────────────┐
                  ▼                              ▼
    ┌───────────────────────────┐  ┌───────────────────────────┐
    │     MySQL 8.0 Cluster     │  │       Redis 7 Broker      │
    │    (Internal Net Only)    │  │    (Internal Net Only)    │
    │    Encrypted At Rest      │  │   Cache, Queues, Sessions │
    └───────────────────────────┘  └─────────────┬─────────────┘
                                                 │
                                                 ▼
                                   ┌───────────────────────────┐
                                   │  Supervisor Queue Worker  │
                                   │   Workers: high, default, │
                                   │   notifications, builds   │
                                   │   Scheduler: every minute │
                                   └─────────────┬─────────────┘
                                                 │
                                                 ▼ (Trigger job via Volume)
                                   ┌───────────────────────────┐
                                   │   Isolated Build Worker   │
                                   │   Flutter 3.24, Java 17,  │
                                   │   Android SDK 34          │
                                   │   cap_drop: [ALL], No DB  │
                                   └───────────────────────────┘
```

### Network Topology & Isolation
1. **`frontend_net`**: Bridges Nginx and the Next.js Dashboard.
2. **`backend_net`**: Internal private network connecting Nginx, Laravel PHP-FPM, Supervisor Worker, MySQL, and Redis. No external host ports are bound to MySQL or Redis.
3. **`build_net`**: Strict sandbox network for the Android Build Worker. Has outbound Internet access to fetch Gradle/Flutter dependencies during compilation, but **has no route or connectivity to MySQL or production credentials**.

---

## 2. Multi-Stage Dockerfiles

All Docker images are multi-stage builds adhering to the principle of least privilege, minimal attack surface, and non-root runtime users.

| Image | Path | Base Image | Run User | Health Check |
|---|---|---|---|---|
| **Backend API** | `infra/docker/Dockerfile.backend` | `php:8.3-fpm-alpine` | `www` (UID 1000) | `cgi-fcgi -bind -connect 127.0.0.1:9000` |
| **Worker / Cron** | `infra/docker/Dockerfile.worker` | `php:8.3-cli-alpine` | `www` (UID 1000) | Supervisord process monitor |
| **Dashboard** | `dashboard/Dockerfile` | `node:20-alpine` | `nextjs` (UID 1001) | `curl -f http://127.0.0.1:3000/` |
| **Build Worker** | `infra/docker/Dockerfile.build-worker` | `ubuntu:22.04` | `builder` (UID 1001) | `flutter --version` |

### Key Docker Hardening Highlights:
- **Zero dev dependencies**: Composer `--no-dev` and npm prune/standalone builds strip all test runners, linters, and build utilities from runtime containers.
- **Tuned OPcache**: In `Dockerfile.backend`, `opcache.validate_timestamps=0` eliminates filesystem stat overhead in production.
- **Sandboxed Build Container**: `Dockerfile.build-worker` drops all Linux capabilities (`cap_drop: [ALL]`), enables `no-new-privileges:true`, and runs as unprivileged user `builder`.

---

## 3. Nginx Hardening & Security Headers

The production Nginx configuration (`infra/nginx/production.conf`) enforces strict transport security, cross-origin restrictions, rate limiting zones, and resource isolation.

### Security Headers Summary
```nginx
# 1. HTTP Strict Transport Security (HSTS) - 2 years + subdomains + preload
add_header Strict-Transport-Security "max-age=63072000; includeSubDomains; preload" always;

# 2. Prevent MIME Sniffing
add_header X-Content-Type-Options "nosniff" always;

# 3. Clickjacking Protection
add_header X-Frame-Options "SAMEORIGIN" always;

# 4. Referrer Policy
add_header Referrer-Policy "strict-origin-when-cross-origin" always;

# 5. Restrict Web Hardware APIs
add_header Permissions-Policy "camera=(), microphone=(), geolocation=(), payment=(self 'https://checkout.razorpay.com')" always;

# 6. Content Security Policy (CSP)
add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' https://checkout.razorpay.com https://api.razorpay.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' data: https://fonts.gstatic.com; img-src 'self' data: https: blob:; connect-src 'self' https://api.razorpay.com https://*.ingest.sentry.io https://fcm.googleapis.com; frame-src 'self' https://api.razorpay.com; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'self';" always;
```

### Rate Limiting Architecture
- **`auth_zone`**: 10 requests/minute (burst: 5) for `/api/v1/auth/*` endpoints (OTP request, OTP verify, Google login) to protect against SMS spam and credential stuffing.
- **`api_zone`**: 100 requests/minute (burst: 20) for general API endpoints.
- **`healthz` & `api/health`**: Bypasses rate limiting for continuous container orchestrator and load balancer probes without false positives.

---

## 4. Supervisor Queue Workers & Scheduler

Background asynchronous tasks and scheduled crons are supervised via `infra/supervisor/supervisord.conf` and `infra/supervisor/conf.d/laravel-worker.conf`.

### Queue Workers
1. **`laravel-worker-default`** (4 processes):
   - Consumes queues: `high,default`
   - Command: `php artisan queue:work redis --queue=high,default --sleep=3 --tries=3 --timeout=90 --max-jobs=1000 --max-time=3600`
   - Handles customer notifications, webhook ingestion, tenant data synchronization.
2. **`laravel-worker-notifications`** (2 processes):
   - Consumes queue: `notifications`
   - Command: `php artisan queue:work redis --queue=notifications --sleep=2 --tries=3 --timeout=120 --max-jobs=500 --max-time=3600`
   - Handles FCM push batches and WhatsApp Cloud API message dispatches.
3. **`laravel-worker-builds`** (2 processes):
   - Consumes queue: `builds`
   - Command: `php artisan queue:work redis --queue=builds --sleep=5 --tries=1 --timeout=1800 --max-jobs=50 --max-time=7200`
   - Prepares build assets, orchestrates sandboxed compilation, and dispatches to the isolated build worker.
4. **`laravel-scheduler`** (1 process):
   - Loop runner executing `php artisan schedule:run` every 60 seconds.
   - Cleans expired OTPs, trims device tokens, processes subscription recurring billing, and checks plan expirations.

---

## 5. Log Rotation Architecture

Logrotate configurations ensure disk exhaustion is prevented while preserving audit trails for compliance.

| Config | Path | Frequency | Retention | Action |
|---|---|---|---|---|
| **Laravel App** | `infra/logrotate/laravel` | Daily | 14 days | `copytruncate`, gzip compressed |
| **Nginx Web** | `infra/logrotate/nginx` | Daily | 30 days | `sharedscripts`, `kill -USR1 $(cat /var/run/nginx.pid)` |
| **Supervisor** | `infra/logrotate/supervisor` | Daily | 14 days | `sharedscripts`, `kill -USR2 $(cat /var/run/supervisord.pid)` |

---

## 6. Sentry Error Tracking & Performance Monitoring

Sentry is integrated in `backend/config/sentry.php` and bootstrapped in `backend/bootstrap/app.php`:
- **Exception Capture**: Unhandled exceptions in web requests and queue workers automatically report to Sentry.
- **Performance Tracing**: Configured with `traces_sample_rate=0.1` (10% of transactions traced in production).
- **Profiling**: `profiles_sample_rate=0.05` (5% profile capture).
- **Sensitive PII Scrubbing**: Passwords, OTP codes, credit card tokens, and authorization headers are sanitized before transmission.
- **Ignored Exceptions**: 404 NotFoundHttpException, ValidationException, and AuthenticationException are filtered to avoid noise.

---

## 7. Health Probe Endpoints

Two health endpoints are exposed for load balancers and container monitoring:

- **Endpoint**: `/healthz` (Standard orchestrator probe)
- **Endpoint**: `/api/health` (API health probe)

### Health Check Verifications
Each probe executes real-time assertions on all critical subsystems:
1. **Database**: Executes `SELECT 1` via PDO and measures query round-trip latency in milliseconds.
2. **Cache / Redis**: Performs an atomic cache write (`health_probe_<timestamp>`), read verification, and immediate cleanup.
3. **Storage**: Verifies write and read capability on the application storage disk.
4. **Memory**: Measures PHP memory consumption against configured `memory_limit`.

**HTTP Status Codes**:
- `200 OK`: All subsystems healthy.
- `503 Service Unavailable`: Any subsystem failed or degraded (includes failure details in JSON payload).

**Automated CLI Probe**:
```bash
./infra/scripts/health-check.sh http://127.0.0.1:8000/healthz 5
```

---

## 8. Database Backup & Disaster Recovery

### Automated Backup (`infra/scripts/backup.sh`)
- Executed nightly at 02:00 UTC via cron.
- Runs `mysqldump` with `--single-transaction --quick --routines --triggers --events --hex-blob --no-autocommit` to guarantee zero lock contention and complete consistency.
- Compresses output via `gzip -9` -> `webintoapp_${DB_DATABASE}_YYYYMMDD_HHMMSS.sql.gz`.
- Computes SHA-256 integrity checksum (`.sha256`).
- Verifies archive integrity using `gunzip -t`.
- Synchronizes backup and checksum to encrypted AWS S3 (`--sse aws:kms`) if `BACKUP_S3_BUCKET` is configured.
- Automatically purges local backups older than 14 days.

### Safe Database Restore (`infra/scripts/restore.sh`)
```bash
./infra/scripts/restore.sh /backups/webintoapp_production_20261014_020000.sql.gz
```
1. Verifies SHA-256 checksum against `.sha256` manifest.
2. Prompts for mandatory confirmation: user must type `RESTORE_<DB_NAME>`.
3. Puts application into maintenance mode (`php artisan down`).
4. Creates an instant safety snapshot (`pre_restore_snapshot_<timestamp>.sql.gz`) before altering any data.
5. Decompresses and imports the backup.
6. Runs pending migrations (`php artisan migrate --force`).
7. Warms configuration and route caches.
8. Brings application back online (`php artisan up`).

---

## 9. Isolated Build Worker Architecture

The Android Flutter build pipeline runs in a dedicated sandboxed container (`infra/docker/Dockerfile.build-worker`):
- **Container Isolation**:
  - `cap_drop: [ALL]` drops all Linux kernel capabilities.
  - `security_opt: [no-new-privileges:true]`.
  - Runs as unprivileged user `builder` (UID 1001).
- **Network Isolation**: Connected to `build_net` only; has no route to MySQL or Redis.
- **Input Validation**: `BuildWorker.php` enforces strict whitelist validation on package names, app names, icons, and configuration values.
- **Execution Script**: `infra/scripts/run-build.sh` runs `flutter pub get` and `flutter build appbundle --release`, generates SHA-256 checksums, and copies release artifacts (`.aab`) to `/builds/artifacts`.
- **Pre-signed Download URLs**: Users download builds via temporary signed URLs (`KeystoreService` / storage disk), never direct filesystem paths.

---

## 10. Linux Host & Firewall/SSH Hardening Checklist

Apply these steps on the host production server before opening ports to traffic.

### A. Kernel Sysctl Hardening (`/etc/sysctl.d/99-security.conf`)
```ini
# Disable IP packet forwarding (unless routing container bridge)
net.ipv4.ip_forward = 1

# Prevent SYN flood attacks
net.ipv4.tcp_syncookies = 1
net.ipv4.tcp_max_syn_backlog = 4096

# Ignore ICMP echo broadcasts
net.ipv4.icmp_echo_ignore_broadcasts = 1

# Disable ICMP redirect acceptance
net.ipv4.conf.all.accept_redirects = 0
net.ipv4.conf.default.accept_redirects = 0

# Enable reverse path filtering (spoofing protection)
net.ipv4.conf.all.rp_filter = 1
net.ipv4.conf.default.rp_filter = 1

# Log martian packets
net.ipv4.conf.all.log_martians = 1

# Virtual memory file descriptor limits
fs.file-max = 2097152
vm.max_map_count = 262144
```
Apply via: `sudo sysctl -p /etc/sysctl.d/99-security.conf`

### B. SSH Daemon Hardening (`/etc/ssh/sshd_config.d/hardening.conf`)
```text
# Move off default port
Port 2222

# Authentication
PermitRootLogin no
PasswordAuthentication no
ChallengeResponseAuthentication no
PubkeyAuthentication yes
MaxAuthTries 3
LoginGraceTime 30

# Network & Sessions
X11Forwarding no
AllowTcpForwarding no
ClientAliveInterval 300
ClientAliveCountMax 2

# Allowed Users
AllowUsers deployer adminops
```
Restart SSH: `sudo sshd -t && sudo systemctl restart ssh`

### C. UFW Firewall Configuration
```bash
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow 2222/tcp comment 'Hardened SSH'
sudo ufw allow 80/tcp comment 'HTTP ACME Challenge'
sudo ufw allow 443/tcp comment 'HTTPS Traffic'
sudo ufw enable
```

### D. Fail2ban Protection (`/etc/fail2ban/jail.d/custom.conf`)
```ini
[sshd]
enabled = true
port = 2222
maxretry = 3
bantime = 86400
findtime = 600

[nginx-req-limit]
enabled = true
filter = nginx-limit-req
logpath = /var/log/nginx/error.log
maxretry = 10
bantime = 3600
findtime = 60
```

---

## 11. Zero-Downtime Deployment Steps

Deployments are executed using the automated atomic deployment script:
```bash
./infra/scripts/deploy.sh
```

### Deployment Pipeline Stages:
1. **Clone Release**: Clones target Git commit/branch into `/var/www/webintoapp/releases/<TIMESTAMP>`.
2. **Link Shared Assets**:
   - Symlinks `/var/www/webintoapp/shared/.env` -> `backend/.env`.
   - Symlinks `/var/www/webintoapp/shared/storage` -> `backend/storage`.
3. **Build Dependencies**: Runs `composer install --no-dev --optimize-autoloader`.
4. **Database Migration**: Executes `php artisan migrate --force`.
5. **Warm Production Caches**:
   - `php artisan config:cache`
   - `php artisan route:cache`
   - `php artisan view:cache`
   - `php artisan event:cache`
6. **Atomic Symlink Flip**: Atomically switches `/var/www/webintoapp/current` to point to the new release directory using `mv -Tf`.
7. **Service Reload**:
   - Sends `kill -USR2` to PHP-FPM (reloads OPcache without closing active TCP sockets).
   - Signals Supervisor workers via `php artisan queue:restart`.
   - Reloads Nginx configuration via `nginx -s reload`.
8. **Health Check Probe**: Probes `http://127.0.0.1:8000/healthz`.
   - If HTTP 200: Retains latest 5 releases and deletes older releases.
   - If probe fails: **Automatically executes `rollback.sh` immediately!**

---

## 12. Rollback Plan

If a deployment fails or regression is discovered in production, execute instant rollback:

```bash
# Instant code and OPcache rollback to previous release
./infra/scripts/rollback.sh

# Instant rollback with database migration reversal
./infra/scripts/rollback.sh --with-db-rollback
```

### Rollback Execution Details:
1. Identifies the previous active release timestamp in `releases/`.
2. Atomically flips `/var/www/webintoapp/current` back to the previous release.
3. If `--with-db-rollback` is specified, rolls back the latest migration batch (`php artisan migrate:rollback --step=1 --force`).
4. Rewarms config, route, and view caches on the rolled-back code.
5. Signals PHP-FPM (`USR2`) and restarts queue workers (`queue:restart`).
6. Reloads Nginx and runs the health check to confirm recovery.

---

## 13. Docker Production Stack Commands

### Start Production Stack
```bash
cd /var/www/webintoapp/infra
docker compose -f docker-compose.prod.yml up -d --build
```

### View Service Health & Status
```bash
docker compose -f docker-compose.prod.yml ps
docker compose -f docker-compose.prod.yml logs -f --tail=100 backend
```

### Run Artisan Commands in Production
```bash
docker compose -f docker-compose.prod.yml exec backend php artisan [command]
```

### Trigger Manual Backup
```bash
docker compose -f docker-compose.prod.yml exec mysql /backups/backup.sh
```
