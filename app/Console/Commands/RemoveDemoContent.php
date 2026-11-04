<?php

namespace App\Console\Commands;

use App\Models\Beneficiary;
use App\Models\Donation;
use App\Models\Event;
use App\Models\MediaItem;
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

        Donation::query()->where('external_reference', 'like', 'SEED-%')->get()->each->delete();

        Beneficiary::withTrashed()->where('public_sponsorship_id', 'like', 'dff00000-0000-4000-8000-%')->get()
            ->each(function (Beneficiary $child): void {
                Storage::disk('public')->delete($child->public_photo_path);
                $child->forceDelete();
            });

        Event::withTrashed()->whereIn('slug', EventSeeder::slugs())->get()->each->forceDelete();

        // Deleting an event keeps its photos and clears their event, so this catches those too.
        MediaItem::query()->whereNull('event_id')->where('path', 'like', 'images/foundation/%')->delete();

        $this->info('Sample content removed.');

        return self::SUCCESS;
    }
}
