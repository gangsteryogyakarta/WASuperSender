<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SegmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'criteria' => [
                ['field' => 'lead_status', 'operator' => '=', 'value' => 'new'],
            ],
            'contact_count' => 0,
        ];
    }
}
