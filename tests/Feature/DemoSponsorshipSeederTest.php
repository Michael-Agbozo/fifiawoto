<?php

use App\Models\Beneficiary;
use Database\Seeders\DemoSponsorshipSeeder;
use Illuminate\Support\Facades\Storage;

it('creates six labelled demo profiles without duplicating them on rerun', function () {
    Storage::fake('public');

    $this->seed(DemoSponsorshipSeeder::class);
    $this->seed(DemoSponsorshipSeeder::class);

    expect(Beneficiary::query()->count())->toBe(6)
        ->and(Beneficiary::query()->publicSponsorship()->count())->toBe(6);

    foreach (Beneficiary::all() as $child) {
        expect($child->public_display_name)->toContain('(Demo)')
            ->and($child->public_story)->toContain('fictional child', 'photograph is illustrative', "\n\n");
        Storage::disk('public')->assertExists($child->public_photo_path);
    }

    expect(Beneficiary::query()->distinct()->count('public_photo_path'))->toBe(6);

    $ama = Beneficiary::query()->where('public_display_name', 'Ama (Demo)')->sole();
    expect(Storage::disk('public')->get($ama->public_photo_path))
        ->toBe(file_get_contents(public_path('images/foundation/outreach-2.webp')));

    $this->get(route('sponsor-a-child'))->assertOk()->assertSee('Ama (Demo)')->assertSee('Yaw (Demo)');
});

it('refuses to create demo sponsorship profiles in production', function () {
    app()->instance('env', 'production');

    expect(fn () => (new DemoSponsorshipSeeder)->run())->toThrow(RuntimeException::class);
    expect(Beneficiary::query()->count())->toBe(0);
});
