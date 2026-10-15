<?php

namespace App\Filament\Pages\Auth;

use App\Http\Responses\Auth\CustomerRegistrationResponse;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Events\Auth\Registered;
use Filament\Facades\Filament;
use Filament\Http\Responses\Auth\Contracts\RegistrationResponse;
use Filament\Pages\Auth\Register as BaseRegister;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;

class Register extends BaseRegister
{
    public function register(): ?RegistrationResponse
    {
        try {
            $this->rateLimit(2);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $user = $this->wrapInDatabaseTransaction(function (): Model {
            $this->callHook('beforeValidate');

            $data = $this->form->getState();

            $this->callHook('afterValidate');

            $data = $this->mutateFormDataBeforeRegister($data);

            $this->callHook('beforeRegister');

            $user = $this->handleRegistration($data);

            $this->form->model($user)->saveRelationships();

            $this->callHook('afterRegister');

            return $user;
        });

        event(new Registered($user));

        $this->sendEmailVerificationNotification($user);

        Filament::auth()->login($user);

        session()->regenerate();
        session()->forget('url.intended');

        return app(CustomerRegistrationResponse::class);
    }

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
