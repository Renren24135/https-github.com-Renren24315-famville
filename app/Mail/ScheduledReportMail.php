<?php

namespace App\Mail;

use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class ScheduledReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public array $summary,
        public string $rangeLabel,
        public ?string $recipientLabel = null,
    ) {}

    public function envelope(): Envelope
    {
        $companyName = Setting::get('company_name', 'Famville Koronadal');
        $subjectScope = $this->recipientLabel ? "{$this->recipientLabel}'s Sales" : $this->rangeLabel;

        return new Envelope(
            subject: "{$companyName} — {$subjectScope} Report ({$this->summary['from']->format('M d')}–{$this->summary['to']->format('M d, Y')})",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.scheduled-report',
            with: [
                'companyName' => Setting::get('company_name', 'Famville Koronadal'),
                'from' => $this->summary['from'],
                'to' => $this->summary['to'],
                'summary' => $this->summary,
                'rangeLabel' => $this->rangeLabel,
                'recipientLabel' => $this->recipientLabel,
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $companyName = Setting::get('company_name', 'Famville Koronadal');

        $pdf = Pdf::loadView('reports.pdf', [
            'companyName' => $companyName,
            'from' => $this->summary['from'],
            'to' => $this->summary['to'],
            'summary' => $this->summary,
            'recipientLabel' => $this->recipientLabel,
        ]);

        $filename = 'famville-report-'.$this->summary['from']->format('Y-m-d').'-to-'.$this->summary['to']->format('Y-m-d').'.pdf';

        return [
            Attachment::fromData(fn () => $pdf->output(), $filename)
                ->withMime('application/pdf'),
        ];
    }
}
