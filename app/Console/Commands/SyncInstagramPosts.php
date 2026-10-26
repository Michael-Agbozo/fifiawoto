<?php

namespace App\Console\Commands;

use App\Enums\InstagramSource;
use App\Models\InstagramPost;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

class SyncInstagramPosts extends Command
{
    protected $signature = 'instagram:sync {--limit=25 : How many recent posts to fetch}';

    protected $description = 'Pull recent posts from the Instagram Graph API (no-op until INSTAGRAM_ACCESS_TOKEN is set)';

    public function handle(): int
    {
        $token = config('services.instagram.access_token');

        if (blank($token)) {
            $this->info('Instagram sync is not configured (INSTAGRAM_ACCESS_TOKEN is empty) — skipping.');

            return self::SUCCESS;
        }

        $endpoint = rtrim(config('services.instagram.graph_url'), '/').'/'.config('services.instagram.user_id').'/media';

        try {
            $response = Http::timeout(15)->retry(2, 500, throw: false)->get($endpoint, [
                'fields' => 'id,caption,media_type,media_url,thumbnail_url,permalink,timestamp',
                'limit' => (int) $this->option('limit'),
                'access_token' => $token,
            ]);
        } catch (ConnectionException $e) {
            $this->error('Could not reach Instagram: '.$e->getMessage());

            return self::FAILURE;
        }

        if ($response->failed()) {
            $this->error('Instagram API error ('.$response->status().'): '.$response->json('error.message', 'unknown error'));

            return self::FAILURE;
        }

        $created = 0;
        $updated = 0;

        foreach ($response->json('data', []) as $item) {
            if (blank($item['id'] ?? null) || blank($item['permalink'] ?? null)) {
                continue;
            }

            $post = InstagramPost::query()->firstOrNew(['external_id' => $item['id']]);

            // New API posts wait for an admin to approve them; existing approval/hidden flags are never overwritten.
            if (! $post->exists) {
                $post->is_approved = false;
                $post->is_hidden = false;
            }

            $post->fill([
                'permalink' => $item['permalink'],
                'caption' => $item['caption'] ?? null,
                'media_url' => $item['media_url'] ?? null,
                'media_type' => $item['media_type'] ?? null,
                'thumbnail_url' => $item['thumbnail_url'] ?? null,
                'posted_at' => isset($item['timestamp']) ? Carbon::parse($item['timestamp']) : null,
                'source' => InstagramSource::Api,
            ]);

            $post->exists ? $updated++ : $created++;
            $post->save();
        }

        $this->info("Instagram sync complete: {$created} new, {$updated} refreshed.");

        return self::SUCCESS;
    }
}
