<?php

namespace App\Providers;

use App\Http\Responses\Auth\CustomerLoginResponse;
use App\Http\Responses\Auth\CustomerRegistrationResponse;
use App\Services\Build\FakeFirebaseProvisioner;
use App\Services\Build\FirebaseProvisionerInterface;
use App\Services\Build\FlutterRunnerInterface;
use App\Services\Build\GoogleFirebaseProvisioner;
use App\Services\Build\MockFlutterRunner;
use App\Services\Build\SystemFlutterRunner;
use App\Services\Notifications\FcmService;
use App\Services\Notifications\FcmServiceInterface;
use App\Services\Notifications\WhatsAppService;
use App\Services\Notifications\WhatsAppServiceInterface;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Http\Responses\Auth\Contracts\RegistrationResponse;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(FirebaseProvisionerInterface::class, function ($app) {
            if ($this->app->environment(['local', 'testing']) || empty(config('services.firebase.access_token'))) {
                return new FakeFirebaseProvisioner;
            }

            return new GoogleFirebaseProvisioner;
        });

        $this->app->singleton(FlutterRunnerInterface::class, function ($app) {
            if ($this->app->environment('testing') || empty(env('FLUTTER_BIN'))) {
                return new MockFlutterRunner;
            }

            return new SystemFlutterRunner;
        });

        $this->app->singleton(FcmServiceInterface::class, FcmService::class);
        $this->app->singleton(WhatsAppServiceInterface::class, WhatsAppService::class);

        $this->app->bind(
            RegistrationResponse::class,
            CustomerRegistrationResponse::class
        );
        $this->app->bind(
            LoginResponse::class,
            CustomerLoginResponse::class
        );
    }

    public function boot(): void
    {
        RateLimiter::for('mobile_api', function (Request $request) {
            $appKey = (string) ($request->header('X-App-Key') ?: 'default');

            return Limit::perMinute(120)->by($appKey.':'.$request->ip());
        });
    }
}
