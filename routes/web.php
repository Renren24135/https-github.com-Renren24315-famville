<?php

use App\Http\Controllers\Customer\BookingController as CustomerBookingController;
use App\Http\Controllers\Customer\DashboardController as CustomerDashboardController;
use App\Http\Controllers\Customer\TransactionController as CustomerTransactionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Staff\BilliardTableController;
use App\Http\Controllers\Staff\BookingController as StaffBookingController;
use App\Http\Controllers\Staff\DashboardController as StaffDashboardController;
use App\Http\Controllers\Staff\LaneController;
use App\Http\Controllers\Staff\PosController;
use App\Http\Controllers\Staff\RateTierController;
use App\Http\Controllers\Staff\SettingController;
use App\Http\Controllers\Staff\TransactionController as StaffTransactionController;
use App\Http\Controllers\Staff\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified', 'role:customer'])
    ->prefix('customer')
    ->name('customer.')
    ->group(function () {
        Route::get('/dashboard', [CustomerDashboardController::class, 'index'])->name('dashboard');

        Route::get('/bookings', [CustomerBookingController::class, 'index'])->name('bookings.index');
        Route::get('/bookings/create', [CustomerBookingController::class, 'create'])->name('bookings.create');
        Route::post('/bookings', [CustomerBookingController::class, 'store'])->name('bookings.store');
        Route::get('/bookings/{booking}', [CustomerBookingController::class, 'show'])->name('bookings.show');
        Route::post('/bookings/{booking}/cancel', [CustomerBookingController::class, 'cancel'])->name('bookings.cancel');

        Route::get('/transactions', [CustomerTransactionController::class, 'index'])->name('transactions.index');
    });

Route::middleware(['auth', 'verified', 'role:admin|cashier'])
    ->prefix('staff')
    ->name('staff.')
    ->group(function () {
        Route::get('/dashboard', [StaffDashboardController::class, 'index'])->name('dashboard');

        Route::resource('lanes', LaneController::class)->only(['index', 'store', 'update', 'destroy']);

        Route::resource('billiard-tables', BilliardTableController::class)
            ->parameters(['billiard-tables' => 'billiard_table'])
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('rate-tiers', RateTierController::class)
            ->parameters(['rate-tiers' => 'rate_tier'])
            ->only(['index', 'store', 'update', 'destroy']);

        Route::get('/bookings', [StaffBookingController::class, 'index'])->name('bookings.index');
        Route::post('/bookings/walk-in', [StaffBookingController::class, 'storeWalkIn'])->name('bookings.walkin');
        Route::patch('/bookings/{booking}/status', [StaffBookingController::class, 'updateStatus'])->name('bookings.status');

        Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
        Route::post('/pos/{booking}/pay', [PosController::class, 'pay'])->name('pos.pay');
        Route::get('/pos/receipt/{transaction}', [PosController::class, 'receipt'])->name('pos.receipt');

        Route::get('/transactions', [StaffTransactionController::class, 'index'])->name('transactions.index');

        Route::middleware('role:admin')->group(function () {
            Route::resource('users', UserController::class)->only(['index', 'store', 'update', 'destroy']);
            Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
            Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
        });
    });

require __DIR__.'/auth.php';
