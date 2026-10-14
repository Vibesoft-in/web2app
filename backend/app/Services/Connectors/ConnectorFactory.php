<?php

namespace App\Services\Connectors;

use App\Models\App;
use InvalidArgumentException;

class ConnectorFactory
{
    /**
     * Create a store connector instance based on platform.
     *
     * @param  array<string, mixed>  $credentials
     */
    public function make(string $platform, string $siteUrl, array $credentials): ConnectorInterface
    {
        return match (strtolower($platform)) {
            'woo' => new WooConnector(
                siteUrl: $siteUrl,
                consumerKey: (string) ($credentials['consumer_key'] ?? ''),
                consumerSecret: (string) ($credentials['consumer_secret'] ?? ''),
            ),
            default => throw new InvalidArgumentException("Unsupported store platform [{$platform}]."),
        };
    }

    /**
     * Create connector instance for given App model.
     */
    public function forApp(App $app): ConnectorInterface
    {
        $connection = $app->connection;
        if (! $connection || empty($connection->site_url)) {
            // Provide fallback sandbox connector with dummy credentials
            return $this->make('woo', 'https://demo.example.com', [
                'consumer_key' => 'ck_demo',
                'consumer_secret' => 'cs_demo',
            ]);
        }

        return $this->make(
            $connection->platform,
            $connection->site_url,
            $connection->credentials ?? []
        );
    }
}
