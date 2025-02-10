<?php

namespace Database\Factories;

use App\Models\BrowserSocket;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class BrowserSocketFactory extends Factory
{
    protected $model = BrowserSocket::class;

    public function definition(): array
    {
        return [
            'uri' => $this->faker->word(),
            'is_currently_active' => $this->faker->boolean(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
