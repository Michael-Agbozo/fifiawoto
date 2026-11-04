<?php

namespace Database\Factories;

use App\Enums\NewsletterCampaignStatus;
use App\Models\NewsletterCampaign;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NewsletterCampaign>
 */
class NewsletterCampaignFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subject' => fake()->sentence(5),
            'body' => "Hello friends,\n\n**Thank you** for standing with us.",
            'status' => NewsletterCampaignStatus::Draft,
        ];
    }
}
