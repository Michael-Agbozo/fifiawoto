# Architecture

## Stack

| Layer        | Choice                                                                                |
| ------------ | ------------------------------------------------------------------------------------- |
| Runtime      | PHP 8.4 (Laravel Herd local), PHP ^8.2 required                                       |
| Framework    | Laravel 12 (streamlined `bootstrap/app.php` structure)                                |
| UI           | Livewire 4 + Flux UI Free + Alpine.js (only where Livewire alone isn't enough)        |
| Styling      | Tailwind CSS v4 (`resources/css/app.css`)                                             |
| Auth         | Laravel Fortify (already scaffolded — login, register, 2FA, password reset, profile) |
| DB           | SQLite for dev (default); MySQL/Postgres in production                                |
| Queues       | `database` driver in dev; Redis in prod                                              |
| Storage      | `local` disk for dev; S3 (or compatible) in prod for beneficiary docs + media        |
| Mail         | `log` in dev; Postmark/SES/Resend in prod                                            |
| Testing      | Pest 4 (feature-first, browser tests where helpful)                                   |
| Code style   | Laravel Pint (`vendor/bin/pint --dirty --format agent`)                              |

## Two app surfaces, one Laravel project

| Surface     | Route prefix    | Layout                              | Auth                         |
| ----------- | --------------- | ----------------------------------- | ---------------------------- |
| Public site | `/`             | `resources/views/layouts/site.blade.php` (new) | Guest                |
| Admin       | `/admin`        | reuse `layouts/app.blade.php` (Flux sidebar) | `auth` + `role:*`           |

Both surfaces share `App\Models\User`. We **do not** create separate auth guards — instead a `role` enum on the user record + middleware enforces admin access. Login/register stay on Fortify defaults; admin links live behind `auth + role`.

## Roles (App\Enums\UserRole)

- `super_admin` — everything
- `foundation_staff` — beneficiaries, events, donations (read/write)
- `volunteer_coordinator` — volunteers + events read-only
- `media_manager` — media gallery + testimonials only
- `volunteer` — public-side authenticated volunteer (future)
- `beneficiary` — public-side authenticated beneficiary applicant (future)

Permissions live in policies (`App\Policies\*`). Middleware `EnsureUserHasRole` gates admin routes. Avoid Spatie Permission for now — keep the surface small until role count grows past ~6.

## Directory plan

```
app/
  Enums/                       UserRole, SupportCategory, SupportStatus, ApplicationStatus, …
  Http/
    Controllers/Site/          Public controllers (Home, About, Events, Volunteer, Contact)
    Controllers/Admin/         Admin controllers (mostly thin — Livewire does work)
    Middleware/EnsureUserHasRole.php
    Requests/                  Form requests for every public form
  Livewire/
    Site/                      Public Livewire components (HeroSlider, NewsletterForm, …)
    Admin/                     Admin Livewire components (BeneficiaryTable, EventForm, …)
  Models/                      Beneficiary, BeneficiaryDocument, BeneficiaryFolder, Event,
                               Donation, Volunteer, VolunteerApplication, Testimonial,
                               MediaItem, InstagramPost, NewsletterSubscriber, ContactMessage,
                               BeneficiaryTimelineEntry
  Policies/                    One per model that needs auth checks
  Services/                    UploadService, DonationService, ImpactMetricService, …

resources/views/
  layouts/
    site.blade.php             new — public layout (header, footer)
    app.blade.php              existing — admin layout (Flux sidebar)
  site/                        new — public page Blades
    home.blade.php
    about.blade.php
    events/{index,show}.blade.php
    volunteer.blade.php
    contact.blade.php
    partials/{header,footer,nav}.blade.php
  admin/                       new — admin page Blades (mostly Livewire mounts)

routes/
  web.php                      public routes
  admin.php                    new — admin routes, included from web.php behind middleware
  settings.php                 existing — Fortify settings
```

## Public vs admin layouts

**Public** (`layouts/site.blade.php`): light theme by default (`<html>` without `.dark`), top nav with logo + nav links + Donate/Get Involved CTAs, sticky on scroll, footer with summary + quick links + social + newsletter form. Use Flux components where they help, raw Tailwind where Flux is overkill (hero, cards).

**Admin** (`layouts/app.blade.php`): keep Flux sidebar pattern from the starter kit; replace the single "Dashboard" link with the full admin menu (Dashboard, Beneficiaries, Events, Donations, Volunteers, Testimonials, Media, Instagram, Reports, Users, Settings). Sidebar respects role permissions — hide what the user can't access.

## File storage

Use a dedicated `beneficiaries` disk pointing to a private directory. In prod this maps to a private S3 bucket. Media gallery uses a separate `media` disk that **can** be public.

```php
// config/filesystems.php (added)
'beneficiaries' => [
    'driver' => 'local',
    'root' => storage_path('app/private/beneficiaries'),
    'visibility' => 'private',
],
'media' => [
    'driver' => 'local',
    'root' => storage_path('app/public/media'),
    'url' => env('APP_URL').'/storage/media',
    'visibility' => 'public',
],
```

Beneficiary docs are streamed via a signed-route `BeneficiaryDocumentController@show` — never served as raw public URLs.

## Donations

Phase 1 records donations manually (admin form). Online giving (Stripe/Paystack) is a follow-on once a payment processor is chosen — keep `Donation::$payment_method` + `external_reference` so we can backfill cleanly.

## Background work

- Newsletter subscribe + Contact form → queued email.
- Instagram sync → scheduled job (`app/Console/Commands/SyncInstagram.php`) hourly.
- Document virus-scan → queued job per upload; status surfaces on document row.

## Image handling

- Media uploads pass through `intervention/image` (add later) for resize + WebP generation.
- Hero slider images: store original + 1920w/1280w/768w variants.

## Testing strategy

- Feature tests cover every public form submission (volunteer, contact, newsletter, beneficiary application).
- Feature tests cover every admin CRUD (happy path + auth gating).
- Browser tests (Pest 4 browser) for the home page's hero + impact numbers + nav.
- Factories + seeders for every model so we can scrub the DB and rebuild realistic demo data with `php artisan migrate:fresh --seed`.

## Out of scope (for now)

- Beneficiary self-service portal (PRD lists the public application form only).
- Donor accounts / recurring giving.
- Multi-language site (English only at launch — see PRD §8).
