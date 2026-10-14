<?php

use App\Http\Controllers\AvailabilityController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\Customer\BookingController as CustomerBookingController;
use App\Http\Controllers\Customer\DashboardController as CustomerDashboardController;
use App\Http\Controllers\Customer\TransactionController as CustomerTransactionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PayMongoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Staff\BilliardTableController;
use App\Http\Controllers\Staff\ClosureController;
use App\Http\Controllers\Staff\BookingController as StaffBookingController;
use App\Http\Controllers\Staff\CashCountController;
use App\Http\Controllers\Staff\DashboardController as StaffDashboardController;
use App\Http\Controllers\Staff\LaneController;
use App\Http\Controllers\Staff\MonthlyArchiveController;
use App\Http\Controllers\Staff\PosController;
use App\Http\Controllers\Staff\RateTierController;
use App\Http\Controllers\Staff\SettingController;
use App\Http\Controllers\Staff\TransactionController as StaffTransactionController;
use App\Http\Controllers\Staff\UserController;
use App\Http\Controllers\TwoFactorSettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/availability/{type}/{id}', [AvailabilityController::class, 'show'])->name('availability.show');

// Public — PayMongo itself calls the webhook (no session, no CSRF token),
// and the return route is where PayMongo redirects the customer's browser
// back to after they pay/cancel on PayMongo's own hosted page.
Route::post('/payments/paymongo/webhook', [PayMongoController::class, 'webhook'])->name('paymongo.webhook');

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Outside EnsureTwoFactorChallenged's own scope by design — these ARE
    // the escape hatch it redirects to. 'auth' only: the user is already
    // session-authenticated (Auth::attempt() did that) but not yet
    // 2fa_verified, so 'verified'/role middleware would be premature here.
    Route::get('/two-factor/challenge', [TwoFactorController::class, 'showChallenge'])->name('two-factor.challenge');
    Route::post('/two-factor/challenge', [TwoFactorController::class, 'verifyChallenge'])
        ->middleware('throttle:10,1')
        ->name('two-factor.verify');
    Route::post('/two-factor/recovery', [TwoFactorController::class, 'useRecoveryCode'])
        ->middleware('throttle:10,1')
        ->name('two-factor.recovery');

    Route::get('/settings/two-factor', [TwoFactorSettingsController::class, 'show'])->name('two-factor.show');
    Route::post('/settings/two-factor/enable', [TwoFactorSettingsController::class, 'enable'])->name('two-factor.enable');
    Route::post('/settings/two-factor/recovery-codes', [TwoFactorSettingsController::class, 'regenerateRecoveryCodes'])->name('two-factor.recovery-codes');
    Route::delete('/settings/two-factor', [TwoFactorSettingsController::class, 'disable'])
        ->middleware('password.confirm')
        ->name('two-factor.disable');
});

Route::middleware(['auth', 'verified', 'role:customer'])
    ->prefix('customer')
    ->name('customer.')
    ->group(function () {
        Route::get('/dashboard', [CustomerDashboardController::class, 'index'])->name('dashboard');

        Route::get('/bookings', [CustomerBookingController::class, 'index'])->name('bookings.index');
        Route::get('/bookings/create', [CustomerBookingController::class, 'create'])->name('bookings.create');
        Route::post('/bookings', [CustomerBookingController::class, 'store'])
            ->middleware('throttle:10,1')
            ->name('bookings.store');
        Route::get('/bookings/{booking}', [CustomerBookingController::class, 'show'])->name('bookings.show');
        Route::post('/bookings/{booking}/cancel', [CustomerBookingController::class, 'cancel'])->name('bookings.cancel');

        Route::get('/transactions', [CustomerTransactionController::class, 'index'])->name('transactions.index');
    });

Route::middleware(['auth', 'verified', 'role:customer'])->group(function () {
    // GET, not POST — the booking wizard reaches this via a 302 redirect
    // Location header (which the browser always follows with GET), and
    // the balance button is a plain link. Neither one is the actual
    // charge (that's PayMongo's hosted page + the webhook) — this just
    // creates the source and forwards the browser to it.
    Route::get('/customer/bookings/{booking}/pay-fee-gcash', [PayMongoController::class, 'payFee'])
        ->middleware('throttle:10,1')
        ->name('paymongo.pay-fee');
    Route::get('/customer/bookings/{booking}/pay-balance-gcash', [PayMongoController::class, 'payBalance'])
        ->middleware('throttle:10,1')
        ->name('paymongo.pay-balance');
    Route::get('/payments/paymongo/return/{booking}', [PayMongoController::class, 'return'])->name('paymongo.return');
});

Route::middleware(['auth', 'verified', 'role:admin|cashier'])
    ->prefix('staff')
    ->name('staff.')
    ->group(function () {
        Route::get('/dashboard', [StaffDashboardController::class, 'index'])->name('dashboard');

        Route::get('/bookings', [StaffBookingController::class, 'index'])->name('bookings.index');
        Route::get('/bookings/notifications', [StaffBookingController::class, 'notifications'])->name('bookings.notifications');
        Route::patch('/bookings/{booking}/status', [StaffBookingController::class, 'updateStatus'])->name('bookings.status');

        Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
        Route::post('/pos/walk-in', [PosController::class, 'storeWalkIn'])
            ->middleware('throttle:10,1')
            ->name('pos.walkin');
        Route::post('/pos/{booking}/pay', [PosController::class, 'pay'])
            ->middleware('throttle:20,1')
            ->name('pos.pay');
        Route::get('/pos/receipt/{transaction}', [PosController::class, 'receipt'])->name('pos.receipt');

        Route::get('/transactions', [StaffTransactionController::class, 'index'])->name('transactions.index');

        Route::post('/dashboard/email-report', [StaffDashboardController::class, 'emailReportNow'])
            ->middleware('permission:send-reports')
            ->name('dashboard.email-report');

        Route::post('/cash-count-logout', [CashCountController::class, 'store'])
            ->middleware('role:cashier')
            ->name('cash-count.logout');

        Route::middleware('role:admin')->group(function () {
            Route::resource('lanes', LaneController::class)->only(['index', 'store', 'update', 'destroy']);

            Route::resource('billiard-tables', BilliardTableController::class)
                ->parameters(['billiard-tables' => 'billiard_table'])
                ->only(['index', 'store', 'update', 'destroy']);

            Route::resource('rate-tiers', RateTierController::class)
                ->parameters(['rate-tiers' => 'rate_tier'])
                ->only(['index', 'store', 'update', 'destroy']);

            Route::resource('closures', ClosureController::class)->only(['index', 'store', 'update', 'destroy']);

            Route::resource('users', UserController::class)->only(['index', 'store', 'update', 'destroy']);
            Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
            Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
            Route::get('/monthly-archive', [MonthlyArchiveController::class, 'index'])->name('monthly-archive.index');
        });
    });

require __DIR__.'/auth.php';
