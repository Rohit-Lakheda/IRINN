<?php

namespace App\Console\Commands;

use App\Mail\IxApplicationInvoiceMail;
use App\Models\Application;
use App\Models\Invoice;
use App\Models\Message;
use App\Models\PaymentTransaction;
use App\Models\PlanChangeRequest;
use App\Models\ApplicationStatusHistory;
use App\Models\Registration;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Exception;

class InvoiceMailReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'membership:process-invoices {--user-id= : Filter by specific user ID} {--application-id= : Filter by specific application ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate invoices for new live memberships and send renewal reminder emails based on billing cycle';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $now = now('Asia/Kolkata');
            $this->info('========================================');
            $this->info('Processing membership invoices and reminders...');
            $this->info('Started at: '.$now->format('Y-m-d H:i:s'));
            $this->info('========================================');

            // Get all LIVE applications
            $query = Application::where('application_type', 'IX')
                ->where('is_active', true)
                ->whereNotNull('service_activation_date')
                ->whereNotNull('billing_cycle')
                ->with(['user', 'invoices']);

            // Filter by user ID if provided (for testing)
            if ($this->option('user-id')) {
                $userId = $this->option('user-id');
                $query->where('user_id', $userId);
                $this->info("✓ Filtering by user ID: {$userId}");
                
                // Verify user exists (Application uses Registration model, not User)
                $userExists = Registration::where('id', $userId)->exists();
                if (!$userExists) {
                    // Check if there are orphaned applications with this user_id
                    $orphanedCount = Application::where('user_id', $userId)->count();
                    if ($orphanedCount > 0) {
                        $this->error("✗ User ID {$userId} does not exist in the database!");
                        $this->warn("⚠ Found {$orphanedCount} orphaned application(s) with user_id {$userId}.");
                        $this->warn("⚠ These applications cannot be processed without a valid user record.");
                        return Command::FAILURE;
                    }
                    $this->error("✗ User ID {$userId} does not exist in the database!");
                    return Command::FAILURE;
                }
                $this->info("✓ User ID {$userId} exists in database");
            }

            // Filter by application ID if provided (for testing)
            if ($this->option('application-id')) {
                $applicationId = $this->option('application-id');
                $query->where('id', $applicationId);
                $this->info("✓ Filtering by application ID: {$applicationId}");
            }

            $applications = $query->get();

            if ($applications->isEmpty()) {
                $this->warn('========================================');
                $this->warn('⚠ No applications found matching the criteria.');
                $this->warn('========================================');
                $this->info('Query conditions:');
                $this->info('  - application_type: IX');
                $this->info('  - is_active: true');
                $this->info('  - service_activation_date: not null');
                $this->info('  - billing_cycle: not null');
                if ($this->option('user-id')) {
                    $this->info('  - user_id: '.$this->option('user-id'));
                }
                if ($this->option('application-id')) {
                    $this->info('  - id: '.$this->option('application-id'));
                }
                return Command::SUCCESS;
            }

            $this->info("✓ Found {$applications->count()} application(s) to process.");

            $invoicesGenerated = 0;
            $remindersSent = 0;

            foreach ($applications as $application) {
                $this->info("----------------------------------------");
                $this->info("Processing Application ID: {$application->id} ({$application->application_id})");
                $this->info("User: {$application->user->fullname} (ID: {$application->user_id})");
                $this->info("Billing Cycle: {$application->billing_cycle}");
                
                // Check if invoice needs to be generated (application is live but has no invoice)
                $hasInvoice = $application->invoices()->where('status', '!=', 'cancelled')->exists();

                if (! $hasInvoice) {
                    $this->info("→ No invoice found - generating new invoice...");
                    // Generate invoice for newly live membership
                    if ($this->generateInvoiceForApplication($application)) {
                        $invoicesGenerated++;
                        $this->info("✓ Generated invoice for application {$application->application_id}");
                    } else {
                        $this->warn("⚠ Failed to generate invoice for application {$application->application_id}");
                    }
                } else {
                    $latestInvoice = $application->invoices()->where('status', '!=', 'cancelled')->latest('invoice_date')->first();
                    $this->info("→ Invoice already exists: {$latestInvoice->invoice_number} (Status: {$latestInvoice->status})");
                }

                // Send reminder emails based on billing cycle
                $this->info("→ Checking renewal reminders...");
                $remindersSent += $this->sendRenewalReminders($application, $now);
            }

            $this->info("========================================");
            $this->info("✓ Processed: {$invoicesGenerated} invoices generated, {$remindersSent} reminders sent");
            $this->info("Completed at: ".now('Asia/Kolkata')->format('Y-m-d H:i:s'));
            $this->info("========================================");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("========================================");
            $this->error("✗ ERROR: Error processing membership invoices");
            $this->error("Message: ".$e->getMessage());
            $this->error("File: ".$e->getFile().":".$e->getLine());
            $this->error("========================================");
            Log::error('Error processing membership invoices: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }

    /**
     * Generate invoice for application when it becomes live.
     */
    private function generateInvoiceForApplication(Application $application): bool
    {
        try {
            // Check if invoice already exists
            $existingInvoice = Invoice::where('application_id', $application->id)
                ->where('status', '!=', 'cancelled')
                ->first();

            if ($existingInvoice) {
                return false;
            }

            // Use AdminController's invoice generation logic
            // We'll need to call the calculateInvoiceDetails method
            // For now, let's use a simplified approach or call the controller method
            $adminController = app(\App\Http\Controllers\AdminController::class);
            $invoiceData = $this->calculateInvoiceDetails($application);

            if (isset($invoiceData['error'])) {
                Log::error("Cannot generate invoice for application {$application->id}: {$invoiceData['error']}");

                return false;
            }

            // Create invoice using the calculated data
            $invoice = $this->createInvoice($application, $invoiceData);

            if ($invoice) {
                // Send invoice email
                $this->sendInvoiceEmail($application, $invoice);

                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error("Error generating invoice for application {$application->id}: ".$e->getMessage());

            return false;
        }
    }

    /**
     * Calculate invoice details using existing AdminController::calculateInvoiceDetails() method.
     * This reuses the same invoice calculation logic used in the admin panel.
     */
    private function calculateInvoiceDetails(Application $application): array
    {
        try {
            $adminController = app(\App\Http\Controllers\AdminController::class);
            $reflection = new \ReflectionClass($adminController);
            $method = $reflection->getMethod('calculateInvoiceDetails');
            $method->setAccessible(true);

            return $method->invoke($adminController, $application);
        } catch (\Exception $e) {
            Log::error('Error calculating invoice details: '.$e->getMessage());

            return ['error' => 'Error calculating invoice details: '.$e->getMessage()];
        }
    }

    /**
     * Create invoice from calculated data.
     */
    private function createInvoice(Application $application, array $invoiceData): ?Invoice
    {
        try {
            // Generate invoice number using same format as AdminController (NIXIEX2526-XXXX)
            $baseInvoiceNumber = 'NIXIEX2526-';

            // Get last invoice with this prefix only
            $lastInvoice = \Illuminate\Support\Facades\DB::table('invoices')
                ->where('invoice_number', 'like', $baseInvoiceNumber.'%')
                ->orderBy('id', 'desc')
                ->value('invoice_number');

            if ($lastInvoice && preg_match('/NIXIEX2526-(\d{4})$/', $lastInvoice, $matches)) {
                $lastNumber = (int) $matches[1];
                $nextNumber = $lastNumber + 1;
            } else {
                // First invoice - start from 1923
                $nextNumber = 1923;
            }

            // Final invoice number
            $invoiceNumber = $baseInvoiceNumber.str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

            // Check if invoice number already exists and make it unique
            $counter = 1;
            $originalInvoiceNumber = $invoiceNumber;
            while (Invoice::where('invoice_number', $invoiceNumber)->exists()) {
                // If invoice number exists, try next sequential number
                $nextNumber = $nextNumber + 1;
                $invoiceNumber = $baseInvoiceNumber.str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
                $counter++;

                // Safety check to prevent infinite loop
                if ($counter > 100) {
                    Log::error("Unable to generate unique invoice number for application {$application->id} after 100 attempts");

                    return null;
                }
            }

            Log::info("Generated invoice number: {$invoiceNumber} for application {$application->id}, billing period: {$invoiceData['billing_period']}");

            // Prepare line items data from segments
            $lineItemsData = $invoiceData['segments'] ?? [];
            $adjustments = $invoiceData['adjustments'] ?? [];
            $carryForwardInvoices = $invoiceData['carry_forward_invoices'] ?? [];

            // Add adjustments as line items if present (only if not already in lineItemsData)
            $adjustmentsInLineItems = false;
            if (is_array($lineItemsData)) {
                foreach ($lineItemsData as $item) {
                    if (is_array($item) && isset($item['is_adjustment']) && $item['is_adjustment']) {
                        $adjustmentsInLineItems = true;
                        break;
                    }
                }
            }

            // Only add adjustments if they're not already in line items
            if (!empty($adjustments) && !$adjustmentsInLineItems) {
                foreach ($adjustments as $adj) {
                    $adjAmount = (float) ($adj['amount'] ?? 0);
                    $isUpgrade = ($adj['type'] ?? '') === 'upgrade' || $adjAmount > 0;
                    $isDowngrade = ($adj['type'] ?? '') === 'downgrade' || $adjAmount < 0;

                    $description = ucfirst($adj['type'] ?? 'Adjustment').' Adjustment: '.($adj['description'] ?? 'Plan change adjustment');
                    if (isset($adj['effective_from']) && $adj['effective_from']) {
                        $description .= ' (Effective from: '.\Carbon\Carbon::parse($adj['effective_from'])->format('d/m/Y').')';
                    }

                    if ($isUpgrade) {
                        $description .= ' - Additional payment (GST will be calculated)';
                    } elseif ($isDowngrade) {
                        $description .= ' - Credit (GST already paid on excess amount)';
                    }

                    $lineItemsData[] = [
                        'description' => $description,
                        'quantity' => 1,
                        'rate' => abs($adjAmount),
                        'amount' => $adjAmount, // Keep original sign (positive for upgrade, negative for downgrade)
                        'is_adjustment' => true,
                        'adjustment_type' => $adj['type'] ?? 'adjustment',
                    ];
                }
            }

            // Add carry forward as a line item if present
            $hasCarryForward = $invoiceData['has_carry_forward'] ?? false;
            $carryForwardAmount = $invoiceData['carry_forward_amount'] ?? 0;
            if ($hasCarryForward && $carryForwardAmount > 0) {
                $carryForwardDescription = 'Carry Forward from Previous Invoice(s): ';
                $invoiceNumbers = array_map(function ($inv) {
                    return $inv['invoice_number'];
                }, $carryForwardInvoices);
                $carryForwardDescription .= implode(', ', $invoiceNumbers);

                $lineItemsData[] = [
                    'description' => $carryForwardDescription,
                    'quantity' => 1,
                    'rate' => $carryForwardAmount,
                    'amount' => $carryForwardAmount,
                    'is_carry_forward' => true,
                ];
            }

            // Ensure due_date is properly formatted as date string
            $dueDateFormatted = $invoiceData['due_date'] instanceof \Carbon\Carbon 
                ? $invoiceData['due_date']->format('Y-m-d') 
                : $invoiceData['due_date'];

            Log::info("Preparing invoice for application {$application->id}: invoiceNumber='{$invoiceNumber}', invoiceDate=".now('Asia/Kolkata')->format('Y-m-d').", dueDate={$dueDateFormatted}, billingPeriod='{$invoiceData['billing_period']}'");

            // Create temporary invoice object (not saved) to call e-invoice API first
            // Note: We don't create PaymentTransaction yet - only after e-invoice API succeeds
            $tempInvoice = new Invoice([
                'application_id' => $application->id,
                'invoice_number' => $invoiceNumber,
                'invoice_date' => now('Asia/Kolkata'),
                'due_date' => $dueDateFormatted,
                'billing_period' => $invoiceData['billing_period'],
                'billing_start_date' => $invoiceData['billing_start_date'],
                'billing_end_date' => $invoiceData['billing_end_date'],
                'line_items' => $lineItemsData,
                'amount' => $invoiceData['amount'],
                'gst_amount' => $invoiceData['gst_amount'],
                'total_amount' => $invoiceData['final_total_amount'],
                'paid_amount' => 0,
                'balance_amount' => $invoiceData['final_total_amount'],
                'payment_status' => 'pending',
                'carry_forward_amount' => $carryForwardAmount,
                'has_carry_forward' => $hasCarryForward,
                'currency' => 'INR',
                'status' => 'pending',
                'payu_payment_link' => null, // Will be set after API succeeds
                'generated_by' => null, // System generated
            ]);

            // Call e-invoice API BEFORE creating invoice or payment transaction - if it fails, don't create anything
            Log::info("Calling e-invoice API for invoice {$invoiceNumber} before creating invoice record");
            $einvoiceData = $this->callEinvoiceApiViaReflection($application, $tempInvoice);
            
            // Check if e-invoice API call was successful
            $isEinvoiceSuccess = false;
            if ($einvoiceData && is_array($einvoiceData)) {
                $status = $einvoiceData['Status'] ?? $einvoiceData['status'] ?? '';
                $irn = $einvoiceData['Irn'] ?? '';
                $errorCode = $einvoiceData['ErrorCode'] ?? '';
                
                // For ErrorCode 2150 (Duplicate IRN), extract IRN and AckNo from InfoDtls
                if ($errorCode === '2150' && empty($irn) && isset($einvoiceData['InfoDtls']) && is_array($einvoiceData['InfoDtls'])) {
                    // Extract IRN and AckNo from InfoDtls array
                    foreach ($einvoiceData['InfoDtls'] as $infoDetail) {
                        if (isset($infoDetail['InfCd']) && $infoDetail['InfCd'] === 'DUPIRN' && isset($infoDetail['Desc'])) {
                            $desc = $infoDetail['Desc'];
                            $irn = $desc['Irn'] ?? '';
                            // Update einvoiceData with extracted values for later storage
                            $einvoiceData['Irn'] = $irn;
                            $einvoiceData['AckNo'] = $desc['AckNo'] ?? '';
                            $einvoiceData['AckDate'] = $desc['AckDt'] ?? '';
                            break;
                        }
                    }
                }
                
                // API is successful if:
                // 1. Status is '1' and IRN is not empty (normal success)
                // 2. ErrorCode is '2150' (Duplicate IRN) and IRN is extracted from InfoDtls
                $isEinvoiceSuccess = (($status === '1' || $status === 1) && !empty($irn)) 
                    || ($errorCode === '2150' && !empty($irn));
                
                // Log duplicate IRN as info (not error) since it's acceptable
                if ($errorCode === '2150' && !empty($irn)) {
                    Log::info("E-invoice API returned duplicate IRN - invoice already registered, proceeding with invoice creation", [
                        'application_id' => $application->id,
                        'invoice_number' => $invoiceNumber,
                        'irn' => $irn,
                        'ack_no' => $einvoiceData['AckNo'] ?? null,
                    ]);
                }
            }

            if (!$isEinvoiceSuccess) {
                $errorMessage = $einvoiceData['ErrorMessage'] ?? 'E-invoice API call failed or returned error';
                $errorCode = $einvoiceData['ErrorCode'] ?? 'Unknown';
                Log::error("E-invoice API failed - invoice will not be created", [
                    'application_id' => $application->id,
                    'invoice_number' => $invoiceNumber,
                    'error_code' => $errorCode,
                    'error_message' => $errorMessage,
                    'response' => $einvoiceData,
                ]);
                // Throw exception to prevent invoice creation - this will be caught by outer try-catch
                throw new Exception("E-invoice API failed (ErrorCode: {$errorCode}): {$errorMessage}. Invoice was not created.");
            }

            Log::info("E-invoice API succeeded for invoice {$invoiceNumber} - proceeding with invoice creation");

            // E-invoice API succeeded - now create PaymentTransaction and invoice
            // Generate PayU payment link
            $payuService = new \App\Services\PayuService;
            $transactionId = 'INV-'.time().'-'.strtoupper(\Illuminate\Support\Str::random(8));

            // Create PaymentTransaction for invoice payment (only after API succeeds)
            $paymentTransaction = PaymentTransaction::create([
                'user_id' => $application->user_id,
                'application_id' => $application->id,
                'transaction_id' => $transactionId,
                'payment_status' => 'pending',
                'payment_mode' => 'live',
                'amount' => $invoiceData['final_total_amount'],
                'currency' => 'INR',
                'product_info' => 'NIXI IX Service Invoice - '.$invoiceNumber,
                'response_message' => 'Invoice payment pending',
            ]);

            $paymentData = $payuService->preparePaymentData([
                'transaction_id' => $transactionId,
                'amount' => $invoiceData['final_total_amount'],
                'product_info' => 'NIXI IX Service Invoice - '.$invoiceNumber,
                'firstname' => $application->user->fullname,
                'email' => $application->user->email,
                'phone' => $application->user->mobile,
                'success_url' => url(route('user.applications.ix.payment-success', [], false)),
                'failure_url' => url(route('user.applications.ix.payment-failure', [], false)),
                'udf1' => $application->application_id,
                'udf2' => (string) $paymentTransaction->id, // Store payment transaction ID
                'udf3' => $invoiceNumber,
            ]);

            Log::info("Creating invoice for application {$application->id}: invoiceNumber='{$invoiceNumber}', invoiceDate=".now('Asia/Kolkata')->format('Y-m-d').", dueDate={$dueDateFormatted}, billingPeriod='{$invoiceData['billing_period']}'");

            // E-invoice API succeeded - now create the invoice
            $invoice = Invoice::create([
                'application_id' => $application->id,
                'invoice_number' => $invoiceNumber,
                'invoice_date' => now('Asia/Kolkata'),
                'due_date' => $dueDateFormatted,
                'billing_period' => $invoiceData['billing_period'],
                'billing_start_date' => $invoiceData['billing_start_date'],
                'billing_end_date' => $invoiceData['billing_end_date'],
                'line_items' => $lineItemsData,
                'amount' => $invoiceData['amount'],
                'gst_amount' => $invoiceData['gst_amount'],
                'total_amount' => $invoiceData['final_total_amount'], // Use final_total_amount (includes carry forward)
                'paid_amount' => 0,
                'balance_amount' => $invoiceData['final_total_amount'],
                'payment_status' => 'pending',
                'carry_forward_amount' => $carryForwardAmount,
                'has_carry_forward' => $hasCarryForward,
                'currency' => 'INR',
                'status' => 'pending',
                'payu_payment_link' => json_encode($paymentData), // Store full payment data
                'generated_by' => null, // System generated
            ]);

            // Mark adjustments as applied
            if (!empty($adjustments)) {
                foreach ($adjustments as $adj) {
                    if (isset($adj['plan_change_id'])) {
                        PlanChangeRequest::where('id', $adj['plan_change_id'])->update([
                            'adjustment_applied' => true,
                            'adjustment_invoice_id' => $invoice->id,
                        ]);
                    }
                }
                Log::info('Marked '.count($adjustments)." adjustments as applied for invoice {$invoice->id}");
            }

            // Mark previous invoices as paid if carry forward is applied
            if ($hasCarryForward && !empty($carryForwardInvoices)) {
                foreach ($carryForwardInvoices as $cfInvoice) {
                    $previousInvoice = Invoice::find($cfInvoice['invoice_id']);
                    if ($previousInvoice) {
                        $forwardedAmount = $cfInvoice['amount'];
                        // When amount is carried forward: paid_amount = total_amount - forwarded_amount
                        // This ensures: Total = Paid + Forwarded (correct calculation)
                        $calculatedPaidAmount = $previousInvoice->total_amount - $forwardedAmount;
                        $previousInvoice->update([
                            'payment_status' => 'paid', // Mark as paid since full amount is handled (paid + forwarded)
                            'status' => 'paid',
                            'paid_amount' => $calculatedPaidAmount, // Set as total - forwarded for correct calculation
                            'balance_amount' => 0, // Balance is forwarded, so it's 0
                            'forwarded_amount' => $forwardedAmount,
                            'forwarded_to_invoice_date' => $invoice->invoice_date,
                            'has_carry_forward' => true, // Mark that this invoice had carry forward
                            'carry_forward_amount' => $forwardedAmount, // Store the forwarded amount
                            'paid_at' => now('Asia/Kolkata'),
                            'paid_by' => null, // System generated
                            'manual_payment_notes' => ($previousInvoice->manual_payment_notes ? $previousInvoice->manual_payment_notes.' | ' : '')."Amount forwarded to invoice {$invoice->invoice_number}",
                        ]);
                        Log::info("Marked invoice {$previousInvoice->invoice_number} as paid (forwarded {$forwardedAmount} to invoice {$invoice->invoice_number}). Paid amount: {$calculatedPaidAmount} (Total: {$previousInvoice->total_amount} - Forwarded: {$forwardedAmount})");
                    }
                }
            }

            Log::info("Invoice created successfully: ID={$invoice->id}, due_date={$invoice->due_date}, billing_start_date={$invoice->billing_start_date}, billing_end_date={$invoice->billing_end_date}");

            // Store e-invoice API response (already called and verified before invoice creation)
            if ($einvoiceData) {
                // Prepare signed data (SignedInvoice and SignedQRCode) for JSON storage
                $signedData = [];
                if (isset($einvoiceData['SignedInvoice'])) {
                    $signedData['SignedInvoice'] = $einvoiceData['SignedInvoice'];
                }
                if (isset($einvoiceData['SignedQRCode'])) {
                    $signedData['SignedQRCode'] = $einvoiceData['SignedQRCode'];
                }

                // Prepare update data
                $updateData = [
                    'einvoice_signed_data' => !empty($signedData) ? $signedData : null,
                    'einvoice_response' => $einvoiceData, // Store full response for reference
                ];

                // Store other fields in separate columns (matching exact API response field names)
                $fieldsToStore = [
                    'Irn' => 'einvoice_irn',
                    'AckNo' => 'einvoice_ack_no',
                    'AckDate' => 'einvoice_ack_date',
                    'Status' => 'einvoice_status',
                    'ErrorMessage' => 'einvoice_error_message',
                    'ErrorCode' => 'einvoice_error_code',
                ];

                foreach ($fieldsToStore as $apiField => $dbField) {
                    if (isset($einvoiceData[$apiField])) {
                        $value = $einvoiceData[$apiField];
                        // Convert AckNo to string if it's numeric
                        if ($apiField === 'AckNo' && is_numeric($value)) {
                            $value = (string) $value;
                        }
                        // Skip empty strings for error fields
                        if (in_array($apiField, ['ErrorMessage', 'ErrorCode']) && (empty($value) || $value === '')) {
                            continue;
                        }
                        $updateData[$dbField] = $value;
                    }
                }

                // Update invoice with API response data
                try {
                    $invoice->update($updateData);
                    Log::info("E-invoice API response stored for invoice {$invoice->id}", [
                        'irn' => $updateData['einvoice_irn'] ?? null,
                        'ack_no' => $updateData['einvoice_ack_no'] ?? null,
                        'status' => $updateData['einvoice_status'] ?? null,
                        'has_signed_data' => !empty($signedData),
                    ]);
                } catch (Exception $e) {
                    Log::error("Failed to store e-invoice response for invoice {$invoice->id}: ".$e->getMessage());
                }
            } else {
                Log::warning("E-invoice API call failed or returned no data for invoice {$invoice->id}");
            }

            // Generate PDF using existing AdminController::generateIxInvoicePdf() method
            // This reuses the same PDF generation function used in the admin panel
            try {
                $adminController = app(\App\Http\Controllers\AdminController::class);
                $reflection = new \ReflectionClass($adminController);
                $method = $reflection->getMethod('generateIxInvoicePdf');
                $method->setAccessible(true);
                $invoicePdf = $method->invoke($adminController, $application, $invoice);

                $invoicePdfPath = 'applications/'.$application->user_id.'/ix/'.$invoiceNumber.'_invoice.pdf';
                Storage::disk('public')->put($invoicePdfPath, $invoicePdf->output());
                $invoice->update(['pdf_path' => $invoicePdfPath]);
                Log::info("Invoice PDF generated using existing function: {$invoicePdfPath}");
            } catch (Exception $e) {
                Log::error('Error generating invoice PDF: '.$e->getMessage());
                // Continue even if PDF generation fails
            }

            // Log invoice generation
            ApplicationStatusHistory::log(
                $application->id,
                $application->status,
                $application->status, // Keep same status
                'system',
                0, // System generated (no admin ID)
                'Invoice generated automatically - '.$invoiceNumber
            );

            return $invoice;
        } catch (Exception $e) {
            Log::error('Error creating invoice: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Call e-invoice API using AdminController method via reflection.
     */
    private function callEinvoiceApiViaReflection(Application $application, Invoice $invoice): ?array
    {
        try {
            $adminController = app(\App\Http\Controllers\AdminController::class);
            $reflection = new \ReflectionClass($adminController);
            $method = $reflection->getMethod('callEinvoiceApi');
            $method->setAccessible(true);

            return $method->invoke($adminController, $application, $invoice);
        } catch (Exception $e) {
            Log::error('Error calling e-invoice API via reflection: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Send invoice email to user.
     */
    private function sendInvoiceEmail(Application $application, Invoice $invoice): void
    {
        try {
            $user = $application->user;
            if (! $user) {
                return;
            }

            // Get authorized representative name
            $authorizedPersonName = $application->authorized_representative_details['name']
                ?? $application->application_data['representative']['name']
                ?? $user->fullname;

            $ispName = $user->fullname;

            $billingStartDate = $invoice->billing_start_date 
                ? (\Carbon\Carbon::parse($invoice->billing_start_date)->format('Y-m-d')) 
                : null;
            $billingEndDate = $invoice->billing_end_date 
                ? (\Carbon\Carbon::parse($invoice->billing_end_date)->format('Y-m-d')) 
                : null;

            $payuService = new \App\Services\PayuService;

            Mail::to($user->email)->send(new IxApplicationInvoiceMail(
                $user->fullname,
                $application->application_id,
                $invoice->invoice_number,
                (float) $invoice->total_amount, // Cast to float as expected by constructor
                $application->status,
                $invoice->pdf_path ?? null,
                $payuService->getPaymentUrl(),
                json_decode($invoice->payu_payment_link, true),
                $authorizedPersonName,
                $ispName,
                $billingStartDate,
                $billingEndDate
            ));

            $invoice->update(['sent_at' => now('Asia/Kolkata')]);

            Log::info("Invoice email sent to user {$user->id} for application {$application->application_id}");

            // Send generation-time reminder for the invoice
            $this->sendGenerationTimeReminder($application, $invoice);
        } catch (Exception $e) {
            Log::error("Error sending invoice email for application {$application->id}: ".$e->getMessage());
        }
    }

    /**
     * Send renewal reminder emails based on billing cycle.
     */
    private function sendRenewalReminders(Application $application, Carbon $now): int
    {
        $remindersSent = 0;

        try {
            // Get the latest invoice
            $latestInvoice = Invoice::where('application_id', $application->id)
                ->where('status', '!=', 'cancelled')
                ->latest('invoice_date')
                ->first();

            if (! $latestInvoice) {
                return 0;
            }

            // Check if invoice is paid - skip reminders if both status and payment_status are paid
            $isFullyPaid = $latestInvoice->status === 'paid' && $latestInvoice->payment_status === 'paid';
            if ($isFullyPaid) {
                Log::info("Skipping renewal reminders for application {$application->id} - invoice {$latestInvoice->invoice_number} is fully paid (status: {$latestInvoice->status}, payment_status: {$latestInvoice->payment_status})");
                return 0; // Skip reminders if invoice is fully paid
            }

            // Use invoice due_date as the renewal date
            $dueDate = Carbon::parse($latestInvoice->due_date);
            $invoiceDate = Carbon::parse($latestInvoice->invoice_date);
            
            // Calculate days until due date
            $daysUntilDue = $now->diffInDays($dueDate, false);

            // Skip if due date has passed (invoice is overdue, no reminders needed)
            if ($daysUntilDue < 0) {
                return 0;
            }

            // Determine billing cycle
            $billingCycle = strtolower(trim($application->billing_cycle ?? 'monthly'));
            if (in_array($billingCycle, ['arc', 'annual'])) {
                $billingCycle = 'annual';
            } elseif ($billingCycle === 'quarterly') {
                $billingCycle = 'quarterly';
            } else {
                $billingCycle = 'monthly';
            }

            // Send reminders based on billing cycle
            if ($billingCycle === 'monthly') {
                // Monthly: generation time (handled separately), 15 days before, 1 day before
                // Note: Generation time reminder is sent when invoice is created
                
                // Check for 15 days before
                if ($daysUntilDue === 15) {
                    if (!$this->hasReminderBeenSent($application, $latestInvoice, 'monthly', '15days')) {
                        $this->sendRenewalReminder($application, $latestInvoice, $dueDate, '15days', 'monthly');
                        $remindersSent++;
                    }
                }
                // Check for 1 day before
                elseif ($daysUntilDue === 1) {
                    if (!$this->hasReminderBeenSent($application, $latestInvoice, 'monthly', '1day')) {
                        $this->sendRenewalReminder($application, $latestInvoice, $dueDate, '1day', 'monthly');
                        $remindersSent++;
                    }
                }
            } elseif ($billingCycle === 'quarterly' || $billingCycle === 'annual') {
                // Quarterly/Yearly: generation time (handled separately), monthly reminders, 15 days before, 1 day before
                
                // Generate monthly reminder dates between invoice date and due date
                $monthlyReminderDates = $this->getMonthlyReminderDates($invoiceDate, $dueDate);
                
                // Check if today matches any monthly reminder date
                $todayDate = $now->format('Y-m-d');
                
                foreach ($monthlyReminderDates as $reminderDate) {
                    $reminderDateStr = $reminderDate->format('Y-m-d');
                    
                    // Check if this reminder date matches today and hasn't been sent
                    if ($todayDate === $reminderDateStr) {
                        // Skip if 15 days before or 1 day before (those are handled separately)
                        $daysFromDue = $reminderDate->diffInDays($dueDate, false);
                        if ($daysFromDue !== 15 && $daysFromDue !== 1) {
                            // Use a unique identifier for monthly reminders
                            $monthlyReminderKey = 'monthly_' . $reminderDateStr;
                            if (!$this->hasReminderBeenSent($application, $latestInvoice, $billingCycle, $monthlyReminderKey)) {
                                $this->sendRenewalReminder($application, $latestInvoice, $dueDate, $monthlyReminderKey, $billingCycle, $reminderDate);
                                $remindersSent++;
                            }
                        }
                    }
                }
                
                // Check for 15 days before
                if ($daysUntilDue === 15) {
                    if (!$this->hasReminderBeenSent($application, $latestInvoice, $billingCycle, '15days')) {
                        $this->sendRenewalReminder($application, $latestInvoice, $dueDate, '15days', $billingCycle);
                        $remindersSent++;
                    }
                }
                // Check for 1 day before
                elseif ($daysUntilDue === 1) {
                    if (!$this->hasReminderBeenSent($application, $latestInvoice, $billingCycle, '1day')) {
                        $this->sendRenewalReminder($application, $latestInvoice, $dueDate, '1day', $billingCycle);
                        $remindersSent++;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error("Error sending renewal reminders for application {$application->id}: ".$e->getMessage());
        }

        return $remindersSent;
    }

    /**
     * Send generation-time reminder when invoice is created.
     */
    private function sendGenerationTimeReminder(Application $application, Invoice $invoice): void
    {
        try {
            $user = $application->user;
            if (! $user) {
                return;
            }

            // Skip if invoice is already paid
            if ($invoice->status === 'paid' && $invoice->payment_status === 'paid') {
                Log::info("Skipping generation-time reminder for application {$application->id} - invoice {$invoice->invoice_number} is already paid");
                return;
            }

            // Check if generation-time reminder was already sent
            if ($this->hasReminderBeenSent($application, $invoice, 'generation', 1)) {
                Log::info("Generation-time reminder already sent for invoice {$invoice->invoice_number}");
                return;
            }

            $dueDate = Carbon::parse($invoice->due_date);
            $billingCycle = strtolower(trim($application->billing_cycle ?? 'monthly'));
            if (in_array($billingCycle, ['arc', 'annual'])) {
                $billingCycle = 'annual';
            } elseif ($billingCycle === 'quarterly') {
                $billingCycle = 'quarterly';
            } else {
                $billingCycle = 'monthly';
            }

            $daysUntilDue = now('Asia/Kolkata')->diffInDays($dueDate, false);
            
            $message = "Your invoice {$invoice->invoice_number} has been generated. Payment of ₹{$invoice->total_amount} is due on {$dueDate->format('d M Y')} ({$daysUntilDue} days remaining). Please ensure payment is completed to avoid service interruption.";
            
            $subject = "Invoice Generated - Payment Reminder - Application {$application->application_id}";

            // Send message
            Message::create([
                'user_id' => $user->id,
                'subject' => $subject,
                'message' => $message,
                'is_read' => false,
                'sent_by' => 'system',
            ]);

            // Send email
            Mail::raw($message, function ($mail) use ($user, $subject) {
                $mail->to($user->email)
                    ->subject($subject);
            });

            Log::info("Generation-time reminder sent for invoice {$invoice->invoice_number} to user {$user->id}");
        } catch (\Exception $e) {
            Log::error("Error sending generation-time reminder for invoice {$invoice->id}: ".$e->getMessage());
        }
    }

    /**
     * Get monthly reminder dates between invoice date and due date.
     */
    private function getMonthlyReminderDates(Carbon $invoiceDate, Carbon $dueDate): array
    {
        $dates = [];
        $currentDate = $invoiceDate->copy()->addMonth();
        
        // Generate monthly dates until we reach within 15 days of due date
        while ($currentDate->lt($dueDate->copy()->subDays(15))) {
            $dates[] = $currentDate->copy();
            $currentDate->addMonth();
        }
        
        return $dates;
    }

    /**
     * Check if a reminder has already been sent for this invoice.
     */
    private function hasReminderBeenSent(Application $application, Invoice $invoice, string $billingCycle, $reminderNumber): bool
    {
        try {
            $subjectPattern = '';
            
            // Build subject pattern based on reminder type
            if ($billingCycle === 'generation') {
                $subjectPattern = "Invoice Generated - Payment Reminder - Application {$application->application_id}";
            } elseif ($reminderNumber === '15days') {
                $subjectPattern = "Payment Reminder - 15 Days Before Due Date - Application {$application->application_id}";
            } elseif ($reminderNumber === '1day') {
                $subjectPattern = "Final Payment Reminder - 1 Day Before Due Date - Application {$application->application_id}";
            } else {
                // Monthly reminder - use LIKE pattern since we calculate months dynamically
                $subjectPattern = "Payment Reminder -%Month%Before Due Date - Application {$application->application_id}";
            }
            
            $query = Message::where('user_id', $application->user_id)
                ->where('sent_by', 'system')
                ->where('created_at', '>=', $invoice->created_at);
            
            // For monthly reminders, use LIKE pattern; for others, use exact match
            if (strpos($reminderNumber, 'monthly_') === 0) {
                // For monthly reminders, check by date pattern in subject
                $query->where('subject', 'like', "Payment Reminder -%Month%Before Due Date - Application {$application->application_id}");
            } else {
                $query->where('subject', $subjectPattern);
            }
            
            return $query->exists();
        } catch (\Exception $e) {
            Log::error("Error checking if reminder was sent: ".$e->getMessage());
            return false;
        }
    }

    /**
     * Send renewal reminder email.
     */
    private function sendRenewalReminder(Application $application, Invoice $invoice, Carbon $dueDate, $reminderNumber, string $billingCycle, ?Carbon $reminderDate = null): void
    {
        try {
            $user = $application->user;
            if (! $user) {
                return;
            }

            // Double-check: Skip if invoice is fully paid (both status and payment_status are 'paid')
            $latestInvoice = Invoice::where('application_id', $application->id)
                ->where('status', '!=', 'cancelled')
                ->latest('invoice_date')
                ->first();

            if ($latestInvoice && $latestInvoice->status === 'paid' && $latestInvoice->payment_status === 'paid') {
                Log::info("Skipping renewal reminder #{$reminderNumber} for application {$application->id} - invoice {$latestInvoice->invoice_number} is fully paid");
                return;
            }

            // Use reminderDate if provided, otherwise use today's date for calculation
            $referenceDate = $reminderDate ?? now('Asia/Kolkata');
            $daysUntilDue = $referenceDate->diffInDays($dueDate, false);

            // Build message based on reminder number and billing cycle
            $message = '';
            $subject = '';
            
            if ($reminderNumber === '15days') {
                $subject = "Payment Reminder - 15 Days Before Due Date - Application {$application->application_id}";
                $message = "This is a payment reminder. Your invoice {$invoice->invoice_number} payment of ₹{$invoice->total_amount} is due in 15 days ({$dueDate->format('d M Y')}). Please complete the payment at your earliest convenience.";
            } elseif ($reminderNumber === '1day') {
                $subject = "Final Payment Reminder - 1 Day Before Due Date - Application {$application->application_id}";
                $message = "This is your final payment reminder. Your invoice {$invoice->invoice_number} payment of ₹{$invoice->total_amount} is due tomorrow ({$dueDate->format('d M Y')}). Please complete the payment immediately to avoid service interruption.";
            } elseif (strpos($reminderNumber, 'monthly_') === 0) {
                // Monthly reminder - extract date from key if needed
                $monthsUntilDue = round($daysUntilDue / 30);
                $subject = "Payment Reminder - {$monthsUntilDue} Month(s) Before Due Date - Application {$application->application_id}";
                $message = "This is a payment reminder. Your invoice {$invoice->invoice_number} payment of ₹{$invoice->total_amount} is due in approximately {$monthsUntilDue} month(s) ({$dueDate->format('d M Y')}). Please ensure payment is completed to avoid service interruption.";
            } else {
                // Fallback for numeric reminder numbers (monthly cycle)
                $monthsUntilDue = round($daysUntilDue / 30);
                $subject = "Payment Reminder - {$monthsUntilDue} Month(s) Before Due Date - Application {$application->application_id}";
                $message = "This is a payment reminder. Your invoice {$invoice->invoice_number} payment of ₹{$invoice->total_amount} is due in approximately {$monthsUntilDue} month(s) ({$dueDate->format('d M Y')}). Please ensure payment is completed to avoid service interruption.";
            }

            // Send message
            Message::create([
                'user_id' => $user->id,
                'subject' => $subject,
                'message' => $message,
                'is_read' => false,
                'sent_by' => 'system',
            ]);

            // Send email
            Mail::raw($message, function ($mail) use ($user, $subject) {
                $mail->to($user->email)
                    ->subject($subject);
            });

            Log::info("Renewal reminder #{$reminderNumber} sent to user {$user->id} for application {$application->application_id}, invoice {$invoice->invoice_number}");
        } catch (\Exception $e) {
            Log::error("Error sending renewal reminder #{$reminderNumber} for application {$application->id}: ".$e->getMessage());
        }
    }
}
