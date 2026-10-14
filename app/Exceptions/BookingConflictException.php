<?php

namespace App\Exceptions;

use Exception;

/**
 * Thrown inside a DB::transaction() closure when the asset turns out to be
 * already reserved for the requested time — lets the closure bail out
 * cleanly (triggering a rollback) while the controller catches it outside
 * the transaction to render the normal validation-error response.
 */
class BookingConflictException extends Exception
{
    public function __construct(public readonly string $assetName)
    {
        parent::__construct("{$assetName} is already reserved at that time.");
    }
}
