<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Sample programmes and sponsorships for previewing on the live site. Runs on each
 * deploy while DEMO_CONTENT=true. Never creates logins, so use AdminUserSeeder for those.
 */
class LiveDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            EventSeeder::class,
            DemoMediaSeeder::class,
            DemoSponsorshipSeeder::class,
            DemoSponsorSeeder::class,
        ]);
    }
}
