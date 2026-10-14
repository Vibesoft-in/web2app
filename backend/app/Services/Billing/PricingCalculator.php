<?php

namespace App\Services\Billing;

use App\Models\Plan;
use InvalidArgumentException;

class PricingCalculator
{
    public const TAX_RATE = 0.18; // 18% GST

    public const ADDON_PRICING = [
        'extra_builds_10' => [
            'name' => '10 Additional Android Builds',
            'price' => 499.00,
        ],
        'extra_push_50k' => [
            'name' => '50,000 Push Notifications',
            'price' => 299.00,
        ],
        'whatsapp_starter' => [
            'name' => 'WhatsApp Notifications Pack (1,000 messages)',
            'price' => 799.00,
        ],
    ];

    /**
     * Calculate strict server-side pricing for a plan.
     *
     * @param  Plan  $plan
     * @param  string  $billingCycle  'monthly' | 'yearly'
     * @param  array<string, int>  $addons  Keyed by addon code => quantity
     * @return array{
     *     plan_id: int,
     *     plan_name: string,
     *     billing_cycle: string,
     *     base_price_monthly: float,
     *     period_months: int,
     *     gross_base_amount: float,
     *     discount_amount: float,
     *     addons_amount: float,
     *     addons_breakdown: array<int, array<string, mixed>>,
     *     subtotal: float,
     *     tax_rate: float,
     *     tax_amount: float,
     *     total_amount: float,
     *     amount_paise: int,
     *     currency: string
     * }
     */
    public function calculate(
        Plan $plan,
        string $billingCycle = 'monthly',
        array $addons = []
    ): array {
        if (! in_array($billingCycle, ['monthly', 'yearly'], true)) {
            throw new InvalidArgumentException("Invalid billing cycle: {$billingCycle}. Must be monthly or yearly.");
        }

        $baseMonthly = (float) $plan->price_monthly;
        $isYearly = $billingCycle === 'yearly';
        $periodMonths = $isYearly ? 12 : 1;

        // Yearly pricing gets 2 months free (10 months cost for 12 months access)
        $grossBaseAmount = round($baseMonthly * $periodMonths, 2);
        $discountAmount = $isYearly ? round($baseMonthly * 2, 2) : 0.00;
        $netBaseAmount = round($grossBaseAmount - $discountAmount, 2);

        // Calculate add-ons
        $addonsAmount = 0.00;
        $addonsBreakdown = [];

        foreach ($addons as $addonKey => $qty) {
            $qty = (int) $qty;
            if ($qty <= 0) {
                continue;
            }

            if (! isset(self::ADDON_PRICING[$addonKey])) {
                throw new InvalidArgumentException("Unknown add-on requested: [{$addonKey}].");
            }

            $addonDef = self::ADDON_PRICING[$addonKey];
            $addonLineTotal = round($addonDef['price'] * $qty, 2);
            $addonsAmount = round($addonsAmount + $addonLineTotal, 2);

            $addonsBreakdown[] = [
                'code' => $addonKey,
                'name' => $addonDef['name'],
                'unit_price' => $addonDef['price'],
                'quantity' => $qty,
                'total' => $addonLineTotal,
            ];
        }

        $subtotal = round($netBaseAmount + $addonsAmount, 2);
        $taxAmount = round($subtotal * self::TAX_RATE, 2);
        $totalAmount = round($subtotal + $taxAmount, 2);
        $amountPaise = (int) round($totalAmount * 100);

        return [
            'plan_id' => $plan->id,
            'plan_name' => $plan->name,
            'billing_cycle' => $billingCycle,
            'base_price_monthly' => $baseMonthly,
            'period_months' => $periodMonths,
            'gross_base_amount' => $grossBaseAmount,
            'discount_amount' => $discountAmount,
            'addons_amount' => $addonsAmount,
            'addons_breakdown' => $addonsBreakdown,
            'subtotal' => $subtotal,
            'tax_rate' => self::TAX_RATE,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
            'amount_paise' => $amountPaise,
            'currency' => 'INR',
        ];
    }
}
