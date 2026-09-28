<?php

namespace App\Providers;

use App\Services\CalendarConnectionStatus;
use Illuminate\Support\Facades\View;
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
        // Solo le pagine autenticate usano questo layout. Nessun controllo al login.
        View::composer('layouts.app', function ($view): void {
            $status = app(CalendarConnectionStatus::class);
            $view->with('showCalendarWarning', $status->notificationsEnabled() && $status->check() === 'disconnected');
        });
    }
}
