<?php

namespace App\Filament\Pages\Auth;

use Filament\Facades\Filament;
use Filament\Pages\Auth\Register as BaseRegister;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;

class Register extends BaseRegister
{
    protected function sendEmailVerificationNotification(Model $user): void
    {
        if (! $user instanceof MustVerifyEmail) {
            return;
        }

        if ($user->hasVerifiedEmail()) {
            return;
        }

        $panel = Filament::getCurrentPanel();
        $panelId = $panel ? $panel->getId() : 'app';
        $verifyRoute = "filament.{$panelId}.auth.email-verification.verify";

        if (! Route::has($verifyRoute)) {
            $user->markEmailAsVerified();

            return;
        }

        parent::sendEmailVerificationNotification($user);
    }
}
