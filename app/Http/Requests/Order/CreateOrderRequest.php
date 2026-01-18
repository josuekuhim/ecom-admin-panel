<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'shipping_service' => ['nullable', 'string'],
            'shipping_price' => ['nullable', 'numeric'],
            'shipping_deadline' => ['nullable', 'string'],
            'cep' => ['nullable', 'string'],
            'address' => ['nullable', 'string'],
            'address_number' => ['nullable', 'string'],
            'address_complement' => ['nullable', 'string'],
            'city' => ['nullable', 'string'],
            'state' => ['nullable', 'string', 'max:2'],
        ];
    }
}
