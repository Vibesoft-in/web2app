<?php

namespace App\Http\Responses\Auth;

use Filament\Http\Responses\Auth\Contracts\RegistrationResponse;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

class CustomerRegistrationResponse implements RegistrationResponse
{
    public function toResponse($request): RedirectResponse|Redirector
    {
        session()->forget('url.intended');

        return redirect()->to('/app');
    }
}
