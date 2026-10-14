<?php

namespace App\Mail;

use App\Models\CashCount;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CashCountReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public CashCount $cashCount,
    ) {}

    public function envelope(): Envelope
    {
        $companyName = Setting::get('company_name', 'Famville Koronadal');

        return new Envelope(
            subject: "{$companyName} — Cash Count: {$this->cashCount->user->name} ({$this->cashCount->counted_at->format('M d, Y g:i A')})",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.cash-count-report',
            with: [
                'companyName' => Setting::get('company_name', 'Famville Koronadal'),
                'cashCount' => $this->cashCount,
            ],
        );
    }
}
