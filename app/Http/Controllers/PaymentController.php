<?php

namespace App\Http\Controllers;

use App\Services\DgepayGateway;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        protected CheckoutController $checkoutController,
        protected DgepayGateway $dgepay
    ) {}

    /**
     * Show membership checkout.
     */
    public function checkout(Request $request)
    {
        return $this->checkoutController->checkout($request);
    }

    /**
     * Process checkout submission.
     */
    public function processCheckout(Request $request)
    {
        return $this->checkoutController->processCheckout($request);
    }

    /**
     * DGePay return / callback handler.
     */
    public function callback(Request $request)
    {
        return $this->checkoutController->dgepayCallback($request);
    }

    /**
     * Check transaction status with DGePay server-to-server.
     */
    public function checkStatus(string $orderId)
    {
        return response()->json($this->dgepay->getTransactionStatus($orderId));
    }
}
