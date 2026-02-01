<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ContactFactory extends Factory
{
    public function definition(): array
    {
        $vehicles = [
            'Toyota Avanza', 'Toyota Innova', 'Toyota Fortuner', 'Toyota Rush',
            'Daihatsu Xenia', 'Daihatsu Terios', 'Daihatsu Ayla',
            'Isuzu Panther', 'Isuzu MU-X', 'BMW X3', 'BMW 320i',
        ];

        $sources = ['showroom', 'referral', 'online', 'exhibition', 'walk-in'];

        $faker = \Faker\Factory::create('id_ID');

        return [
            'phone' => '08' . $faker->numerify('##########'),
            'name' => $faker->name(),
            'email' => $faker->optional()->safeEmail(),
            'lead_status' => $faker->randomElement([
                'new', 'contacted', 'qualified', 'proposal', 'negotiation', 'closed_won', 'closed_lost'
            ]),
            'vehicle_interest' => $faker->randomElement($vehicles),
            'budget' => $faker->randomElement([
                150000000, 200000000, 250000000, 300000000, 400000000, 500000000, 750000000, 1000000000
            ]),
            'source' => $faker->randomElement($sources),
            'metadata' => null,
            'assigned_to' => null,
        ];
    }

    public function newLead(): static
    {
        return $this->state(fn (array $attributes) => [
            'lead_status' => 'new',
        ]);
    }

    public function qualified(): static
    {
        return $this->state(fn (array $attributes) => [
            'lead_status' => 'qualified',
        ]);
    }

    public function closedWon(): static
    {
        return $this->state(fn (array $attributes) => [
            'lead_status' => 'closed_won',
        ]);
    }
}
