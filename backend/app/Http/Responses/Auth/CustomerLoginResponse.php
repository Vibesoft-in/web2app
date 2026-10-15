<?php

namespace App\Http\Responses\Auth;

use Filament\Facades\Filament;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

class CustomerLoginResponse implements LoginResponse
{
    public function toResponse($request): RedirectResponse|Redirector
    {
        $user = Filament::auth()->user();
        $intended = session()->pull('url.intended');

        if ($intended && str_contains($intended, '/admin')) {
            $adminPanel = Filament::getPanel('admin');
            if (! $user instanceof FilamentUser || ! $user->canAccessPanel($adminPanel)) {
                $intended = null;
            }
        }

        if ($intended) {
            return redirect()->intended($intended);
        }

        $currentPanel = Filament::getCurrentPanel();
        if ($currentPanel && $currentPanel->getId() === 'admin') {
            return redirect()->to('/admin');
        }

        return redirect()->to('/app');
    }
}
