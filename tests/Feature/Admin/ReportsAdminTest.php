<?php

use App\Enums\UserRole;
use App\Models\Donation;
use App\Models\Event;
use App\Models\User;
use App\Services\ReportAnalyticsService;
use Livewire\Livewire;

beforeEach(fn () => $this->actingAs(User::factory()->superAdmin()->create()));

it('renders the donations report by default with the right summary', function () {
    Donation::factory()->create([
        'donor_name' => 'Esi Report',
        'amount_cents' => 50000,
        'received_at' => now()->subDays(5)->toDateString(),
    ]);

    Livewire::test('admin.reports')
        ->assertSee('$500')
        ->assertSee('Esi Report');
});

function downloadedReportCsv($component): string
{
    return (string) base64_decode(data_get($component->effects, 'download.content'));
}

it('streams a CSV export', function () {
    Donation::factory()->create([
        'donor_name' => 'Csv Donor',
        'received_at' => now()->toDateString(),
    ]);

    $component = Livewire::test('admin.reports')
        ->set('category', 'donations')
        ->call('downloadCsv')
        ->assertFileDownloaded();

    expect(downloadedReportCsv($component))->toContain('Csv Donor')
        ->not->toContain('No rows in selected range');
});

it('includes rows dated on the "to" day in the table and the CSV', function () {
    Donation::factory()->create(['donor_name' => 'Edge Day Donor', 'received_at' => '2026-05-31']);
    Donation::factory()->create(['donor_name' => 'Outside Donor', 'received_at' => '2026-06-01']);

    $component = Livewire::test('admin.reports')
        ->set('category', 'donations')
        ->set('fromDate', '2026-05-31')
        ->set('toDate', '2026-05-31')
        ->assertSee('Edge Day Donor')
        ->assertDontSee('Outside Donor')
        ->call('downloadCsv');

    expect(downloadedReportCsv($component))->toContain('Edge Day Donor')
        ->not->toContain('Outside Donor');
});

it('rejects an invalid date instead of erroring', function () {
    Livewire::test('admin.reports')
        ->set('category', 'donations')
        ->set('fromDate', 'not-a-date')
        ->assertHasErrors(['fromDate'])
        ->assertOk();
});

it('rejects a from date after the to date', function () {
    Livewire::test('admin.reports')
        ->set('fromDate', '2026-06-10')
        ->set('toDate', '2026-06-01')
        ->assertHasErrors(['toDate'])
        ->call('downloadCsv')
        ->assertHasErrors(['toDate'])
        ->assertNoFileDownloaded();
});

it('escapes spreadsheet formulas in the report CSV', function () {
    Donation::factory()->create(['donor_name' => '=HYPERLINK("http://evil.test","x")', 'received_at' => now()->toDateString()]);

    $component = Livewire::test('admin.reports')->set('category', 'donations')->call('downloadCsv');

    expect(downloadedReportCsv($component))->toContain('"\'=HYPERLINK(')
        ->not->toContain(',"=HYPERLINK(');
});

it('forbids the reports page without reports.view', function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::Volunteer, 'permissions' => []]));

    Livewire::test('admin.reports')->assertForbidden();
});

it('forbids report downloads without reports.export', function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::Volunteer, 'permissions' => ['reports.view']]));

    Livewire::test('admin.reports')->call('downloadCsv')->assertForbidden();
    Livewire::test('admin.reports')->call('downloadPdf')->assertForbidden();
});

it('shows the same all-time base-currency raised total in the events table and KPIs', function () {
    $event = Event::factory()->create(['starts_at' => now()->addDays(3), 'goal_cents' => 100000]);
    Donation::factory()->for($event)->create(['amount_cents' => 30000, 'received_at' => now()->subYears(2)->toDateString()]);
    Donation::factory()->for($event)->create(['amount_cents' => 20000, 'received_at' => now()->toDateString()]);
    Donation::factory()->for($event)->create(['amount_cents' => 999900, 'currency' => 'GHS', 'received_at' => now()->toDateString()]);

    $analytics = app(ReportAnalyticsService::class)->for('events', now()->toDateString(), now()->addDays(10)->toDateString());

    expect($analytics['kpis']['raised']['value'])->toEqual(500)
        ->and($analytics['top_by_raised'][0]['raised_cents'])->toBe(50000);

    Livewire::test('admin.reports')
        ->set('category', 'events')
        ->set('fromDate', now()->toDateString())
        ->set('toDate', now()->addDays(10)->toDateString())
        ->assertSee('500.00');
});

it('caps PDF detail rows and notes the truncation', function () {
    $limit = (new ReflectionClassConstant(get_class(Livewire::test('admin.reports')->instance()), 'PDF_ROW_LIMIT'))->getValue();

    $html = view('reports.pdf', [
        'category' => 'donations',
        'categoryLabel' => 'Donations',
        'fromDate' => '',
        'toDate' => '',
        'rows' => [['Donor' => 'Capped Donor']],
        'rowLimit' => $limit,
        'analytics' => app(ReportAnalyticsService::class)->for('donations', now()->toDateString(), now()->toDateString()),
        'generatedAt' => now(),
    ])->render();

    expect($limit)->toBe(2000)
        ->and($html)->toContain('Showing the first 2,000 rows only')
        ->and($html)->toContain('Capped Donor');
});

it('downloads a PDF for users with reports.export', function () {
    Donation::factory()->create(['received_at' => now()->toDateString()]);

    Livewire::test('admin.reports')
        ->set('category', 'donations')
        ->call('downloadPdf')
        ->assertHasNoErrors()
        ->assertFileDownloaded();
});

it('labels report totals with the configured base currency', function () {
    config(['app.currency' => 'GHS']);
    Donation::factory()->create(['currency' => 'GHS', 'amount_cents' => 1234_00, 'received_at' => now()->toDateString()]);

    Livewire::test('admin.reports')
        ->set('category', 'donations')
        ->assertSee('GH₵1,234');
});

it('exports the KPIs from the overview tab', function () {
    Donation::factory()->create(['amount_cents' => 12345, 'received_at' => now()]);

    $csv = downloadedReportCsv(Livewire::test('admin.reports')->call('downloadCsv')->assertFileDownloaded());

    expect($csv)->toContain('Metric,Value')
        ->toContain('Donations received')
        ->toContain('New beneficiaries')
        ->not->toContain('No rows in selected range');

    Livewire::test('admin.reports')->call('downloadExcel')->assertFileDownloaded();
});

it('paginates the detail table on screen while exports stay complete', function () {
    Donation::factory()->count(30)->create(['received_at' => now()]);
    Donation::factory()->create(['donor_name' => 'Zed Last Donor', 'received_at' => now()->addSecond()]);

    $component = Livewire::test('admin.reports')->set('category', 'donations');

    $component->assertSee('31 row(s)')->assertDontSee('Zed Last Donor');
    expect($component->instance()->detailRows->count())->toBe(25);

    $component->call('gotoPage', 2)->assertSee('Zed Last Donor');

    expect(downloadedReportCsv($component->call('downloadCsv')))->toContain('Zed Last Donor');

    // Changing tab goes back to the first page.
    $component->set('category', 'events');
    expect($component->instance()->getPage())->toBe(1);
});
