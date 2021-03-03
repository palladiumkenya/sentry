<?php

namespace App\Providers;

use Eminiarts\Tabs\Tabs;
use Eminiarts\Tabs\Tab;
use Illuminate\Support\Facades\Gate;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Nova;
use Laravel\Nova\NovaApplicationServiceProvider;
use OptimistDigital\NovaSettings\NovaSettings;

class NovaServiceProvider extends NovaApplicationServiceProvider
{
    public function boot()
    {
        parent::boot();
        NovaSettings::addSettingsFields([
            Boolean::make('Production Environment', 'production'),
            Tabs::make('General', [
                Tab::make('Production', [
                    Text::make('Production LiveSync API URL', 'live_sync_api_url'),
                    Text::make('Production SPOT URL', 'spot_url'),
                    Text::make('Production SPOT API URL', 'spot_api_url'),
                    Text::make('Production Email Unsubsribe URL', 'email_unsubscribe_url'),
                ]),
                Tab::make('Staging', [
                    Text::make('Staging LiveSync API URL', 'live_sync_api_url_staging'),
                    Text::make('Staging SPOT URL', 'spot_url_staging'),
                    Text::make('Staging SPOT API URL', 'spot_api_url_staging'),
                    Text::make('Staging Email Unsubsribe URL', 'email_unsubscribe_url_staging'),
                ]),
            ]),
        ], [
            'production' => 'boolean',
        ], 'General');
        NovaSettings::addSettingsFields([
            Tabs::make('Contact Person', [
                Tab::make('Details', [
                    Text::make('Contact Person Name', 'contact_person_name'),
                    Text::make('Contact Person Email', 'contact_person_email'),
                ]),
            ]),
        ], [], 'Contact Person');
        NovaSettings::addSettingsFields([
            Tabs::make('Test Person', [
                Tab::make('Details', [
                    Text::make('Test Person Name', 'test_person_name'),
                    Text::make('Test Person Email', 'test_person_email'),
                ]),
            ]),
        ], [], 'Test Person');
    }

    protected function routes()
    {
        Nova::routes()->withAuthenticationRoutes()
            ->withPasswordResetRoutes()
            ->register();
    }

    protected function gate()
    {
        Gate::define('viewNova', function ($user) {
            return true;
        });
    }

    protected function cards()
    {
        return [];
    }

    protected function dashboards()
    {
        return [];
    }

    public function tools()
    {
        return [
            new \OptimistDigital\NovaSettings\NovaSettings
        ];
    }

    public function register()
    {
        //
    }
}
