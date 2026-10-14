<?php

namespace App\Services\Catalog;

class CommonProduct
{
    /**
     * @param  list<string>  $images
     * @param  list<array<string, mixed>>  $variants
     * @param  list<int>  $categoryIds
     */
    public function __construct(
        public string $id,
        public string $title,
        public string $descriptionHtml,
        public float $price,
        public ?float $salePrice,
        public string $currency,
        public array $images,
        public array $variants,
        public bool $inStock,
        public array $categoryIds,
        public string $url,
    ) {}

    /**
     * Convert to array representation for serialization.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description_html' => $this->descriptionHtml,
            'price' => $this->price,
            'sale_price' => $this->salePrice,
            'currency' => $this->currency,
            'images' => $this->images,
            'variants' => $this->variants,
            'in_stock' => $this->inStock,
            'category_ids' => $this->categoryIds,
            'url' => $this->url,
        ];
    }
}
