# Custom Admin Panel — Full Build Plan (Filament Replace)

Version: 2.0  
Date: June 2026  
Last reviewed: full codebase audit (Filament resources, services, tests, middleware)  
Decision: **Filament use nahi karna** — custom admin Laravel + Inertia + React + TypeScript se banayenge (user app jaisa same stack).

---

## 1. Kyon Filament hata rahe hain

| Problem (Filament) | Custom admin solution |
|--------------------|------------------------|
| `/admin/*` routes `web.php` me nahi dikhte (auto-register) | Sab routes `routes/admin.php` me explicit |
| Livewire + Inertia mix, sidebar/CSS bugs | Pure React admin layout |
| Table state/search/filter bugs (users list) | Controlled Inertia props + simple tables |
| Alag login (`/admin/login`) | Same auth, role middleware |
| Hard to customize UI/branding | Full control (logo, SEO, theme) |

**Backend logic reuse hoga:** Models, Services, Jobs, Policies, Webhooks — ye sab same rahenge.

---

## 2. Tech Stack (Custom Admin)

| Layer | Choice |
|-------|--------|
| Backend | Laravel 12 |
| Frontend | Inertia.js + React + TypeScript |
| UI | Tailwind + shadcn/ui (user app jaisa) |
| Auth | Same session (`auth` middleware) + `role:admin,super_admin` |
| Charts | Recharts ya Chart.js |
| Tables | Server-side pagination via Inertia |
| Permissions | Laravel Policies + `AdminPolicy` / `SuperAdminPolicy` |

**Filament remove (later phase):** `filament/filament` package, `app/Filament/*`, `AdminPanelProvider`, Filament assets.

### 2.1 Folder Structure (Final Decision)

**Same `resources/js/` folder — admin subfolder use karo (alag root folder nahi).**

```
resources/js/pages/admin/       ← admin pages
resources/js/layouts/admin-layout.tsx
resources/js/components/admin/  ← admin-only (sidebar, stats)
resources/js/components/ui/     ← shared (Button, Card, Input)
app/Http/Controllers/Admin/
app/Http/Requests/Admin/
routes/admin.php
```

Inertia render: `Inertia::render('admin/users/index', ...)`

### 2.2 Blade vs React (Decision Locked)

Admin **React + Inertia** me banega (user app jaisa). Blade admin nahi — ek hi stack, components reuse.

---

## 3. Abhi Project Me Kya Hai (Current State)

### 3.1 User App (Inertia) — ✅ ~95% Done

| Module | Routes | Status |
|--------|--------|--------|
| Landing | `GET /` | ✅ hero, features, pricing preview, FAQ |
| Pricing | `GET /pricing` | ✅ plans, discount display |
| Auth | `/login`, `/register`, forgot/reset, verify | ✅ |
| Dashboard | `GET /dashboard` | ✅ stats, recent QRs, plan widget |
| QR Create | `GET/POST /qr/create` | ✅ 7 types, static/dynamic wizard |
| QR List | `GET /qr` | ✅ search, filter, pagination |
| QR Detail | `GET /qr/{id}` | ✅ preview, analytics, download |
| QR Edit | `GET/PUT /qr/{id}/edit` | ✅ dynamic edit, static name only |
| QR Download | `GET /qr/{id}/download` | ✅ PNG sizes, SVG (Pro) |
| QR Toggle | `POST /qr/{id}/toggle-status` | ✅ pause/active |
| Billing | `GET /billing` | ✅ plan, payments, cancel |
| Subscribe | `POST /billing/subscribe` | ✅ Razorpay checkout |
| Settings | `/settings/profile`, password, appearance | ✅ |
| Legal | `/terms`, `/privacy`, `/refund-policy` | ✅ |
| Redirect | `GET /q/{slug}` | ✅ Redis cache, scan logging |
| Report QR | `GET/POST /q/{slug}/report` | ✅ abuse report form |
| SEO | `robots.txt`, `sitemap.xml` | ✅ |

### 3.2 Backend Services — ✅ Done

- `PlanLimitService` — free/pro limits enforce
- `QrContentBuilder` — 7 QR payload types
- `QrImageService` — PNG/SVG on-demand generation
- `SlugGenerator` — unique slugs, never reused
- `UrlSafetyService` — abuse URL checks
- `SubscriptionService` — activate, charge, grace, freeze, cancel
- `RazorpayGateway` — subscription create/cancel/webhook verify
- `AdminSubscriptionService` — complimentary access, discount, extend/revoke manual

### 3.3 Jobs & Scheduler — ✅ Done

- `RecordScanJob` — async scan events
- `ProcessRazorpayWebhookJob` — payment webhooks
- `AggregateDailyStatsJob` — daily analytics rollup
- `SubscriptionLifecycleJob` — grace/freeze daily
- Grace/payment emails (partial)

### 3.4 Filament Admin (`/admin`) — ⚠️ Replace karna hai

Abhi ye sab Filament me hai — custom admin me **same features** chahiye:

---

## 4. Filament Admin — Feature Inventory (Copy to Custom Admin)

### 4.1 Access & Auth

| Feature | Filament (abhi) | Custom admin me banana |
|---------|-----------------|------------------------|
| Admin-only access | `User::canAccessPanel()` | Middleware `EnsureAdmin` |
| Roles | `user`, `admin`, `super_admin` | Same enum + policies |
| Banned admin block | ✅ | ✅ middleware check |
| Separate admin login | `/admin/login` | ❌ use `/login` + redirect by role |
| Regular user → admin 403 | ✅ | ✅ |

### 4.2 Dashboard (`/admin` or `/admin/dashboard`)

| Widget | Status (Filament) | Custom build |
|--------|-------------------|--------------|
| Total users | ✅ | 🔨 Build |
| Active users (30d) | ✅ | 🔨 Build |
| Paid users count | ✅ | 🔨 Build |
| MRR | ✅ | 🔨 Build |
| Revenue this month + growth % | ✅ | 🔨 Build |
| Total QR codes | ✅ | 🔨 Build |
| Total scans | ✅ | 🔨 Build |
| New signups chart (30 days) | ✅ | 🔨 Build |
| QR type distribution chart | ❌ pending | 🔨 Build |

### 4.3 Users (`/admin/users`)

| Feature | Filament | Custom build |
|---------|----------|--------------|
| List all users (pagination, search) | ✅ (buggy state) | 🔨 **Priority** — fix "all users show" |
| Columns: name, email, role, status, discount, QR count, created | ✅ | 🔨 |
| Filter by role, status | ✅ | 🔨 |
| Create user | ✅ | 🔨 |
| Edit user (name, email, role, status, password optional) | ✅ | 🔨 |
| View user detail | ✅ | 🔨 |
| Ban / Unban | ✅ | 🔨 |
| Set billing discount % | ✅ | 🔨 |
| Grant free Pro access (complimentary) | ✅ | 🔨 |
| Resend email verification | ❌ | 🔨 |
| Impersonate user (super_admin) | ❌ | 🔨 |
| Edit role (super_admin only) | ❌ partial | 🔨 |
| User 360° (QRs + subs + payments tabs) | ❌ | 🔨 |
| CSV export | ❌ | 🔨 |

### 4.4 QR Codes (`/admin/qr-codes`)

| Feature | Filament | Custom build |
|---------|----------|--------------|
| List all QRs (owner, name, type, status, scans) | ✅ | 🔨 |
| Filter type, status | ✅ | 🔨 |
| View QR content/destination | ✅ | 🔨 |
| Admin pause (admin_locked — user cannot reactivate) | ✅ | 🔨 |

### 4.5 QR Reports / Abuse (`/admin/qr-reports`)

| Feature | Filament | Custom build |
|---------|----------|--------------|
| Pending reports list | ✅ | 🔨 |
| Dismiss report | ✅ | 🔨 |
| Pause reported QR | ✅ | 🔨 |
| Ban user from report row | ❌ | 🔨 |

### 4.6 Blocked Domains (`/admin/blocked-domains`)

| Feature | Filament | Custom build |
|---------|----------|--------------|
| CRUD blocked domains | ✅ | 🔨 |
| List with search | ✅ | 🔨 |

### 4.7 Plans (`/admin/plans`)

| Feature | Filament | Custom build |
|---------|----------|--------------|
| List plans (Free, Pro Monthly, Pro Yearly) | ✅ | 🔨 |
| Edit price | ✅ | 🔨 |
| Edit limits JSON | ✅ | 🔨 |
| Toggle is_active | ✅ | 🔨 |
| Warning on limit change | ❌ | 🔨 |
| Create/delete plan | ❌ by design | skip MVP |

### 4.8 Subscriptions (`/admin/subscriptions`)

| Feature | Filament | Custom build |
|---------|----------|--------------|
| List (user, plan, status, gateway, dates) | ✅ read-only | 🔨 |
| View detail | ✅ | 🔨 |
| Extend manual subscription | ✅ | 🔨 |
| Revoke manual (complimentary) | ✅ | 🔨 |
| Force activate/cancel (paid) | ❌ | 🔨 post-MVP |

### 4.9 Payments (`/admin/payments`)

| Feature | Filament | Custom build |
|---------|----------|--------------|
| List (searchable, status filter) | ✅ read-only | 🔨 |
| View payment + meta (raw Razorpay) | ✅ | 🔨 |
| Mark refunded | ❌ | 🔨 |

### 4.10 Settings (`/admin/settings`)

| Feature | Filament | Custom build |
|---------|----------|--------------|
| Key-value settings editor | ✅ | 🔨 |
| Branding & SEO page | ✅ (custom page) | 🔨 |
| — project name | ✅ | 🔨 |
| — SEO title, description | ✅ | 🔨 |
| — logo upload | ✅ | 🔨 |
| — favicon upload | ✅ | 🔨 |

### 4.11 Audit Logs (`/admin/audit-logs`)

| Feature | Filament | Custom build |
|---------|----------|--------------|
| Read-only list | ✅ | 🔨 |
| Filter by action, user, date | partial | 🔨 |
| View detail (meta JSON) | ✅ | 🔨 |

### 4.12 Filament Se Copy Karne Wali Exact Details (Pehle Miss Thi)

#### Users — extra fields & actions
- Soft deletes: `User` model uses `SoftDeletes` — list me **trashed filter** + restore option
- Bulk delete (super_admin only) — Filament me toolbar pe hai
- User form fields: `name`, `email`, `password` (create required, edit optional), `email_verified_at`, `role`, `status`, `country`, `billing_discount_percent`, `billing_note`, `last_login_at`
- List row actions: View, Edit, Set discount, Ban, Unban
- **Grant complimentary** sirf **user view page** pe hai (list row pe nahi)
- Discount options: `null`, `10`, `25`, `50`, `75` percent
- Complimentary form: `plan_id` (active non-free plans), `duration_days` (`7/14/30/90/365`), `admin_note` (required)

#### QR Codes — extra
- Columns: owner email, name, type badge, **slug** (copyable), **is_dynamic**, status, scan_count, created_at
- Filters: type, status, **is_dynamic** (ternary), **trashed**
- Admin pause: `status=paused` + **`admin_locked=true`** (user reactivate nahi kar sakta)

#### QR Reports
- Status values: `pending` → `reviewed` (dismiss) ya `actioned` (pause QR)
- Pause QR: same as admin pause (`admin_locked=true`)

#### Subscriptions — extend/revoke rules
- Extend / Revoke sirf `gateway === 'manual'` subscriptions pe
- Revoke visible jab status `grantsProAccess()` ho
- Extend days: `7/14/30/90`

#### Plans — edit fields
- Editable: `name`, `price`, `limits` (JSON textarea), `razorpay_plan_id`, `is_active`, `sort_order`
- Read-only on form: `slug`, `billing_cycle`, `currency` (default INR)

#### Branding settings keys (`Setting` model)
- `project_name`, `seo_title`, `seo_description`, `logo_path`, `favicon_path`
- Upload: `storage/app/public/branding/` disk `public`

#### Dashboard — MRR formula (copy from `StatsOverview.php`)
- Monthly plans: sum `plans.price` where active subscription + `billing_cycle=monthly`
- Yearly plans: sum yearly price ÷ 12
- Revenue growth: this month vs last month paid payments

#### Signups chart (copy from `SignupsChart.php`)
- Last 30 days daily `User::whereDate('created_at')` counts

#### Admin sidebar nav groups (Filament order reference)
1. Dashboard
2. **Management:** Users, QR Codes
3. **Billing:** Plans, Subscriptions, Payments
4. **Abuse:** QR Reports, Blocked Domains
5. **System:** Settings, Branding & SEO, Audit Logs

---

## 5. Custom Admin — Proposed Routes (`routes/admin.php`)

### ⚠️ 5.0 Route Conflict — IMPORTANT

Abhi Filament **`/admin` path own** karta hai (`AdminPanelProvider` → `->path('admin')`).

**Migration strategy (pick one):**

| Option | Kaise | Kab use |
|--------|-------|---------|
| **A (recommended)** | Pehle React admin module-by-module banao; **jab route ready ho** us Filament resource ko disable/remove karo; last me Filament package hatao | Production-safe |
| **B** | Dev me temporary path `/manage` use karo; sab ready hone ke baad `/admin` pe switch + Filament off | Parallel testing |

**HandleInertiaRequests change required:** Abhi `admin/*` pe Inertia **skip** hota hai — React admin ke liye ye skip **hataana** hoga (sirf Filament completely off hone ke baad safe).

```php
// app/Http/Middleware/HandleInertiaRequests.php — REMOVE this block for React admin:
if ($request->is('admin', 'admin/*')) {
    return $next($request);
}
```

### 5.1 Route List

```php
// Prefix: /admin
// Middleware: web, auth, admin

GET  /admin                          → AdminDashboardController
GET  /admin/users                    → Admin\UserController@index
GET  /admin/users/create             → Admin\UserController@create
POST /admin/users                    → Admin\UserController@store
GET  /admin/users/{user}             → Admin\UserController@show
GET  /admin/users/{user}/edit        → Admin\UserController@edit
PUT  /admin/users/{user}             → Admin\UserController@update
POST /admin/users/{user}/ban         → Admin\UserController@ban
POST /admin/users/{user}/unban       → Admin\UserController@unban
POST /admin/users/{user}/discount    → Admin\UserController@setDiscount
POST /admin/users/{user}/complimentary → Admin\UserController@grantComplimentary
POST /admin/users/{user}/impersonate → Admin\UserController@impersonate (super_admin)

GET  /admin/qr-codes                 → Admin\QrCodeController@index
GET  /admin/qr-codes/{qr}            → Admin\QrCodeController@show
POST /admin/qr-codes/{qr}/pause      → Admin\QrCodeController@pause

GET  /admin/qr-reports               → Admin\QrReportController@index
POST /admin/qr-reports/{report}/dismiss
POST /admin/qr-reports/{report}/pause-qr
POST /admin/qr-reports/{report}/ban-user

GET|POST|PUT|DELETE /admin/blocked-domains ...

GET  /admin/plans                    → Admin\PlanController@index
GET  /admin/plans/{plan}/edit        → Admin\PlanController@edit
PUT  /admin/plans/{plan}             → Admin\PlanController@update

GET  /admin/subscriptions            → Admin\SubscriptionController@index
GET  /admin/subscriptions/{sub}      → Admin\SubscriptionController@show
POST /admin/subscriptions/{sub}/extend
POST /admin/subscriptions/{sub}/revoke

GET  /admin/payments                 → Admin\PaymentController@index
GET  /admin/payments/{payment}       → Admin\PaymentController@show
POST /admin/payments/{payment}/refund

GET  /admin/settings                 → Admin\SettingController@index
PUT  /admin/settings                 → Admin\SettingController@update
GET  /admin/settings/branding        → Admin\BrandingController@edit
PUT  /admin/settings/branding        → Admin\BrandingController@update

GET  /admin/audit-logs               → Admin\AuditLogController@index
GET  /admin/audit-logs/{log}         → Admin\AuditLogController@show
```

`bootstrap/app.php` me register:
```php
->withRouting(
    web: __DIR__.'/../routes/web.php',
    commands: __DIR__.'/../routes/console.php',
    health: '/up',
    then: function () {
        Route::middleware('web')
            ->group(base_path('routes/admin.php'));
    },
)
```

Middleware alias `bootstrap/app.php` me:
```php
$middleware->alias([
    'admin' => \App\Http\Middleware\EnsureAdmin::class,
]);
```

---

## 6. Custom Admin — Frontend Pages (`resources/js/pages/admin/`)

```
admin/
  dashboard.tsx
  users/
    index.tsx
    create.tsx
    edit.tsx
    show.tsx
  qr-codes/
    index.tsx
    show.tsx
  qr-reports/
    index.tsx
  blocked-domains/
    index.tsx
    create.tsx
    edit.tsx
  plans/
    index.tsx
    edit.tsx
  subscriptions/
    index.tsx
    show.tsx
  payments/
    index.tsx
    show.tsx
  settings/
    index.tsx
    branding.tsx
  audit-logs/
    index.tsx
    show.tsx
```

**Layout:** `resources/js/layouts/admin-layout.tsx` — sidebar + header (user app jaisa, alag nav items).

---

## 7. Backend Controllers to Create

```
app/Http/Controllers/Admin/
  DashboardController.php
  UserController.php
  QrCodeController.php
  QrReportController.php
  BlockedDomainController.php
  PlanController.php
  SubscriptionController.php
  PaymentController.php
  SettingController.php
  BrandingController.php
  AuditLogController.php
```

**Reuse existing services:**
- `AdminSubscriptionService` — discount, complimentary, extend, revoke
- `SubscriptionService` — lifecycle
- `AuditLog::record()` — all admin actions log karo

**Move from Filament (refactor):**
- `app/Filament/Actions/AdminBillingActions.php` → `app/Actions/Admin/BillingActions.php` (plain PHP methods, no Filament)

**New backend helpers to create:**
- `app/Services/AdminDashboardService.php` — stats + chart data (logic from `StatsOverview` + `SignupsChart`)
- `app/Http/Middleware/EnsureAdmin.php` — `auth` + `isAdmin()` + not banned

**Form Request classes (`app/Http/Requests/Admin/`):**
- `StoreUserRequest`, `UpdateUserRequest`
- `SetBillingDiscountRequest`, `GrantComplimentaryRequest`
- `ExtendSubscriptionRequest`, `RevokeSubscriptionRequest`
- `UpdatePlanRequest`, `UpdateBrandingRequest`
- `StoreBlockedDomainRequest`, `UpdateBlockedDomainRequest`

**TypeScript types (`resources/js/types/admin.ts`):**
- `AdminUser`, `AdminUserListItem`, `Paginated<T>`
- `AdminStats`, `SignupsChartData`
- Enum labels: `UserRole`, `UserStatus`, `QrType`, `QrStatus`, `SubscriptionStatus`, `PaymentStatus`

---

## 8. Permissions Matrix

| Action | admin | super_admin |
|--------|-------|-------------|
| View dashboard | ✅ | ✅ |
| List/view users | ✅ | ✅ |
| Edit user (non-role fields) | ✅ | ✅ |
| Change user role | ❌ | ✅ |
| Delete user | ❌ | ✅ |
| Impersonate user | ❌ | ✅ |
| Ban/unban user | ✅ | ✅ |
| Set billing discount | ✅ | ✅ |
| Grant complimentary Pro | ✅ | ✅ |
| Pause any QR | ✅ | ✅ |
| Edit plans | ✅ | ✅ |
| Mark payment refunded | ✅ | ✅ |
| Edit branding/SEO | ✅ | ✅ |
| View audit logs | ✅ | ✅ |

Implement via `app/Policies/Admin/*` or gate methods on `User` model.

---

## 9. Database Tables (No Change Needed)

Admin custom panel same tables use karega:

- `users` (+ billing_discount_percent, billing_note)
- `plans`
- `subscriptions` (+ is_complimentary, admin_note, granted_by)
- `payments`
- `qr_codes` (+ admin_locked)
- `qr_scan_events`, `qr_daily_stats`
- `settings`
- `audit_logs`
- `blocked_domains`
- `qr_reports`

### 9.1 Enums (Display Labels Admin UI Me)

| Enum | Values |
|------|--------|
| `UserRole` | user, admin, super_admin |
| `UserStatus` | active, banned |
| `QrType` | url, text, email, phone, wifi, vcard, pdf |
| `QrStatus` | active, paused, frozen |
| `SubscriptionStatus` | pending, active, grace, frozen, cancelled, expired |
| `PaymentStatus` | pending, paid, failed, refunded |
| `BillingCycle` | free, monthly, yearly |

### 9.2 Audit Log Actions (Already Used in Code)

| Action | Source |
|--------|--------|
| `subscription.complimentary_granted` | AdminSubscriptionService |
| `subscription.manual_extended` | AdminSubscriptionService |
| `subscription.manual_revoked` | AdminSubscriptionService |
| `user.billing_discount_set` | AdminSubscriptionService |
| `subscription.activated` | Razorpay webhook |
| `payment.recorded` / `payment.failed` | Razorpay webhook |
| `subscription.checkout_started` / `subscription.cancelled` | BillingController |
| `qr.created` / `qr.updated` / `qr.deleted` / `qr.status_toggled` | QrCodeController |
| `qr.auto_paused_abuse` | QrReportController |

Custom admin actions bhi `AuditLog::record()` se log karo: `user.banned`, `user.unbanned`, `qr.admin_paused`, `qr_report.dismissed`, etc.

### 9.3 Login & Auth Changes

| File | Change |
|------|--------|
| `AuthenticatedSessionController@store` | Admin role → `redirect('/admin')`, user → `/dashboard` |
| `User` model | Filament remove ke baad: `FilamentUser` interface + `canAccessPanel()` hatao |
| `bootstrap/providers.php` | Filament remove ke baad: `AdminPanelProvider` hatao |

### 9.4 Shared Inertia Props (Admin Pages)

Admin pages ko bhi `HandleInertiaRequests` se `auth`, `branding`, `flash` milega. Admin layout me `flash.success` toast dikhana (user app pattern follow karo).

Pagination pattern reuse: `resources/js/pages/qr/index.tsx` — `router.get` + `preserveState` + links.

---

## 10. User App — Jo Abhi Bhi Pending Hai (Non-Admin)

Ye Filament se alag, pure product ke liye:

### Billing
- [ ] Discount auto-apply at Razorpay checkout (abhi sirf display)
- [ ] Razorpay live keys + live webhook URL
- [ ] Future: second payment gateway (Stripe/Cashfree) — architecture ready

### Abuse
- [ ] UrlSafetyService full wire on create/edit
- [ ] Google Safe Browsing API
- [ ] Auto-pause QR on 3+ reports
- [ ] Admin alert emails

### Emails
- [x] Payment success/failed, grace emails
- [ ] Welcome, subscription activated, renewal reminder, frozen alert
- [ ] SMTP production (Brevo/Mailgun) — abhi log driver

### Auth polish
- [ ] Banned user login message
- [ ] Update last_login_at on login
- [ ] Admin redirect after login (`/admin` for admin role)

### Launch
- [ ] Production deploy (VPS, Docker, SSL)
- [ ] Queue workers (supervisor)
- [ ] Backups, monitoring, Sentry

---

## 11. Migration Plan — Filament → Custom Admin

### Phase 1 — Foundation (Week 1)
1. Create `routes/admin.php` + `EnsureAdmin` middleware
2. `AdminLayout` + sidebar navigation
3. Admin dashboard (stats widgets)
4. **Users list** (fix: show ALL users, server-side pagination) — **TOP PRIORITY**
5. User view/edit/ban/discount/complimentary

### Phase 2 — Core Ops (Week 2)
6. QR codes admin list + pause
7. QR reports queue (dismiss, pause)
8. Blocked domains CRUD
9. Plans edit
10. Subscriptions + payments (read-only lists)

### Phase 3 — Settings & Polish (Week 3)
11. Settings key-value editor
12. Branding & SEO page (logo, favicon, project name)
13. Audit logs viewer
14. super_admin permission guards
15. Impersonate + resend verification

### Phase 4 — Remove Filament (Week 4)
16. Remove `filament/filament` from composer
17. Delete `app/Filament/*`, `AdminPanelProvider`
18. Remove Filament assets from `public/`
19. Update `qr_mvp_plan.md` Module H
20. Full regression tests

---

## 12. Files to Delete (After Custom Admin Ready)

```
app/Filament/                          (entire folder)
app/Providers/Filament/AdminPanelProvider.php
public/css/filament/
public/js/filament/
public/fonts/filament/
composer.json → remove filament/filament
```

**Keep & reuse:**
```
app/Services/AdminSubscriptionService.php
app/Filament/Actions/AdminBillingActions.php → move to app/Http/Controllers/Admin or Actions/
database/migrations/*
app/Models/*
```

---

## 13. Testing Checklist (Custom Admin)

- [ ] Regular user cannot access `/admin/*` (403)
- [ ] Admin can access all admin routes
- [ ] Banned admin blocked
- [ ] Users list shows ALL users (count matches `User::count()`)
- [ ] Search/filter works without hiding all users
- [ ] Ban/unban updates status
- [ ] Discount saves and shows on user `/pricing`
- [ ] Complimentary grant creates manual subscription
- [ ] Admin pause sets `admin_locked` on QR
- [ ] Audit log written for every admin action
- [ ] super_admin only: role change, impersonate

---

## 14. Quick Reference — Abhi Kya Kahan Hai

| Area | Location |
|------|----------|
| User routes | `routes/web.php`, `routes/auth.php`, `routes/settings.php` |
| Filament admin (temporary) | Auto `/admin/*` via Filament |
| User React pages | `resources/js/pages/` |
| Filament resources | `app/Filament/Resources/` |
| Business logic | `app/Services/` |
| Admin billing logic | `app/Services/AdminSubscriptionService.php` |
| Models | `app/Models/` |
| Tests | `tests/Feature/` (~89 passing) |
| MVP plan (old) | `qr_mvp_plan.md` |
| **This plan (new)** | `CUSTOM_ADMIN_PLAN.md` |

---

## 15. Filament Logic Reference Map (Copy From)

| Custom admin feature | Copy logic from |
|---------------------|-----------------|
| Dashboard stats | `app/Filament/Widgets/StatsOverview.php` |
| Signups chart | `app/Filament/Widgets/SignupsChart.php` |
| Users table/actions | `app/Filament/Resources/Users/Tables/UsersTable.php` |
| User form fields | `app/Filament/Resources/Users/Schemas/UserForm.php` |
| User detail fields | `app/Filament/Resources/Users/Schemas/UserInfolist.php` |
| Billing actions | `app/Filament/Actions/AdminBillingActions.php` |
| QR admin pause | `app/Filament/Resources/QrCodes/Tables/QrCodesTable.php` |
| QR reports actions | `app/Filament/Resources/QrReports/Tables/QrReportsTable.php` |
| Plan edit form | `app/Filament/Resources/Plans/Schemas/PlanForm.php` |
| Branding save | `app/Filament/Pages/BrandingSeoSettings.php` |
| Subscription extend/revoke | `app/Filament/Resources/Subscriptions/Pages/ViewSubscription.php` |

---

## 16. Step-by-Step Implementation (Chhote Steps)

Har step alag commit / alag session me kar sakte ho. ✅ = done mark karo jab complete ho.

### Phase 0 — Prep (Filament abhi chalta rahega)

| Step | Task | Files | Status |
|------|------|-------|--------|
| 0.1 | `EnsureAdmin` middleware banao | `app/Http/Middleware/EnsureAdmin.php` | ✅ |
| 0.2 | Middleware alias register karo | `bootstrap/app.php` | ✅ |
| 0.3 | Empty `routes/admin.php` banao (prefix `/admin`, middleware `web,auth,admin`) | `routes/admin.php` | ✅ |
| 0.4 | `bootstrap/app.php` me `then:` se admin routes load karo | `bootstrap/app.php` | ✅ |
| 0.5 | Test route: `GET /admin/ping` → `{'ok':true}` (temporary) | `tests/Feature/Admin/AdminFoundationTest.php` | ✅ |
| 0.6 | `resources/js/types/admin.ts` — basic types | types file | ✅ |
| 0.7 | `AdminDashboardService` skeleton | `app/Services/AdminDashboardService.php` | ✅ |

### Phase 1 — Foundation + Users (TOP PRIORITY)

| Step | Task | Files | Status |
|------|------|-------|--------|
| 1.1 | `admin-layout.tsx` + `admin-sidebar.tsx` banao | `layouts/`, `components/admin/` | ✅ |
| 1.2 | Sidebar nav items: Dashboard, Users, QRs, Reports, Plans, Subs, Payments, Settings, Audit | `admin-sidebar.tsx` | ✅ |
| 1.3 | `DashboardController@index` — stats from `AdminDashboardService` | controller + service | ✅ |
| 1.4 | `pages/admin/dashboard.tsx` — stat cards (no chart yet) | React page | ✅ |
| 1.5 | **Filament dashboard disable** ya `/admin` dashboard route React ko priority (conflict fix) | React at `/admin/dashboard`, Filament keeps `/admin` | ✅ |
| 1.6 | `HandleInertiaRequests` — admin skip block hatao (jab React admin live ho) | middleware | ✅ |
| 1.7 | `UserController@index` — `withCount('qrCodes')`, search, filters, paginate(20) | controller | ✅ |
| 1.8 | `pages/admin/users/index.tsx` — table + pagination (`qr/index` pattern) | React page | ✅ |
| 1.9 | Verify: `User::count()` === total pagination count | `UserManagementTest` | ✅ |
| 1.10 | `UserController@show` — user detail | controller + `users/show.tsx` | ✅ |
| 1.11 | `UserController@edit` + `update` — Form Request validation | controller + form | ✅ |
| 1.12 | `UserController@create` + `store` | controller + form | ✅ |
| 1.13 | `POST ban` / `POST unban` + AuditLog | controller | ✅ |
| 1.14 | `BillingActions` refactor — `setBillingDiscount` | `app/Actions/Admin/` | ✅ |
| 1.15 | `POST discount` — modal/form on user show page | React + route | ✅ |
| 1.16 | `grantComplimentary` action + form (plan, days, note) | action + route + UI | ✅ |
| 1.17 | `AuthenticatedSessionController` — admin → `/admin/dashboard` redirect | auth controller | ✅ |
| 1.18 | Test: `tests/Feature/Admin/UserManagementTest.php` | new test file | ✅ |
| 1.19 | Update `AdminPanelTest` — React assertions (ya naya test suite) | tests | ✅ |

### Phase 2 — QR, Abuse, Plans

| Step | Task | Files |
|------|------|-------|
| 2.1 | `QrCodeController@index` — all QRs, filters | controller | ✅ |
| 2.2 | `pages/admin/qr-codes/index.tsx` | React | ✅ |
| 2.3 | `QrCodeController@show` — content/destination preview | controller + page | ✅ |
| 2.4 | `POST pause` — admin_locked + AuditLog | controller | ✅ |
| 2.5 | `QrReportController@index` — pending filter default | controller | ✅ |
| 2.6 | `pages/admin/qr-reports/index.tsx` + dismiss/pause actions | React | ✅ |
| 2.7 | `BlockedDomainController` — full CRUD | controller + pages | ✅ |
| 2.8 | `PlanController@index` + `edit` + `update` — JSON limits validation | controller + page | ✅ |
| 2.9 | Plan save pe confirm dialog (limit change warning) | React | ✅ |

### Phase 3 — Billing Read-Only + Settings

| Step | Task | Files |
|------|------|-------|
| 3.1 | `SubscriptionController@index` + `show` | controller + pages | ✅ |
| 3.2 | Extend + revoke manual (subscription show page) | routes + UI | ✅ |
| 3.3 | `PaymentController@index` + `show` (meta JSON pretty print) | controller + pages | ✅ |
| 3.4 | `SettingController@index` — key-value editor | controller + page | ✅ |
| 3.5 | `BrandingController` — logo/favicon upload | controller + page | ✅ |
| 3.6 | `AuditLogController@index` + `show` — filters | controller + pages | ✅ |
| 3.7 | Dashboard me signups chart add (Recharts) | dashboard.tsx | ✅ |
| 3.8 | QR type distribution chart (optional) | dashboard.tsx | ✅ |

### Phase 4 — Permissions + Advanced

| Step | Task | Files |
|------|------|-------|
| 4.1 | `super_admin` policy — role change, delete, impersonate | policies | ✅ |
| 4.2 | Impersonate: `POST impersonate` + session `impersonator_id` + audit | controller | ✅ |
| 4.3 | Stop impersonate button in admin header | layout | ✅ |
| 4.4 | Resend verification email action | controller | ✅ |
| 4.5 | User 360° tabs on show page (QRs, subs, payments) | users/show.tsx | ✅ |
| 4.6 | Mark payment refunded (placeholder if Razorpay API pending) | payment show | ✅ |
| 4.7 | Ban user from QR report row | qr-reports | ✅ |
| 4.8 | CSV export users + payments | controller | ✅ |

### Phase 5 — Remove Filament

| Step | Task | Files |
|------|------|-------|
| 5.1 | Sab admin features React me verified | manual QA |
| 5.2 | `composer remove filament/filament` | composer.json |
| 5.3 | Delete `app/Filament/` | folder |
| 5.4 | Delete `AdminPanelProvider` + `bootstrap/providers.php` entry | provider |
| 5.5 | Delete `public/css/filament`, `public/js/filament`, `public/fonts/filament` | assets |
| 5.6 | `User` model se `FilamentUser` hatao | User.php |
| 5.7 | `php artisan test` — full suite pass | tests |
| 5.8 | Update `qr_mvp_plan.md` Module H → Custom React Admin | docs |

---

## 17. Per-Step "Done" Checklist (Quick)

Copy this when working:

```
Phase 0: [x] 0.1 [x] 0.2 [x] 0.3 [x] 0.4 [x] 0.5 [x] 0.6 [x] 0.7
Phase 1: [x] 1.1 [x] 1.2 [x] 1.3 [x] 1.4 [x] 1.5 [x] 1.6 [x] 1.7 [x] 1.8 [x] 1.9 [x] 1.10 [x] 1.11 [x] 1.12 [x] 1.13 [x] 1.14 [x] 1.15 [x] [x] 1.16 [x] 1.17 [x] 1.18 [x] 1.19 [x]
Phase 2: [x] 2.1 [x] 2.2 [x] 2.3 [x] 2.4 [x] 2.5 [x] 2.6 [x] 2.7 [x] 2.8 [x] 2.9
Phase 3: [x] 3.1 [x] 3.2 [x] 3.3 [x] 3.4 [x] 3.5 [x] 3.6 [x] 3.7 [x] 3.8
Phase 4: [x] 4.1 [x] 4.2 [x] 4.3 [x] 4.4 [x] 4.5 [x] 4.6 [x] 4.7 [x] 4.8
Phase 5: [x] 5.1 [x] 5.2 [x] 5.3 [x] 5.4 [x] 5.5 [x] 5.6 [x] 5.7 [x] 5.8
```

---

## 18. Recommended Next Task

**Phase 0–5 complete ✅** — Custom React admin is live; Filament fully removed.

**Next:** Manual QA in browser, then production deploy checklist (see `qr_mvp_plan.md` Week 6).

---

*End of Custom Admin Plan v2.0 — Filament replacement complete*
