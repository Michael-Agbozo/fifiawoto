<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ImpactMetricService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardController extends Controller
{
    public function __invoke(ImpactMetricService $metrics): View
    {
        return view('admin.dashboard', [
            'cards' => $metrics->dashboardCards(),
            'activity' => $metrics->recentActivity(),
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
        $tab = $request->query('tab', 'all');
        $rows = collect($metrics->recentActivity(200));

        if (in_array($tab, ['volunteer', 'donation', 'contact', 'newsletter'], true)) {
            $rows = $rows->where('kind', $tab);
        }

        $filename = 'dashboard-activity-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Kind', 'Name', 'When', 'Detail', 'Category', 'Type', 'Status']);
            foreach ($rows as $entry) {
                fputcsv($out, [
                    $entry['kind'] ?? '',
                    $entry['name'] ?? '',
                    optional($entry['when'] ?? null)->toDateTimeString() ?? '',
                    $entry['detail'] ?? '',
                    $entry['category'] ?? '',
                    $entry['label'] ?? '',
                    $entry['status'] ?? '',
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
