<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'items' => $this->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'quantity' => $item->quantity,
                    'product_variant_id' => $item->product_variant_id,
                    'variant' => $item->variant ? [
                        'id' => $item->variant->id,
                        'name' => $item->variant->name,
                        'value' => $item->variant->value,
                        'stock' => $item->variant->stock,
                        'product' => $item->variant->product ? [
                            'id' => $item->variant->product->id,
                            'name' => $item->variant->product->name,
                            'price' => $item->variant->product->price,
                        ] : null,
                    ] : null,
                ];
            })->values(),
            'items_count' => $this->items->sum('quantity'),
        ];
    }
}
