<?php

namespace App\Providers;

use App\Support\People\PeopleDirectory;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Die Naht zur Frage, welche Menschen es gibt. Sobald FamilyNetwork
        // angebunden ist, wird hier eine andere Implementierung gebunden.
        $this->app->bind(PeopleDirectory::class, config('tasks.people_directory'));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
