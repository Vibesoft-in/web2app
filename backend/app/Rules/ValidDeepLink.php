<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class ValidDeepLink implements ValidationRule
{
    /**
     * Regular expression matching the strict allowlist of deep link formats.
     * Allowed: product:ID, order:ID, category:ID
     */
    public const PATTERN = '/^(product:[a-zA-Z0-9_-]+|order:[a-zA-Z0-9_-]+|category:[a-zA-Z0-9_-]+)$/';

    /**
     * Validate whether a string conforms to the allowed deep link formats.
     */
    public static function isValid(?string $value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        return (bool) preg_match(self::PATTERN, $value);
    }

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! is_string($value) || ! self::isValid($value)) {
            $fail('The :attribute must match an allowed deep link format (product:ID, order:ID, category:ID). Malicious or external URIs are prohibited.');
        }
    }
}
