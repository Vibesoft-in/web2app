<?php

namespace App\Services\Catalog;

class PaginatedProducts
{
    /**
     * @var list<CommonProduct>
     */
    public array $items;

    public int $totalProducts;

    /**
     * @param  list<CommonProduct>  $products
     */
    public function __construct(
        public array $products,
        public int $total,
        public int $totalPages,
        public int $currentPage,
    ) {
        $this->items = $products;
        $this->totalProducts = $total;
    }


    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'products' => array_map(fn (CommonProduct $p) => $p->toArray(), $this->products),
            'total' => $this->total,
            'total_pages' => $this->totalPages,
            'current_page' => $this->currentPage,
        ];
    }
}
