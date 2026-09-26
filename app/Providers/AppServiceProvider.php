<?php

namespace App\Providers;

use App\Modules\Auth\Interfaces\AuthServiceInterface;
use App\Modules\Auth\Services\AuthService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(AuthServiceInterface::class, AuthService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('login', static function (Request $request): Limit {
            $login = Str::lower((string) $request->input('user_name'));

            return Limit::perMinute(5)->by($login.'|'.$request->ip());
        });
    }
}
