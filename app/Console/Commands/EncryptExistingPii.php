<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

#[Signature('app:encrypt-existing-pii')]
#[Description('One-off: encrypts any plaintext users.phone / bookings.customer_phone left over from before the "encrypted" cast was added. Safe to re-run — already-encrypted values are detected and skipped.')]
class EncryptExistingPii extends Command
{
    /**
     * Deliberately bypasses Eloquent/casts and talks to the raw columns via
     * the query builder. Reading a still-plaintext value through a model
     * that now has an 'encrypted' cast throws a DecryptException instead of
     * just returning the plaintext — this command exists specifically to
     * fix that state, so it can't rely on the thing it's fixing.
     */
    public function handle(): int
    {
        $this->encryptColumn('users', 'phone');
        $this->encryptColumn('bookings', 'customer_phone');

        $this->info('Done.');

        return self::SUCCESS;
    }

    private function encryptColumn(string $table, string $column): void
    {
        $rows = DB::table($table)->select('id', $column)->whereNotNull($column)->where($column, '!=', '')->get();

        $encrypted = 0;
        $alreadyDone = 0;

        foreach ($rows as $row) {
            $value = $row->{$column};

            // Already a valid encrypted payload — Crypt::decryptString()
            // succeeds, meaning a previous run (or the cast itself, once
            // some other field triggered a save) already handled this row.
            try {
                Crypt::decryptString($value);
                $alreadyDone++;

                continue;
            } catch (DecryptException) {
                // Not valid ciphertext — this is the plaintext we're here
                // to encrypt.
            }

            DB::table($table)->where('id', $row->id)->update([
                $column => Crypt::encryptString($value),
            ]);
            $encrypted++;
        }

        $this->line("{$table}.{$column}: encrypted {$encrypted}, already done {$alreadyDone}.");
    }
}
