<?php

namespace Database\Factories;

use App\Enums\EventStatus;
use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    protected $model = Event::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = $this->faker->randomElement([
            'Community Outreach Program',
            'Back-to-School Drive',
            'Widows Empowerment Workshop',
            'Mobile Health Clinic',
            'Christmas Food Distribution',
            'Skills & Vocational Training',
        ]);

        $location = $this->faker->randomElement([
            'Volta Region, Ghana',
            'Greater Accra, Ghana',
            'Lomé, Togo',
            'Cotonou, Benin',
            'Kpando, Ghana',
            'Brooklyn, New York',
        ]);

        $country = match (true) {
            str_contains($location, 'Togo') => 'Togo',
            str_contains($location, 'Benin') => 'Benin',
            str_contains($location, 'York'), str_contains($location, 'United States') => 'United States',
            default => 'Ghana',
        };

        $startsAt = $this->faker->dateTimeBetween('+1 week', '+12 weeks');

        return [
            'title' => $title.' — '.$location,
            'slug' => Str::slug($title.' '.$location.' '.uniqid()),
            'starts_at' => $startsAt,
            'ends_at' => (clone $startsAt)->modify('+'.$this->faker->numberBetween(0, 3).' days'),
            'location' => $location,
            'country' => $country,
            'description' => $this->faker->paragraphs(2, true),
            'activities' => "Food distribution\nEducation support\nMedical screening\nFamily counselling",
            'expected_impact' => 'Direct support for at least '.$this->faker->numberBetween(50, 400).' beneficiaries across the host community.',
            'volunteer_opportunities' => "On-site logistics\nProgramme facilitation\nMedia & communications\nChild care",
            'goal_cents' => $this->faker->randomElement([5000, 10000, 15000, 20000, 25000]) * 100,
            'hero_image_path' => null,
            'status' => EventStatus::Published->value,
            'published_at' => now()->subDays($this->faker->numberBetween(1, 30)),
        ];
    }

    public function draft(): self
    {
        return $this->state(fn () => [
            'status' => EventStatus::Draft->value,
            'published_at' => null,
        ]);
    }

    public function past(): self
    {
        return $this->state(function () {
            $start = $this->faker->dateTimeBetween('-12 weeks', '-1 week');

            return [
                'starts_at' => $start,
                'ends_at' => (clone $start)->modify('+2 days'),
            ];
        });
    }
}
