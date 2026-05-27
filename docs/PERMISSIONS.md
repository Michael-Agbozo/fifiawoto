# Roles + permissions

The platform uses **role-based access control** with **per-resource CRUD permissions**. Every role has a default permission set, and individual users can be granted extra permissions on top of their role.

This is the canonical reference. The source of truth in code lives at:

- `app/Enums/UserRole.php` — role definitions + default permissions per role
- `app/Support/Permissions.php` — registry of every valid resource + action
- `app/Models/User.php` — `canDo()` / `hasPermission()` / `effectivePermissions()`

---

## Roles

| Enum value             | Label                  | Can access `/admin`? |
| ---------------------- | ---------------------- | :------------------: |
| `owner`                | Super Admin            | ✅                   |
| `super_admin`          | Admin                  | ✅                   |
| `foundation_staff`     | Foundation Staff       | ✅                   |
| `volunteer_coordinator`| Volunteer Coordinator  | ✅                   |
| `media_manager`        | Media Manager          | ✅                   |
| `volunteer`            | Volunteer              | ❌                   |

---

## Permission keys

Every permission key follows the format **`{resource}.{action}`**. Resources and the actions each supports:

| Resource                    | Actions                                  |
| --------------------------- | ---------------------------------------- |
| `beneficiaries`             | `view`, `create`, `update`, `delete`     |
| `beneficiary_applications`  | `view`, `create`, `update`, `delete`     |
| `volunteers`                | `view`, `create`, `update`, `delete`     |
| `events`                    | `view`, `create`, `update`, `delete`     |
| `donations`                 | `view`, `create`, `update`, `delete`     |
| `testimonials`              | `view`, `create`, `update`, `delete`     |
| `leaders`                   | `view`, `create`, `update`, `delete`     |
| `media`                     | `view`, `create`, `update`, `delete`     |
| `instagram`                 | `view`, `create`, `update`, `delete`     |
| `newsletter`                | `view`, `create`, `update`, `delete`     |
| `inbox`                     | `view`, `reply`, `delete`                |
| `reports`                   | `view`, `export`                         |
| `users`                     | `view`, `create`, `update`, `delete`     |
| `system_logs`               | `view`                                   |

Total: 14 resources × ~4 actions ≈ **52 distinct permission keys**.

---

## Default permissions by role

### `owner` (Super Admin)
- **Everything.** Every permission key in the registry, including `system_logs.view`.

### `super_admin` (Admin)
- All keys **except** `system_logs.*`. Owner is the only role with access to the system log.

### `foundation_staff`
- Full CRUD: `beneficiaries`, `beneficiary_applications`, `events`, `donations`, `newsletter`
- View + export reports: `reports.view`, `reports.export`
- Full inbox: `inbox.view`, `inbox.reply`, `inbox.delete`
- View-only on website surfaces: `volunteers.view`, `media.view`, `testimonials.view`, `instagram.view`, `leaders.view`

### `volunteer_coordinator`
- Full CRUD: `volunteers`
- View-only: `beneficiaries.view`, `events.view`, `reports.view`, `inbox.view`

### `media_manager`
- Full CRUD: `media`, `instagram`, `testimonials`, `leaders`, `newsletter`
- View-only: `events.view`, `inbox.view`

### `volunteer`
- No admin permissions. Public site access only.

---

## Per-user extras

A user can be granted **additional** permissions on top of their role's defaults via the admin UI:

1. **User management → row actions → Permissions**
2. Defaults from the role are locked-on (checked + disabled).
3. Tick any **extra** keys to grant more.
4. Save.

Removing a role-default is not possible from this screen — change the user's role instead. This prevents accidental footguns where someone is left "stuck" with a non-functional role.

Permissions are stored as a JSON array on the `users.permissions` column. The model's `effectivePermissions()` method returns `array_unique(array_merge($role->defaultPermissions(), $user->permissions ?? []))`.

---

## How permissions are checked

### Three layers of enforcement

The platform enforces permissions at three independent layers — defence-in-depth:

1. **Route middleware** (`EnsureUserHasRole` in `bootstrap/app.php`) — the coarse gate. The `/admin/users` route, for example, is restricted to `role:owner,super_admin`. If your role isn't on the list, you get a 403 before any controller runs.

2. **UI gating** in Blade — buttons and menus only render when the user has the relevant permission:
   ```blade
   @if (auth()->user()?->canDo('leaders', 'create'))
       <button wire:click="startCreate">Add leader</button>
   @endif
   ```
   This stops users from seeing controls they can't use, but it's **not** the security boundary.

3. **Server-side checks** in every Livewire mutation method — the actual security boundary:
   ```php
   public function delete(int $id): void
   {
       abort_unless(auth()->user()?->canDo('leaders', 'delete'), 403);
       // ...
   }
   ```
   Even if someone crafts a manual Livewire RPC call, the server refuses unauthorised actions and returns 403.

### Why all three?

UI-only gating is bypassable — a hostile user with `leaders.view` could submit the `delete()` action via raw Livewire payload. Route middleware is too coarse — Foundation Staff can reach `/admin/media` to view, but shouldn't be able to delete from it. Method-level `abort_unless(canDo(...))` is what actually keeps the system secure.

### The guard test

[`tests/Feature/Admin/AdminAuthorizationGuardTest.php`](../tests/Feature/Admin/AdminAuthorizationGuardTest.php) — 22 tests that log in as a role lacking the permission, call each mutation method directly, and assert the data wasn't mutated. **If anyone adds a new mutation method without an `abort_unless` guard, one of these tests fails.**

---

## In code

```php
// Single-action check
if (auth()->user()->canDo('beneficiaries', 'delete')) {
    // ...
}

// Raw key check (when you have the string handy)
if ($user->hasPermission('reports.export')) {
    // ...
}

// Get every permission a user effectively has
$keys = $user->effectivePermissions();          // role defaults + extras, de-duped

// Just the extras (excluding role defaults)
$extras = $user->extraPermissions();
```

---

## Adding a new resource

When introducing a new resource (say, `partners`):

1. Add it to the registry in `app/Support/Permissions.php`:
   ```php
   'partners' => ['label' => 'Partner organisations', 'actions' => $crud],
   ```
2. Add the appropriate defaults to one or more roles in `app/Enums/UserRole.php` (`self::crudFor('partners')` for full CRUD, or specific keys for view-only).
3. Wire the **route middleware** to admit the right roles.
4. Add **UI gating** in the Blade view (`@if canDo(...)`).
5. Add **`abort_unless(canDo(...))` server-side guards** to every mutation method on the Livewire component.
6. Add the resource's mutations to `AdminAuthorizationGuardTest.php` so regressions are caught automatically.

---

## Useful queries

```bash
# Who has which role right now
php artisan tinker
> \App\Models\User::query()->select('email', 'role', 'permissions')->get();

# Add an extra permission to a user
> $u = \App\Models\User::firstWhere('email', 'alice@example.com');
> $u->forceFill(['permissions' => array_merge($u->permissions ?? [], ['reports.export'])])->save();
```
