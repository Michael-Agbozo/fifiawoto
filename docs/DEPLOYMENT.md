# Production deployment

This document covers two deployment paths: **Coolify (current production setup)** and a traditional VPS/Forge setup.

---

## Coolify (Nixpacks) — current setup

The app is deployed via [Coolify](https://coolify.io) using Nixpacks as the build system. The config lives in `nixpacks.toml` and `start.sh`.

### How it works

```
nixpacks.toml        → build phase (npm + composer install + npm run build)
start.sh             → runtime phase (php-fpm, artisan bootstrap, nginx)
nginx.conf           → in-container Nginx (port 80, proxied by Coolify's Traefik)
```

### Required environment variables (Coolify → Production)

```ini
APP_NAME=Fifiawoto Foundation
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...          # generate with: php artisan key:generate --show
APP_URL=https://your-domain.duckdns.org

# Do NOT set LIVEWIRE_ASSET_URL — leave it out entirely
# Do NOT set FORCE_HTTPS — it does nothing in Laravel

DB_CONNECTION=mysql
DB_HOST=10.0.2.1            # Coolify internal host
DB_PORT=3306
DB_DATABASE=fifiawoto
DB_USERNAME=...
DB_PASSWORD=...

CACHE_STORE=database
QUEUE_CONNECTION=database
SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true

LOG_CHANNEL=stderr
LOG_LEVEL=error

MAIL_FROM_ADDRESS=hello@fifiawoto.org
MAIL_FROM_NAME=Fifiawoto Foundation
ADMIN_NOTIFICATION_EMAIL=hello@fifiawoto.org

# Traefik is the only way into the container, so trust the hop in front of it.
TRUSTED_PROXIES=*

NIXPACKS_BUILD_CMD=composer install --no-dev --optimize-autoloader --ignore-platform-reqs && npm install && npm run build
NIXPACKS_NODE_VERSION=20
NIXPACKS_PHP_ROOT_DIR=/app/public
NIXPACKS_PHP_FALLBACK_PATH=/index.php
```

### HTTPS behind Coolify's reverse proxy

Coolify runs Traefik in front of the container. The container's Nginx only listens on port 80. To make Laravel generate correct HTTPS URLs:

1. **`config/trustedproxy.php`** — `TRUSTED_PROXIES` (read by Laravel's `TrustProxies` middleware) tells Laravel which proxies may set `X-Forwarded-*` headers. Set `TRUSTED_PROXIES=*` on Coolify (the container is only reachable through Traefik) so Laravel honours `X-Forwarded-Proto: https`.

> **`TRUSTED_PROXIES`** — comma-separated proxy IPs/CIDRs (e.g. `10.0.0.0/8,172.16.0.0/12`), or `*` to trust the immediate caller. **Leave it unset** when the app is reachable directly from the internet: trusting every client lets anyone spoof their IP via `X-Forwarded-For`, which defeats rate limiting and pollutes audit logs. Laravel Cloud is detected automatically, so leave it unset there. Behind Cloudflare *without* another proxy, list [Cloudflare's IP ranges](https://www.cloudflare.com/ips/) instead of `*` (or make sure the origin only accepts traffic from Cloudflare).
2. **`app/Providers/AppServiceProvider.php`** — `URL::forceScheme('https')` runs on `production` and `staging` environments as a belt-and-suspenders fallback.
3. **`nginx.conf`** — passes `HTTP_X_FORWARDED_PROTO` to PHP-FPM so Laravel's middleware can read it.

Do **not** set `LIVEWIRE_ASSET_URL` — Livewire v4 uses published static assets (written to `public/vendor/livewire/` at container start by `start.sh`) and derives the correct HTTPS URL automatically once `trustProxies` is active.

### First admin user (one-time only)

After the first successful deploy, create the admin accounts by running the seeder once via Coolify's terminal:

```bash
# Coolify → your app → Terminal
OWNER_EMAIL=owner@fifiawoto.org OWNER_PASSWORD=StrongPassword \
ADMIN_EMAIL=admin@fifiawoto.org ADMIN_PASSWORD=StrongPassword \
php artisan db:seed --class=AdminUserSeeder --force
```

Or add the four env vars temporarily, redeploy, then remove them. The seeder uses `firstOrCreate` — it is safe to run multiple times and will never overwrite an existing user's password.

### Redeployment

Push to `main` — Coolify auto-deploys. The `start.sh` sequence on every container start:

```
php-fpm (background)
chmod storage + bootstrap/cache
php artisan storage:link --force
php artisan vendor:publish --tag=livewire:assets --force
php artisan config:cache
php artisan route:cache
php artisan migrate --force
nginx (foreground)
```

No downtime steps are needed for typical deploys. Migrations run automatically.

### Preview deployments (dev branch)

Preview deployments use the same `start.sh` but the `Preview Deployments Environment Variables` in Coolify. Set `APP_ENV=staging`, `APP_DEBUG=true`. Do **not** set `LIVEWIRE_ASSET_URL`.

---

## VPS / Laravel Forge — traditional setup

This document is the operator's checklist for getting the application running in production on a Linux VPS or a managed PHP host (Laravel Forge, Ploi, Vapor — all work).

---

## 1. Server requirements

| Item        | Minimum                                       |
| ----------- | --------------------------------------------- |
| OS          | Ubuntu 22.04 LTS or newer (Debian works too)  |
| PHP         | 8.2+ with extensions: `bcmath`, `curl`, `gd`, `intl`, `mbstring`, `mysql` or `pgsql`, `xml`, `zip`, `fileinfo`, `tokenizer`, `redis` (if using Redis queues) |
| Web server  | Nginx (recommended) or Caddy                  |
| Database    | MySQL 8 / MariaDB 10.6+ / Postgres 14+        |
| Cache/queue | Redis 6+ (for queue + cache + sessions)       |
| Node        | 20+ (build step only — not needed at runtime) |
| Composer    | 2.x                                           |
| Memory      | 1 GB minimum, 2 GB recommended                |

---

## 2. First deploy

```bash
# As the deploy user
git clone <repo-url> /var/www/fifiawoto
cd /var/www/fifiawoto

composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
# Edit .env (see section 3 below)
php artisan storage:link
php artisan migrate --force
# Create the Owner + Admin accounts (never run DatabaseSeeder in production —
# it seeds demo accounts with known passwords and refuses to run when APP_ENV=production)
OWNER_EMAIL=owner@fifiawoto.org OWNER_PASSWORD='<strong password>' \
ADMIN_EMAIL=admin@fifiawoto.org ADMIN_PASSWORD='<strong password>' \
php artisan db:seed --class=AdminUserSeeder --force

npm ci
npm run build

# Cache config + routes + views for performance
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

---

## 3. Required `.env` keys

```ini
APP_NAME="Dadaa Fifiawoto Nyamadi Foundation"
APP_ENV=production
APP_KEY=                                # set by `php artisan key:generate`
APP_DEBUG=false                         # MUST be false in production
APP_URL=https://fifiawoto.org
APP_TIMEZONE=Africa/Accra

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=fifiawoto_prod
DB_USERNAME=fifiawoto
DB_PASSWORD=                            # strong, generated

# Sessions, cache, queues — Redis recommended
SESSION_DRIVER=redis
CACHE_STORE=redis
QUEUE_CONNECTION=redis
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=                         # strong

# Mail (Postmark / SES / Resend — pick one)
MAIL_MAILER=postmark
MAIL_FROM_ADDRESS="hello@fifiawoto.org"
MAIL_FROM_NAME="${APP_NAME}"
POSTMARK_TOKEN=

# Notifications
ADMIN_NOTIFICATION_EMAIL=hello@fifiawoto.org

# Social
FOUNDATION_INSTAGRAM_URL=https://www.instagram.com/the_fifiawotofoundation/
FOUNDATION_FACEBOOK_URL=
FOUNDATION_YOUTUBE_URL=
FOUNDATION_INSTAGRAM_REEL_URL=

# File uploads — use the public disk's S3 driver in cloud deploys
FILESYSTEM_DISK=public
# For S3:
# FILESYSTEM_DISK=s3
# AWS_ACCESS_KEY_ID=
# AWS_SECRET_ACCESS_KEY=
# AWS_DEFAULT_REGION=eu-west-1
# AWS_BUCKET=fifiawoto-uploads
# AWS_USE_PATH_STYLE_ENDPOINT=false
```

**Never commit `.env`.** Use your host's secret manager (Forge environment editor, Vapor secrets, etc.).

---

## 4. Web server

### Nginx (Forge default)

Forge provisions this automatically. If hand-rolling, the doc root is `/var/www/fifiawoto/public` and the main location block proxies PHP-FPM:

```nginx
server {
    listen 443 ssl http2;
    server_name fifiawoto.org www.fifiawoto.org;

    root /var/www/fifiawoto/public;
    index index.php;

    ssl_certificate     /etc/letsencrypt/live/fifiawoto.org/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/fifiawoto.org/privkey.pem;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Long-cache hashed Vite assets
    location ^~ /build/ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    client_max_body_size 10M;       # supports the 5 MB image upload cap with headroom
}
```

---

## 5. Queue worker (REQUIRED for emails)

`EventVolunteerInvitation` and other mailables implement `ShouldQueue`. **Without a worker, emails will never send.**

Systemd unit `/etc/systemd/system/fifiawoto-queue.service`:

```ini
[Unit]
Description=Fifiawoto queue worker
After=redis-server.service

[Service]
Type=simple
User=forge
WorkingDirectory=/var/www/fifiawoto
ExecStart=/usr/bin/php artisan queue:work --queue=default --sleep=1 --tries=3 --max-time=3600
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl enable --now fifiawoto-queue
```

Or use **Supervisor** if Forge/Ploi prefers it — both are equivalent. On Forge, use the "Daemons" UI; that's the same thing.

---

## 6. Scheduler

```bash
# /etc/cron.d/fifiawoto
* * * * * forge cd /var/www/fifiawoto && php artisan schedule:run >> /dev/null 2>&1
```

(Even if no scheduled tasks exist today, Laravel ships defaults like model pruning that benefit from this hook.)

---

## 7. Deploying updates

```bash
cd /var/www/fifiawoto
php artisan down --secret=<deploy-token>

git pull
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

php artisan queue:restart        # picks up new code in the worker
php artisan up
```

On Forge / Ploi this is one click — they generate the script above by default.

---

## Protecting beneficiary documents

Documents are encrypted with `APP_KEY` when uploaded. **If `APP_KEY` is lost, those files can't be recovered**, so back it up. When rotating it, keep the old key in `APP_PREVIOUS_KEYS`.

After deploying, encrypt documents uploaded before this was added:

```bash
php artisan beneficiaries:secure-documents --dry-run
php artisan beneficiaries:secure-documents
```

To scan uploads for viruses, install ClamAV, make sure `clamd` is running, and set:

```
UPLOAD_SCANNER=clamav
CLAMAV_SOCKET=unix:///var/run/clamav/clamd.ctl
```

With scanning on, uploads are refused if `clamd` can't be reached. The scheduler and queue worker must also be running: pledge reminders and newsletters depend on both.

---

## 8. Backups

| What                  | How                                                                                   |
| --------------------- | ------------------------------------------------------------------------------------- |
| Database              | Daily `mysqldump` to off-site storage. Forge has a built-in toggle.                   |
| `storage/app/public/` | Daily rsync or S3 sync — contains every uploaded image, document, and beneficiary file. |
| `.env`                | Stored in your secret manager + a sealed offline copy.                                |

Recommend 30-day retention minimum.

---

## 9. Monitoring + observability

| Layer  | Recommendation                                                                |
| ------ | ----------------------------------------------------------------------------- |
| Errors | Sentry or Bugsnag (`config/logging.php` — add a Sentry channel)               |
| Uptime | Better Stack / Pingdom / UptimeRobot on `/up` (Laravel ships a health endpoint) |
| Logs   | Tail with `php artisan pail` in dev; ship `storage/logs/laravel.log` to Papertrail/Datadog in prod |

---

## 10. Hardening checklist

- [ ] `APP_DEBUG=false` in `.env`
- [ ] `APP_ENV=production`
- [ ] Database credentials use a per-app user (not root)
- [ ] Force HTTPS — `AppServiceProvider::boot()` should call `URL::forceScheme('https')` in production (or rely on the reverse proxy)
- [ ] Rate limits on login + contact form remain enabled (`config/fortify.php` `limiters` + `RateLimiter::for('contact-form')`)
- [ ] Self-service registration disabled in `config/fortify.php` (already done — see [SECURITY.md](SECURITY.md))
- [ ] HTTPS-only cookies (`SESSION_SECURE_COOKIE=true`, `SESSION_SAME_SITE=lax`)
- [ ] File-upload type + size validators left in place (see admin Livewire components)
- [ ] `php artisan optimize` run after every deploy

---

## 11. Rollback

Standard zero-drama recipe:

```bash
cd /var/www/fifiawoto
git reset --hard <previous-sha>
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate:rollback --step=1   # only if the bad deploy added migrations
php artisan optimize:clear
php artisan optimize
php artisan queue:restart
```

If a deploy broke the schema, restore the most recent DB snapshot before running anything else.
