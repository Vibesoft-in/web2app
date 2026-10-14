<?php

namespace App\Http\Resources;

use App\Models\Connection;
use App\Services\Security\CredentialVault;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Connection
 */
class ConnectionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $vault = app(CredentialVault::class);
        $credentials = is_array($this->credentials) ? $this->credentials : [];
        $masked = $vault->getMaskedView($credentials);

        return [
            'id' => $this->id,
            'app_id' => $this->app_id,
            'platform' => $this->platform,
            'site_url' => $this->site_url,
            'status' => $this->status,
            'last_verified_at' => $this->last_verified_at?->toIso8601String(),
            'last_error_code' => $this->last_error_code,
            'consumer_key_masked' => $masked['consumer_key_masked'],
            'consumer_secret_masked' => $masked['consumer_secret_masked'],
            // Strictly exclude any raw consumer_key or consumer_secret
        ];
    }
}
