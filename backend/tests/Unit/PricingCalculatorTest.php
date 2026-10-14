<?php

use App\Models\Plan;
use App\Services\Billing\PricingCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->calculator = new PricingCalculator();
});

test('pricing calculator calculates server-side base monthly price with 18% GST', function () {
    $plan = Plan::factory()->create([
        'price_monthly' => 1999.00,
    ]);

    $result = $this->calculator->calculate($plan, 'monthly');

    expect($result['plan_id'])->toBe($plan->id)
        ->and($result['billing_cycle'])->toBe('monthly')
        ->and($result['base_price_monthly'])->toBe(1999.0)
        ->and($result['period_months'])->toBe(1)
        ->and($result['discount_amount'])->toBe(0.0)
        ->and($result['subtotal'])->toBe(1999.0)
        ->and($result['tax_rate'])->toBe(0.18)
        ->and($result['tax_amount'])->toBe(359.82)
        ->and($result['total_amount'])->toBe(2358.82)
        ->and($result['amount_paise'])->toBe(235882)
        ->and($result['currency'])->toBe('INR');
});

test('pricing calculator gives 2 months free discount on yearly billing', function () {
    $plan = Plan::factory()->create([
        'price_monthly' => 1000.00,
    ]);

    $result = $this->calculator->calculate($plan, 'yearly');

    // Gross: 12 * 1000 = 12000. Discount: 2 * 1000 = 2000. Net: 10000.
    // Tax 18%: 1800. Total: 11800. Amount in paise: 1180000
    expect($result['billing_cycle'])->toBe('yearly')
        ->and($result['period_months'])->toBe(12)
        ->and($result['gross_base_amount'])->toBe(12000.0)
        ->and($result['discount_amount'])->toBe(2000.0)
        ->and($result['subtotal'])->toBe(10000.0)
        ->and($result['tax_amount'])->toBe(1800.0)
        ->and($result['total_amount'])->toBe(11800.0)
        ->and($result['amount_paise'])->toBe(1180000);
});

test('pricing calculator includes valid add-ons and rejects unknown add-ons', function () {
    $plan = Plan::factory()->create([
        'price_monthly' => 2000.00,
    ]);

    $result = $this->calculator->calculate($plan, 'monthly', [
        'extra_builds_10' => 2, // 499 * 2 = 998
    ]);

    expect($result['addons_amount'])->toBe(998.0)
        ->and($result['subtotal'])->toBe(2998.0)
        ->and($result['tax_amount'])->toBe(round(2998.0 * 0.18, 2))
        ->and($result['addons_breakdown'])->toHaveCount(1)
        ->and($result['addons_breakdown'][0]['code'])->toBe('extra_builds_10');

    expect(fn () => $this->calculator->calculate($plan, 'monthly', ['hacked_addon' => 1]))
        ->toThrow(InvalidArgumentException::class);
});
