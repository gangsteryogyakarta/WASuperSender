<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CampaignFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->sentence(4),
            'message_template' => 'Halo [Nama], ada promo spesial untuk [Kendaraan]!',
            'media_path' => null,
            'status' => fake()->randomElement(['draft', 'scheduled', 'running', 'completed']),
            'scheduled_at' => null,
            'started_at' => null,
            'completed_at' => null,
            'total_recipients' => fake()->numberBetween(10, 500),
            'sent_count' => 0,
            'delivered_count' => 0,
            'read_count' => 0,
            'failed_count' => 0,
            'segment_id' => null,
            'created_by' => \App\Models\User::factory(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
        ]);
    }

    public function running(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'running',
            'started_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'started_at' => now()->subHours(2),
            'completed_at' => now(),
        ]);
    }
}
