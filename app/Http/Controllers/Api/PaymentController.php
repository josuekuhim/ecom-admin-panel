<?php

namespace App\Http\Controllers\Api;

use App\Actions\CheckoutOrderAction;
use App\Contracts\PaymentGateway;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderTotalService;
use App\Services\PaymentResponseNormalizer;
use App\Services\PaymentSignatureService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class PaymentController extends Controller
{
    public function __construct(protected CheckoutOrderAction $checkoutOrderAction)
    {
    }

    public function checkout(Request $request, $id)
    {
        try {
            $normalized = $this->checkoutOrderAction->execute($request->user(), (int) $id);
            return response()->json($normalized);
        } catch (\App\Exceptions\Domain\OrderNotFoundException $e) {
            return response()->json(['message' => 'Not found'], Response::HTTP_NOT_FOUND);
        } catch (\App\Exceptions\Domain\OrderAlreadyProcessedException $e) {
            return response()->json(['message' => 'This order has already been processed.'], Response::HTTP_BAD_REQUEST);
        }
    }
}
