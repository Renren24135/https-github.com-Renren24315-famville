<?php

namespace App\Console\Commands;

use App\Mail\ScheduledReportMail;
use App\Services\ReportService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

#[Signature('report:send {--range=daily : daily or weekly}')]
#[Description('Emails the executive report (revenue, utilization, peak hours) to configured recipients.')]
class SendScheduledReport extends Command
{
    public function handle(): int
    {
        $range = $this->option('range') === 'weekly' ? 'weekly' : 'daily';
        $summary = ReportService::forRange($range);
        $recipients = ReportService::recipientEmails();

        if ($recipients->isEmpty()) {
            $this->warn('No report recipients configured (no admin users and no report_recipients setting) — skipping.');

            return self::SUCCESS;
        }

        $rangeLabel = $range === 'weekly' ? 'Weekly' : 'Daily';

        try {
            Mail::to($recipients->first())
                ->cc($recipients->skip(1)->all())
                ->send(new ScheduledReportMail($summary, $rangeLabel));
        } catch (Throwable $e) {
            Log::error("Failed to send {$range} scheduled report email: ".$e->getMessage(), ['exception' => $e]);
            $this->error("Failed to send the {$rangeLabel} report: ".$e->getMessage());

            return self::FAILURE;
        }

        $this->info("{$rangeLabel} report emailed to: ".$recipients->implode(', '));

        return self::SUCCESS;
    }
}
