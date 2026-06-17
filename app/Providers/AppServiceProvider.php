<?php

namespace App\Providers;

use App\Models\User;
use App\Policies\Admin\AdminUserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

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
        Gate::policy(User::class, AdminUserPolicy::class);

        if ($this->app->runningInConsole()) {
            return;
        }

        if (! $this->app->environment('local')) {
            return;
        }

        $rootUrl = request()->getSchemeAndHttpHost();

        if ($rootUrl !== '') {
            URL::forceRootUrl($rootUrl);
        }
    }
}
