<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // The SPA reads the list as a plain array, not under "data"
        JsonResource::withoutWrapping();

        // The SPA refreshes the token in its last 5 minutes
        Passport::personalAccessTokensExpireIn(now()->addHour());

        Gate::define('admin', fn (User $user) => $user->role === 'admin');
    }
}
