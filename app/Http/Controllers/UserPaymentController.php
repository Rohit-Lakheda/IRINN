<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\ApplicationReactivationRequest;
use App\Models\Invoice;
use App\Models\PaymentTransaction;
use App\Models\Registration;
use App\Services\PayuService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UserPaymentController extends Controller
{
    private function markReactivationRequestPaidIfNeeded(Invoice $invoice): void
    {
        if (($invoice->invoice_purpose ?? null) !== 'reactivation') {
            return;
        }

        if (($invoice->payment_status ?? null) !== 'paid') {
            return;
        }

        $reactivationRequest = ApplicationReactivationRequest::query()
            ->where('invoice_id', $invoice->id)
            ->whereIn('status', ['approved', 'invoiced', 'pending'])
            ->latest()
            ->first();

        if (! $reactivationRequest) {
            return;
        }

        $reactivationRequest->update([
            'status' => 'paid',
            'paid_at' => now('Asia/Kolkata'),
        ]);
    }

    /**
     * Display pending payments list.
     */
    public function pending()
    {
        try {
            $userId = session('user_id');
            $user = Registration::find($userId);

            if (! $user) {
                return redirect()->route('login.index')
                    ->with('error', 'User session expired. Please login again.');
            }

            // Get all pending and partial invoices with applications (exclude cancelled and credit note invoices)
            $pendingInvoices = Invoice::whereHas('application', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
                ->activeForTotals()
                ->where(function ($q) {
                    $q->where('status', 'pending')
                        ->orWhere('payment_status', 'partial');
                })
                ->with(['application'])
                ->latest('due_date')
                ->get();

            // Calculate total outstanding amount (use balance_amount for partial payments)
            $outstandingAmount = $pendingInvoices->sum(function ($invoice) {
                return $invoice->balance_amount ?? $invoice->total_amount;
            });

            // Redirect to invoice page with pending filter
            return redirect()->route('user.invoices.index', ['filter' => 'pending']);
        } catch (Exception $e) {
            Log::error('Error loading pending payments: '.$e->getMessage());

            return redirect()->route('user.dashboard')
                ->with('error', 'Unable to load pending payments.');
        }
    }

    /**
     * Pay invoice with wallet.
     */
    public function payWithWallet(Request $request, $invoiceId): RedirectResponse
    {
        try {
            $userId = session('user_id');
            $user = Registration::find($userId);

            if (! $user) {
                return redirect()->route('login.index')
                    ->with('error', 'User session expired. Please login again.');
            }

            $wallet = $user->wallet;
            if (! $wallet || $wallet->status !== 'active') {
                return redirect()->route('user.payments.pending')
                    ->with('error', 'Wallet not available or inactive. Please use PayU payment.');
            }

            $invoice = Invoice::whereHas('application', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
                ->where(function ($q) {
                    $q->where('status', 'pending')
                        ->orWhere('payment_status', 'partial');
                })
                ->with(['application'])
                ->findOrFail($invoiceId);

            $application = $invoice->application;
            $paymentAmount = $invoice->balance_amount ?? $invoice->total_amount;

            // Check wallet balance
            if ($wallet->balance < $paymentAmount) {
                return redirect()->route('user.payments.pending')
                    ->with('error', 'Insufficient wallet balance. Please top-up your wallet or use PayU payment.');
            }

            // Use WalletService to debit wallet (resolve via container so dependencies are injected)
            $walletService = app(\App\Services\WalletService::class);
            $transactionId = 'INV-WLT-'.time().'-'.strtoupper(\Illuminate\Support\Str::random(8));

            // Create payment transaction
            $paymentTransaction = PaymentTransaction::create([
                'user_id' => $user->id,
                'application_id' => $application->id,
                'transaction_id' => $transactionId,
                'payment_status' => 'pending',
                'payment_mode' => 'wallet',
                'amount' => $paymentAmount,
                'currency' => 'INR',
                'product_info' => 'NIXI IRINN Invoice - '.$invoice->invoice_number,
                'response_message' => 'Invoice payment via wallet',
            ]);

            // Debit wallet
            $success = $walletService->debitWallet(
                $wallet,
                $paymentAmount,
                $transactionId,
                $paymentTransaction->id,
                $application->id,
                "Invoice payment - {$invoice->invoice_number}"
            );

            if (! $success) {
                $paymentTransaction->update(['payment_status' => 'failed']);

                return redirect()->route('user.payments.pending')
                    ->with('error', 'Wallet payment failed. Please try again or use PayU payment.');
            }

            // Update payment transaction
            $paymentTransaction->update([
                'payment_status' => 'success',
                'payment_id' => $transactionId,
            ]);

            // Update invoice payment status (similar to PayU success callback)
            $paidAmount = $invoice->paid_amount ?? 0;
            $newPaidAmount = $paidAmount + $paymentAmount;
            $balanceAmount = $invoice->total_amount - $newPaidAmount;

            $invoice->update([
                'paid_amount' => $newPaidAmount,
                'balance_amount' => max(0, $balanceAmount),
                'payment_status' => $balanceAmount <= 0 ? 'paid' : 'partial',
                'status' => $balanceAmount <= 0 ? 'paid' : 'pending',
                'paid_at' => now('Asia/Kolkata'),
            ]);

            $invoice->refresh();
            $this->markReactivationRequestPaidIfNeeded($invoice);

            Log::info('Invoice paid with wallet', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'amount' => $paymentAmount,
                'transaction_id' => $transactionId,
                'wallet_id' => $wallet->id,
            ]);

            return redirect()->route('user.payments.pending')
                ->with('success', "Invoice {$invoice->invoice_number} paid successfully using wallet balance!");
        } catch (Exception $e) {
            Log::error('Error paying invoice with wallet: '.$e->getMessage());

            return redirect()->route('user.payments.pending')
                ->with('error', 'Unable to process wallet payment. Please try again.');
        }
    }

    /**
     * Pay for a single invoice.
     */
    public function payNow(Request $request, $invoiceId)
    {
        try {
            $userId = session('user_id');
            $user = Registration::find($userId);

            if (! $user) {
                return redirect()->route('login.index')
                    ->with('error', 'User session expired. Please login again.');
            }

            $invoice = Invoice::whereHas('application', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
                ->where(function ($q) {
                    $q->where('status', 'pending')
                        ->orWhere('payment_status', 'partial');
                })
                ->with(['application'])
                ->findOrFail($invoiceId);

            $application = $invoice->application;

            // Use balance_amount for partial payments, otherwise total_amount
            $paymentAmount = $invoice->balance_amount ?? $invoice->total_amount;

            // Generate PayU payment link
            $payuService = new PayuService;
            $transactionId = 'INV-'.time().'-'.strtoupper(Str::random(8));

            // Create PaymentTransaction for invoice payment
            $paymentTransaction = PaymentTransaction::create([
                'user_id' => $user->id,
                'application_id' => $application->id,
                'transaction_id' => $transactionId,
                'payment_status' => 'pending',
                'payment_mode' => config('services.payu.mode', 'test'),
                'amount' => $paymentAmount,
                'currency' => 'INR',
                'product_info' => 'NIXI IRINN Invoice - '.$invoice->invoice_number,
                'response_message' => 'Invoice payment pending',
            ]);

            $successRoute = 'user.applications.irin.payment-success';
            $failureRoute = 'user.applications.irin.payment-failure';
            $productLabel = 'IRINN Invoice - '.$invoice->invoice_number;

            // Prepare payment data with all required parameters
            $paymentData = $payuService->preparePaymentData([
                'transaction_id' => $transactionId,
                'amount' => $paymentAmount,
                'product_info' => $productLabel,
                'firstname' => $user->fullname,
                'email' => $user->email,
                'phone' => $user->mobile,
                'success_url' => url(route($successRoute, [], false)),
                'failure_url' => url(route($failureRoute, [], false)),
                'udf1' => $application->application_id,
                'udf2' => (string) $paymentTransaction->id,
                'udf3' => $invoice->invoice_number, // Invoice number for identification
            ]);

            // Store payment data in cookies for callback
            $cookieData = [
                'payment_transaction_id' => $paymentTransaction->id,
                'transaction_id' => $transactionId,
                'application_id' => $application->id,
                'user_id' => $userId,
                'amount' => $paymentAmount,
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
            ];

            $userSessionData = [
                'user_id' => $userId,
                'user_email' => $user->email,
                'user_name' => $user->fullname,
                'user_registration_id' => $user->registrationid,
            ];

            $response = response()->view('user.payments.redirect-payu', [
                'paymentUrl' => $payuService->getPaymentUrl(),
                'paymentData' => $paymentData,
            ]);

            // Set cookies for callback handling
            $response->cookie(
                'pending_payment_data',
                json_encode($cookieData),
                60,
                '/',
                null,
                true,
                false,
                false,
                'lax'
            );
            $response->cookie(
                'user_session_data',
                json_encode($userSessionData),
                60,
                '/',
                null,
                true,
                false,
                false,
                'lax'
            );

            return $response;
        } catch (Exception $e) {
            Log::error('Error initiating payment for invoice '.$invoiceId.': '.$e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('user.payments.pending')
                ->with('error', 'Unable to initiate payment. Please try again.');
        }
    }

    /**
     * Pay all pending invoices with wallet.
     */
    public function payAllWithWallet(Request $request): RedirectResponse
    {
        try {
            $userId = session('user_id');
            $user = Registration::find($userId);

            if (! $user) {
                return redirect()->route('login.index')
                    ->with('error', 'User session expired. Please login again.');
            }

            $wallet = $user->wallet;
            if (! $wallet || $wallet->status !== 'active') {
                return redirect()->route('user.payments.pending')
                    ->with('error', 'Wallet not available or inactive. Please use PayU payment.');
            }

            // Get all pending and partial invoices
            $pendingInvoices = Invoice::whereHas('application', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
                ->where(function ($q) {
                    $q->where('status', 'pending')
                        ->orWhere('payment_status', 'partial');
                })
                ->with(['application'])
                ->get();

            if ($pendingInvoices->isEmpty()) {
                return redirect()->route('user.payments.pending')
                    ->with('info', 'No pending invoices to pay.');
            }

            // Calculate total outstanding amount
            $totalAmount = $pendingInvoices->sum(function ($invoice) {
                return $invoice->balance_amount ?? $invoice->total_amount;
            });

            // Check wallet balance
            if ($wallet->balance < $totalAmount) {
                return redirect()->route('user.payments.pending')
                    ->with('error', 'Insufficient wallet balance. Please top-up your wallet or use PayU payment.');
            }

            // Use WalletService to debit wallet
            $walletService = app(\App\Services\WalletService::class);
            $baseTransactionId = 'BULK-WLT-'.time().'-'.strtoupper(Str::random(8));

            // Process each invoice
            $processedInvoices = [];
            $failedInvoices = [];

            foreach ($pendingInvoices as $invoice) {
                $application = $invoice->application;
                $paymentAmount = $invoice->balance_amount ?? $invoice->total_amount;

                // Create payment transaction for each invoice
                $paymentTransaction = PaymentTransaction::create([
                    'user_id' => $user->id,
                    'application_id' => $application->id,
                    'transaction_id' => $baseTransactionId.'-INV'.$invoice->id,
                    'payment_status' => 'pending',
                    'payment_mode' => 'wallet',
                    'amount' => $paymentAmount,
                    'currency' => 'INR',
                    'product_info' => 'NIXI IRINN Invoice - '.$invoice->invoice_number,
                    'response_message' => 'Bulk invoice payment via wallet',
                ]);

                // Debit wallet for this invoice
                $success = $walletService->debitWallet(
                    $wallet,
                    $paymentAmount,
                    $paymentTransaction->transaction_id,
                    $paymentTransaction->id,
                    $application->id,
                    "Bulk payment - Invoice {$invoice->invoice_number}"
                );

                if ($success) {
                    // Update payment transaction
                    $paymentTransaction->update([
                        'payment_status' => 'success',
                        'payment_id' => $paymentTransaction->transaction_id,
                    ]);

                    // Update invoice payment status
                    $paidAmount = $invoice->paid_amount ?? 0;
                    $newPaidAmount = $paidAmount + $paymentAmount;
                    $balanceAmount = $invoice->total_amount - $newPaidAmount;

                    $invoice->update([
                        'paid_amount' => $newPaidAmount,
                        'balance_amount' => max(0, $balanceAmount),
                        'payment_status' => $balanceAmount <= 0 ? 'paid' : 'partial',
                        'status' => $balanceAmount <= 0 ? 'paid' : 'pending',
                        'paid_at' => now('Asia/Kolkata'),
                    ]);

                    $invoice->refresh();
                    $this->markReactivationRequestPaidIfNeeded($invoice);

                    $processedInvoices[] = $invoice->invoice_number;
                } else {
                    $paymentTransaction->update(['payment_status' => 'failed']);
                    $failedInvoices[] = $invoice->invoice_number;
                }
            }

            Log::info('Bulk invoice payment with wallet', [
                'user_id' => $userId,
                'total_amount' => $totalAmount,
                'processed_count' => count($processedInvoices),
                'failed_count' => count($failedInvoices),
                'transaction_id' => $baseTransactionId,
            ]);

            if (count($processedInvoices) > 0) {
                $message = count($processedInvoices).' invoice(s) paid successfully using wallet balance!';
                if (count($failedInvoices) > 0) {
                    $message .= ' Failed: '.implode(', ', $failedInvoices);
                }

                return redirect()->route('user.payments.pending')
                    ->with('success', $message);
            }

            return redirect()->route('user.payments.pending')
                ->with('error', 'Failed to process wallet payment. Please try again.');
        } catch (Exception $e) {
            Log::error('Error paying all invoices with wallet: '.$e->getMessage());

            return redirect()->route('user.payments.pending')
                ->with('error', 'Unable to process wallet payment. Please try again.');
        }
    }

    /**
     * Pay for all pending invoices at once.
     */
    public function payAll(Request $request)
    {
        try {
            $userId = session('user_id');
            $user = Registration::find($userId);

            if (! $user) {
                return redirect()->route('login.index')
                    ->with('error', 'User session expired. Please login again.');
            }

            // Get all pending invoices
            $pendingInvoices = Invoice::whereHas('application', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
                ->where('status', 'pending')
                ->with(['application'])
                ->get();

            if ($pendingInvoices->isEmpty()) {
                return redirect()->route('user.payments.pending')
                    ->with('info', 'No pending invoices to pay.');
            }

            // Calculate total amount
            $totalAmount = $pendingInvoices->sum('total_amount');

            // Get first application (for payment transaction)
            $firstApplication = $pendingInvoices->first()->application;

            // Generate PayU payment link
            $payuService = new PayuService;
            $transactionId = 'BULK-'.time().'-'.strtoupper(Str::random(8));

            // Create PaymentTransaction for bulk payment
            $paymentTransaction = PaymentTransaction::create([
                'user_id' => $user->id,
                'application_id' => $firstApplication->id,
                'transaction_id' => $transactionId,
                'payment_status' => 'pending',
                'payment_mode' => 'live',
                'amount' => $totalAmount,
                'currency' => 'INR',
                'product_info' => 'NIXI IX Service - Bulk Payment for '.$pendingInvoices->count().' invoices',
                'response_message' => 'Bulk invoice payment pending',
            ]);

            // Store invoice IDs in payment transaction metadata for processing after payment
            $invoiceIds = $pendingInvoices->pluck('id')->toArray();
            $paymentTransaction->update([
                'product_info' => json_encode([
                    'type' => 'bulk_invoice_payment',
                    'invoice_ids' => $invoiceIds,
                    'invoice_count' => count($invoiceIds),
                ]),
            ]);

            // Prepare payment data
            $paymentData = $payuService->preparePaymentData([
                'transaction_id' => $transactionId,
                'amount' => $totalAmount,
                'product_info' => 'NIXI IX Service - Bulk Payment for '.$pendingInvoices->count().' invoices',
                'firstname' => $user->fullname,
                'email' => $user->email,
                'phone' => $user->mobile,
                'success_url' => url(route('user.applications.irin.payment-success', [], false)),
                'failure_url' => url(route('user.applications.irin.payment-failure', [], false)),
                'udf1' => $firstApplication->application_id,
                'udf2' => (string) $paymentTransaction->id,
                'udf3' => 'BULK-'.implode(',', $invoiceIds), // Store invoice IDs
            ]);

            // Store payment data in cookies for callback
            $cookieData = [
                'payment_transaction_id' => $paymentTransaction->id,
                'transaction_id' => $transactionId,
                'application_id' => $firstApplication->id,
                'user_id' => $user->id,
                'amount' => $totalAmount,
                'bulk_payment' => true,
                'invoice_ids' => $invoiceIds,
            ];

            $userSessionData = [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'user_name' => $user->fullname,
                'user_registration_id' => $user->registrationid,
            ];

            $response = response()->view('user.payments.redirect-payu', [
                'paymentUrl' => $payuService->getPaymentUrl(),
                'paymentData' => $paymentData,
            ]);

            // Set cookies for callback handling
            $response->cookie(
                'pending_payment_data',
                json_encode($cookieData),
                60,
                '/',
                null,
                true,
                false,
                false,
                'lax'
            );
            $response->cookie(
                'user_session_data',
                json_encode($userSessionData),
                60,
                '/',
                null,
                true,
                false,
                false,
                'lax'
            );

            return $response;
        } catch (Exception $e) {
            Log::error('Error initiating bulk payment: '.$e->getMessage());

            return redirect()->route('user.payments.pending')
                ->with('error', 'Unable to initiate bulk payment. Please try again.');
        }
    }
}
