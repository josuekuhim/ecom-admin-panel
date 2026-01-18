<?php

namespace App\Http\Controllers\Api;

use App\Contracts\ShippingGateway;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ShippingController extends Controller
{
    protected $shippingService;

    public function __construct(ShippingGateway $shippingService)
    {
        $this->shippingService = $shippingService;
    }

    public function calculate(Request $request)
    {
        $validatedData = $request->validate([
            'cep' => 'required|digits:8',
            'weight' => 'required|numeric',
            'length' => 'required|numeric',
            'height' => 'required|numeric',
            'width' => 'required|numeric',
        ]);

        $shippingOptions = $this->shippingService->calculate(
            $validatedData['cep'],
            $validatedData['weight'],
            $validatedData['length'],
            $validatedData['height'],
            $validatedData['width']
        );

        return response()->json($shippingOptions);
    }
}
