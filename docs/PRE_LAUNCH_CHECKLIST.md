# WebIntoApp Pre-Launch Production Checklist

This checklist must be signed off by Engineering and DevOps before routing live customer traffic to the production cluster.

---

## 1. Environment & Configuration Hardening

- [ ] **`APP_DEBUG=false`**: Verified in `backend/.env` and verified that debug bar / verbose stack traces are disabled.
- [ ] **`APP_ENV=production`**: Set in environment.
- [ ] **Cryptographic Key**: `APP_KEY` generated using `php artisan key:generate` and securely stored.
- [ ] **Production Secrets**:
  - [ ] Database credentials (`DB_PASSWORD`) are random, 32+ characters, and distinct from staging/development.
  - [ ] Redis password (`REDIS_PASSWORD`) is strong and required (`--requirepass`).
  - [ ] Razorpay Live Keys (`RAZORPAY_KEY_ID`, `RAZORPAY_KEY_SECRET`, `RAZORPAY_WEBHOOK_SECRET`) configured and verified against Razorpay Dashboard.
  - [ ] WhatsApp Cloud API system user token and phone number ID validated.
  - [ ] Firebase Service Account private key (`google-services.json` / service account JSON) stored in encrypted, restricted-permission path.
- [ ] **Caches Pre-Compiled**:
  - [ ] `php artisan config:cache`
  - [ ] `php artisan route:cache`
  - [ ] `php artisan view:cache`
  - [ ] `php artisan event:cache`
- [ ] **Next.js Dashboard**: Standalone build generated with `NEXT_PUBLIC_API_URL` pointing to HTTPS production API endpoint.

---

## 2. Infrastructure, Network & Firewall

- [ ] **TLS / SSL Certificates**:
  - [ ] Valid SSL certificate installed for `app.webintoapp.com`, `api.webintoapp.com`, `admin.webintoapp.com`.
  - [ ] Automated renewal configured via Certbot timer or Let's Encrypt container.
  - [ ] TLS 1.0 and 1.1 disabled; only TLS 1.2 and TLS 1.3 permitted.
  - [ ] SSL Labs score tested and verifies A+ rating.
- [ ] **Security Headers Verified** (Tested via `curl -I https://api.webintoapp.com/healthz`):
  - [ ] `Strict-Transport-Security: max-age=63072000; includeSubDomains; preload`
  - [ ] `X-Content-Type-Options: nosniff`
  - [ ] `X-Frame-Options: SAMEORIGIN`
  - [ ] `Referrer-Policy: strict-origin-when-cross-origin`
  - [ ] `Permissions-Policy` restricts unused browser features.
  - [ ] `Content-Security-Policy` allows Razorpay, Google Fonts, and Sentry while blocking unauthorized origins.
- [ ] **Firewall (UFW)**:
  - [ ] Inbound traffic blocked by default (`ufw default deny incoming`).
  - [ ] Only ports 80, 443, and hardened SSH (port 2222) open.
  - [ ] MySQL (port 3306) and Redis (port 6379) are strictly internal Docker networks and NOT exposed to the public Internet.
- [ ] **SSH Hardened**:
  - [ ] Password authentication disabled (`PasswordAuthentication no`).
  - [ ] Root login disabled (`PermitRootLogin no`).
  - [ ] Key-based authentication enforced (`PubkeyAuthentication yes`).
- [ ] **Fail2ban Active**:
  - [ ] SSH jail active.
  - [ ] Nginx rate limit violation jail active.

---

## 3. Database & Disaster Recovery

- [ ] **Database Migrations**: Ran `php artisan migrate --force` and schema is up-to-date with 0 pending migrations.
- [ ] **Automated Backups Verified**:
  - [ ] Nightly cron job installed and tested (`infra/scripts/backup.sh`).
  - [ ] Test backup created successfully with SHA-256 checksum.
  - [ ] Offsite S3 upload verified (`aws s3 ls s3://webintoapp-production-backups/backups/`).
  - [ ] 14-day retention pruning tested.
- [ ] **Disaster Recovery Restore Dry-Run**:
  - [ ] Successfully ran `./infra/scripts/restore.sh` on a staging copy.
  - [ ] Pre-restore snapshot verified.
  - [ ] Data integrity and row counts verified post-restore.

---

## 4. Background Workers & Queue Management

- [ ] **Supervisor Running**:
  - [ ] `supervisord` service active and enabled on boot.
  - [ ] Workers running for `default`, `high`, `notifications`, and `builds` queues.
  - [ ] `laravel-scheduler` active and executing `php artisan schedule:run` every minute.
- [ ] **Queue Failure Handling**:
  - [ ] `failed_jobs` table migrated and accessible.
  - [ ] Sentry alerts configured for unhandled job failures.
  - [ ] Retry backoffs configured (`--tries=3 --timeout=90`).

---

## 5. Sandboxed Android Build Worker

- [ ] **Container Isolation**:
  - [ ] Build worker runs in isolated container with dropped capabilities (`cap_drop: [ALL]`).
  - [ ] Unprivileged user `builder` (UID 1001) verified.
  - [ ] No network route exists between build worker container and MySQL database.
- [ ] **SDK Readiness**:
  - [ ] Flutter SDK stable (3.24.x) pre-cached.
  - [ ] Android SDK 34 and Build-Tools 34.0.0 installed.
  - [ ] Java 17 headless runtime verified.
- [ ] **Keystore Encryption**:
  - [ ] Tenant app keystores encrypted with `AES-256-GCM` before database storage.
  - [ ] Master keystore encryption key configured in secure environment.
- [ ] **Pre-signed Downloads**:
  - [ ] Download endpoint returns temporary expiring signed URLs (15-minute expiry).
  - [ ] Tenant authorization strictly enforced before URL generation.

---

## 6. Sentry & Observability

- [ ] **Error Reporting**:
  - [ ] Sentry DSN verified.
  - [ ] Triggered test exception and verified appearance in Sentry dashboard.
  - [ ] PII scrubbing confirmed: passwords, OTPs, and auth tokens are stripped.
- [ ] **Health Probes Active**:
  - [ ] `/healthz` responds with HTTP 200 and subsystem latencies < 50ms.
  - [ ] Load balancer / uptime monitoring configured (e.g. UptimeRobot, Datadog, or Cloudflare Health Checks) targeting `/healthz` at 1-minute intervals.
- [ ] **Log Rotation**:
  - [ ] Logrotate configs installed in `/etc/logrotate.d/`.
  - [ ] Tested with `logrotate -d /etc/logrotate.d/laravel`.

---

## 7. Security & Abuse Prevention

- [ ] **Rate Limiting**:
  - [ ] OTP send endpoint (`/api/v1/auth/send-otp`) rate limited to 10 req/min per IP and phone number.
  - [ ] OTP verify endpoint rate limited to 5 failed attempts before temporary lockout.
  - [ ] General API rate limiting active in Nginx (`api_zone`).
- [ ] **Tenant Isolation Audited**:
  - [ ] `TenantContext` middleware enforced on all tenant routes.
  - [ ] Cross-tenant data access blocked (verified via automated security test suite).
  - [ ] Store customer ID scoping enforced on all `/v1/orders` and checkout endpoints.
- [ ] **Webhook Idempotency**:
  - [ ] Razorpay webhook handler checks `webhook_events` table for duplicate `event_id`.
  - [ ] Signature verification enforced with `RAZORPAY_WEBHOOK_SECRET`.

---

## 8. Deployment & Rollback Drill

- [ ] **Zero-Downtime Deployment Tested**:
  - [ ] Ran `./infra/scripts/deploy.sh` in pre-production environment.
  - [ ] Zero dropped requests verified during release switch.
  - [ ] Caches cleanly warmed.
- [ ] **Instant Rollback Tested**:
  - [ ] Ran `./infra/scripts/rollback.sh`.
  - [ ] Verified instant reversion to previous release within < 3 seconds.
  - [ ] Health check returned HTTP 200 post-rollback.

---

### Sign-off Matrix

| Role | Name | Signature / Approval | Date |
|---|---|---|---|
| **Lead Backend Engineer** | | | |
| **DevOps / SRE Lead** | | | |
| **Security Officer** | | | |
