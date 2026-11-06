<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ImpactMetricService;
use App\Support\CsvCell;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardController extends Controller
{
    public function __invoke(Request $request, ImpactMetricService $metrics): View
    {
        return view('admin.dashboard', [
            'cards' => $metrics->dashboardCards(),
            'activity' => $metrics->recentActivity($request->user()),
            'activityKinds' => $metrics->activityKindsFor($request->user()),
            'dailyDonations' => $metrics->dailyDonations(30),
            'cumulativeDonations' => $metrics->cumulativeDonations(30),
            'totalDonations' => $metrics->totalDonations(),
            'pendingVolunteerApplications' => $metrics->pendingVolunteerApplications(),
            'newContactMessages' => $metrics->newContactMessages(),
            'newsletterSubscribers' => $metrics->newsletterSubscriberCount(),
        ]);
    }

    public function exportActivity(Request $request, ImpactMetricService $metrics): StreamedResponse
    {
        abort_unless($request->user()?->canDo('reports', 'export'), 403);

        $tab = $request->query('tab', 'all');
        $kind = in_array($tab, ['volunteer', 'donation', 'contact', 'newsletter'], true) ? $tab : null;

        // Filter by kind before the 200-row limit is applied, not after.
        $rows = $metrics->recentActivity($request->user(), 200, $kind);

        $filename = 'dashboard-activity-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Kind', 'Name', 'When', 'Detail', 'Category', 'Type', 'Status']);
            foreach ($rows as $entry) {
                fputcsv($out, CsvCell::row([
                    $entry['kind'] ?? '',
                    $entry['name'] ?? '',
                    optional($entry['when'] ?? null)->toDateTimeString() ?? '',
                    $entry['detail'] ?? '',
                    $entry['category'] ?? '',
                    $entry['label'] ?? '',
                    $entry['status'] ?? '',
                ]));
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
