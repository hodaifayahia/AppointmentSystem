<?php

namespace App\Providers;

use CarbonInmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
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
        
        // Model::preventLazyLoading(!app()->isProduction());
        // Model::unguard();
        //  Model::shouldBeStrict(!app()->isProduction());
        //  Date::use(CarbonInmutable::class);
        
    }
}
