<?php

namespace App\Mail;

use App\Models\Booking;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingVoidRequestedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Booking $booking,
    ) {}

    public function envelope(): Envelope
    {
        $companyName = Setting::get('company_name', 'Famville Koronadal');

        return new Envelope(
            subject: "{$companyName} — Late customer: void approval needed",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.booking-void-requested',
            with: [
                'companyName' => Setting::get('company_name', 'Famville Koronadal'),
                'booking' => $this->booking,
            ],
        );
    }
}
