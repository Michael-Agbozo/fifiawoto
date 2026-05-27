# Contributing

Workflow, conventions, and testing for engineers working on this codebase.

---

## Branching

- `main` — production-ready code
- `dev` — integration branch (the team's primary working branch)
- Feature branches: `feat/<short-slug>` off `dev`
- Bug fix branches: `fix/<short-slug>` off `dev`
- Hotfixes: `hotfix/<short-slug>` off `main`, merged to both

---

## Commits

Conventional, lowercase, imperative:

```
add testimonial filter to admin testimonials index
fix events publish loop blocking the request
refactor permission registry into a single source of truth
remove unused fortify register action
```

Keep commits small and focused. Multi-file changes that touch unrelated concerns should be separate commits. Reviewers will ask you to split.

---

## Pull requests

Every PR should:

1. **Target `dev`** (unless it's a hotfix to `main`)
2. **Pass CI** — Pest tests + Pint formatting
3. **Include tests** for new behaviour — `tests/Feature/...`
4. **Update docs** when touching anything user-facing or breaking conventions
5. **Have a clear description** — what changed, why, and how to verify

Use the [`pr-template`](https://github.com/) workflow conventions (placeholder — link to the team's actual template once it's added).

---

## Local checks before pushing

```bash
php artisan test --compact              # 249+ tests pass
vendor/bin/pint --dirty --format agent  # auto-format dirty files
npm run build                           # production CSS+JS builds clean
```

Run all three before opening a PR. CI is canonical, but catching issues locally saves a round-trip.

---

## Code style

### PHP

- **PSR-12** + Laravel conventions, enforced by [Pint](https://laravel.com/docs/12.x/pint)
- Run `vendor/bin/pint --dirty --format agent` from the project root
- Don't use `vendor/bin/pint` without `--dirty` — it'll reformat the entire codebase and your diff becomes unreadable
- Type-hint everything (params + returns). Use `void` for actions that don't return anything.

### Blade

- Use `<x-component>` syntax for reusable UI; pass data via props
- Avoid logic in views — derive in the controller/Livewire component and pass values down
- Prefer `{{ $value }}` (escaped) over `{!! $value !!}` — only use `{!! !!}` for content you've explicitly sanitised or built yourself
- Class lists go on multiple lines when the line gets noisy; prefer `@class([])` for conditional classes

### Tailwind

- Use design tokens (`text-brand-900`, `bg-cream-100`) from the `@theme` block — never raw hex values in views
- Keep new utilities behind the established breakpoints (`sm:`, `lg:`, `xl:`); don't introduce custom breakpoints
- For complex one-offs, write a short comment explaining why the inline arbitrary value (`[3rem]`, `[0.18em]`) is needed

### JavaScript

- The site has very little custom JS. The `resources/js/app.js` covers reveal animations + counters
- Prefer Alpine.js (`x-data`, `x-show`, `x-transition`) for new interactive bits — no semicolons, `let` not `const` (per Livewire conventions)
- Don't add npm dependencies casually; Livewire already bundles Alpine + the directives we need (`x-intersect`)

---

## File conventions

| Item                            | Path                                                              |
| ------------------------------- | ----------------------------------------------------------------- |
| Public site pages               | `resources/views/site/*.blade.php`                                |
| Public site components          | `resources/views/components/site/*.blade.php`                     |
| Site Livewire components        | `resources/views/components/site/⚡{name}.blade.php`              |
| Admin pages                     | `resources/views/admin/{section}/index.blade.php`                 |
| Admin Livewire components       | `resources/views/components/admin/⚡{name}.blade.php`             |
| Admin shared components         | `resources/views/components/admin/{name}.blade.php` (no ⚡)       |
| Models                          | `app/Models/`                                                     |
| Enums                           | `app/Enums/`                                                      |
| Mail                            | `app/Mail/`                                                       |
| Email templates                 | `resources/views/emails/`                                         |
| Migrations                      | `database/migrations/`                                            |
| Factories                       | `database/factories/`                                             |
| Seeders                         | `database/seeders/`                                               |
| Tests                           | `tests/Feature/{Admin\|Site}/`                                    |

The `⚡` (lightning-bolt) filename prefix marks **single-file Livewire components** — class + view in one file. They auto-register at `admin.{name}` or `site.{name}` (kebab-case).

---

## Livewire conventions

### Single-file components

```php
<?php

use Livewire\Component;
use Livewire\Attributes\Validate;

new class extends Component
{
    #[Validate('required|string|max:120')]
    public string $name = '';

    public function save(): void
    {
        abort_unless(auth()->user()?->canDo('resource', 'create'), 403);

        $data = $this->validate();
        // ... persist
    }
}; ?>

<div>
    <form wire:submit="save">
        <!-- view -->
    </form>
</div>
```

### Mutation methods MUST start with `abort_unless`

Every admin Livewire method that creates/updates/deletes data **must** start with:

```php
abort_unless(auth()->user()?->canDo('{resource}', '{action}'), 403);
```

If you forget, the `AdminAuthorizationGuardTest` will fail. This is non-negotiable — see [SECURITY.md](SECURITY.md#authorisation--defence-in-depth).

### Validation

- Use `#[Validate]` PHP attributes when possible (preferred — visible at the property declaration)
- Use array syntax for complex rules: `#[Validate(['required', 'string', 'regex:/.../'])]`
- Pair validation with database constraints (`unique`, `exists`) and DB-level limits (text vs varchar)

### Computed properties

Use `#[Computed]` for derived data that the view needs:

```php
#[Computed]
public function items()
{
    return Item::query()->ordered()->paginate(10);
}
```

Access in the view as `$this->items`.

---

## Testing

- Stack: **Pest 4** with the Laravel plugin
- Run: `php artisan test` or `vendor/bin/pest`
- New features need feature tests under `tests/Feature/{Admin,Site}/`
- Database isolation: tests use `RefreshDatabase` via `pest.php` boilerplate — every test gets a clean SQLite

### What to test

| Layer            | What to test                                                                                |
| ---------------- | ------------------------------------------------------------------------------------------- |
| Public page      | Renders 200, contains expected copy (`assertSee`), public form submits + persists           |
| Admin page       | Renders 200 for the right role, 403/redirect for the wrong role                             |
| Livewire mutation| Required permission via the guard test; validation rules; database state after `call('save')` |
| Mail             | `Mail::fake()` + `Mail::assertQueued(...)` (mailables implement `ShouldQueue`)              |
| Permission       | If you add a new mutation method, add a row to `AdminAuthorizationGuardTest`                |

### Useful test helpers

```php
$this->actingAs(User::factory()->superAdmin()->create());
Storage::fake('public');
Mail::fake();
$this->seed(LeaderSeeder::class);
Livewire::test('admin.testimonials')->call('startCreate')->set('quote', '...')->call('save');
```

---

## Adding a new resource

(Recap from [PERMISSIONS.md](PERMISSIONS.md#adding-a-new-resource).)

1. Migration → model → factory → seeder
2. Add resource to `app/Support/Permissions.php`
3. Grant default keys to relevant roles in `app/Enums/UserRole.php`
4. Add route under `routes/admin.php` with the right `role:` middleware
5. Add link to the sidebar in `resources/views/layouts/admin.blade.php`
6. Build `resources/views/admin/{resource}/index.blade.php` + `⚡{resource}.blade.php`
7. **Add `abort_unless(canDo(...))` to every mutation method**
8. Update the AdminAuthorizationGuardTest
9. Test the public surface if there's one
10. Document the addition here + in `PERMISSIONS.md`

---

## Database changes

- Always write a migration — never edit a previous migration that's been merged
- Use `chunkById` / `LazyCollection` for large data backfills; don't `->get()` on tables that can grow
- Add an index when introducing a new column you'll query against

---

## Reviewing PRs

Things reviewers look for:

- Server-side `abort_unless(canDo(...))` guards on every new mutation method
- Tests pass; new behaviour has new tests
- No N+1 queries (eager-load relationships used in views)
- Mailables that broadcast or fan out implement `ShouldQueue`
- Mass-assignment safety on new models (`$fillable` whitelist)
- No `{!! !!}` with user-controlled content
- Docs updated when public behaviour changes

---

## Releasing

Owner does the release:

1. PR `dev` → `main`
2. Tag the merge commit (`vYYYY.MM.DD` or `vX.Y.Z`)
3. Push the tag
4. Deploy via Forge/Ploi/CI to production
5. Smoke test the production site

See [DEPLOYMENT.md](DEPLOYMENT.md).
