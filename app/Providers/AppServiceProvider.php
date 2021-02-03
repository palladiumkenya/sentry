<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        \App\Models\GetIndicatorsJob::observe(\App\Observers\GetIndicatorsJobObserver::class);
        \App\Models\PostLiveSyncIndicatorsJob::observe(\App\Observers\PostLiveSyncIndicatorsJobObserver::class);
    }
}
