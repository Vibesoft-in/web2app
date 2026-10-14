<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\App;
use App\Models\Plan;
use App\Services\Billing\RazorpayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function __construct(
        protected RazorpayService $razorpayService,
    ) {}

    /**
     * Create Razorpay checkout order for the app's subscription plan.
     */
    public function checkout(Request $request, App $app): JsonResponse
    {
        $validated = $request->validate([
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
            'billing_cycle' => ['sometimes', 'string', 'in:monthly,yearly'],
            'addons' => ['sometimes', 'array'],
            'addons.*' => ['integer', 'min:1'],
        ]);

        /** @var Plan $plan */
        $plan = Plan::where('is_active', true)->findOrFail($validated['plan_id']);

        $billingCycle = $validated['billing_cycle'] ?? 'monthly';
        $addons = $validated['addons'] ?? [];

        $orderData = $this->razorpayService->createOrder(
            tenant: $app->tenant,
            app: $app,
            plan: $plan,
            billingCycle: $billingCycle,
            addons: $addons
        );

        return response()->json([
            'data' => $orderData,
        ]);
    }
}
