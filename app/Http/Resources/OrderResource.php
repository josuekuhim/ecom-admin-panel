<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'total_amount' => $this->total_amount,
            'status' => $this->status,
            'shipping_service' => $this->shipping_service,
            'shipping_price' => $this->shipping_price,
            'shipping_deadline' => $this->shipping_deadline,
            'cep' => $this->cep,
            'address' => $this->address,
            'address_number' => $this->address_number,
            'address_complement' => $this->address_complement,
            'city' => $this->city,
            'state' => $this->state,
            'transaction_id' => $this->transaction_id,
            'payment_method' => $this->payment_method,
            'items' => $this->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product_variant_id' => $item->product_variant_id,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'variant' => $item->variant ? [
                        'id' => $item->variant->id,
                        'name' => $item->variant->name,
                        'value' => $item->variant->value,
                        'product' => $item->variant->product ? [
                            'id' => $item->variant->product->id,
                            'name' => $item->variant->product->name,
                            'price' => $item->variant->product->price,
                        ] : null,
                    ] : null,
                ];
            })->values(),
            'created_at' => $this->created_at,
        ];
    }
}
