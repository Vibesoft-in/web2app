<?php

namespace App\Services\Catalog;

class WooProductMapper
{
    /**
     * Map a WooCommerce v3 product array to CommonProduct.
     *
     * @param  array<string, mixed>  $raw
     */
    public function map(array $raw, string $currency = 'INR'): CommonProduct
    {
        $id = (string) ($raw['id'] ?? '');
        $title = (string) ($raw['name'] ?? '');
        $rawDescription = (string) ($raw['description'] ?? '');
        $descriptionHtml = $this->sanitizeHtml($rawDescription);

        $price = isset($raw['regular_price']) && is_numeric($raw['regular_price'])
            ? (float) $raw['regular_price']
            : (isset($raw['price']) && is_numeric($raw['price']) ? (float) $raw['price'] : 0.0);

        $salePrice = isset($raw['sale_price']) && is_numeric($raw['sale_price']) && (float) $raw['sale_price'] > 0
            ? (float) $raw['sale_price']
            : null;

        // Extract images
        $images = [];
        if (isset($raw['images']) && is_array($raw['images'])) {
            foreach ($raw['images'] as $img) {
                if (isset($img['src']) && is_string($img['src'])) {
                    $images[] = $img['src'];
                }
            }
        }

        // Extract variants / variations
        $variants = [];
        if (isset($raw['attributes']) && is_array($raw['attributes'])) {
            foreach ($raw['attributes'] as $attr) {
                if (is_array($attr)) {
                    $variants[] = [
                        'name' => $attr['name'] ?? '',
                        'options' => $attr['options'] ?? [],
                    ];
                }
            }
        }

        // In stock
        $inStock = ($raw['stock_status'] ?? 'instock') === 'instock';

        // Categories
        $categoryIds = [];
        if (isset($raw['categories']) && is_array($raw['categories'])) {
            foreach ($raw['categories'] as $cat) {
                if (isset($cat['id']) && is_numeric($cat['id'])) {
                    $categoryIds[] = (int) $cat['id'];
                }
            }
        }

        $url = (string) ($raw['permalink'] ?? '');

        return new CommonProduct(
            id: $id,
            title: $title,
            descriptionHtml: $descriptionHtml,
            price: $price,
            salePrice: $salePrice,
            currency: $currency,
            images: $images,
            variants: $variants,
            inStock: $inStock,
            categoryIds: $categoryIds,
            url: $url,
        );
    }

    /**
     * Sanitize HTML content to prevent XSS attacks while preserving basic formatting.
     */
    public function sanitizeHtml(string $html): string
    {
        // Strip out script tags and contents
        $html = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $html) ?? $html;
        $html = preg_replace('#<iframe(.*?)>(.*?)</iframe>#is', '', $html) ?? $html;

        // Strip dangerous attributes (e.g., onload, onclick, javascript: protocol)
        $html = preg_replace('/on[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html) ?? $html;
        $html = preg_replace('/href\s*=\s*["\']\s*javascript:[^"\']*["\']/i', '', $html) ?? $html;

        // Allow only safe formatting tags
        return strip_tags($html, '<p><br><b><strong><i><em><u><ul><ol><li><span><h1><h2><h3><h4><h5><h6><table><thead><tbody><tr><th><td>');
    }
}
