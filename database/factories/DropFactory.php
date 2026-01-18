<?php

namespace Database\Factories;

use App\Models\Drop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Drop>
 */
class DropFactory extends Factory
{
    protected $model = Drop::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'image_url' => $this->faker->imageUrl(800, 600, 'fashion', true),
        ];
    }
}
