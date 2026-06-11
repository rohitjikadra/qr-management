# QR Generator SaaS

## Final Product, Business & Technical Blueprint (V1.1)

---

# Project Goal

Build a scalable SaaS platform where users can:

* Generate QR Codes
* Manage QR Codes
* Track QR Performance
* Optimize QR Campaigns

The business is NOT a QR Generator.

The business is a QR Management Platform.

---

# Core Project Principles

Every future decision must be evaluated using:

1. Feature Value
2. Revenue Impact
3. Why Us
4. Defensibility
5. Distribution / Growth
6. Operational Cost

Priority:

Feature Value
→ Revenue Impact
→ Why Us
→ Defensibility
→ Distribution
→ Operational Cost

If a feature fails the first three filters, it should normally be rejected.

---

# Business Positioning

Most QR websites offer:

Generate QR → Download → Done

We offer:

Generate → Manage → Track → Optimize

This creates recurring value and subscription opportunities.

---

# Target Customers

## Individuals

* Personal links
* Digital cards
* Social profiles

## Creators

* Instagram
* YouTube
* Portfolio

## Small Businesses

* Menus
* Product Catalogs
* Marketing Campaigns

## Agencies

* Client QR Management
* Analytics

## Enterprises

* API
* White Label
* Team Management

---

# Revenue Model

## Free Plan

Purpose:

Acquire users and generate traffic.

Features:

* Static QR
* Limited Dynamic QR
* PNG Download
* Basic Dashboard
* Ads Enabled

Revenue Sources:

* Ads
* Affiliate Promotions

---

## Pro Monthly

Price:

₹199–299/month

Features:

* Dynamic QR
* Unlimited Scans
* Analytics
* Custom Logo
* Custom Colors
* No Ads

---

## Pro Yearly

Price:

₹1,999–2,999/year

Features:

Everything from Pro Monthly.

Purpose:

Increase annual cash flow.

---

## Future Business Plan

Features:

* Team Members
* API
* Bulk QR
* White Label
* Webhooks

Not required for initial launch.

---

# Product Roadmap

## Phase 1 (MVP)

Authentication

* Register
* Login
* Forgot Password
* Email Verification

Dashboard

* User Dashboard
* QR Overview

QR Types

* URL QR
* WhatsApp QR
* Email QR
* Phone QR
* WiFi QR
* vCard QR
* Text QR

QR Management

* Create
* Edit
* Delete
* Download
* Active / Paused

Analytics

* Total Scans
* Daily Scans
* Timeline

Billing

* Free Plan
* Pro Monthly
* Pro Yearly

Admin Panel

* User Management
* QR Management
* Revenue Overview
* Subscription Management

---

## Phase 2

Advanced Analytics

* Country Tracking
* City Tracking
* Device Tracking
* Browser Tracking

Premium Features

* Custom Logo
* Custom Colors
* Dynamic QR History
* Custom Domains

Business Features

* Lead Capture QR
* QR Health Monitor
* Email Alerts

---

## Phase 3

Agency Features

* API Access
* Bulk QR Generation
* CSV Import
* CSV Export
* Team Accounts
* White Label
* Webhooks

---

# Why Users Will Choose Us

1. Better Analytics
2. Dynamic QR Management
3. QR Health Monitoring
4. Lead Capture QR
5. Clean Dashboard
6. Affordable Pricing

---

# Defensibility Strategy

Competitors can copy QR generation.

Harder to copy:

* Analytics Engine
* Lead Capture System
* QR Health Monitoring
* Reporting Engine
* API Ecosystem

These become long-term moats.

---

# Technology Stack

Approach

* Single codebase, single deploy (monolith)

Frontend

* Inertia.js
* React
* TypeScript
* Tailwind CSS
* shadcn/ui

Backend

* Laravel 12
* Laravel Queue
* Filament (Admin Panel)

Database

* PostgreSQL

Cache / Queue

* Redis

Storage

* Local Storage (V1)
* S3 Compatible Storage (Future)

Server

* VPS

Deployment

* Docker

Cloudflare

* Future Integration

Marketing Website

* V1: Served from same Laravel app
* Future: Separate static site (Next.js/Astro) if SEO demands

---

# Architecture

Model

* Single Tenant

Data Isolation

* user_id based isolation

Reason

* Simpler
* Faster
* Cheaper
* Scalable enough for early stage

---

# User Roles

## User

Can:

* Manage own QR
* View analytics
* Manage subscription

## Admin

Can:

* View all users
* View all QR codes
* Manage plans
* Manage subscriptions

## Super Admin

Can:

* Manage entire platform
* View revenue
* Configure settings
* Feature controls

---

# URL Strategy

Marketing Website

yourdomain.com

Application

app.yourdomain.com

QR Redirect

yourdomain.com/q/{slug}

Example:

yourdomain.com/q/A7K92XZP

Never expose database IDs.

---

# QR Strategy

Store Data

Do NOT store generated QR images.

Store QR content only.

Generate:

* PNG
* SVG

on demand.

Benefits:

* Lower storage cost
* Better scalability

---

# Redirect Architecture (Hot Path)

Flow for GET /q/{slug}:

1. Redis lookup: qr:{slug} → destination_url + status
2. Cache miss → fetch from DB → store in Redis
3. Send 302 redirect immediately
4. Push scan event to Queue (async)
5. Queue worker writes to qr_scan_events
   (GeoIP lookup + device parsing happens in worker)

Rules:

* No DB writes in redirect path
* Cache invalidation is event-based (on QR edit/pause), not TTL-based
* Paused/expired QR → branded "QR inactive" page (never 404)
* Target redirect latency: under 50ms

---

# Database Structure

users

* id
* name
* email
* password
* role
* status (active / banned)
* country
* last_login_at
* email_verified_at
* deleted_at (soft delete)

plans

* id
* name
* price
* billing_cycle
* limits (JSON)

limits JSON example:

* dynamic_qr: 2
* static_qr: -1 (unlimited)
* scans_per_month: 100
* analytics_history_days: 30
* custom_logo: false
* custom_colors: false
* svg_download: false
* ads: true

All plan limits are admin-configurable. No code change needed.
A centralized PlanLimitService enforces limits before every action.

subscriptions

* id
* user_id
* plan_id
* gateway_subscription_id
* starts_at
* expires_at
* cancelled_at
* status

payments

* id
* user_id
* subscription_id
* gateway
* gateway_payment_id
* invoice_number
* amount
* currency
* status
* meta (JSON — raw gateway response)

qr_codes

* id
* user_id
* name
* slug
* type
* content (JSON — type-specific data: wifi, vcard, etc.)
* destination_url
* is_dynamic
* status
* design_options (JSON — colors, logo, error correction)
* scan_count (denormalized counter, incremented by queue worker)
* last_scanned_at (for QR Health)
* expires_at (nullable)
* created_at / updated_at
* deleted_at (soft delete)

qr_scan_events

* id
* qr_code_id
* country
* city
* device_type
* os
* browser
* referrer (nullable)
* ip_hash (never store raw IP)
* scanned_at

Scale plan:

* V1: normal table + index on (qr_code_id, scanned_at)
* Later: monthly partitioning by scanned_at (PostgreSQL native)
* Retention: Free = 30 days, Pro = full history (paid differentiator)

qr_daily_stats (aggregation table)

* qr_code_id
* date
* scans
* top_country
* top_device

Populated by nightly job. Dashboard graphs read from this table;
raw events used only for detailed drill-down.

settings

* key
* value

audit_logs

* id
* user_id
* action
* entity_type
* entity_id
* created_at

---

# Payment Architecture

Current Gateway

* Razorpay Subscriptions API (UPI Autopay + cards, auto-recurring)

Future Gateways

* Stripe
* PayPal

Rules:

* Database must remain gateway-agnostic
* Payment confirmation via webhooks only (never trust redirect callback)
* Fallback option if recurring has friction: manual renewal
  (payment link + reminder emails 7/3/1 days before expiry)

---

# Subscription Lifecycle Rules

Core principle:

The end-user scanning a QR must NEVER see a broken QR.
Billing problems are between us and our customer,
not their audience.

Rules:

* Pro expires → redirects keep working, editing + analytics locked
* Grace period: 7 days (reminder emails on day 1, 3, 7)
* After grace: QRs frozen (redirect works, management locked)
* 90 days unpaid → optional cleanup
* Downgrade (Pro → Free): extra dynamic QRs frozen, not deleted
* Refund policy: 7-day no-questions refund

---

# Abuse Prevention & Security (Required in MVP)

Why critical:

One blacklisted domain kills ALL users' QR codes.

Measures:

1. URL validation on create/edit — Google Safe Browsing API (free)
2. Blocklist: known phishing domains + URL shorteners
3. Rate limiting: QR creation per user/IP + redirect endpoint
4. "Report this QR" link on inactive page
5. Email verification mandatory before creating dynamic QR
6. Admin abuse queue: flagged QRs reviewed in admin panel
7. (Future, not V1) Optional interstitial page on free plan redirects

---

# Global Expansion Strategy

Launch Market

* India

Future

* Global

Database must support:

* Multiple currencies
* Multiple gateways
* Global users

from Day 1.

---

# Admin Dashboard Metrics

Users

* Total Users
* Active Users
* Paid Users

Revenue

* MRR
* Revenue Growth

Product

* Total QR Codes
* Total Scans
* Popular QR Types

Subscriptions

* Free Users
* Pro Users

---

# Success Metrics

Business

* Monthly Revenue
* Conversion Rate
* Churn Rate

Product

* QR Created
* Active QR Codes
* Total Scans

Growth

* Organic Traffic
* Referral Traffic
* Landing Page Conversion

---

# Golden Rule

Never build a feature because competitors have it.

Build only if it:

* Solves a real problem
* Increases revenue
* Improves differentiation
* Strengthens the moat
* Supports long-term SaaS growth

---

Status

Business Model: Locked
Revenue Strategy: Locked
Technology Stack: Locked (Laravel + Inertia + React + Filament)
Architecture: Locked
Redirect Architecture: Locked
Roles: Locked
Subscription Model: Locked
Subscription Lifecycle Rules: Locked
Payment Gateway: Locked (Razorpay Subscriptions)
Analytics Strategy: Locked
Database Direction: Locked
Abuse Prevention Strategy: Locked
Global Expansion Path: Locked

Version: 1.2 Final
