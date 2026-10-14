<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Log;
use Throwable;

abstract class Controller
{
    use AuthorizesRequests;

    /**
     * Send an email without letting a mail failure (e.g. the SMTP server
     * timing out or being unreachable) take down the whole request — the
     * booking/transaction change it's reporting on has already been saved,
     * so a notification hiccup should never turn into a 500 for the user.
     */
    protected function sendMailSafely(callable $send): void
    {
        try {
            $send();
        } catch (Throwable $e) {
            Log::error('Failed to send email: '.$e->getMessage(), ['exception' => $e]);
        }
    }

    /**
     * Hand an email off to the queue (a fast DB insert) instead of sending
     * it inline — a cashier clicking Approve/Verify GCash shouldn't have to
     * sit through a slow (this Gmail SMTP connection alone measured ~10-12s)
     * round trip just to see the page update. dispatch()->afterResponse()
     * looks like the obvious fix but doesn't actually help here: without
     * FastCGI (XAMPP's Apache+mod_php, and `artisan serve`, both lack
     * fastcgi_finish_request()), the response is never flushed to the
     * browser until the whole request — deferred work included — finishes,
     * so it wouldn't feel any faster. A real queue.php worker
     * (`php artisan queue:work`) is what actually decouples it; the mailable
     * just needs ->queue() instead of ->send() at the call site.
     */
    protected function queueMailSafely(callable $queue): void
    {
        try {
            $queue();
        } catch (Throwable $e) {
            Log::error('Failed to queue email: '.$e->getMessage(), ['exception' => $e]);
        }
    }
}
