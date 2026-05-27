# Troubleshooting

Quick fixes for the most common dev + prod issues.

---

## Local development

### `Class "Faker\Factory" not found`

You ran `composer install --no-dev` locally. Re-run without the flag — Faker is a dev dep.

```bash
composer install
```

### `SQLSTATE[HY000]: General error: 1 no such table: ...`

Database file doesn't have the schema. Run migrations.

```bash
php artisan migrate
# or to wipe and reseed from scratch:
php artisan migrate:fresh --seed
```

### `View [components.admin.⚡leaders] not found.`

Compiled views are stale after editing a Livewire single-file component. Clear them.

```bash
php artisan view:clear
```

### Tailwind classes aren't applying

You added a new utility (e.g. `text-[20px]`) and Vite hasn't recompiled. With `npm run dev` running you should be fine. If you've only run `npm run build`:

```bash
php artisan view:clear
npm run build
```

If your editor injected a class with a weird character (smart quote, em-dash), Tailwind's regex will silently skip it. Double-check the raw class string.

### Livewire requests return 419 Page Expired

Your session expired. In dev this happens after long idle periods. Refresh the page. If it's reproducible:

```bash
php artisan optimize:clear
```

### `Class "Symfony\Component\HttpKernel\Exception\HttpException" not thrown` in tests

You're trying to assert that `abort(403)` propagates out of a Livewire `call()`. **Livewire 4 catches `HttpException`** and converts it to a response. Assert on the *state* instead — that the row wasn't actually mutated. See `tests/Feature/Admin/AdminAuthorizationGuardTest.php` for the pattern.

### `Target [App\Http\Controllers\...] does not exist.`

You added a controller but didn't add the namespace import in `routes/web.php` or `routes/admin.php`.

### Hot reload (Vite HMR) not picking up Tailwind v4 `@theme` changes

Restart `npm run dev`. Tailwind v4 sometimes caches the `@theme` block on first scan.

### `composer dev` exits immediately

`concurrently` needs a sane PATH. If you're on macOS Herd, run inside Terminal (not your editor's integrated terminal) once to confirm. If a single child process fails (e.g. port 8000 in use), all four die.

```bash
# Find what's on port 8000
lsof -iTCP:8000 -sTCP:LISTEN
```

---

## Production

### Emails not sending

99% of the time: **the queue worker is not running**. Mailables implement `ShouldQueue`, so without a worker they queue forever.

```bash
sudo systemctl status fifiawoto-queue
sudo systemctl restart fifiawoto-queue

# Check failed jobs
php artisan queue:failed
```

If the worker is up but mail still doesn't arrive:

```bash
php artisan queue:work --once --verbose   # see what fails in real-time
```

Then check `storage/logs/laravel.log` for the exception.

### Newly deployed code doesn't show up

Three caches need clearing on deploy. The standard deploy script handles this, but if you SSH'd in and changed files:

```bash
php artisan view:clear
php artisan config:clear
php artisan route:clear
php artisan queue:restart       # workers must restart to pick up new code
```

### `419 Page Expired` on production form submits

Usually means session cookies aren't being persisted. Check:

- `SESSION_DOMAIN` in `.env` matches the actual domain
- `APP_URL` matches the actual URL
- Browser cookies aren't being blocked (third-party cookie blockers do this occasionally)

### 500 error with no useful response

`APP_DEBUG=false` is hiding the real error. Look at `storage/logs/laravel.log`:

```bash
tail -100 /var/www/fifiawoto/storage/logs/laravel.log
```

### Uploaded images return 404

The storage symlink doesn't exist on this server.

```bash
php artisan storage:link
```

### Slow homepage

Eager-load anything the view iterates. The home controller in `PageController::home()` pre-loads featured events, testimonials, and Instagram posts. If you added a section that queries inside a view loop, you've introduced N+1. Use Telescope or `DB::listen()` to confirm.

### Mass-assignment exception when seeding

You added a field to `$fillable` then removed it. Update the seeder to use `forceFill()` for the protected fields:

```php
$user = User::query()->firstOrNew(['email' => '...']);
$user->forceFill(['role' => 'owner', /* ... */]);
$user->save();
```

This pattern is already used in `database/seeders/DatabaseSeeder::seedUser()`.

---

## Tests

### `RefreshDatabase` says the DB is locked (SQLite)

Concurrent test runs hitting the same SQLite file. Pest defaults to in-memory. Confirm `phpunit.xml`:

```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

### A test asserts on copy that just changed

Update the test. The old copy is gone — assert on the new copy. We never weaken assertions just to make tests green; if the new copy is intentional, the test is the documentation.

### A guard test fails after I added a mutation method

You forgot the `abort_unless(canDo(...))` at the top of the method. Add it. The guard test is doing its job.

---

## Admin UI

### "You don't have permission" when I clearly should

Check the user's effective permissions:

```bash
php artisan tinker
> \App\Models\User::firstWhere('email', 'you@fifiawoto.org')->effectivePermissions();
```

If the role is right but the key is missing, the role's defaults may be incorrect — verify in `app/Enums/UserRole.php`.

### Modal won't close

Click the backdrop or press Escape. If neither works, the Livewire component is in an errored state (validation error blocking close, JS exception). Open browser devtools console + the network tab; look for the failed Livewire request.

### Cannot upload an image

Three checks:

1. **File size** — limit is 5 MB for photos, 10 MB for documents. Compress and retry.
2. **MIME type** — only PNG/JPG/WebP for images, PDF for documents.
3. **Disk permissions** — `chmod -R 775 storage/` on the server. The web user needs write.

---

## Database

### Migration order broke a foreign key

If you renamed a table or added a foreign key in the wrong migration order:

```bash
# Reset locally
php artisan migrate:fresh --seed
# Then re-order the migration timestamps in their filenames so the dependency runs first
```

In prod, **do not** run `migrate:fresh`. Write a remediation migration that drops the broken FK and re-creates it correctly.

### A seeded record has the wrong field

Seeders use `forceFill` / `firstOrNew` so re-running them is safe. Just edit the seeder and re-run:

```bash
php artisan db:seed --class=LeaderSeeder
```

---

## When all else fails

```bash
# Nuke every cache locally and rebuild
php artisan optimize:clear
composer dump-autoload
npm ci
npm run build
php artisan migrate:fresh --seed
```

In prod, **don't** run `migrate:fresh`. Take a snapshot first, then deploy a remediation migration.
