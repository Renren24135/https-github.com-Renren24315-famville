<?php

namespace App\Providers;

use App\Models\Booking;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        Paginator::defaultView('pagination::bootstrap-5');
        Paginator::defaultSimpleView('pagination::simple-bootstrap-5');

        Password::defaults(function () {
            $rule = Password::min(8)->mixedCase()->numbers()->symbols();

            return $this->app->environment('production') ? $rule->uncompromised() : $rule;
        });

        // Badge count on the staff nav (Bookings / New Booking) — online
        // reservations for today or tomorrow that no cashier has acted on
        // yet, so the team notices a fresh booking without hunting for it.
        View::composer('layouts.app', function ($view) {
            $user = auth()->user();

            $view->with('newBookingsCount', $user && $user->isStaff()
                ? Booking::where('source', 'online')
                    ->where('status', 'pending')
                    ->whereBetween('booking_date', [now()->toDateString(), now()->addDay()->toDateString()])
                    ->count()
                : 0);
        });
    }
}
