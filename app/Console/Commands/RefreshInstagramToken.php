<?php

namespace App\Console\Commands;

use App\Support\InstagramToken;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RefreshInstagramToken extends Command
{
    protected $signature = 'instagram:refresh-token';

    protected $description = 'Extend the long-lived Instagram access token (valid 60 days) and persist the refreshed token';

    public function handle(): int
    {
        $token = InstagramToken::current();

        if (blank($token)) {
            $this->info('Instagram sync is not configured (INSTAGRAM_ACCESS_TOKEN is empty) — skipping.');

            return self::SUCCESS;
        }

        $endpoint = rtrim(config('services.instagram.graph_url'), '/').'/refresh_access_token';

        try {
            $response = Http::timeout(15)->retry(2, 500, throw: false)->get($endpoint, [
                'grant_type' => 'ig_refresh_token',
                'access_token' => $token,
            ]);
        } catch (ConnectionException $e) {
            Log::error('Instagram token refresh: could not reach the Graph API.', [
                'error' => InstagramToken::redact($e->getMessage(), $token),
            ]);
            $this->error('Could not reach Instagram — see the application log for details.');

            return self::FAILURE;
        }

        $refreshed = $response->json('access_token');

        if ($response->failed() || ! is_string($refreshed) || $refreshed === '') {
            $apiMessage = InstagramToken::redact((string) $response->json('error.message', 'unknown error'), $token);

            Log::error('Instagram token refresh failed. If the token has expired, generate a new long-lived token and set INSTAGRAM_ACCESS_TOKEN.', [
                'status' => $response->status(),
                'error' => $apiMessage,
            ]);
            $this->error('Instagram token refresh failed ('.$response->status().'): '.$apiMessage);

            return self::FAILURE;
        }

        InstagramToken::store($refreshed);

        $days = (int) floor(((int) $response->json('expires_in', 0)) / 86400);
        $this->info("Instagram access token refreshed (valid for ~{$days} days).");

        return self::SUCCESS;
    }
}
