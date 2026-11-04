<?php

namespace Database\Factories;

use App\Models\Faq;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Faq>
 */
class FaqFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'page' => fake()->randomElement(array_keys(Faq::PAGES)),
            'question' => rtrim(fake()->sentence(6), '.').'?',
            'answer' => fake()->paragraph(),
            'is_published' => true,
            'sort' => fake()->numberBetween(0, 20),
        ];
    }

    public function hidden(): static
    {
        return $this->state(fn () => ['is_published' => false]);
    }
}
