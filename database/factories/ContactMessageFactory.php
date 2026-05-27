<?php

namespace Database\Factories;

use App\Enums\ContactMessageStatus;
use App\Enums\ContactSubject;
use App\Models\ContactMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContactMessage>
 */
class ContactMessageFactory extends Factory
{
    protected $model = ContactMessage::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'full_name' => $this->faker->name(),
            'email' => $this->faker->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'subject' => $this->faker->randomElement(ContactSubject::cases())->value,
            'message' => $this->faker->paragraph(),
            'consented_at' => now(),
            'status' => ContactMessageStatus::New->value,
        ];
    }
}
