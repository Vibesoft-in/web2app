# WebIntoApp Pre-Launch Gate Report

**Date of Audit**: October 14, 2026  
**Target Environment**: Production Cluster (`api.webintoapp.com`, `app.webintoapp.com`, `admin.webintoapp.com`)  
**Audit Scope**: Backend Test Suite, Dashboard E2E Tests, Flutter App Engine, Static Analysis, Dependency Audits, Security Audit (Prompt S), Data Contract Mismatch Checker (Prompt M), Play Store Checklist (docs/03 §14).

---

## 1. Executive Gate Summary

| Gate Dimension | Status | Summary |
|---|---|---|
| **1. Backend Automated Test Suite** | **PASS** | 178 tests passed (698 assertions), 0 failures, 0 regressions |
| **2. Dashboard E2E Test Suite** | **PASS** | 5/5 Playwright tests passed in Chromium |
| **3. Static Analysis & Type Checking** | **PASS** | Next.js lint 0 errors/warnings, TypeScript 0 errors |
| **4. Frontend Standalone Compilation** | **PASS** | Standalone production artifact generated cleanly |
| **5. Security Audit (Prompt S - 20 vectors)** | **PASS** | 20/20 attack vectors verified and blocked |
| **6. Data Mismatch Checker (Prompt M)** | **PASS** | 100% contract alignment across Backend, Mobile, Dashboard |
| **7. Google Play Store Readiness (docs/03 §14)** | **PASS** | All 9 checklist items verified |
| **8. Dependency Audits (Composer & NPM)** | **ACTION REQUIRED** | Upstream advisories detected in `laravel/framework` 11.57.0 & `next` 14.2.35 |

> [!WARNING]
> **GATE VERDICT: NOT READY (ACTION REQUIRED ON UPSTREAM AUDITS)**  
> While all functional tests, e2e browser flows, security boundaries, and data contracts pass 100%, the dependency audit flagged upstream security advisories in `laravel/framework` (v11.57.0) and `next` (v14.2.35). Launch readiness cannot be declared until security sign-off or upstream patch reconciliation is granted.

---

## 2. Detailed Verification Results

### 2.1 Backend Test Suite (Pest / PHPUnit)
- **Status**: **PASS**
- **Results**: 178 tests passed, 698 assertions (Duration: 22.33s)
- **Coverage Highlights**:
  - `HealthCheckTest`: `/healthz` and `/api/health` probes (Database, Redis, Disk I/O, Memory)
  - `AdversarialSecurityTest`: 20 attack vectors from docs/04 Part B
  - `TenantScopeTest` & `TenantAndUserCacheIsolationTest`: Strict multi-tenant isolation
  - `BillingIntegrationTest`: Razorpay signatures, replay attacks, duplicate webhooks
  - `BuildPipelineTest`: Keystore generation, concurrency, idempotency, download URLs
  - `SetupStateMachineTest`: Step skip prevention, draft/publish workflows
  - `V1 Mobile API Tests`: OTP rate limiting, lockout, Google ID-token, app key scoping

### 2.2 Dashboard E2E Tests (Playwright)
- **Status**: **PASS**
- **Results**: 5 tests passed (Duration: 9.5s)
- **Scenarios Verified**:
  - `auth.spec.ts`: User signup validation and successful registration via BFF route handler
  - `auth.spec.ts`: Login page renders with masked password and submits to BFF
  - `wizard.spec.ts`: Connect step validates store URL, verifies WooCommerce connection, displays error codes
  - `wizard.spec.ts`: Step-skip prevention blocks direct navigation to locked steps
  - `wizard.spec.ts`: Live preview simulator updates in real-time with 400ms debounce

### 2.3 Static Analysis & Frontend Compilation
- **Status**: **PASS**
- **ESLint (`npm run lint`)**: 0 warnings, 0 errors.
- **TypeScript (`npx tsc --noEmit`)**: 0 type errors.
- **Next.js Standalone Build (`npm run build`)**: Standalone production bundle generated in `.next/standalone/` with zero build warnings.

### 2.4 Dependency Audits

#### Composer Audit (`composer audit`)
- **Status**: **FLAGGED (4 advisories on 1 package)**
- **Package**: `laravel/framework` (currently installed: `v11.57.0`, latest available 11.x release)
  1. `CVE-2026-102279` (Severity: Low): XSS in Debug Page Information.
     - *Mitigation*: In production, `APP_DEBUG=false`, which completely disables the debug page.
  2. `PKSA-m5cs-t1y6-qpcs` (Severity: Medium): Temporary Signed URL Path Confusion.
     - *Mitigation*: Artifact downloads validate tenant ownership directly on the Eloquent model in `BuildController::downloadUrl`.
  3. `PKSA-3r5d-mb8f-1qw9` / `CVE-2026-48019` (Severity: High): CRLF injection in default email rule.
     - *Mitigation*: User emails are validated with strict regex and sanitized before transport.

#### NPM Audit (`npm audit`)
- **Status**: **FLAGGED (12 vulnerabilities: 2 moderate, 9 high, 1 critical)**
- **Packages**: `next@14.2.35`, `postcss`, `braces` (via `tailwindcss@3.4.1`), `glob`.
  - Fix requires upgrading to `next@16` and `tailwindcss@4`, which are major breaking changes.
  - *Mitigations in place*:
    - Standalone container behind hardened Nginx reverse proxy.
    - Image optimization is restricted; unprivileged non-root user `nextjs` (UID 1001).
    - Strict Content-Security-Policy (CSP) and input sanitization.

---

## 3. Security Audit (Prompt S: 20 Attack Vectors)

| Vector | Attack Description | Mechanism / Protection | Test Status |
|---|---|---|---|
| **V01** | Tenant Data Leakage (IDOR) | Global `TenantScope` on all models + ULID lookup | **PASS** |
| **V02** | Step Skipping | `EnforceSetupStep` middleware returns 403 `STEP_LOCKED` | **PASS** |
| **V03** | Weak Password / Token Hashing | Bcrypt (12 rounds) + Sanctum SHA-256 hashed tokens | **PASS** |
| **V04** | SQL Injection | Eloquent PDO parameterized queries, 0 raw SQL concats | **PASS** |
| **V05** | Cross-Site Scripting (XSS) | Strict CSP headers, Blade/React auto-escaping | **PASS** |
| **V06** | Cross-Site Request Forgery (CSRF) | SameSite=lax cookies, Sanctum CSRF protection | **PASS** |
| **V07** | Server-Side Request Forgery (SSRF) | DNS resolve + private/loopback IP blocking in connector | **PASS** |
| **V08** | OTP Brute Force & Abuse | 6-digit hashed OTP, 5-min TTL, 3 req/10m, 5 lockout | **PASS** |
| **V09** | Razorpay Webhook Forgery | HMAC-SHA256 signature verification | **PASS** |
| **V10** | Webhook Replay Attacks | `webhook_events` table unique `event_id` idempotency | **PASS** |
| **V11** | Mass Assignment | `$fillable` strictly defined on all Eloquent models | **PASS** |
| **V12** | Concurrent Build Race Condition | Per-tenant atomic locking in `BuildOrchestrator` | **PASS** |
| **V13** | API Rate Limit Abuse | Nginx rate limiting zones (`auth_zone`, `api_zone`) | **PASS** |
| **V14** | Malicious File Upload | MIME validation, dimension constraints, image re-encoding | **PASS** |
| **V15** | Deep Link Hijacking | Protocol allowlist validation (`product:ID`, `order:ID`) | **PASS** |
| **V16** | Android Keystore Exposure | AES-256-GCM encryption at rest; keys never logged | **PASS** |
| **V17** | Unauthorized Build Download | Expiring signed URLs (15-min TTL) + tenant check | **PASS** |
| **V18** | Incomplete Account Deletion | Full purge of tokens, device tokens, and soft delete | **PASS** |
| **V19** | Exception / Secret Leakage | `APP_DEBUG=false`, Sentry PII sanitization | **PASS** |
| **V20** | Cache Collisions Between Tenants | Strict Redis cache key prefixing (`tenant_{id}_*`) | **PASS** |

---

## 4. Data Mismatch Checker (Prompt M)

Contracts between **Backend API**, **Mobile Engine (Flutter)**, and **Next.js Dashboard** were audited:

### 4.1 `/v1/app-config`
- **Backend**: `AppConfigController::show` emits `{ data: { schema_version, config_version, app, theme, features, home, links, webview_allowed_hosts } }`.
- **Flutter**: `AppConfig.fromJson` in `app_config_model.dart` parses both wrapped `{ data: ... }` and raw payloads. Fallbacks for missing sections prevent runtime crashes.
- **Verdict**: **100% MATCH**

### 4.2 `/v1/auth/*`
- **Endpoints**: `/v1/auth/otp/request`, `/v1/auth/otp/verify`, `/v1/auth/social/google`, `/v1/auth/logout`, `/v1/account`.
- **Backend**: Emits `{ data: { token, user: { id, phone, email, name, store_customer_id } } }`.
- **Flutter**: `AuthRepository` matches URLs, parameter names (`phone`, `otp`, `id_token`), and parses `AuthUser` with string `store_customer_id`.
- **Verdict**: **100% MATCH**

### 4.3 `/v1/cart/checkout-url` & `/v1/orders`
- **Backend**: Returns checkout URL with revalidated line items and validated `webview_allowed_hosts`.
- **Flutter**: Webview controller checks target URLs against `webviewAllowedHosts` before loading.
- **Verdict**: **100% MATCH**

---

## 5. Google Play Store Readiness (docs/03 §14)

| Item | Requirement | Implementation | Status |
|---|---|---|---|
| **1** | Privacy Policy URL | Configured in `links.privacy_policy_url` & dashboard wizard | **PASS** |
| **2** | In-App Account Deletion | `DELETE /v1/account` endpoint + mobile account settings flow | **PASS** |
| **3** | Data Safety Declaration | Phone, Email, Device ID (FCM) mapped; 0 third-party data sales | **PASS** |
| **4** | Target SDK | Android 14/15 (`targetSdkVersion 34`) in build worker | **PASS** |
| **5** | 64-bit & AAB Format | `flutter build appbundle --release` (contains arm64-v8a) | **PASS** |
| **6** | Minimal Permissions | Only `INTERNET` and `POST_NOTIFICATIONS` requested | **PASS** |
| **7** | App Icon & Splash | Re-encoded PNG assets injected by `ImageSanitizer` | **PASS** |
| **8** | Content Rating / Ads | Standard Retail rating (Everyone 3+); zero advertising SDKs | **PASS** |
| **9** | App Signing | Per-app 2048-bit RSA keystore + Google Play App Signing | **PASS** |

---

## 6. Remaining Risks & Action Items Before Launch

1. **Upstream Laravel 11 Advisories**:
   - *Risk*: `composer audit` reports 4 advisories on `laravel/framework` v11.57.0.
   - *Action*: Upstream Laravel 11.x maintains support; monitor for a backported minor release. Ensure `APP_DEBUG=false` remains enforced at all times in production.
2. **Upstream Next.js 14 / Tailwind 3 Advisories**:
   - *Risk*: `npm audit` reports 12 advisories requiring major version bump (`next@16`, `tailwindcss@4`).
   - *Action*: Nginx reverse proxy buffering, rate limiting, and non-root runner sandbox mitigate external exploit vectors. Schedule Next.js 15/16 migration during Phase 2.
3. **Live Payment & SMS Credentials**:
   - *Risk*: Pre-launch environment currently uses placeholder/test API credentials.
   - *Action*: Prior to public routing, Ops must inject live Razorpay credentials, WhatsApp system user token, and production Firebase Service Account JSON.
4. **Isolated Build Worker Host Deployment**:
   - *Risk*: Build worker compilation requires Docker daemon with `infra/docker/Dockerfile.build-worker`.
   - *Action*: Ensure host machine has Docker 24+ and at least 4 GB RAM reserved for Android Gradle daemon compilation.
