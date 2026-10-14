<?php

namespace App\Providers;

use App\Services\Build\FakeFirebaseProvisioner;
use App\Services\Build\FirebaseProvisionerInterface;
use App\Services\Build\FlutterRunnerInterface;
use App\Services\Build\GoogleFirebaseProvisioner;
use App\Services\Build\MockFlutterRunner;
use App\Services\Build\SystemFlutterRunner;
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
                return new FakeFirebaseProvisioner();
            }

            return new GoogleFirebaseProvisioner();
        });

        $this->app->singleton(FlutterRunnerInterface::class, function ($app) {
            if ($this->app->environment('testing') || empty(env('FLUTTER_BIN'))) {
                return new MockFlutterRunner();
            }

            return new SystemFlutterRunner();
        });

        $this->app->singleton(\App\Services\Notifications\FcmServiceInterface::class, \App\Services\Notifications\FcmService::class);
        $this->app->singleton(\App\Services\Notifications\WhatsAppServiceInterface::class, \App\Services\Notifications\WhatsAppService::class);
    }

    public function boot(): void
    {
        RateLimiter::for('mobile_api', function (Request $request) {
            $appKey = (string) ($request->header('X-App-Key') ?: 'default');

            return Limit::perMinute(120)->by($appKey.':'.$request->ip());
        });
    }
}
