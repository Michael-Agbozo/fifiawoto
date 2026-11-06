<?php

namespace App\Console\Commands;

use App\Enums\InstagramSource;
use App\Models\InstagramPost;
use App\Support\InstagramToken;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncInstagramPosts extends Command
{
    protected $signature = 'instagram:sync {--limit=25 : How many recent posts to fetch}';

    protected $description = 'Pull recent posts from the Instagram Graph API (no-op until INSTAGRAM_ACCESS_TOKEN is set)';

    public function handle(): int
    {
        $token = InstagramToken::current();

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
            // The exception message echoes the request URL, which carries the access token.
            Log::error('Instagram sync: could not reach the Graph API.', [
                'error' => InstagramToken::redact($e->getMessage(), $token),
            ]);
            $this->error('Could not reach Instagram — see the application log for details.');

            return self::FAILURE;
        }

        if ($response->failed()) {
            $apiMessage = InstagramToken::redact((string) $response->json('error.message', 'unknown error'), $token);

            // Graph API error code 190 = invalid or expired access token.
            if ((int) $response->json('error.code') === 190) {
                Log::error('Instagram sync: access token is invalid or expired. Generate a new long-lived token and set INSTAGRAM_ACCESS_TOKEN (instagram:refresh-token only works while the current token is still valid).', [
                    'status' => $response->status(),
                    'error' => $apiMessage,
                ]);
                $this->error('Instagram access token is invalid or expired: '.$apiMessage);

                return self::FAILURE;
            }

            Log::error('Instagram sync: Graph API request failed.', [
                'status' => $response->status(),
                'error' => $apiMessage,
            ]);
            $this->error('Instagram API error ('.$response->status().'): '.$apiMessage);

            return self::FAILURE;
        }

        $created = 0;
        $updated = 0;

        foreach ($response->json('data', []) as $item) {
            if (blank($item['id'] ?? null) || blank($item['permalink'] ?? null)) {
                continue;
            }

            // A post added by hand first is adopted rather than duplicated, keeping its flags.
            $post = InstagramPost::query()->where('external_id', $item['id'])->first()
                ?? InstagramPost::findByPermalink($item['permalink'])
                ?? new InstagramPost;

            $post->external_id = $item['id'];

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
