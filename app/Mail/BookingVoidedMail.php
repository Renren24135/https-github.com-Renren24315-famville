<?php

namespace App\Mail;

use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingVoidedMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Plain values, not the Booking model — this mail is queued right
     * before the booking itself is deleted, and SerializesModels would try
     * to re-fetch it by id once the queue worker picks the job up, which
     * fails with a ModelNotFoundException once the row is already gone.
     */
    public function __construct(
        public string $customerName,
        public string $assetLabel,
        public string $bookingDate,
        public string $scheduledTime,
        public string $reason,
    ) {}

    public function envelope(): Envelope
    {
        $companyName = Setting::get('company_name', 'Famville Koronadal');

        return new Envelope(
            subject: "{$companyName} — Your reservation was cancelled",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.booking-voided',
            with: [
                'companyName' => Setting::get('company_name', 'Famville Koronadal'),
                'customerName' => $this->customerName,
                'assetLabel' => $this->assetLabel,
                'bookingDate' => $this->bookingDate,
                'scheduledTime' => $this->scheduledTime,
                'reason' => $this->reason,
            ],
        );
    }
}
