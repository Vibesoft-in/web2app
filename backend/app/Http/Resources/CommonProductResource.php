<?php

namespace App\Http\Resources;

use App\Services\Catalog\CommonProduct;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CommonProduct
 */
class CommonProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
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
