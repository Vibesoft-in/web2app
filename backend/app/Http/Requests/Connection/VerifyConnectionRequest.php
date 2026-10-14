<?php

namespace App\Http\Requests\Connection;

use Illuminate\Foundation\Http\FormRequest;

class VerifyConnectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'site_url' => ['required', 'string', 'url'],
            'consumer_key' => ['required', 'string', 'starts_with:ck_'],
            'consumer_secret' => ['required', 'string', 'starts_with:cs_'],
        ];
    }
}
