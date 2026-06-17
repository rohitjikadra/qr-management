# QR Manager SaaS — Project Audit Report

**Date:** 16 June 2026  
**Scope:** Full codebase scan (local dev environment)  
**Stack:** Laravel 12 + Inertia/React + PostgreSQL + Redis  
**Auditor:** Automated scan + test suite + build verification

---

## Executive Summary / सारांश

| Area | Status |
|------|--------|
| **Automated tests** | ✅ **163 passed** (893 assertions) |
| **Frontend production build** | ✅ `npm run build` successful |
| **Composer security audit** | ✅ No known vulnerabilities |
| **Core MVP features** | ✅ Built and tested |
| **Production readiness** | ⚠️ **Not ready** — mail, storage link, deploy config pending |
| **Critical bugs found** | ✅ **None blocking local dev** |

**English:** The project is in strong shape for MVP development. Core user flows, billing, admin panel, and abuse features work with good test coverage. Before launch, fix a few infrastructure gaps (storage link, SMTP, production Docker, Razorpay live keys).

**Hindi:** Project development ke liye bahut accha stage par hai — tests pass, build OK. Launch se pehle mail, storage link, aur VPS deploy setup karna baaki hai.

---

## 1. What Was Scanned

- Full PHPUnit test suite (`php artisan test`)
- Vite production build (`npm run build`)
- Composer security audit (`composer audit`)
- Laravel environment (`php artisan about`)
- Routes, services, admin panel, auth, billing, QR flows
- `.env.example` vs actual Docker setup
- `qr_mvp_plan.md` pending items

---

## 2. Test & Build Results

```
Tests:    163 passed (893 assertions)
Duration: ~24 seconds
Build:    Vite 6 — 2703 modules, no errors
PHP:      8.3.14
Laravel:  12.62.0
Database: pgsql (Docker port 5434)
Cache/Queue/Session: redis
```

### Test coverage by module

| Module | Tests | Status |
|--------|-------|--------|
| Auth (login, register, verify, reset, banned) | 18+ | ✅ |
| QR CRUD + limits + verification gate | 14 | ✅ |
| Redirect + scan job + abuse reports | 9 | ✅ |
| Billing + Razorpay webhooks + lifecycle | 15 | ✅ |
| Admin foundation + users + team | 33+ | ✅ |
| Admin QR, reports, plans, billing, audit | 30+ | ✅ |
| Public pages + dashboard | 7+ | ✅ |
| Unit (QrContentBuilder) | 10 | ✅ |

---

## 3. Completed Features ✅

### User App
- Register / login / forgot password / email verification
- Dashboard with stats + recent QRs
- **Email verification required** before QR create (UI + backend)
- QR create/edit/delete/download (7 types, static + dynamic)
- Plan limits (free vs pro), SVG download gate
- Billing page + Razorpay subscribe/cancel
- Pricing page with admin discount display
- Settings (profile, password, appearance)
- **Banned user:** login block + suspended page

### QR Redirect (`/q/{slug}`)
- Active / paused / expired / deleted handling
- Async scan logging via queue
- Redis cache for redirect performance
- Abuse report form + auto-pause after 3 reports
- URL safety (shorteners blocked, blocked domains list)

### Admin Panel (Custom React `/admin/*`)
- Dashboard with stats + charts (signups, QR types)
- **Users** — customer accounts only (role=user); no manual create
- **Admin Team** — admin/super_admin list; super admin can **Add admin**
- QR codes, QR reports, blocked domains, plans
- Subscriptions (extend/revoke manual), payments (mark refunded placeholder)
- Settings, branding/SEO, audit logs
- Impersonation (super_admin), ban/unban, CSV exports
- User 360° view (Overview | QRs | Subscriptions | Payments)
- Permissions via `AdminUserPolicy` + `EnsureSuperAdmin` middleware

### Marketing & Legal
- Landing page, Terms, Privacy, Refund Policy
- robots.txt, sitemap.xml
- Custom 404 page

### Removed
- Filament admin (fully removed, Phase 5 complete)

---

## 4. Issues Found

### 🔴 High — Fix before production launch

| # | Issue | Details | Fix |
|---|-------|---------|-----|
| H1 | **`public/storage` not linked** | `php artisan about` shows `public/storage NOT LINKED`. Admin branding logo/favicon uploads will **404** in browser. | Run `php artisan storage:link` on every deploy |
| H2 | **Mail driver = `log`** | Emails (verification, payment success/fail, grace) go to log file, not inbox. | Configure SMTP (Brevo/Mailgun/SES) in production `.env` |
| H3 | **Razorpay live keys + webhook** | Billing works in tests; live payment cycle needs manual QA with real/test Razorpay dashboard. | Set `RAZORPAY_KEY`, `RAZORPAY_SECRET`, `RAZORPAY_WEBHOOK_SECRET`; register webhook URL |
| H4 | **No production Docker / deploy config** | Only `docker-compose.dev.yml` exists. No production compose, Supervisor, or Nginx config in repo. | VPS setup per `DOCKER_GUIDE.md` + Week 6 checklist |
| H5 | **Queue worker must run 24/7** | Scans, webhooks, emails depend on `php artisan queue:work`. If worker stops, scans won't log. | Supervisor on VPS (or Horizon later) |

### 🟡 Medium — Should fix soon

| # | Issue | Details | Fix |
|---|-------|---------|-----|
| M1 | **`.env.example` outdated** | Still shows `sqlite`, `QUEUE_CONNECTION=database`, `CACHE_STORE=database`. Actual dev uses **pgsql + redis**. | Update `.env.example` to match Docker dev + document production vars |
| M2 | **Mark payment refunded = placeholder** | Admin can mark locally; **Razorpay refund API not called**. | Wire Razorpay refund API when going live |
| M3 | **Google Safe Browsing fails open** | Without `GOOGLE_SAFE_BROWSING_API_KEY`, malicious URLs may pass if not in blocked_domains list. | Add API key for production; or accept risk + rely on abuse reports |
| M4 | **Admin user detail via direct URL** | `/admin/users/{id}` works for admin accounts even though they don't appear in Users list. Linked from Admin Team — OK by design, but regular admin could open admin profiles. | Optional: restrict `UserController@show` to role=user unless super_admin |
| M5 | **Missing transactional emails** | Only PaymentSuccess, PaymentFailed, SubscriptionGrace exist. No welcome, renewal reminder, frozen notice emails. | Add before launch if promised in marketing |
| M6 | **Mobile responsive pass** | Not done (Week 6 pending in MVP plan). | Manual QA on phone for user + admin UI |
| M7 | **GeoLite2 database** | Optional; scan geo data collected but UI not built. Graceful skip if missing — OK for MVP. | Download DB before launch if you want country stats later |

### 🟢 Low — Nice to have

| # | Issue | Details |
|---|-------|---------|
| L1 | Plan limit change warning dialog | Not implemented on admin plan edit |
| L2 | Laravel Horizon | Not installed; using `queue:work` manually |
| L3 | MRR/revenue trend charts | Only basic dashboard charts; 6–12 month trends post-MVP |
| L4 | Razorpay refund sync | Local mark only |
| L5 | Bulk admin actions | Ban/pause bulk — post-MVP |
| L6 | `APP_DEBUG=true` | Fine for local; must be `false` in production |

---

## 5. Security Checklist

| Check | Status |
|-------|--------|
| Admin routes protected (`EnsureAdmin`) | ✅ |
| Super admin routes (`EnsureSuperAdmin`) | ✅ |
| Banned users blocked at login + middleware | ✅ |
| CSRF on web routes (webhook exempt, signature verified) | ✅ |
| Razorpay webhook signature verification | ✅ |
| Webhook idempotency | ✅ |
| User QR policies (can't view others' QRs) | ✅ |
| Impersonation audit logged | ✅ |
| `composer audit` clean | ✅ |
| Filament/Livewire removed (attack surface reduced) | ✅ |

**No critical security holes found in automated review.** Manual penetration testing still recommended before launch.

---

## 6. Local Dev Checklist (Your Machine)

Ensure these are running for full functionality:

```powershell
# Terminal 1 — Docker DB + Redis
docker compose -f docker-compose.dev.yml up -d

# Terminal 2 — Laravel
php artisan serve

# Terminal 3 — Queue (IMPORTANT — scans & webhooks)
php artisan queue:work --queue=scans,default

# Terminal 4 — Vite (dev)
npm run dev

# One-time if branding uploads 404:
php artisan storage:link
```

**.env should have:**
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5434
DB_DATABASE=qr_saas
DB_USERNAME=qr_user
DB_PASSWORD=qr_secret_local

REDIS_HOST=127.0.0.1
QUEUE_CONNECTION=redis
CACHE_STORE=redis
SESSION_DRIVER=redis
```

---

## 7. MVP Plan Status (Week 6)

| Item | Status |
|------|--------|
| Weeks 1–5 core features | ✅ Done |
| Custom React admin (Filament removed) | ✅ Done |
| Landing + legal + SEO | ✅ Done |
| Mobile responsive pass | ❌ Pending |
| Production Docker + VPS deploy | ❌ Pending |
| Database backups cron | ❌ Pending |
| Soft launch | ❌ Pending |

---

## 8. Recommended Next Steps (Priority Order)

1. **`php artisan storage:link`** — fix branding uploads locally now  
2. **Update `.env.example`** — match pgsql + redis Docker setup  
3. **Configure SMTP** — test verification + payment emails in real inbox  
4. **Razorpay test mode E2E** — subscribe → webhook → active → cancel (manual)  
5. **Mobile QA** — user dashboard, QR create, admin panel on phone  
6. **VPS deploy** — Postgres, Redis, Nginx, Supervisor, SSL, cron for scheduler  
7. **Production `.env`** — `APP_DEBUG=false`, live Razorpay, mail, backups  

---

## 9. File / Architecture Health

| Item | Count / Notes |
|------|----------------|
| Admin routes | ~45 routes under `/admin/*` |
| React admin pages | 25+ pages under `resources/js/pages/admin/` |
| Feature tests | 163 tests |
| Filament remnants | None found ✅ |
| Git uncommitted work | Large — many features not committed (normal during dev) |

---

## 10. Conclusion

**Verdict: ✅ Healthy MVP — ready for polish & deploy phase, not yet ready for public launch.**

The codebase is well-structured with strong automated test coverage. No blocking bugs were found in tests or build. The main gaps are **operational** (mail, storage link, queue worker in prod, VPS deploy) and **launch checklist** items from `qr_mvp_plan.md` Week 6.

---

*Report generated from automated scan. Re-run after major changes or before production deploy.*
