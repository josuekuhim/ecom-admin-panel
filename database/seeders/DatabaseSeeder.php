<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Drop;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Cart;
use App\Models\CartItem;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $users = User::factory()->count(3)->create();

        // Create drops with products and variants
        $drops = Drop::factory()->count(3)->create();
        $variants = collect();
        foreach ($drops as $drop) {
            $products = Product::factory()->count(3)->create(['drop_id' => $drop->id]);
            foreach ($products as $product) {
                $variants->push(ProductVariant::factory()->count(2)->create([
                    'product_id' => $product->id,
                    'value' => $product->name . ' ' . fake()->randomElement(['S', 'M', 'L']),
                ]));
            }
        }

        // Seed carts with items for the first user
        $primaryUser = $users->first();
        $cart = Cart::factory()->create(['user_id' => $primaryUser->id]);
        $chosenVariants = ProductVariant::inRandomOrder()->limit(3)->get();
        foreach ($chosenVariants as $variant) {
            CartItem::factory()->create([
                'cart_id' => $cart->id,
                'product_variant_id' => $variant->id,
            ]);
        }

        // Create one order with items for the first user
        $order = Order::factory()->create([
            'user_id' => $primaryUser->id,
            'status' => 'paid',
        ]);

        foreach ($chosenVariants as $variant) {
            OrderItem::factory()->create([
                'order_id' => $order->id,
                'product_variant_id' => $variant->id,
                'price' => $variant->product->price,
            ]);
        }
    }
}
