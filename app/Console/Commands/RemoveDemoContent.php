<?php

namespace App\Console\Commands;

use App\Models\Beneficiary;
use App\Models\Donation;
use App\Models\Event;
use App\Models\MediaItem;
use App\Support\Audit;
use Database\Seeders\EventSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class RemoveDemoContent extends Command
{
    protected $signature = 'demo:remove {--force : Skip the confirmation prompt}';

    protected $description = 'Delete the sample events, photos, children, sponsors and gifts added by LiveDemoSeeder';

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm('Delete all sample programmes and sponsorship records?')) {
            return self::SUCCESS;
        }

        $donations = Donation::query()->where('external_reference', 'like', 'SEED-%')->get();
        $donations->each->delete();

        $children = Beneficiary::withTrashed()->where('public_sponsorship_id', 'like', 'dff00000-0000-4000-8000-%')->get();
        $children->each(function (Beneficiary $child): void {
            Storage::disk('public')->delete($child->public_photo_path);
            $child->forceDelete();
        });

        $events = Event::withTrashed()->whereIn('slug', EventSeeder::slugs())->get();
        $events->each->forceDelete();

        // Deleting an event keeps its photos and clears their event, so this catches those too.
        $mediaCount = MediaItem::query()->whereNull('event_id')->where('path', 'like', 'images/foundation/%')->delete();

        Audit::record('system.demo_removed', 'Ran demo:remove to delete the sample content.', properties: [
            'donations' => $donations->count(),
            'beneficiaries' => $children->count(),
            'events' => $events->count(),
            'media_items' => $mediaCount,
        ]);

        $this->info('Sample content removed.');

        return self::SUCCESS;
    }
}
