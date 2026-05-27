# Local development setup

This guide gets you from a fresh clone to a running dev server with seeded data.

---

## 1. System requirements

| Tool      | Version |
| --------- | ------- |
| PHP       | ^8.2 (8.4 recommended) with `bcmath`, `intl`, `mbstring`, `xml`, `zip`, `gd`, `sqlite3` |
| Composer  | 2.x     |
| Node.js   | 20.x or later (for Vite + Tailwind v4) |
| npm       | 10.x    |
| SQLite    | Bundled with PHP — no separate install needed for dev |

**macOS users:** [Laravel Herd](https://herd.laravel.com) covers the PHP/Composer/Node trio in one installer and is the assumed dev environment.

---

## 2. Clone and install

```bash
git clone <repo-url> fifiawoto
cd fifiawoto
composer setup
```

`composer setup` is defined in `composer.json` and runs the full bootstrap:

1. `composer install`
2. Copies `.env.example` → `.env` (if missing)
3. `php artisan key:generate`
4. `php artisan migrate --force`
5. `npm install`
6. `npm run build`

If you want to run those steps manually instead, that's the order.

---

## 3. Seed sample data

```bash
php artisan db:seed
```

Seeds five admin user accounts (one per role), 11 leadership team members, sample events, volunteers, beneficiaries, and an Instagram highlight set so the public site has content out-of-the-box. Default password for all seeded admins is `password`.

| Role                  | Email                              |
| --------------------- | ---------------------------------- |
| Super Admin (Owner)   | `superadmin@fifiawoto.test`        |
| Admin                 | `admin@fifiawoto.test`             |
| Foundation Staff      | `staff@fifiawoto.test`             |
| Volunteer Coordinator | `volunteer-coord@fifiawoto.test`   |
| Media Manager         | `media@fifiawoto.test`             |

---

## 4. Run the app

```bash
composer dev
```

This is a one-shot dev command (defined in `composer.json`) that spins up four parallel processes via `concurrently`:

| Process | What it does                                |
| ------- | ------------------------------------------- |
| server  | `php artisan serve`                         |
| queue   | `php artisan queue:listen --tries=1`        |
| logs    | `php artisan pail` — real-time log stream   |
| vite    | `npm run dev` — Vite HMR for CSS + JS       |

When started under Herd you can also visit `https://fifiawoto.test/` (Herd auto-creates the cert + DNS); under `php artisan serve` it's `http://127.0.0.1:8000/`.

---

## 5. Environment variables that matter

`.env` defaults work for SQLite + log mail. You may want to override:

```ini
APP_URL=https://fifiawoto.test
APP_TIMEZONE=Africa/Accra

# Mail (defaults to log driver — emails go to storage/logs/laravel.log)
MAIL_MAILER=log
MAIL_FROM_ADDRESS="hello@fifiawoto.org"
MAIL_FROM_NAME="${APP_NAME}"

# Where admin notifications (new beneficiary apps, contact form, etc.) are sent
ADMIN_NOTIFICATION_EMAIL=hello@fifiawoto.org

# Social profiles surfaced in the footer + IG strip
FOUNDATION_INSTAGRAM_URL=https://www.instagram.com/the_fifiawotofoundation/
FOUNDATION_INSTAGRAM_REEL_URL=  # paste the latest reel URL here for the about-page video CTA
FOUNDATION_FACEBOOK_URL=
FOUNDATION_YOUTUBE_URL=
```

---

## 6. Switching to MySQL or Postgres

The project runs on SQLite by default for zero-config dev. To switch:

```ini
DB_CONNECTION=mysql        # or pgsql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=fifiawoto
DB_USERNAME=root
DB_PASSWORD=
```

Then `php artisan migrate:fresh --seed`.

---

## 7. Useful one-liners

```bash
# Run the test suite (compact output)
php artisan test --compact

# Lint dirty files (auto-fix)
vendor/bin/pint --dirty --format agent

# Clear all caches (handy after editing config, blade components, or theme)
php artisan optimize:clear

# Just clear compiled views (most common after Blade edits)
php artisan view:clear

# Rebuild assets after Tailwind theme tweaks
npm run build

# Tail logs without running the full dev stack
php artisan pail
```

---

## 8. Editor setup tips

- **VS Code** — install Laravel Blade Snippets, Tailwind CSS IntelliSense (with `experimental.classRegex` set to also pick up `@class([])` and `class:` attributes), and PHP Intelephense
- **PHPStorm** — built-in Laravel/Blade/Pint support; enable "Blade" in Tailwind plugin settings
- **Pint on save** — point your editor to run `vendor/bin/pint --dirty` on save for the project

---

## 9. Troubleshooting setup

See [TROUBLESHOOTING.md](TROUBLESHOOTING.md) — covers permission errors, missing PHP extensions, port conflicts, and the most common Vite + Livewire wiring issues.
