<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property User $resource
 */
class CustomerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'email' => $this->resource->email,
            'phone' => $this->resource->phone,
            'clerk_user_id' => $this->resource->clerk_user_id,
            'has_complete_profile' => $this->resource->hasCompleteAddress(),
            'cart_id' => $this->resource->cart?->id,
        ];
    }

    /**
     * Include full profile details.
     */
    public function withFullProfile(): array
    {
        return array_merge($this->toArray(request()), [
            'birth_date' => $this->resource->birth_date ? \Carbon\Carbon::parse($this->resource->birth_date)->format('Y-m-d') : null,
            'gender' => $this->resource->gender,
            'address' => $this->resource->address,
            'address_number' => $this->resource->address_number,
            'complement' => $this->resource->complement,
            'neighborhood' => $this->resource->neighborhood,
            'city' => $this->resource->city,
            'state' => $this->resource->state,
            'zip_code' => $this->resource->zip_code,
            'country' => $this->resource->country,
            'marketing_emails' => $this->resource->marketing_emails,
            'customer_type' => $this->resource->customer_type,
            'document_type' => $this->resource->document_type,
            'document_number' => $this->resource->document_number,
            'full_address' => $this->resource->full_address,
            'first_login_at' => $this->resource->first_login_at ? \Carbon\Carbon::parse($this->resource->first_login_at)->toISOString() : null,
            'last_login_at' => $this->resource->last_login_at ? \Carbon\Carbon::parse($this->resource->last_login_at)->toISOString() : null,
        ]);
    }

    /**
     * Include auth-specific fields.
     */
    public function withAuthContext(): array
    {
        return array_merge($this->toArray(request()), [
            'is_new_customer' => $this->resource->first_login_at ? \Carbon\Carbon::parse($this->resource->first_login_at)->isToday() : false,
        ]);
    }
}
