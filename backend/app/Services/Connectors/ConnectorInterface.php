<?php

namespace App\Services\Connectors;

use App\Models\AppUser;
use App\Services\Catalog\CommonProduct;
use App\Services\Catalog\PaginatedProducts;

interface ConnectorInterface
{
    /**
     * Verify store connection, credentials, and read/write permissions.
     */
    public function verify(): VerifyResult;

    /**
     * Fetch paginated products from the store.
     *
     * @param  array<string, mixed>  $query
     */
    public function getProducts(array $query = []): PaginatedProducts;

    /**
     * Fetch a single product by its store ID.
     */
    public function getProduct(string $id): CommonProduct;

    /**
     * Fetch store product categories.
     *
     * @return list<array<string, mixed>>
     */
    public function getCategories(): array;

    /**
     * Search products matching a query term.
     *
     * @return list<CommonProduct>
     */
    public function searchProducts(string $term): array;

    /**
     * Find or create a store customer by email/phone identifier.
     * Returns the store's customer ID.
     */
    public function findOrCreateCustomer(string $identifier, ?string $name = null): string;

    /**
     * Revalidate cart items/prices and generate a webview checkout URL.
     *
     * @param  list<array{product_id: string|int, quantity: int, variant_id?: string|int|null}>  $cartItems
     */
    public function createCheckoutUrl(array $cartItems, ?AppUser $user = null): string;

    /**
     * Fetch orders exclusively belonging to the specified logged-in customer.
     *
     * @param  array<string, mixed>  $query
     * @return list<array<string, mixed>>
     */
    public function getOrders(AppUser $user, array $query = []): array;

    /**
     * Fetch a single order, strictly checking customer ownership.
     *
     * @return array<string, mixed>|null
     */
    public function getOrder(AppUser $user, string $orderId): ?array;
}
