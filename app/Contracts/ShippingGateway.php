<?php

namespace App\Contracts;

interface ShippingGateway
{
    public function calculate(string $destinationCep, float $weightInKg, float $lengthInCm, float $heightInCm, float $widthInCm);
}
