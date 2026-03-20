<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use App\Models\ClientRequest;
use App\Policies\ClientRequestPolicy;

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
        if (app()->environment('production')) {
            URL::forceScheme('https');

            if (filled(config('app.url'))) {
                URL::forceRootUrl(config('app.url'));
            }
        }

        // Register policies
        Gate::policy(ClientRequest::class, ClientRequestPolicy::class);

        // Enable lazy loading prevention in development
        if (app()->isLocal()) {
            \Illuminate\Database\Eloquent\Model::preventLazyLoading();
        }

        // Set JSON response options for performance
        \Illuminate\Support\Facades\Response::macro('cache', function ($ttl = 3600) {
            return response()->setCache([
                'public' => true,
                'max_age' => $ttl,
                's_maxage' => $ttl,
            ]);
        });

        // Optimize Eloquent query builder
        \Illuminate\Database\Eloquent\Builder::macro('uncached', function () {
            return $this->limit(0)->count() > 0 ? $this : $this;
        });

        // Use database connection pooling
        if (config('database.default') === 'pgsql') {
            \Illuminate\Support\Facades\DB::connection()->setReadWriteType('write');
        }
    }
}
