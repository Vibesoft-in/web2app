<?php

namespace App\Services\Connectors;

class VerifyResult
{
    public function __construct(
        public bool $isSuccess,
        public ?string $storeName = null,
        public int $productsCount = 0,
        public string $currency = 'INR',
        public ?string $wcVersion = null,
        public ?string $errorCode = null,
        public ?string $errorMessage = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'is_success' => $this->isSuccess,
            'store_name' => $this->storeName,
            'products_count' => $this->productsCount,
            'currency' => $this->currency,
            'wc_version' => $this->wcVersion,
            'error_code' => $this->errorCode,
            'error_message' => $this->errorMessage,
        ];
    }
}
