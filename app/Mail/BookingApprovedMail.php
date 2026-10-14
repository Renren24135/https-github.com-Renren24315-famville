<?php

namespace App\Mail;

use App\Models\Booking;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Booking $booking,
    ) {}

    public function envelope(): Envelope
    {
        $companyName = Setting::get('company_name', 'Famville Koronadal');

        return new Envelope(
            subject: "{$companyName} — Your booking is approved",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.booking-approved',
            with: [
                'companyName' => Setting::get('company_name', 'Famville Koronadal'),
                'booking' => $this->booking,
            ],
        );
    }
}
