<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'total_amount' => $this->faker->randomFloat(2, 20, 500),
            'status' => $this->faker->randomElement(['pending', 'paid', 'failed', 'canceled']),
            'shipping_service' => $this->faker->randomElement(['pac', 'sedex', null]),
            'shipping_price' => $this->faker->randomFloat(2, 0, 50),
            'shipping_deadline' => $this->faker->randomElement(['2 dias úteis', '5 dias úteis', null]),
            'cep' => $this->faker->numerify('########'),
            'address' => $this->faker->streetAddress(),
            'address_number' => (string) $this->faker->buildingNumber(),
            'address_complement' => $this->faker->optional()->secondaryAddress(),
            'city' => $this->faker->city(),
            'state' => $this->faker->stateAbbr(),
            'transaction_id' => $this->faker->uuid(),
            'payment_method' => $this->faker->randomElement(['pix', 'credit_card', 'boleto']),
        ];
    }
}
