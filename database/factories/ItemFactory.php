<?php

namespace Database\Factories;

use App\Enums\ItemStatus;
use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class ItemFactory extends Factory
{
    protected $model = Item::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->word(),
            'price' => $this->faker->randomNumber(5),
            'town' => $this->faker->city(),
            'uploadedDateTime' => Carbon::now(),
            'link' => $this->faker->word(),
            'status' => fake()->randomElement(ItemStatus::cases())->value,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
