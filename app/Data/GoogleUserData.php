<?php

namespace App\Data;

class GoogleUserData
{
    public function __construct(
        public readonly string $google_id,
        public readonly string $name,
        public readonly string $email,
        public readonly ?string $image = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            google_id: $data['google_id'],
            name: $data['name'],
            email: $data['email'],
            image: $data['image'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'google_id' => $this->google_id,
            'name' => $this->name,
            'email' => $this->email,
            'image' => $this->image,
        ];
    }
}
