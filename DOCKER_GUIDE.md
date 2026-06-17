# Docker Guide — Is Project Ke Liye (Simple Language)

> **Kis ke liye hai ye file?**  
> Agar aapne Docker pehle kabhi use nahi kiya, to ye file step-by-step samjhayegi — pehle basic concept, phir is QR project me kaise use hota hai (local), aur baad me production me kya alag hoga.

---

## 1. Docker Kya Hai? (Bilkul Simple)

Socho aapke computer par **alag-alag machines chal rahi hon**, lekin wo asli machine nahi — chhoti virtual boxes hain.

**Example:**
- Aapka Laravel app = aapke Windows par directly chal raha hai (`php artisan serve`)
- PostgreSQL database = Docker ke andar chal raha hai (alag box)
- Redis (cache + queue) = Docker ke andar chal raha hai (alag box)

**Fayda:**
- PostgreSQL install karne ki zaroorat nahi — Docker image se turant mil jata hai
- Team me sab ka same setup — "mere PC par chal raha tha" problem kam hoti hai
- Production me bhi same type ka setup use kar sakte ho

**Is project me abhi kya Docker me hai?**

| Service    | Docker me? | Kahan chal raha hai?        |
|-----------|------------|-----------------------------|
| Laravel   | ❌ Nahi    | Aapke PC par (PHP direct)   |
| PostgreSQL| ✅ Haan    | Docker container            |
| Redis     | ✅ Haan    | Docker container            |
| Node/Vite | ❌ Nahi    | Aapke PC par (`npm run dev`)|

> Matlab: **Poora app Docker me nahi hai** — sirf database aur Redis Docker me hain. Ye local development ke liye common aur easy approach hai.

---

## 2. 4 Important Words (Yaad Rakh Lo)

### Image
- **Recipe / template** — jaise "PostgreSQL 16 ka ready package"
- Download hoti hai pehli baar jab `docker compose up` chalate ho

### Container
- Image se bana **running box**
- Jaise recipe (image) se actual dish (container) ban gayi
- Is project me: `qr_postgres` aur `qr_redis` containers hain

### Volume
- Container ke andar ka data **permanent save** karne ke liye
- Container band/delete ho jaye to bhi data safe rehta hai
- Is project me: `qr_pgdata` (database files), `qr_redisdata` (redis data)

### Port
- Container ko bahar se connect karne ka **darwaza**
- Format: `"5434:5432"` matlab:
  - **5434** = aapke PC par (Laravel yahan connect karta hai)
  - **5432** = container ke andar PostgreSQL ka default port

---

## 3. Pehle Docker Install Karo (Windows)

1. Download: [Docker Desktop for Windows](https://www.docker.com/products/docker-desktop/)
2. Install karo, restart karo agar maange
3. Docker Desktop open karo — neeche status **"Docker Desktop is running"** hona chahiye
4. Terminal (PowerShell) me check karo:

```powershell
docker --version
docker compose version
```

Dono commands version dikhayen to theek hai.

---

## 4. Is Project Ka Docker File

File: `docker-compose.dev.yml`

```yaml
name: qr_saas_dev          # Project ka naam Docker me

services:
  postgres:                # Service 1: Database
    image: postgres:16-alpine
    container_name: qr_postgres
    ports:
      - "5434:5432"          # PC:5434 → container:5432
    environment:
      POSTGRES_DB: qr_saas
      POSTGRES_USER: qr_user
      POSTGRES_PASSWORD: qr_secret_local
    volumes:
      - qr_pgdata:/var/lib/postgresql/data   # Data yahan save

  redis:                   # Service 2: Cache + Queue
    image: redis:7-alpine
    container_name: qr_redis
    ports:
      - "6379:6379"
    volumes:
      - qr_redisdata:/data

volumes:
  qr_pgdata:               # Named volumes (permanent storage)
  qr_redisdata:
```

**Simple samjho:**
- `postgres` = database server
- `redis` = fast memory store (session, cache, queue ke liye)
- `5434` aur `6379` = aapke `.env` file me yehi ports likhe hain

---

## 5. Local Development — Roz Ke Steps

### Step 1: Project folder me jao

```powershell
cd D:\rohit\qr_project
```

### Step 2: Docker containers start karo

```powershell
docker compose -f docker-compose.dev.yml up -d
```

**Kya hota hai?**
- `-f docker-compose.dev.yml` = ye specific file use karo
- `up` = containers start karo
- `-d` = background me chalao (detached — terminal block nahi hoga)

**Pehli baar:** images download hongi — thoda time lagega (internet chahiye).

### Step 3: Check karo sab chal raha hai

```powershell
docker compose -f docker-compose.dev.yml ps
```

Aapko kuch aisa dikhna chahiye:

```
NAME          STATUS    PORTS
qr_postgres   running   0.0.0.0:5434->5432/tcp
qr_redis      running   0.0.0.0:6379->6379/tcp
```

### Step 4: Laravel setup (sirf pehli baar ya fresh DB par)

```powershell
php artisan migrate
php artisan db:seed
```

### Step 5: App chalao (Docker ke **baahar** — normal tarike se)

**Terminal 1 — Laravel:**
```powershell
php artisan serve
```

**Terminal 2 — Queue worker (scans + webhooks ke liye zaroori):**
```powershell
php artisan queue:work --queue=scans,default
```

**Terminal 3 — Frontend (development me):**
```powershell
npm run dev
```

Browser: `http://localhost:8000`

---

## 6. `.env` File Ka Connection (Important)

Aapki `.env` file already Docker se match karti hai:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1      # localhost = aapka PC, jahan port 5434 open hai
DB_PORT=5434           # Docker ne postgres ko 5434 par expose kiya
DB_DATABASE=qr_saas
DB_USERNAME=qr_user
DB_PASSWORD=qr_secret_local

REDIS_HOST=127.0.0.1
REDIS_PORT=6379

SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
CACHE_STORE=redis
```

**Flow samjho:**
```
Laravel (localhost:8000)
    ↓
127.0.0.1:5434  →  Docker postgres container
127.0.0.1:6379  →  Docker redis container
```

Laravel ko pata nahi ki andar Docker hai — use bas port dikhta hai.

---

## 7. Useful Commands (Roz Kaam Aayenge)

### Containers band karna (data safe rehta hai)

```powershell
docker compose -f docker-compose.dev.yml stop
```

### Containers dubara start karna

```powershell
docker compose -f docker-compose.dev.yml start
```

### Containers hata dena (data volume me safe rehta hai)

```powershell
docker compose -f docker-compose.dev.yml down
```

### Sab kuch delete + fresh database (⚠️ SAVDHAN — saara DB data udd jayega)

```powershell
docker compose -f docker-compose.dev.yml down -v
```

`-v` = volumes bhi delete — **sirf jab poora fresh start chahiye ho**.

### Logs dekhna (error debug ke liye)

```powershell
# Postgres logs
docker logs qr_postgres

# Redis logs
docker logs qr_redis

# Live follow (real-time)
docker logs -f qr_postgres
```

### Container ke andar jana (advanced, kabhi-kabhi)

```powershell
# PostgreSQL shell
docker exec -it qr_postgres psql -U qr_user -d qr_saas
```

Andar `\dt` likho = tables list. Bahar aane ke liye: `\q`

---

## 8. Roz Ka Short Routine (Cheat Sheet)

**Subah kaam shuru:**
```powershell
cd D:\rohit\qr_project
docker compose -f docker-compose.dev.yml up -d
php artisan serve
# alag terminal: php artisan queue:work --queue=scans,default
# alag terminal: npm run dev
```

**Shaam ko band:**
```powershell
# Ctrl+C se artisan/npm band karo
docker compose -f docker-compose.dev.yml stop
```

**PC restart ke baad:**
```powershell
docker compose -f docker-compose.dev.yml start
```

---

## 9. Common Problems + Fix

### Problem: `port is already allocated` (5434 ya 6379 busy)

Koi aur program same port use kar raha hai.

```powershell
# Kaun use kar raha hai dekhna (Windows)
netstat -ano | findstr :5434
netstat -ano | findstr :6379
```

Fix: ya to wo program band karo, ya `docker-compose.dev.yml` me port badlo (e.g. `"5435:5432"`) aur `.env` me `DB_PORT=5435` update karo.

---

### Problem: `connection refused` — Laravel DB connect nahi kar pa raha

Checklist:
1. Docker Desktop chal raha hai?
2. `docker compose -f docker-compose.dev.yml ps` — STATUS `running` hai?
3. `.env` me `DB_PORT=5434` hai?
4. `php artisan config:clear` chalao

---

### Problem: Docker Desktop start nahi ho raha

- WSL 2 enable karo (Docker Desktop install ke time usually khud setup hota hai)
- Windows restart
- Docker Desktop → Settings → Resources check karo

---

### Problem: `migrate` fail — database does not exist

Containers start karo pehle:
```powershell
docker compose -f docker-compose.dev.yml up -d
php artisan migrate
```

---

### Problem: Queue / scans kaam nahi kar rahe

Redis chal raha hai check karo + queue worker chalao:
```powershell
docker compose -f docker-compose.dev.yml ps
php artisan queue:work --queue=scans,default
```

---

## 10. Local vs Production — Abhi vs Baad Me

### Abhi (Local — aap jo kar rahe ho)

```
[ Aapka Windows PC ]
├── PHP + Laravel     ← directly
├── Node + Vite       ← directly
└── Docker
    ├── PostgreSQL    ← port 5434
    └── Redis         ← port 6379
```

- Password simple hai (`qr_secret_local`) — theek hai local ke liye
- `APP_DEBUG=true` — errors screen par dikhte hain
- Mail `log` driver par — email actually nahi jati, file me log hoti hai

### Baad Me (Production — VPS par)

Production me usually **poora stack** Docker me ya server par properly setup hota hai:

```
[ VPS Server (e.g. DigitalOcean, Hetzner) ]
├── Nginx             ← public web server (HTTPS)
├── PHP-FPM + Laravel ← app code
├── PostgreSQL        ← strong password, backup daily
├── Redis             ← queue + cache
├── Queue worker      ← systemd/supervisor se hamesha chale
├── SSL certificate   ← Let's Encrypt (HTTPS)
└── Domain            ← yourdomain.com
```

**Local se production me kya badlega:**

| Cheez | Local (abhi) | Production (baad me) |
|-------|--------------|----------------------|
| Docker file | `docker-compose.dev.yml` | `docker-compose.prod.yml` (banani hogi) |
| APP_DEBUG | `true` | `false` |
| APP_URL | `http://localhost` | `https://yourdomain.com` |
| DB password | simple | strong random password |
| Mail | `log` | SMTP (Brevo/Mailgun) |
| Razorpay | test keys | live keys |
| ngrok | webhook testing | real domain webhook |
| Backups | optional | daily `pg_dump` cron |
| SSL | nahi | HTTPS zaroori |

> **Note:** Production Docker setup abhi project me pending hai (`qr_mvp_plan.md` — Week 6). Jab launch karenge tab alag `docker-compose.prod.yml` + Nginx config banegi.

---

## 11. Docker Desktop Me Kya Dikhega?

Docker Desktop open karo → **Containers** tab:

- `qr_postgres` — green dot = running
- `qr_redis` — green dot = running

Yahan se bhi Start/Stop/Logs kar sakte ho — terminal ki zaroorat nahi.

**Volumes** tab me:
- `qr_saas_dev_qr_pgdata` — database ki files
- `qr_saas_dev_qr_redisdata` — redis data

Inhe randomly delete mat karo — database udd sakta hai.

---

## 12. Quick Glossary (English → Simple Hindi)

| Term | Matlab |
|------|--------|
| `docker compose up` | Containers start karo |
| `docker compose down` | Containers band + hata do |
| `docker compose ps` | Kaun se containers chal rahe hain |
| `docker logs` | Container ki output / errors |
| `docker exec` | Running container ke andar command chalao |
| `-d` | Background me chalao |
| `-v` (with down) | Volumes bhi delete (data loss!) |
| Image | Template / package |
| Container | Chalta hua instance |
| Volume | Permanent data storage |

---

## 13. Ek Line Me Summary

> **Docker = alag services (DB, Redis) ko bina install kiye chalane ka tareeka.**  
> Is project me aap Laravel normal chalate ho, bas pehle `docker compose -f docker-compose.dev.yml up -d` se database aur Redis ready kar lo.

---

## 14. Agla Kadam (Jab Production Ready Ho)

Week 6 me ye karenge:
1. `docker-compose.prod.yml` — full production stack
2. Nginx + SSL setup
3. Environment variables production values
4. Daily database backup script
5. Deploy steps VPS par

Abhi ke liye local routine yaad rakho:

```powershell
docker compose -f docker-compose.dev.yml up -d    # Docker start
php artisan serve                                  # App start
php artisan queue:work --queue=scans,default       # Queue start
npm run dev                                        # Frontend start
```

---

*File location: `D:\rohit\qr_project\DOCKER_GUIDE.md`*  
*Project: QR Manager SaaS — Laravel 12 + PostgreSQL + Redis*
