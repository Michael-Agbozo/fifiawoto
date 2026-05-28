# Dadaa Fifiawoto Nyamadi Foundation

The official web platform for the **Dadaa Fifiawoto Nyamadi Foundation** — a charitable foundation honouring the legacy of Madam Dadaa Fifiawoto Nyamadi-Adabla through sustainable programmes for women, children, and vulnerable communities across Ghana, Togo, Benin, and the United States.

The application is a Laravel 12 + Livewire 4 monorepo containing both the public-facing marketing site and a private admin dashboard for foundation staff to manage beneficiaries, donations, events, volunteers, media, and more.

---

## At a glance

- **Public site** at `/` — home, about, events, donate, volunteer, contact, testimonials, media gallery, legal pages
- **Admin dashboard** at `/admin` — role-gated CMS for every public surface, plus operational tools (inbox, reports, user management, system logs)
- **Granular permission system** — six roles × granular CRUD permissions per resource ([see PERMISSIONS.md](docs/PERMISSIONS.md))
- **Pest 4 test suite** — 249+ feature tests covering site rendering, admin authorisation, mail, validation, and database state
- **Brand palette** — white surfaces · deep navy structure · red primary accent, with Playfair Display headings and Roboto body

---

## Quick start

```bash
composer setup           # installs PHP+JS deps, .env, key, migrations, builds assets
composer dev             # starts artisan serve + queue + pail + vite concurrently
```

Default seeded accounts (password `password`):

| Role                  | Email                              |
| --------------------- | ---------------------------------- |
| Super Admin (Owner)   | `superadmin@fifiawoto.test`        |
| Admin                 | `admin@fifiawoto.test`             |
| Foundation Staff      | `staff@fifiawoto.test`             |
| Volunteer Coordinator | `volunteer-coord@fifiawoto.test`   |
| Media Manager         | `media@fifiawoto.test`             |

See [docs/SETUP.md](docs/SETUP.md) for a detailed walkthrough including system requirements, PHP version, mail driver, file storage, and queue setup.

---

## Documentation

All project documentation lives under `docs/`:

| File | What it covers |
| --- | --- |
| [PRD.md](docs/PRD.md) | Product requirements — features, user stories, scope |
| [ROADMAP.md](docs/ROADMAP.md) | Phased delivery plan |
| [ARCHITECTURE.md](docs/ARCHITECTURE.md) | Stack, layout, key conventions |
| [DATABASE_SCHEMA.md](docs/DATABASE_SCHEMA.md) | Tables, columns, relationships |
| [SETUP.md](docs/SETUP.md) | Local development setup |
| [DEPLOYMENT.md](docs/DEPLOYMENT.md) | Production deployment guide |
| [ADMIN_GUIDE.md](docs/ADMIN_GUIDE.md) | End-user guide for the admin dashboard |
| [PERMISSIONS.md](docs/PERMISSIONS.md) | Roles + permission registry reference |
| [SECURITY.md](docs/SECURITY.md) | Threat model, authorisation rules, hardening |
| [STYLE_GUIDE.md](docs/STYLE_GUIDE.md) | Brand palette, typography, components, animations |
| [CONTRIBUTING.md](docs/CONTRIBUTING.md) | Dev workflow, conventions, testing |
| [TROUBLESHOOTING.md](docs/TROUBLESHOOTING.md) | Common errors + fixes |

---

## Tech stack

| Layer        | Choice                                                                            |
| ------------ | --------------------------------------------------------------------------------- |
| Runtime      | PHP ^8.2 (PHP 8.4 in Herd dev)                                                    |
| Framework    | Laravel 12 (streamlined `bootstrap/app.php`)                                      |
| UI           | Livewire 4 (single-file components) + Alpine.js                                   |
| Styling      | Tailwind CSS v4 with custom `@theme` block                                        |
| Auth         | Laravel Fortify (login, 2FA, password reset — public registration disabled)       |
| Database     | SQLite (dev) · MySQL / Postgres (prod)                                            |
| Queues       | `database` driver (dev) · Redis (prod)                                            |
| Storage      | `public` disk for assets (avatars, hero images, beneficiary docs)                 |
| Mail         | `log` driver (dev) · Postmark / SES / Resend (prod)                               |
| PDF          | `barryvdh/laravel-dompdf` for report exports                                      |
| Testing      | Pest 4                                                                            |
| Lint         | Laravel Pint                                                                      |

---

## Common commands

```bash
php artisan test --compact              # run the full test suite
vendor/bin/pint --dirty --format agent  # auto-format changed PHP files
npm run dev                             # Vite dev server with HMR
npm run build                           # production CSS + JS bundle
php artisan migrate:fresh --seed        # rebuild DB with seeders
php artisan view:clear                  # clear compiled Blade after editing components
```

---

## License

Proprietary — © Dadaa Fifiawoto Nyamadi Foundation. All rights reserved.
