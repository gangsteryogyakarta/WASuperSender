<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SegmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'criteria' => [
                ['field' => 'lead_status', 'operator' => '=', 'value' => 'new'],
            ],
            'contact_count' => 0,
        ];
    }
}
