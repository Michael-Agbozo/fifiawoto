# Production deployment

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
php artisan db:seed --class=DatabaseSeeder --force   # only on first deploy

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
