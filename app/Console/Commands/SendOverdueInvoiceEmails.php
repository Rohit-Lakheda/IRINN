<?php

namespace App\Console\Commands;

use App\Mail\OverdueInvoiceMail;
use App\Models\Invoice;
use App\Models\NodalOfficerEmail;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendOverdueInvoiceEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:send-overdue-emails
                            {--dry-run : Show what would be sent without actually sending emails}
                            {--application-id= : Only process invoices for a specific application}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send overdue invoice reminder emails to authorized representatives with BCC to nodal officers';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $now = now('Asia/Kolkata');
            $isDryRun = $this->option('dry-run');

            $this->info('========================================');
            $this->info('Sending Overdue Invoice Emails');
            $this->info('Started at: '.$now->format('Y-m-d H:i:s'));
            if ($isDryRun) {
                $this->warn('DRY RUN MODE - No emails will be sent');
            }
            $this->info('========================================');

            // Find overdue invoices (due_date < today, status pending or partial, not cancelled/credit note)
            $query = Invoice::with(['application.user'])
                ->whereHas('application', fn ($q) => $q->where('application_type', 'IRINN'))
                ->activeForTotals()
                ->where(function ($q) {
                    $q->where('status', 'pending')
                        ->orWhere('payment_status', 'partial');
                })
                ->whereDate('due_date', '<', $now->toDateString());

            // Filter by application ID if provided
            if ($this->option('application-id')) {
                $query->where('application_id', $this->option('application-id'));
                $this->info("✓ Filtering by application ID: {$this->option('application-id')}");
            }

            $overdueInvoices = $query->get();

            if ($overdueInvoices->isEmpty()) {
                $this->info('✓ No overdue invoices found.');

                return Command::SUCCESS;
            }

            $this->info("✓ Found {$overdueInvoices->count()} overdue invoice(s)");

            $emailsSent = 0;
            $emailsFailed = 0;

            foreach ($overdueInvoices as $invoice) {
                $application = $invoice->application;
                if (! $application) {
                    $this->warn("⚠ Invoice {$invoice->invoice_number} has no associated application - skipping");
                    $emailsFailed++;

                    continue;
                }

                // Get authorized representative email
                $authorizedRepDetails = $application->authorized_representative_details ?? [];
                $recipientEmail = $authorizedRepDetails['email'] ?? null;
                $recipientName = $authorizedRepDetails['name'] ?? $application->user->fullname ?? 'Valued Customer';

                if (! $recipientEmail) {
                    // Fallback to user email if authorized representative email not found
                    $recipientEmail = $application->user->email ?? null;
                    if (! $recipientEmail) {
                        $this->warn("⚠ Invoice {$invoice->invoice_number} - No recipient email found (application: {$application->application_id}) - skipping");
                        $emailsFailed++;

                        continue;
                    }
                }

                // Get nodal officer email from application's location
                $bccEmails = $this->getNodalOfficerEmailsForApplication($application);
                if (empty($bccEmails)) {
                    $this->warn("⚠ Invoice {$invoice->invoice_number} - No nodal officer email found for location - sending without BCC");
                } else {
                    $this->info('✓ BCC emails for this invoice: '.implode(', ', $bccEmails));
                }

                $daysOverdue = $now->diffInDays($invoice->due_date, false);
                $daysOverdue = abs($daysOverdue);

                $this->info('----------------------------------------');
                $this->info("Invoice: {$invoice->invoice_number}");
                $this->info("Application: {$application->application_id}");
                $this->info("Recipient: {$recipientName} ({$recipientEmail})");
                $this->info('Amount: ₹'.number_format($invoice->balance_amount ?? $invoice->total_amount, 2));
                $this->info("Days Overdue: {$daysOverdue}");

                if ($isDryRun) {
                    $this->info("→ [DRY RUN] Would send email to {$recipientEmail}");
                    if (! empty($bccEmails)) {
                        $this->info('→ [DRY RUN] BCC: '.implode(', ', $bccEmails));
                    }
                    $emailsSent++;

                    continue;
                }

                try {
                    // Send email with BCC
                    $mailable = new OverdueInvoiceMail(
                        $invoice,
                        $application,
                        $recipientEmail,
                        $recipientName
                    );

                    // Add BCC if nodal officer emails are configured
                    if (! empty($bccEmails)) {
                        $mailable->bcc($bccEmails);
                    }

                    Mail::to($recipientEmail)->send($mailable);

                    $this->info('✓ Email sent successfully');
                    $emailsSent++;

                    Log::info('Overdue invoice email sent', [
                        'invoice_id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                        'application_id' => $application->application_id,
                        'recipient_email' => $recipientEmail,
                        'recipient_name' => $recipientName,
                        'bcc_emails' => $bccEmails,
                        'days_overdue' => $daysOverdue,
                    ]);
                } catch (Exception $e) {
                    $this->error('✗ Failed to send email: '.$e->getMessage());
                    $emailsFailed++;

                    Log::error('Failed to send overdue invoice email', [
                        'invoice_id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                        'application_id' => $application->application_id,
                        'recipient_email' => $recipientEmail,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $this->info('========================================');
            $this->info("✓ Processed: {$emailsSent} emails sent, {$emailsFailed} failed");
            $this->info('Completed at: '.now('Asia/Kolkata')->format('Y-m-d H:i:s'));
            $this->info('========================================');

            return Command::SUCCESS;
        } catch (Exception $e) {
            $this->error('========================================');
            $this->error('✗ ERROR: '.$e->getMessage());
            $this->error('File: '.$e->getFile().':'.$e->getLine());
            $this->error('========================================');
            Log::error('Error sending overdue invoice emails: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }

    /**
     * Get nodal officer email(s) for an application based on its location.
     */
    private function getNodalOfficerEmailsForApplication($application): array
    {
        $bccEmails = [];

        try {
            $applicationData = $application->application_data ?? [];
            $locationData = $applicationData['location'] ?? null;

            if (! $locationData) {
                // Try to get location from IX location ID if stored separately
                // This is a fallback - normally location should be in application_data
                return [];
            }

            // Get nodal officer name from application data (already stored when location was selected)
            $nodalOfficerName = $locationData['nodal_officer'] ?? null;

            if ($nodalOfficerName) {
                // First try to get email from database mapping
                $email = NodalOfficerEmail::getEmailByName($nodalOfficerName);

                // If not found in database, fallback to hardcoded mapping
                if (! $email) {
                    $email = $this->convertNodalOfficerNameToEmail($nodalOfficerName);
                }

                if ($email) {
                    $bccEmails[] = $email;
                } else {
                    Log::warning('No email mapping found for nodal officer', [
                        'nodal_officer_name' => $nodalOfficerName,
                        'application_id' => $application->id,
                    ]);
                }
            }
        } catch (Exception $e) {
            Log::error('Error getting nodal officer email for application: '.$e->getMessage(), [
                'application_id' => $application->id,
            ]);
        }

        return $bccEmails;
    }

    /**
     * Convert nodal officer name to email address.
     */
    private function convertNodalOfficerNameToEmail(string $name): ?string
    {
        // Normalize the name (trim, lowercase for comparison)
        $normalizedName = strtolower(trim($name));

        // Map known nodal officer names to their email addresses
        $emailMapping = [
            'chirag vasani' => 'chirag.vasani@nixi.in',
            'jignesh patel' => 'jignesh@nixi.in',
            'rajesh kumar' => 'rajesh@nixi.in',
            'shashank sharma' => 'shashank@nixi.in',
        ];

        // Check exact match first
        if (isset($emailMapping[$normalizedName])) {
            return $emailMapping[$normalizedName];
        }

        // Try partial match (e.g., "Chirag Vasani" matches "chirag vasani")
        foreach ($emailMapping as $key => $email) {
            if (strpos($normalizedName, $key) !== false || strpos($key, $normalizedName) !== false) {
                return $email;
            }
        }

        // If no match found, try to generate email from name format
        // Format: "First Last" -> "first.last@nixi.in"
        $parts = explode(' ', $normalizedName);
        if (count($parts) >= 2) {
            $firstName = $parts[0];
            $lastName = $parts[count($parts) - 1];
            $generatedEmail = $firstName.'.'.$lastName.'@nixi.in';

            // Log warning for manual review
            Log::warning('Nodal officer email not found in mapping, using generated email', [
                'name' => $name,
                'generated_email' => $generatedEmail,
            ]);

            return $generatedEmail;
        }

        return null;
    }
}
