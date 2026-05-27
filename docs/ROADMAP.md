# Build Roadmap

Phased delivery plan. Tick boxes as we go. Each phase ends with: migrations run cleanly, Pest green, Pint clean, page renders in the browser.

## Phase 0 — Docs ✅
- [x] PRD.md
- [x] ARCHITECTURE.md
- [x] DATABASE_SCHEMA.md
- [x] ROADMAP.md (this file)

## Phase 1 — Brand & public layout
- [ ] Set `APP_NAME="Fifiawoto Foundation"`.
- [ ] Pick brand palette (provisional: warm earth / gold + deep green) in `resources/css/app.css`.
- [ ] Create `resources/views/layouts/site.blade.php` (light theme).
- [ ] Header partial: logo, nav (Home/About/Events/Volunteer/Contact), Donate (primary) + Get Involved (secondary).
- [ ] Footer partial: summary blurb, quick links, social icons, newsletter form (stubbed).
- [ ] Wire routes: `home`, `about`, `events.index`, `events.show`, `volunteer`, `contact` to placeholder Blade pages.
- [ ] Replace `welcome.blade.php` route with new home placeholder.
- [ ] Smoke Pest test: every route returns 200 and contains the brand name.

## Phase 2 — Home page
- [ ] Hero (headline, subhead, intro, CTAs). Background = static image first, slider Livewire component second.
- [ ] Impact dashboard (`App\Services\ImpactMetricService` returns hardcoded values for now, sourced from DB once seeders exist).
- [ ] Founder tribute section.
- [ ] Programs grid (5 cards).
- [ ] Media gallery preview (4–8 most recent `MediaItem` rows, fallback to placeholder).
- [ ] Instagram strip (placeholder cards until model exists).
- [ ] Testimonials carousel.
- [ ] Featured event card with progress bar (placeholder until events exist).
- [ ] Newsletter signup (real Livewire form → `newsletter_subscribers`).

## Phase 3 — About page
- [ ] Legacy, mission, vision, values.
- [ ] Global presence — static SVG map for now (Leaflet upgrade later).
- [ ] Leadership lists (Board of Directors + Advisors) from a config/seeder.

## Phase 4 — Events module (public)
- [ ] Migration + model + factory + seeder for `events` and `event_images`.
- [ ] `events.index` listing.
- [ ] `events.show` detail with goal/raised progress.
- [ ] Donation + volunteer CTAs (linking to `/donate?event=…` and `/volunteer?event=…`).

## Phase 5 — Volunteer module
- [ ] Migration + model for `volunteer_applications`.
- [ ] `VolunteerInterest`, `VolunteerAvailability`, `VolunteerStatus` enums.
- [ ] Livewire form with validation.
- [ ] Confirmation email (queued).
- [ ] Pest test: happy path + validation errors.

## Phase 6 — Contact + Newsletter
- [ ] Migration + model `contact_messages`.
- [ ] Livewire form + validation.
- [ ] Migration + model `newsletter_subscribers`.
- [ ] Newsletter signup component reused in home, about, footer.
- [ ] Confirmation email (queued).

## Phase 7 — Admin auth, roles, layout
- [ ] Add `role`, `phone`, `avatar_path` columns to `users` (migration + cast).
- [ ] `App\Enums\UserRole`.
- [ ] `EnsureUserHasRole` middleware.
- [ ] `routes/admin.php` mounted under `/admin` with `auth` + role gate.
- [ ] Admin layout: extend `layouts/app.blade.php`, swap sidebar nav for full admin menu (role-aware).
- [ ] Seeder creates a `super_admin@example.test` / `password` for dev.

## Phase 8 — Admin dashboard
- [ ] Impact overview cards.
- [ ] Recent activities feed (latest from each activity table — polymorphic-lite).
- [ ] Quick action buttons.
- [ ] Pest test: dashboard renders for super admin, 403s for non-admin.

## Phase 9 — Beneficiaries module
- [ ] Migrations + models + factories for `beneficiaries`, `beneficiary_folders`, `beneficiary_documents`, `beneficiary_applications`, `beneficiary_application_documents`, `beneficiary_timeline_entries`.
- [ ] Enums: `SupportCategory`, `SupportStatus`, `ApplicationStatus`, `TimelineEntryType`.
- [ ] Filesystem disk `beneficiaries` (private).
- [ ] Livewire table: list, filter by status/category, search.
- [ ] Livewire `BeneficiaryForm` (create + edit).
- [ ] Profile page with folders panel + timeline.
- [ ] Folder CRUD + file upload Livewire components.
- [ ] Signed-URL download route for documents.
- [ ] Public beneficiary application page (`/apply-for-support`).
- [ ] Admin review screen — approve converts to `Beneficiary`.
- [ ] Pest tests: storage isolation, signed URL gating, application → conversion flow.

## Phase 10 — Events admin
- [ ] Livewire CRUD.
- [ ] Image manager (multi-upload, sort, caption).
- [ ] Per-event fundraising tracker widget.

## Phase 11 — Donations admin
- [ ] Livewire CRUD for manual donation records.
- [ ] Filters: event, date range.
- [ ] Export CSV (built-in) + PDF (later — optional).

## Phase 12 — Volunteers admin
- [ ] Application list + review actions (approve/reject + assign role).
- [ ] Active volunteer roster.

## Phase 13 — Testimonials + Media gallery admin
- [ ] Testimonials CRUD (featured toggle, sort).
- [ ] Media items CRUD (multi-upload, category filter).

## Phase 14 — Instagram + Reports + Users
- [ ] Instagram admin: manual paste flow first; API auto-pull behind feature flag.
- [ ] Scheduled command `instagram:sync` (no-op until configured).
- [ ] Reports page: pick category + date range, render summary table, export CSV/PDF.
- [ ] User management: list, invite, role assign, deactivate.

## Phase 15 — Polish
- [ ] Accessibility sweep (alt text, focus rings, contrast).
- [ ] Responsive QA at 360 / 768 / 1280 / 1920.
- [ ] Pint clean across repo.
- [ ] `php artisan test --compact` green.
- [ ] README updated with run instructions.

## Definition of done per phase

1. Migrations run cleanly on a fresh DB (`php artisan migrate:fresh --seed`).
2. New routes covered by at least one Pest feature test.
3. Pint clean (`vendor/bin/pint --dirty --format agent`).
4. UI verified at the relevant Herd URL.
5. Roadmap checkbox ticked + brief note in commit message.
