<?php


namespace Database\Factories;


use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PatientFactory extends Factory
{
    
    public function definition(): array
    {
        return [
            'Firstname' => $this->faker->firstName(),
            'Lastname' => $this->faker->lastName(),
            'phone' => $this->faker->phoneNumber(),
        ];
    }
}