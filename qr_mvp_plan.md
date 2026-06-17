# QR Generator SaaS — Phase 1 (MVP) Detailed Build Plan

Version: 1.0
Based on: qr_business.md V1.2
Stack: Laravel 12 + Inertia.js + React + TypeScript + Tailwind + shadcn/ui + PostgreSQL + Redis

---

# 0. MVP Scope Summary

In scope:

* Auth (register, login, forgot password, email verification)
* User dashboard
* 7 QR types (URL, WhatsApp, Email, Phone, WiFi, vCard, Text)
* QR management (create, edit, delete, download, pause/active)
* Static + Dynamic QR
* Redirect service (/q/{slug}) with Redis cache + async scan logging
* Basic analytics (total scans, daily scans, timeline)
* Billing (Free, Pro Monthly, Pro Yearly via Razorpay Subscriptions)
* Admin panel (Custom React at `/admin/*`)
* Abuse prevention basics
* Transactional emails

NOT in scope (Phase 2+):

* Country/city/device analytics UI (data is collected, UI later)
* Custom logo / custom colors UI
* Custom domains, lead capture, QR health monitor
* API, bulk QR, teams, white label, webhooks

---

# 1. Project Setup

## 1.1 Scaffold

* [x] Laravel 12 new project (official React starter kit, in repo root)
* [x] Install Inertia.js (server + client adapter)
* [x] Install React + TypeScript + Vite
* [x] Install Tailwind CSS
* [x] Install shadcn/ui (init + base components: button, input, card, dialog, dropdown, table, tabs, badge, toast)
* [x] Custom React admin panel at `/admin/*` (replaced Filament, June 2026) — Week 5 ✅
* [ ] Install Laravel Horizon (queue monitoring) OR simple queue:work
* [ ] Install packages:
  * [x] endroid/qr-code v6 (QR generation — PNG + SVG)
  * [x] predis/predis (Redis client)
  * [x] qrcode.react (frontend live preview)
  * [x] razorpay/razorpay (official SDK) — Week 4
  * [x] jenssegers/agent (device/browser parsing) — Week 3
  * [x] GeoIP: MaxMind GeoLite2 (free DB) — Week 3 (graceful skip if DB missing)
* [x] Configure PostgreSQL connection (Docker container, port 5434 — native PG password mismatch)
* [x] Configure Redis (cache + queue + session) (Docker container, port 6379)
* [ ] Configure mail (SMTP — start with Brevo/Mailgun free tier) — currently log driver
* [x] Docker setup (dev): docker-compose.dev.yml (postgres + redis) — production compose pending
* [ ] .env.example with all required keys documented

## 1.2 Code Conventions

* [x] Form Requests for all validation (no inline validation in controllers)
* [x] Service classes for business logic (app/Services)
* [x] Enums: QrType, QrStatus, SubscriptionStatus, PaymentStatus, UserRole, UserStatus, BillingCycle
* [x] Policies for authorization (QrCodePolicy)
* [ ] All user-facing strings ready for future i18n (not blocking)

---

# 2. Database Migrations

Status: ALL DONE (Week 1) — 11 tables migrated + plans/admin seeders

Order of migrations:

## 2.1 users (modify default)

* id (bigint)
* name (string)
* email (string, unique)
* password
* role (enum: user / admin / super_admin, default user)
* status (enum: active / banned, default active)
* country (string, nullable)
* last_login_at (timestamp, nullable)
* email_verified_at
* remember_token
* timestamps
* deleted_at (soft delete)

## 2.2 plans

* id
* name (string)
* slug (string, unique — free / pro_monthly / pro_yearly)
* price (decimal 10,2)
* currency (string, default INR)
* billing_cycle (enum: free / monthly / yearly)
* razorpay_plan_id (string, nullable)
* limits (jsonb)
* is_active (boolean, default true)
* sort_order (int)
* timestamps

Seeder — 3 plans:

Free limits:
{ "dynamic_qr": 2, "static_qr": -1, "scans_per_month": 100,
  "analytics_history_days": 30, "custom_logo": false,
  "custom_colors": false, "svg_download": false, "ads": true }

Pro (monthly & yearly) limits:
{ "dynamic_qr": -1, "static_qr": -1, "scans_per_month": -1,
  "analytics_history_days": -1, "custom_logo": true,
  "custom_colors": true, "svg_download": true, "ads": false }

## 2.3 subscriptions

* id
* user_id (FK)
* plan_id (FK)
* gateway (string, default razorpay)
* gateway_subscription_id (string, nullable, indexed)
* starts_at (timestamp)
* expires_at (timestamp, nullable)
* cancelled_at (timestamp, nullable)
* status (enum: active / grace / expired / cancelled / frozen)
* timestamps

## 2.4 payments

* id
* user_id (FK)
* subscription_id (FK, nullable)
* gateway (string)
* gateway_payment_id (string, indexed)
* gateway_order_id (string, nullable)
* invoice_number (string, unique, nullable)
* amount (decimal 10,2)
* currency (string)
* status (enum: created / paid / failed / refunded)
* meta (jsonb — raw gateway response)
* paid_at (timestamp, nullable)
* timestamps

## 2.5 qr_codes

* id
* user_id (FK, indexed)
* name (string)
* slug (string 8-10 chars, unique, indexed — random alphanumeric, no DB id exposure)
* type (enum: url / whatsapp / email / phone / wifi / vcard / text)
* content (jsonb — type-specific data)
* destination_url (text, nullable — final URL for dynamic redirect)
* is_dynamic (boolean)
* status (enum: active / paused, default active)
* design_options (jsonb, nullable — reserved for Phase 2)
* scan_count (bigint, default 0)
* last_scanned_at (timestamp, nullable)
* expires_at (timestamp, nullable)
* timestamps
* deleted_at (soft delete)

## 2.6 qr_scan_events

* id (bigint)
* qr_code_id (FK, indexed)
* country (string, nullable)
* city (string, nullable)
* device_type (string, nullable — mobile/tablet/desktop)
* os (string, nullable)
* browser (string, nullable)
* referrer (text, nullable)
* ip_hash (string — sha256(ip + app salt), never raw IP)
* scanned_at (timestamp, indexed)

Composite index: (qr_code_id, scanned_at)

## 2.7 qr_daily_stats

* id
* qr_code_id (FK)
* date (date)
* scans (int)
* top_country (string, nullable)
* top_device (string, nullable)
* unique: (qr_code_id, date)

## 2.8 settings

* key (string, primary)
* value (text)

## 2.9 audit_logs

* id
* user_id (FK, nullable)
* action (string)
* entity_type (string)
* entity_id (bigint)
* meta (jsonb, nullable)
* created_at

## 2.10 blocked_domains (abuse)

* id
* domain (string, unique)
* reason (string, nullable)
* timestamps

## 2.11 qr_reports (abuse)

* id
* qr_code_id (FK)
* reason (string)
* reporter_ip_hash (string)
* status (enum: pending / reviewed / actioned)
* timestamps

---

# 3. Core Services

Status: ALL DONE (3.1–3.5 Week 2 · 3.6 Week 4)

## 3.1 PlanLimitService ✅

Single source of truth for all limit checks.

Methods:

* limits(User $user): array — merge user's active plan limits
* canCreateDynamicQr(User $user): bool
* canCreateStaticQr(User $user): bool
* hasScanQuota(User $user): bool (Free: scans this month < limit)
* analyticsHistoryDays(User $user): int
* can(User $user, string $feature): bool (svg_download, custom_logo...)

Rules:

* -1 means unlimited
* User without subscription = Free plan limits
* Frozen subscription = Free plan limits, but frozen QRs not editable

## 3.2 QrContentBuilder ✅

Converts type + content JSON → final QR payload string.

* url → the URL itself
* whatsapp → https://wa.me/{phone}?text={message}
* email → mailto:{to}?subject={s}&body={b}
* phone → tel:{number}
* wifi → WIFI:T:{WPA|WEP|nopass};S:{ssid};P:{password};H:{hidden};;
* vcard → BEGIN:VCARD ... END:VCARD (v3.0)
* text → raw text

## 3.3 QrImageService ✅

* render(string $payload, string $format, int $size): binary
* Formats: png (all), svg (Pro only — checked via PlanLimitService)
* Sizes: 256 / 512 / 1024 px
* Never stored on disk — generated on demand, streamed as download
* For dynamic QR: payload = redirect URL (https://domain/q/{slug})
* For static QR: payload = actual content (QR works forever, no tracking)

## 3.4 SlugGenerator ✅

* 8-char random alphanumeric (A-Z, a-z, 0-9, exclude confusing: 0/O, 1/l/I)
* Retry on collision (unique index guarantees)

## 3.5 UrlSafetyService (abuse) ✅

* isBlocked(string $url): bool — check blocked_domains table
* isShortener(string $url): bool — static list (bit.ly, tinyurl, t.co...)
* checkSafeBrowsing(string $url): bool — Google Safe Browsing API v4
* Called on QR create + edit (only for url/whatsapp types with links)
* Safe Browsing call is async-tolerant: if API down, allow + flag for review

## 3.6 SubscriptionService ✅

* activate(User, Plan, gatewayData)
* cancel(Subscription)
* markGrace(Subscription) / markExpired(Subscription) / freeze(Subscription)
* currentPlan(User): Plan

---

# 4. Module-by-Module Feature Breakdown

---

## MODULE A — Authentication

Pages (Inertia):

* /register
* /login
* /forgot-password
* /reset-password/{token}
* /verify-email (notice page)

### A1. Register

* [x] Fields: name, email, password, password confirmation (starter kit)
* [x] Validation: email valid + unique; password rules (starter kit defaults)
* [x] On success: create user, send verification email, login, redirect to /dashboard
* [ ] Rate limit: 5 attempts/min per IP
* [ ] Audit log: user.registered

### A2. Login

* [x] Fields: email, password, remember me
* [ ] Banned user → error "Account suspended, contact support"
* [ ] Update last_login_at
* [x] Rate limit: per IP + email (Laravel throttle, starter kit)
* [ ] Redirect: user → /dashboard, admin → /admin (admin panel Week 5)

### A3. Email Verification

* [x] Signed verification link (starter kit)
* [x] Resend button (throttled)
* [x] Unverified users: can browse dashboard, CANNOT create dynamic QR
  (enforced in StoreQrCodeRequest — tested)
* [ ] Banner on dashboard: "Verify your email to unlock dynamic QR" — Week 3 (dashboard)

### A4. Forgot / Reset Password

* [x] Standard Laravel flow (starter kit)
* [x] Generic success message (no email enumeration)
* [x] On reset: session security handled by framework

### A5. Profile Settings (/settings)

* [x] Update name, email (starter kit)
* [x] Change password (current password required)
* [x] Delete account (password confirm, soft delete — test updated)

---

## MODULE B — User Dashboard ✅ (Week 3)

Page: /dashboard

* [x] Stat cards: Total QR Codes, Active QRs, Total Scans, Scans This Month
* [x] Scans This Month shows progress bar vs plan limit (Free only)
* [x] Recent QRs list (last 5, with mini scan count)
* [x] "Create QR" primary CTA
* [x] Plan widget: current plan name + Upgrade button (Free users)
* [x] Email verification banner (if unverified)
* [x] Empty state with "Create your first QR" CTA

---

## MODULE C — QR Creation (Wizard) ✅ (Week 2)

Page: /qr/create — 3-step wizard

### Step 1 — Choose Type

* [x] 7 type cards with short description
* [x] URL, WhatsApp, Email, Phone, WiFi, vCard, Text

### Step 2 — Enter Content (type-specific forms)

URL QR:
* [x] url (required, valid http/https URL, max 2048)

WhatsApp QR:
* [x] phone (required, validated format; country code picker UI — Phase 2 polish)
* [x] message (optional, max 500)

Email QR:
* [x] to (required, valid email)
* [x] subject (optional, max 200)
* [x] body (optional, max 1000)

Phone QR:
* [x] phone (required, validated format)

WiFi QR:
* [x] ssid (required, max 32)
* [x] security (WPA/WPA2 | WEP | None)
* [x] password (required unless None, max 63)
* [x] hidden network (checkbox)

vCard QR:
* [x] first_name (required), last_name
* [x] organization, job_title
* [x] phone, email, website
* [x] address (city, country in UI; full address fields validated server-side)

Text QR:
* [x] text (required, max 1000)

Common to all:
* [x] name (required, max 100 — internal label, e.g. "Shop Menu")
* [x] Live QR preview (client-side via qrcode.react)

### Step 3 — Static or Dynamic

* [x] Static: content baked into QR. Free forever. No edit, no tracking.
* [x] Dynamic: QR points to /q/{slug}. Editable + trackable.
* [x] Clear comparison UI of both options
* [x] Dynamic option disabled states:
  * [x] email unverified → "Verify email first"
  * [x] free limit reached (2) → "Upgrade to Pro" CTA
* [x] Dynamic only available for URL + WhatsApp (locked decision)
* [x] On submit:
  * [x] Validate via PlanLimitService
  * [x] UrlSafetyService check (url/whatsapp)
  * [x] Generate slug (dynamic only)
  * [x] Save → redirect to QR detail page
  * [x] Audit log: qr.created

Decision (locked): V1 dynamic QR supported for URL and WhatsApp types.
Other 5 types are static-only in V1 (technically they have no server-redirectable equivalent without a landing page — landing pages are Phase 2).

---

## MODULE D — QR Management ✅ (Week 2)

Page: /qr (list)

* [x] Cards: QR thumbnail, name, type badge, static/dynamic badge,
      status badge, scan count, created date
* [x] Search by name
* [x] Filter: type, status, static/dynamic
* [ ] Sort: newest, most scanned, name (currently newest only — minor)
* [x] Pagination (20/page)
* [x] Empty state with CTA

Page: /qr/{id} (detail)

* [x] Large QR preview
* [x] Download buttons: PNG (256/512/1024), SVG (Pro — locked icon for Free)
* [x] Copy short link button (dynamic: domain/q/slug)
* [x] Edit, Pause/Activate, Delete actions
* [x] Analytics section placeholder (full charts — Week 3)
* [x] Created date, type, content summary

### D1. Edit (/qr/{id}/edit) ✅

* [x] Dynamic QR: edit name + content/destination → QR image stays SAME
      (slug unchanged), Redis cache invalidated immediately (model events)
* [x] Static QR: edit name only (content locked — explainer shown)
* [x] UrlSafetyService re-check on URL change
* [x] Audit log: qr.updated

### D2. Pause / Activate ✅

* [x] Toggle on detail page
* [ ] Paused dynamic QR → branded "QR inactive" page — Week 3 (redirect service)
* [x] Paused static QR → only marks status
* [x] Redis cache invalidated on toggle (model events)

### D3. Delete ✅

* [x] Confirmation modal with dynamic/static specific warning
* [x] Soft delete (deleted_at)
* [ ] Deleted dynamic QR → "QR not found" branded page — Week 3
* [x] Slug NEVER reused (withTrashed collision check)
* [x] Audit log: qr.deleted

### D4. Download ✅

* [x] GET /qr/{id}/download?format=png&size=512
* [x] Authorization: owner only (QrCodePolicy)
* [x] SVG: PlanLimitService check (403 for Free)
* [x] Filename: {qr-name-slugified}.png
* [x] Generated on the fly, never stored

### D5. Authorization ✅

* [x] QrCodePolicy: view/update/delete → qr.user_id === auth user
* [x] All QR routes behind auth middleware

---

## MODULE E — Redirect Service (HOT PATH) ✅ (Week 3)

Route: GET /q/{slug} — NO auth, NO Inertia, raw fast controller

* [x] Redis lookup: key qr:{slug} → JSON {url, status, expires_at} (RedirectResolver)
* [x] Cache miss → DB query → write to Redis (no TTL; negative results cached 10 min)
* [x] status=active + not expired → 302 redirect
* [x] status=paused → branded "QR paused" page (HTTP 200, minimal blade)
* [x] not found / deleted → branded "QR not found" page (404)
* [x] expired (expires_at passed) → branded "QR expired" page
* [x] AFTER deciding response: dispatch RecordScanJob to queue ('scans' queue)
* [x] No DB write in request cycle
* [x] Rate limit: 60 req/min per IP on this endpoint
* [x] Inactive pages include "Report this QR" link (report form live, 3+ reports auto-pause)

Cache invalidation (event-based, on QrCode model events):

* [x] updated → cache key forgotten (rebuilt on next scan)
* [x] deleted → cache key forgotten
* [x] status toggle → cache key forgotten

### RecordScanJob (queued)

* [x] Resolve qr_code by slug (skip silently if deleted)
* [x] Scan quota rule (locked): scans always work + always recorded —
      quota only gates analytics visibility (enforced at read time)
* [x] Parse user-agent → device_type, os, browser (jenssegers/agent)
* [x] GeoIP lookup → country, city (GeoLite2 local DB, graceful skip if
      DB file missing — download pending, see Launch Checklist)
* [x] ip_hash = sha256(ip + QR_SCAN_SALT)
* [x] Insert qr_scan_events row
* [x] Increment qr_codes.scan_count + update last_scanned_at
      (single UPDATE query, no model events)

---

## MODULE F — Analytics (User-Facing) ✅ (Week 3)

On QR detail page (/qr/{id}) — dynamic QRs only:

* [x] Total scans (lifetime)
* [x] Scans today / this week / this month
* [x] Timeline chart (recharts AreaChart): daily scans, 7/30/90 days toggle
      (V1: direct grouped query on qr_scan_events — accurate + simple at MVP
      scale; qr_daily_stats feeds Phase 2 country/device analytics)
* [x] Free plan: range capped to analytics_history_days (30), locked 90d
      button + upgrade CTA
* [x] Static QRs: section shows "Tracking available on dynamic QRs" upsell

Account-level (/dashboard):

* [x] Aggregate totals only (cards) — no charts in V1

### Nightly Aggregation Job (scheduled 00:30)

* [x] AggregateDailyStatsJob: yesterday's qr_scan_events →
      upsert qr_daily_stats rows (scans, top_country, top_device)

---

## MODULE G — Billing (Razorpay Subscriptions)

Pages:

* /pricing (public + in-app)
* /billing (subscription management)

### G1. Pricing Page ✅

* [x] 3 plan cards: Free, Pro Monthly (₹249), Pro Yearly (₹2,499 — "2 months free" badge)
* [x] Feature comparison list per card
* [x] CTA: Free → Register; Pro → Checkout (auth required)
* [x] Current plan highlighted for logged-in users

### G2. Checkout Flow ✅

* [x] POST /billing/subscribe {plan} → create Razorpay subscription
      via API (RazorpayGateway wrapper, testable)
* [x] Open Razorpay Checkout JS (loaded on demand, UPI Autopay + cards)
* [x] Local subscription row created with status=pending
* [x] Success handler → redirect to /billing (webhook confirms activation)
* [x] NEVER activate plan from frontend callback — webhook only

### G3. Webhooks (POST /webhooks/razorpay) ✅

* [x] Verify webhook signature (HMAC, reject + log otherwise)
* [x] Idempotent handling (event id cached 7 days, duplicates skipped)
* [x] Events:
  * [x] subscription.activated → activate subscription, set expires_at
  * [x] subscription.charged → record payment, extend expires_at, unfreeze QRs
  * [x] payment.failed → record failed payment (notify email — Week 5)
  * [x] subscription.cancelled → mark cancelled (active till expires_at)
  * [x] subscription.completed / expired → grace flow
* [x] All raw payloads saved in payments.meta
* [x] Queue webhook processing (respond fast, ProcessRazorpayWebhookJob)

### G4. Billing Page (/billing) ✅

* [x] Current plan, status, renewal date
* [x] Payment history (date, invoice no, amount, status)
* [x] Cancel subscription (confirm modal — "active until {date}")
* [x] Upgrade button (free users)
* [x] Frozen/grace state banners

### G5. Subscription Lifecycle (scheduled job, daily 01:00) ✅

* [x] expires_at passed + not renewed → status=grace (email — Week 5)
* [x] Grace day 3, day 7 reminder emails — Week 5
* [x] Grace over (7 days) → status=frozen:
  * [x] Redirects KEEP WORKING (tested)
  * [x] Editing locked (QrCodePolicy blocks frozen QRs)
  * [x] Dynamic QRs beyond free limit → frozen flag (oldest kept editable)
* [x] Re-subscribe / successful charge → unfreeze everything (tested)

### G6. Invoice Numbering ✅

* [x] Format: INV-{YYYY}{MM}-{sequence} on successful payment (tested)

---

## MODULE H — Admin Panel (Custom React, `/admin/*`)

Access: role = `admin` or `super_admin` (`EnsureAdmin` middleware + `AdminUserPolicy`)  
Login: `/login` → admins redirect to `/admin/dashboard`  
**Overall admin module: complete** (custom React admin, Filament removed June 2026)

### H0. Admin Audit Status (June 2026)

**Working (verified via tests + code review):**

* [x] Access control: regular user → 403; banned admin → 403
* [x] All admin pages load via Inertia: Dashboard, Users, QR Codes, QR Reports, Plans, Subscriptions, Payments, Blocked Domains, Settings, Branding, Audit Logs
* [x] Same stack as user app (Laravel + Inertia + React + shadcn/ui)
* [x] `/admin` redirects to `/admin/dashboard`
* [x] Filament package removed — no Livewire/Filament assets

### H1. Dashboard Widgets

* [x] Total Users / Active (30d) / Paid Users
* [x] MRR (sum active subscription monthly value)
* [x] Revenue this month + growth % vs last month
* [x] Total QR codes / Total scans
* [x] QR type distribution chart (Recharts)
* [x] New signups chart (30 days)

### H2. User Management

* [x] List: name, email, role, status, QR count, registered date
* [x] Search + filters (role, status)
* [x] View user detail with 360° tabs (Overview | QR Codes | Subscriptions | Payments)
* [x] Actions: ban/unban, resend verification, impersonate (super_admin)
* [x] Edit role restricted via `AdminUserPolicy` (super_admin only)
* [x] CSV export

### H3. QR Management

* [x] List all QRs: owner, name, type, status, scans
* [x] Filters: type, status
* [x] Actions: pause (admin override), view content/destination
* [x] Admin-paused QR → user cannot reactivate (`admin_locked` flag)

### H4. Subscription & Payment Management

* [x] Subscriptions list: user, plan, status, dates
* [x] Payments list: searchable, status filter
* [x] Admin complimentary access — grant / extend / revoke
* [x] Per-user billing discount % (pricing + billing page display)
* [x] Mark payment refunded (local placeholder)
* [x] CSV export (payments)

### H5. Plan Management

* [x] Edit plan price, limits JSON, is_active
* [ ] Warning: limit changes affect users immediately (nice-to-have confirm dialog)

### H6. Abuse Queue

* [x] qr_reports list: pending badge + actions
* [x] Actions: dismiss, pause QR, ban user
* [x] blocked_domains CRUD

### H7. Settings & Audit

* [x] settings key-value editor
* [x] branding/SEO settings page
* [x] audit_logs viewer (read-only)

---

### H8. Admin — Remaining / Post-MVP

| # | Item | Status | Notes |
|---|------|--------|-------|
| 1 | Plan limit change warning | Low | Confirm dialog when saving plan limits |
| 2 | Razorpay refund API sync | Medium | Local mark-refunded exists; wire Razorpay when needed |
| 3 | MRR / revenue trend chart (6–12 months) | Low | Post-launch analytics |
| 4 | Admin email alerts (abuse spike, payment failures) | Low | Ops automation |
| 5 | Bulk actions (ban users, pause QRs) | Low | Post-MVP |

**Already read-only by design (OK for MVP):**

* Subscriptions: no arbitrary create/delete from admin UI
* Payments: no create
* Plans: no create/delete (seeded plans only)
* Audit logs: read-only

---

### H9. Admin — Extra Features Roadmap (post-MVP)

* [ ] MRR / revenue trend chart (6–12 months)
* [ ] Churn + failed payments widget
* [ ] Top scanned QRs leaderboard
* [ ] Razorpay webhook event log viewer
* [ ] Bulk actions: ban users, pause QRs
* [ ] Admin activity log (which admin did what — beyond audit_logs)
* [ ] Revenue by plan breakdown
* [ ] Subscription renewal calendar view

---

## MODULE I — Abuse Prevention

* [ ] UrlSafetyService wired into QR create + edit (Module C/D)
* [ ] Shortener blocklist (static config list)
* [ ] blocked_domains DB check
* [ ] Google Safe Browsing API check (graceful degradation if down)
* [ ] Rate limits:
  * [ ] QR creation: 10/hour per user
  * [ ] Redirect endpoint: 60/min per IP
  * [ ] Auth endpoints: 5/min
* [ ] Email verification required for dynamic QR (Module A/C)
* [ ] "Report this QR" page: GET /q/{slug}/report → simple form (reason) → qr_reports row, rate limited 3/day per IP
* [ ] Auto-rule: QR with 3+ pending reports → auto-pause + admin notification email

---

## MODULE J — Transactional Emails

All queued. Branded simple template (logo + content + footer).

* [ ] Welcome + verify email (register)
* [ ] Password reset
* [ ] Email changed verification
* [x] Payment success (with invoice details)
* [x] Payment failed
* [ ] Subscription activated
* [ ] Renewal upcoming (3 days before charge)
* [x] Grace period day 1 / 3 / 7
* [ ] Subscription frozen
* [ ] QR auto-paused (abuse) — to owner
* [ ] Admin alert: abuse reports threshold

---

## MODULE K — Public Pages (same Laravel app)

* [x] Landing page: hero, features, how it works, pricing preview, FAQ, footer
* [x] /pricing (shared with Module G)
* [x] Terms of Service, Privacy Policy, Refund Policy (static)
* [x] QR inactive/not-found/expired branded pages (Module E)
* [x] SEO: meta tags, OG tags, sitemap.xml, robots.txt

---

# 5. Routes Summary

Public:
* GET  /                      landing
* GET  /pricing
* GET  /q/{slug}              redirect (hot path)
* GET  /q/{slug}/report + POST
* GET  /terms, /privacy, /refund-policy
* POST /webhooks/razorpay

Auth (guest):
* GET/POST /register, /login, /forgot-password, /reset-password/{token}

App (auth):
* GET  /dashboard
* GET  /qr, /qr/create, /qr/{id}, /qr/{id}/edit
* POST /qr, PUT /qr/{id}, DELETE /qr/{id}
* POST /qr/{id}/toggle-status
* GET  /qr/{id}/download
* GET  /billing, POST /billing/subscribe, POST /billing/cancel
* GET/PUT /settings (profile, password)
* Email verification routes

Admin:
* /admin/* (Custom React + Inertia — dashboard, users, QR, billing, settings, audit)

---

# 6. Jobs & Scheduled Tasks

Queued jobs:
* RecordScanJob (high priority queue: scans)
* ProcessRazorpayWebhookJob
* All mail jobs

Scheduler:
* 00:30 daily — AggregateDailyStatsJob
* 01:00 daily — SubscriptionLifecycleJob (grace/freeze transitions)
* 02:00 weekly — PruneOldScanEventsJob (free users beyond retention — Phase 2, skeleton only)
* Monthly — GeoLite2 DB update

---

# 7. Build Order (Week by Week)

## Week 1 — Foundation ✅ DONE
* Project scaffold (1.1 complete)
* All migrations + seeders + enums + models + relationships
* Auth module complete (A1-A5)
* App layout shell (sidebar, topbar, toasts, dark mode optional)

## Week 2 — QR Core ✅ DONE (48 tests passing)
* QrContentBuilder + QrImageService + SlugGenerator
* QR create wizard (Module C) — all 7 types
* QR list + detail + edit + delete + toggle (Module D)
* Download (PNG sizes; SVG behind plan check)

## Week 3 — Redirect + Analytics ✅ DONE (57 tests passing)
* Redirect service + Redis caching + invalidation (Module E)
* RecordScanJob + device/geo parsing
* Analytics UI on detail page (Module F)
* AggregateDailyStatsJob
* Dashboard (Module B)

## Week 4 — Billing ✅ DONE (70 tests passing; Razorpay keys + emails pending)
* Plans seeder + PlanLimitService enforcement everywhere
* Pricing page + Razorpay checkout (G1-G2)
* Webhook handling (G3)
* Billing page (G4)
* SubscriptionLifecycleJob (G5)
* Billing emails

## Week 5 — Admin + Abuse ✅ DONE
* [x] Custom React admin at `/admin/*` (replaced Filament, June 2026)
* [x] Users, QR, plans, subscriptions, payments, abuse queue, settings, audit (Module H)
* [x] Abuse queue + blocked domains + ban user from report
* [x] Payment + grace emails (Module J — partial; welcome/renewal/frozen still pending)
* [x] Audit logging on key actions (billing, QR, reports, impersonation)
* [x] Permissions: `AdminUserPolicy`, impersonation, CSV exports, user 360° view

## Week 6 — Polish + Launch 🔄 IN PROGRESS
* [x] Public landing page (Module K — hero, features, how-it-works, pricing preview, FAQ)
* [x] Terms of Service, Privacy Policy, Refund Policy
* [x] Error pages (404/500/503)
* [x] robots.txt + sitemap.xml
* [ ] Mobile responsive pass
* [ ] Docker production config + deploy to VPS
* [ ] Backups (pg_dump daily cron), monitoring
* [ ] Soft launch

---

# 8. Testing Checklist (Minimum)

Feature tests (Pest/PHPUnit):

* [ ] Auth: register/login/verify/reset flows
* [ ] QR CRUD with policy (user A cannot touch user B's QR)
* [ ] Each QR type payload generation (QrContentBuilder unit tests)
* [ ] Plan limits: free user blocked at 3rd dynamic QR
* [ ] Redirect: active/paused/deleted/expired paths
* [ ] Redirect: cache invalidation on edit
* [ ] RecordScanJob: event written, counter incremented
* [ ] Webhook: signature verification, idempotency, activation
* [ ] Subscription lifecycle transitions (grace → frozen)
* [ ] UrlSafetyService: blocked domain rejected

Manual QA:

* [ ] Scan real QRs from physical phone (iOS + Android cameras)
* [ ] WiFi QR connects, vCard saves contact, WhatsApp opens chat
* [ ] Razorpay test mode full cycle (subscribe → webhook → active → cancel)
* [ ] Email rendering in Gmail mobile

---

# 9. Launch Checklist

* [ ] Domain + SSL (Let's Encrypt)
* [ ] Production .env (APP_DEBUG=false, queue workers via supervisor)
* [ ] Razorpay live keys + live webhook URL configured
* [ ] Google Safe Browsing API key
* [ ] GeoLite2 DB downloaded
* [ ] Database backup cron verified (restore tested once)
* [ ] Error tracking (Sentry free tier or Laravel log + alert)
* [ ] Uptime monitor on / and /q health
* [ ] Admin + super_admin accounts seeded
* [ ] Legal pages reviewed

---

# 10. Locked V1 Decisions (from discussion)

1. Dynamic QR in V1: URL + WhatsApp types only (others static-only)
2. Free plan: 2 dynamic QR, 100 scans/month analytics, 30-day history
3. Scan quota never breaks redirects — only gates analytics visibility
4. Slugs never reused after delete
5. Plan activation via webhook only
6. QR images never stored — always generated on demand
7. Raw IPs never stored — salted hash only
8. Admin-paused QRs cannot be reactivated by user

---

End of Phase 1 Plan
