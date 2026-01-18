<?php

namespace App\Data;

class ProfileData
{
    public function __construct(
        public readonly ?string $phone = null,
        public readonly ?string $birth_date = null,
        public readonly ?string $gender = null,
        public readonly ?string $address = null,
        public readonly ?string $address_number = null,
        public readonly ?string $complement = null,
        public readonly ?string $neighborhood = null,
        public readonly ?string $city = null,
        public readonly ?string $state = null,
        public readonly ?string $zip_code = null,
        public readonly ?string $country = null,
        public readonly ?bool $marketing_emails = null,
        public readonly ?string $customer_type = null,
        public readonly ?string $document_type = null,
        public readonly ?string $document_number = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            phone: $data['phone'] ?? null,
            birth_date: $data['birth_date'] ?? null,
            gender: $data['gender'] ?? null,
            address: $data['address'] ?? null,
            address_number: $data['address_number'] ?? null,
            complement: $data['complement'] ?? null,
            neighborhood: $data['neighborhood'] ?? null,
            city: $data['city'] ?? null,
            state: $data['state'] ?? null,
            zip_code: $data['zip_code'] ?? null,
            country: $data['country'] ?? null,
            marketing_emails: array_key_exists('marketing_emails', $data) ? (bool) $data['marketing_emails'] : null,
            customer_type: $data['customer_type'] ?? null,
            document_type: $data['document_type'] ?? null,
            document_number: $data['document_number'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'phone' => $this->phone,
            'birth_date' => $this->birth_date,
            'gender' => $this->gender,
            'address' => $this->address,
            'address_number' => $this->address_number,
            'complement' => $this->complement,
            'neighborhood' => $this->neighborhood,
            'city' => $this->city,
            'state' => $this->state,
            'zip_code' => $this->zip_code,
            'country' => $this->country,
            'marketing_emails' => $this->marketing_emails,
            'customer_type' => $this->customer_type,
            'document_type' => $this->document_type,
            'document_number' => $this->document_number,
        ], fn ($value) => $value !== null);
    }
}
