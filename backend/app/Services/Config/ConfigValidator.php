<?php

namespace App\Services\Config;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ConfigValidator
{
    /**
     * Allowed home section types.
     *
     * @var list<string>
     */
    protected const ALLOWED_HOME_TYPES = [
        'banner',
        'categories',
        'product_rail',
        'grid',
        'html_banner',
    ];

    /**
     * Validate an application configuration array against security rules and schema.
     *
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function validate(array $config): array
    {
        // 1. Base structure validation
        $validator = Validator::make($config, [
            'schema_version' => ['nullable', 'integer', 'min:1'],
            'config_version' => ['nullable', 'integer', 'min:1'],
            'app' => ['nullable', 'array'],
            'app.name' => ['nullable', 'string', 'max:50', 'not_regex:/[<>]/'],
            'app.currency' => ['nullable', 'string', 'max:10'],
            'app.min_version' => ['nullable', 'string', 'max:20'],
            'app.maintenance' => ['nullable', 'boolean'],

            'theme' => ['required', 'array'],
            'theme.layout' => ['required', 'string', 'max:50'],
            'theme.colors' => ['required', 'array'],
            'theme.radius' => ['nullable', 'integer', 'min:0', 'max:50'],
            'theme.font' => ['nullable', 'string', 'max:50'],
            'theme.nav' => ['nullable', 'string', 'max:20'],

            'features' => ['nullable', 'array'],
            'features.wishlist' => ['nullable', 'boolean'],
            'features.otp_login' => ['nullable', 'boolean'],
            'features.google_login' => ['nullable', 'boolean'],
            'features.notifications' => ['nullable', 'boolean'],
            'features.reviews' => ['nullable', 'boolean'],

            'home' => ['nullable', 'array'],
            'home.*.type' => ['required', 'string', 'in:'.implode(',', self::ALLOWED_HOME_TYPES)],
            'home.*.title' => ['nullable', 'string', 'max:100', 'not_regex:/[<>]/'],
            'home.*.source' => ['nullable', 'string', 'max:50'],
            'home.*.items' => ['nullable', 'array'],

            'links' => ['nullable', 'array'],
            'links.privacy' => ['nullable', 'string', 'max:255'],
            'links.terms' => ['nullable', 'string', 'max:255'],
            'links.support_phone' => ['nullable', 'string', 'max:30'],

            'webview_allowed_hosts' => ['nullable', 'array'],
            'webview_allowed_hosts.*' => ['string', 'max:255'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $errors = [];

        // 2. Strict Hex Color Validation
        /** @var array<string, mixed> $theme */
        $theme = $config['theme'] ?? [];
        /** @var array<string, mixed> $colors */
        $colors = $theme['colors'] ?? [];

        foreach ($colors as $name => $colorValue) {
            if (! is_string($colorValue)) {
                $errors["theme.colors.{$name}"][] = "Color '{$name}' must be a string.";

                continue;
            }

            if (! $this->isValidHexColor($colorValue)) {
                $errors["theme.colors.{$name}"][] = "Color '{$name}' must be a valid hex code (e.g. #FFFFFF or #FFF), received: '{$colorValue}'.";
            }
        }

        // 3. Strict HTTPS enforcement for all external URLs
        $this->validateUrls($config, $errors);

        // 4. Validate home items structure
        if (isset($config['home']) && is_array($config['home'])) {
            foreach ($config['home'] as $index => $section) {
                if (! is_array($section)) {
                    $errors["home.{$index}"][] = 'Home section must be an object.';

                    continue;
                }

                $type = $section['type'] ?? '';
                if ($type === 'banner' && isset($section['items']) && is_array($section['items'])) {
                    foreach ($section['items'] as $itemIndex => $item) {
                        if (! is_array($item)) {
                            $errors["home.{$index}.items.{$itemIndex}"][] = 'Banner item must be an object.';

                            continue;
                        }

                        if (isset($item['image']) && (! is_string($item['image']) || ! $this->isHttpsUrl($item['image']))) {
                            $errors["home.{$index}.items.{$itemIndex}.image"][] = 'Banner image URL must be a secure HTTPS URL.';
                        }

                        if (isset($item['link']) && is_string($item['link']) && strlen($item['link']) > 255) {
                            $errors["home.{$index}.items.{$itemIndex}.link"][] = 'Banner link must not exceed 255 characters.';
                        }
                    }
                }
            }
        }

        if (! empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        return $config;
    }

    /**
     * Check if a string is a valid hex color code (#RGB, #RRGGBB, #RRGGBBAA).
     */
    public function isValidHexColor(string $color): bool
    {
        return (bool) preg_match('/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6}|[A-Fa-f0-9]{8})$/', trim($color));
    }

    /**
     * Check if a URL uses secure HTTPS and has no unsafe scripts/schemes.
     */
    public function isHttpsUrl(string $url): bool
    {
        $trimmed = trim($url);

        if (! str_starts_with(strtolower($trimmed), 'https://')) {
            return false;
        }

        // Reject control characters or script injections
        if (preg_match('/[\x00-\x1F\x7F<>"\'\s]/', $trimmed)) {
            return false;
        }

        $parts = parse_url($trimmed);

        return $parts !== false && isset($parts['scheme'], $parts['host']) && strtolower($parts['scheme']) === 'https';
    }

    /**
     * Validate all URLs inside config (links, banners, assets).
     *
     * @param  array<string, mixed>  $config
     * @param  array<string, list<string>>  $errors
     */
    protected function validateUrls(array $config, array &$errors): void
    {
        if (isset($config['links']) && is_array($config['links'])) {
            foreach (['privacy', 'terms'] as $linkKey) {
                if (isset($config['links'][$linkKey]) && is_string($config['links'][$linkKey])) {
                    $val = $config['links'][$linkKey];
                    if ($val !== '' && ! $this->isHttpsUrl($val)) {
                        $errors["links.{$linkKey}"][] = "URL for '{$linkKey}' must be a secure HTTPS URL.";
                    }
                }
            }
        }
    }
}
