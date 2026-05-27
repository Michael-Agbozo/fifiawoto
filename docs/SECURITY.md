# Security model

This document is the engineer's reference for the platform's security posture: what the threat model is, what's enforced where, and how to extend it without regressing.

For role/permission specifics see [PERMISSIONS.md](PERMISSIONS.md).

---

## Threat model

| Actor                          | What they can attempt                                    | What stops them                                                                                  |
| ------------------------------ | -------------------------------------------------------- | ------------------------------------------------------------------------------------------------ |
| Anonymous web visitor          | Read public pages, submit contact/volunteer/newsletter   | Rate limiting, input validation, hCaptcha (planned), strict mail-header handling                  |
| Authenticated low-privilege admin (e.g. Media Manager) | Escalate to delete data they can only view | Per-method `abort_unless(canDo(...))` checks in every Livewire mutation                          |
| Authenticated admin (any role) | Mass-assign sensitive fields like `role` / `permissions` | `User::$fillable` excludes `role` + `permissions`; explicit `forceFill()` only in audited paths   |
| Hostile user pasting URLs      | Stored XSS via fields like media `path`                  | Validation `regex:/^(https?:\/\/|\/|[\w\-]+\/)/i` on URL fields rejects `javascript:`/`data:`     |
| Anyone                         | Self-register an admin account                           | Fortify registration feature disabled in `config/fortify.php`; admin invite is the only path     |
| Existing user                  | Brute-force a password                                   | Login throttler in `FortifyServiceProvider::configureRateLimiting()`: 5 attempts/min/email+IP    |

---

## Authentication

- Driven by **Laravel Fortify** with the standard email/password + Remember-me + Forgot-password flow.
- **Two-factor (TOTP)** is supported via Fortify; enable it on `Settings → Two-factor`.
- **Public registration is disabled** — `Features::registration()` is removed from `config/fortify.php`. The `/register` route returns 404. The only way to create an account is the admin invite at `/admin/users`.
- **Login throttling**: 5 attempts per minute per (email, IP) tuple via Fortify's rate limiter.
- **Session driver** in prod: Redis. Cookie `HttpOnly`, `SameSite=Lax`, `Secure` when `APP_URL` is HTTPS.

---

## Authorisation — defence in depth

Three layers, all required. See [PERMISSIONS.md § How permissions are checked](PERMISSIONS.md#how-permissions-are-checked).

1. **Route middleware** — `bootstrap/app.php` defines a `role:` alias backed by `EnsureUserHasRole`. Routes like `/admin/users` are gated by `->middleware('role:owner,super_admin')`. Coarse-grained; first line of defence.

2. **UI gating** — Blade renders buttons only when the user has the permission:
   ```blade
   @if (auth()->user()?->canDo('events', 'delete'))
       <button wire:click="delete({{ $event->id }})">Delete</button>
   @endif
   ```
   Cosmetic; do not rely on it for security.

3. **Server-side method guards** — every Livewire method that mutates data starts with:
   ```php
   abort_unless(auth()->user()?->canDo('events', 'delete'), 403);
   ```
   This is the real security boundary. Bypassable UI doesn't matter because the server rejects the action.

---

## Mass-assignment safety

- `App\Models\User::$fillable` deliberately **excludes** `role`, `permissions`, and `email_verified_at`. Any code that needs to set these must use `forceFill()` or `forceCreate()` and be auditable.
- Only three places do this:
  1. `database/seeders/DatabaseSeeder::seedUser()` — initial seed accounts
  2. `database/factories/UserFactory::newModel()` — test factories (override bypasses fillable, never reachable from a request)
  3. `resources/views/components/admin/⚡users.blade.php` — the admin invite + role-edit + permissions-edit methods, each preceded by `abort_unless(canDo('users','update'|'create'|'delete'), 403)`
- Other models use plain `$fillable` whitelists; review any new model's `$fillable` against its mutation paths before merging.

---

## Input validation

- **Public forms** (contact, volunteer application, beneficiary application, newsletter) — full Livewire `#[Validate]` rules + database `unique`/`exists` constraints + rate limits.
- **Admin forms** — same `#[Validate]` rules. File uploads enforce `image|mimes:jpg,jpeg,png,webp|max:5120` (5 MB) for photos and `file|mimes:pdf|max:10240` (10 MB) for documents.
- **URL fields** — `MediaItem.path` and `poster_path` validate with `regex:/^(https?:\/\/|\/|[\w\-]+\/)/i` to reject `javascript:`, `data:`, and other dangerous schemes. Instagram permalink fields validate against `regex:/^https:\/\/(www\.)?instagram\.com\//i` to reject non-Instagram URLs.

---

## File uploads

- Stored on the `public` disk (`storage/app/public/`, symlinked to `public/storage/`) — never user-controlled paths.
- File names are generated by `->store('folder', 'public')` — Laravel hashes the name, so user-supplied filenames cannot path-traverse.
- Old files are deleted from storage when a record is updated or removed (audited in `delete()` methods on leaders, testimonials, and event mailables).
- MIME types validated server-side via the `image` / `mimes:` validation rules — extension-spoofing is rejected.

---

## CSRF

- All state-changing requests go through Livewire, which automatically attaches CSRF tokens to its requests.
- Traditional `<form method="POST">` paths (Fortify login) include `@csrf` directives by default.

---

## Mail security

- **Header injection** — Symfony Mailer's `Address` parser rejects CR/LF in addresses; the contact form's `email` validation rule (`required|email`) blocks newline-bearing strings before they reach the mailer. Defence in depth.
- **`Mailable::replyTo()`** uses an array of `Address` objects, not string concatenation.
- **No remote URLs in mail bodies** are constructed from user input — every URL is a route helper.
- **Bulk send** (event invitations to all volunteers) is queued via `Mail::queue(...)` so a hostile / large list cannot timeout the request — see `events.blade.php` save flow.

---

## Rate limiting

| Surface              | Limit                                                                                                  |
| -------------------- | ------------------------------------------------------------------------------------------------------ |
| Login                | 5 attempts/minute per (email + IP)                                                                     |
| 2FA challenge        | 5/minute per session                                                                                   |
| Contact form         | 3 submissions/minute per IP (configured in the Livewire component)                                     |
| Volunteer application| 3 submissions/minute per IP                                                                            |
| Newsletter signup    | 6 submissions/minute per IP                                                                            |

---

## Audit trail

- **System logs** (Owner-only at `/admin/system-logs`) record sensitive admin actions: role changes, permission grants, account deletes, sensitive content edits.
- **Beneficiary timeline** records every change to a beneficiary record with the acting user + timestamp.
- **Inbox replies** record the staff member who replied + timestamp, never lost when the original message is archived.

---

## Outbound email + queues

- All transactional mail is queued (`implements ShouldQueue`) so failures don't block the user's request. Queue worker must be running in prod — see [DEPLOYMENT.md § 5](DEPLOYMENT.md#5-queue-worker-required-for-emails).
- Bounce + complaint handling is delegated to the configured mail driver (Postmark/SES/Resend). Subscribers who hard-bounce should be marked `unsubscribed_at`.

---

## Dependencies

- `composer audit` is run as part of CI (planned). `npm audit` likewise.
- Dependabot / Renovate suggested for prod deployments.

---

## What still needs hardening

Tracked in [ROADMAP.md](ROADMAP.md), but at a glance:

- [ ] **Content Security Policy** header — once added, replace any remaining inline `onerror=` handlers with Alpine equivalents.
- [ ] **hCaptcha** on public forms (contact, volunteer, beneficiary).
- [ ] **Sentry / Bugsnag** error reporting wired in.
- [ ] **GDPR/data-export** endpoint for newsletter + beneficiary records (right to access).
- [ ] **Audit log indexing** — `system_logs` may grow large; partition or archive after 12 months.

---

## Reporting a vulnerability

If you discover a security issue, **do not open a public issue**. Email `security@fifiawoto.org` (or the tech lead directly) with:

- A description of the issue
- Steps to reproduce
- Any proof-of-concept code
- Your name (optional — for crediting)

We aim to acknowledge within 48 hours.
