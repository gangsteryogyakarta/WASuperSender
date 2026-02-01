<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SegmentFactory extends Factory
{
    public function definition(): array
    {
        $faker = \Faker\Factory::create('id_ID');
        return [
            'name' => $faker->words(3, true),
            'description' => $faker->sentence(),
            'criteria' => [
                ['field' => 'lead_status', 'operator' => '=', 'value' => 'new'],
            ],
            'contact_count' => 0,
        ];
    }
}
