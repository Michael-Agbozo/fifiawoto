<?php

use App\Enums\UserRole;
use App\Http\Controllers\Admin\DocumentDownloadController;
use App\Models\Beneficiary;
use App\Models\BeneficiaryDocument;
use App\Models\Donation;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/**
 * Sidebar route => every admin role that should see it and be let in.
 * Mirrors docs/PERMISSIONS.md and UserRole::defaultPermissions().
 *
 * @return array<string, array<int, UserRole>>
 */
function adminGatingMatrix(): array
{
    $management = [UserRole::Owner, UserRole::SuperAdmin];
    $everyone = UserRole::adminRoles();

    return [
        'admin.inbox.index' => $everyone,
        'admin.beneficiaries.index' => [...$management, UserRole::FoundationStaff],
        'admin.beneficiary-applications.index' => [...$management, UserRole::FoundationStaff],
        'admin.volunteers.index' => [...$management, UserRole::FoundationStaff, UserRole::VolunteerCoordinator],
        'admin.events.index' => $everyone,
        'admin.donations.index' => [...$management, UserRole::FoundationStaff],
        'admin.sponsorships.index' => [...$management, UserRole::FoundationStaff],
        'admin.media.index' => [...$management, UserRole::FoundationStaff, UserRole::MediaManager],
        'admin.instagram.index' => [...$management, UserRole::FoundationStaff, UserRole::MediaManager],
        'admin.testimonials.index' => [...$management, UserRole::FoundationStaff, UserRole::MediaManager],
        'admin.leaders.index' => [...$management, UserRole::FoundationStaff, UserRole::MediaManager],
        'admin.faqs.index' => [...$management, UserRole::FoundationStaff, UserRole::MediaManager],
        'admin.newsletter.index' => [...$management, UserRole::FoundationStaff, UserRole::MediaManager],
        'admin.newsletter.campaigns' => [...$management, UserRole::FoundationStaff, UserRole::MediaManager],
        'admin.analytics.index' => [...$management, UserRole::FoundationStaff, UserRole::MediaManager],
        'admin.settings.site' => $management,
        'admin.settings.popup' => $management,
        'admin.users.index' => $management,
        'admin.system-logs.index' => [UserRole::Owner],
    ];
}

function sidebarLinks(User $user): string
{
    test()->actingAs($user);

    return Blade::render('<x-layouts::admin title="Matrix">Body</x-layouts::admin>');
}

function sidebarShows(string $html, string $routeName): bool
{
    return str_contains($html, 'href="'.route($routeName).'"');
}

it('keeps the sidebar and the route in agreement for every role', function (UserRole $role) {
    $user = User::factory()->withRole($role)->create();
    $sidebar = sidebarLinks($user);

    foreach (adminGatingMatrix() as $routeName => $allowedRoles) {
        $allowed = in_array($role, $allowedRoles, true);

        expect(sidebarShows($sidebar, $routeName))->toBe($allowed, "{$role->value} sidebar: {$routeName}");

        $this->actingAs($user)->get(route($routeName))
            ->assertStatus($allowed ? 200 : 403);
    }
})->with(UserRole::adminRoles());

it('honours a per-user grant in both the sidebar and the route', function () {
    $user = User::factory()->mediaManager()->create(['permissions' => ['donations.view']]);

    expect(sidebarShows(sidebarLinks($user), 'admin.donations.index'))->toBeTrue();
    $this->actingAs($user)->get(route('admin.donations.index'))->assertOk();
});

it('never opens user management or system logs through a per-user grant', function () {
    $user = User::factory()->foundationStaff()->create(['permissions' => ['users.view', 'users.update', 'system_logs.view']]);
    $sidebar = sidebarLinks($user);

    expect(sidebarShows($sidebar, 'admin.users.index'))->toBeFalse()
        ->and(sidebarShows($sidebar, 'admin.system-logs.index'))->toBeFalse();

    $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();
    $this->actingAs($user)->get(route('admin.system-logs.index'))->assertForbidden();
});

it('keeps private files locked for a coordinator granted beneficiary view access', function () {
    Storage::fake('beneficiaries');
    $beneficiary = Beneficiary::factory()->create();
    Storage::disk('beneficiaries')->put('1/id-card.pdf', 'secret');
    $document = BeneficiaryDocument::query()->create([
        'beneficiary_id' => $beneficiary->id,
        'disk' => 'beneficiaries',
        'path' => '1/id-card.pdf',
        'original_name' => 'id-card.pdf',
        'mime_type' => 'application/pdf',
        'size_bytes' => 6,
    ]);
    $url = DocumentDownloadController::beneficiaryDocumentUrl($document);

    $this->actingAs(User::factory()->volunteerCoordinator()->create(['permissions' => ['beneficiaries.view']]));
    $this->get(route('admin.beneficiaries.show', $beneficiary))->assertOk();
    $this->get($url)->assertForbidden();

    // A per-user grant of the view key doesn't open them either.
    $this->actingAs(User::factory()->mediaManager()->create(['permissions' => ['beneficiaries.view']]));
    $this->get(route('admin.beneficiaries.show', $beneficiary))->assertOk();
    $this->get($url)->assertForbidden();

    $this->actingAs(User::factory()->foundationStaff()->create());
    $this->get($url)->assertOk();
});

it('hides donor details in reports from a role without donations.view', function () {
    Donation::factory()->create(['donor_name' => 'Private Donor', 'received_at' => now()]);
    $this->actingAs(User::factory()->volunteerCoordinator()->create());

    $this->get(route('admin.reports.index'))->assertOk()->assertDontSee('Private Donor');

    Livewire::test('admin.reports')->set('category', 'donations')->assertForbidden();
    Livewire::test('admin.reports')->set('category', 'volunteers')->assertOk();
});

it('shows view-only roles no edit controls on events or volunteers', function () {
    Event::factory()->create();
    $this->actingAs(User::factory()->mediaManager()->create());

    Livewire::test('admin.events')
        ->assertSee('Sign-ups')
        ->assertDontSee('New event')
        ->assertDontSeeHtml('wire:click="startEdit(')
        ->assertDontSeeHtml('wire:click="askDelete(');
});

it('keeps beneficiary records closed to coordinators by default', function () {
    $beneficiary = Beneficiary::factory()->create();
    $this->actingAs(User::factory()->volunteerCoordinator()->create());

    $this->get(route('admin.beneficiaries.index'))->assertForbidden();
    $this->get(route('admin.beneficiaries.show', $beneficiary))->assertForbidden();
});

it('hides edit controls and file links from view-only beneficiary access', function () {
    Storage::fake('beneficiaries');
    $beneficiary = Beneficiary::factory()->create(['full_name' => 'View Only Child']);
    Storage::disk('beneficiaries')->put('1/id-card.pdf', 'secret');
    BeneficiaryDocument::query()->create([
        'beneficiary_id' => $beneficiary->id,
        'disk' => 'beneficiaries',
        'path' => '1/id-card.pdf',
        'original_name' => 'id-card.pdf',
        'mime_type' => 'application/pdf',
        'size_bytes' => 6,
    ]);
    $this->actingAs(User::factory()->volunteerCoordinator()->create(['permissions' => ['beneficiaries.view']]));

    $this->get(route('admin.beneficiaries.index'))->assertOk()
        ->assertSee('View Only Child')
        ->assertDontSee('Add beneficiary')
        ->assertDontSee('wire:click="startEdit(', false)
        ->assertDontSee('wire:click="askDelete(', false);

    $this->get(route('admin.beneficiaries.show', $beneficiary))->assertOk()
        ->assertSee('id-card.pdf')
        ->assertDontSee('/documents/', false);
});
