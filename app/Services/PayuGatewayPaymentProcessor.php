<?php

namespace App\Services;

use App\Models\Application;
use App\Models\ApplicationReactivationRequest;
use App\Models\ApplicationStatusHistory;
use App\Models\Invoice;
use App\Models\Message;
use App\Models\PaymentTransaction;
use App\Models\PaymentVerificationLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PayuGatewayPaymentProcessor
{
    /**
     * Extract PayU response fields from a browser or S2S callback request.
     *
     * @return array<string, mixed>
     */
    public function extractPayuResponseFields(Request $request): array
    {
        $response = array_merge($request->query(), $request->post());

        $payuFields = [
            'mihpayid' => $request->input('mihpayid') ?? $request->input('payuMoneyId') ?? $request->input('payuid'),
            'txnid' => $request->input('txnid'),
            'key' => $request->input('key'),
            'status' => $request->input('status'),
            'unmappedstatus' => $request->input('unmappedstatus'),
            'amount' => $request->input('amount'),
            'productinfo' => $request->input('productinfo'),
            'firstname' => $request->input('firstname'),
            'lastname' => $request->input('lastname'),
            'email' => $request->input('email'),
            'phone' => $request->input('phone'),
            'mode' => $request->input('mode'),
            'bankcode' => $request->input('bankcode'),
            'bank_ref_num' => $request->input('bank_ref_num'),
            'pg_type' => $request->input('pg_type'),
            'cardnum' => $request->input('cardnum'),
            'name_on_card' => $request->input('name_on_card'),
            'card_type' => $request->input('card_type'),
            'issuing_bank' => $request->input('issuing_bank'),
            'card_category' => $request->input('card_category'),
            'error' => $request->input('error'),
            'error_code' => $request->input('error_code'),
            'error_Message' => $request->input('error_Message') ?? $request->input('error_message'),
            'udf1' => $request->input('udf1'),
            'udf2' => $request->input('udf2'),
            'udf3' => $request->input('udf3'),
            'udf4' => $request->input('udf4'),
            'udf5' => $request->input('udf5'),
            'hash' => $request->input('hash'),
            'field1' => $request->input('field1'),
            'field2' => $request->input('field2'),
            'field3' => $request->input('field3'),
            'field4' => $request->input('field4'),
            'field5' => $request->input('field5'),
            'field6' => $request->input('field6'),
            'field7' => $request->input('field7'),
            'field8' => $request->input('field8'),
            'field9' => $request->input('field9'),
            'discount' => $request->input('discount'),
            'net_amount_debit' => $request->input('net_amount_debit'),
            'addedon' => $request->input('addedon'),
            'payment_source' => $request->input('payment_source'),
            'card_token' => $request->input('card_token'),
            'offer_key' => $request->input('offer_key'),
            'offer_type' => $request->input('offer_type'),
            'offer_availed' => $request->input('offer_available'),
            'failure_reason' => $request->input('failure_reason'),
            'retry' => $request->input('retry'),
        ];

        $payuFields = array_filter($payuFields, function ($value) {
            return $value !== null && $value !== '';
        });

        $payuFields['raw_response'] = $response;

        return $payuFields;
    }

    /**
     * @param  array<string, mixed>  $payuResponseFields
     */
    public function resolvePaymentTransaction(array $payuResponseFields): ?PaymentTransaction
    {
        $transactionId = $payuResponseFields['txnid'] ?? null;
        $paymentTransactionId = $payuResponseFields['udf2'] ?? null;

        $paymentTransaction = null;
        if ($paymentTransactionId) {
            $paymentTransaction = PaymentTransaction::find($paymentTransactionId);
            if ($paymentTransaction && $paymentTransaction->transaction_id !== $transactionId) {
                $paymentTransaction = null;
            }
        }

        if (! $paymentTransaction && $transactionId) {
            $paymentTransaction = PaymentTransaction::where('transaction_id', $transactionId)->first();
        }

        return $paymentTransaction;
    }

    public function normalizePaymentStatus(string $status): string
    {
        if (strtolower($status) === 'success') {
            return 'success';
        }

        if (in_array(strtolower($status), ['failure', 'failed', 'error', 'cancelled'], true)) {
            return 'failed';
        }

        return 'pending';
    }

    /**
     * @param  array<string, mixed>  $payuResponseFields
     */
    public function buildResponseMessage(array $payuResponseFields): string
    {
        $status = $payuResponseFields['status'] ?? '';
        $error = $payuResponseFields['error'] ?? null;
        $errorMessage = $payuResponseFields['error_Message'] ?? null;
        $errorCode = $payuResponseFields['error_code'] ?? null;
        $unmappedStatus = $payuResponseFields['unmappedstatus'] ?? '';
        $failureReason = $payuResponseFields['failure_reason'] ?? null;
        $bankRefNum = $payuResponseFields['bank_ref_num'] ?? null;
        $mode = $payuResponseFields['mode'] ?? null;

        $responseMessage = $status ?: ($error ?: $errorMessage ?: 'Payment processed');
        if ($unmappedStatus) {
            $responseMessage .= ' ('.$unmappedStatus.')';
        }
        if ($errorCode) {
            $responseMessage .= ' - Error Code: '.$errorCode;
        }
        if ($failureReason) {
            $responseMessage .= ' - Reason: '.$failureReason;
        }
        if ($bankRefNum) {
            $responseMessage .= ' - Bank Ref: '.$bankRefNum;
        }
        if ($mode) {
            $responseMessage .= ' - Mode: '.$mode;
        }

        return $responseMessage;
    }

    /**
     * Persist PayU outcome and apply invoice / IRINN application-fee side effects.
     * Used by S2S webhook and browser success/failure URLs after hash verification (when applicable).
     *
     * @param  array<string, mixed>  $payuResponseFields
     */
    public function processAfterGatewayResponse(
        Request $request,
        PaymentTransaction $paymentTransaction,
        array $payuResponseFields,
        string $paymentStatus,
        string $source
    ): void {
        $transactionId = $payuResponseFields['txnid'] ?? null;
        $rawUdf3 = $payuResponseFields['udf3'] ?? null;
        $isApplicationFeeMarker = $rawUdf3 === null || $rawUdf3 === '' || strtoupper((string) $rawUdf3) === 'IRINN';
        $isInvoicePayment = $rawUdf3 !== null && $rawUdf3 !== '' && ! $isApplicationFeeMarker;
        $invoiceNumber = $isInvoicePayment ? (string) $rawUdf3 : null;

        $payuPaymentId = $payuResponseFields['mihpayid'] ?? null;
        $status = $payuResponseFields['status'] ?? '';
        $bankRefNum = $payuResponseFields['bank_ref_num'] ?? null;
        $mode = $payuResponseFields['mode'] ?? null;
        $unmappedStatus = $payuResponseFields['unmappedstatus'] ?? '';

        if ($source === 's2s') {
            $payuResponseFields['webhook_received_at'] = now('Asia/Kolkata')->toDateTimeString();
            $payuResponseFields['webhook_source'] = 's2s';
        } else {
            $payuResponseFields['browser_return_at'] = now('Asia/Kolkata')->toDateTimeString();
            $payuResponseFields['browser_return_source'] = $source;
        }

        $responseMessage = $this->buildResponseMessage($payuResponseFields);

        Log::info('PayU gateway processing', [
            'transaction_id' => $transactionId,
            'payment_transaction_id' => $paymentTransaction->id,
            'payu_fields_count' => count($payuResponseFields),
            'source' => $source,
            'payment_status' => $paymentStatus,
            'is_invoice_payment' => $isInvoicePayment,
        ]);

        $paymentTransaction->update([
            'payment_id' => $payuPaymentId ?? $paymentTransaction->payment_id,
            'payment_status' => $paymentStatus,
            'response_message' => $responseMessage,
            'payu_response' => $payuResponseFields,
            'hash' => $payuResponseFields['hash'] ?? null,
        ]);

        if ($paymentStatus === 'success' && $paymentTransaction->application_id) {
            $application = Application::find($paymentTransaction->application_id);

            if ($application) {
                if ($isInvoicePayment && $invoiceNumber) {
                    $this->applySuccessfulInvoicePayment(
                        $application,
                        $paymentTransaction,
                        $invoiceNumber,
                        $transactionId,
                        $payuPaymentId
                    );
                } elseif ($application->application_type === 'IRINN') {
                    $applicationData = $application->application_data ?? [];
                    $applicationData['part5'] = array_merge($applicationData['part5'] ?? [], [
                        'payment_status' => 'success',
                        'payment_id' => $payuPaymentId ?? $paymentTransaction->payment_id,
                        'paid_at' => now('Asia/Kolkata')->toDateTimeString(),
                        'bank_ref_num' => $bankRefNum,
                        'mode' => $mode,
                        'unmappedstatus' => $unmappedStatus,
                        'webhook_confirmed' => $source === 's2s',
                        'browser_return_confirmed' => $source === 'browser_return',
                        'payu_confirmation_source' => $source,
                    ]);

                    $application->update([
                        'status' => 'helpdesk',
                        'submitted_at' => $application->submitted_at ?? now('Asia/Kolkata'),
                        'application_data' => $applicationData,
                    ]);

                    $historyNote = $source === 's2s'
                        ? 'IRINN application fee confirmed via PayU S2S webhook'
                        : 'IRINN application fee confirmed via PayU return URL';

                    ApplicationStatusHistory::log(
                        $application->id,
                        null,
                        'helpdesk',
                        'system',
                        null,
                        $historyNote
                    );
                }
            }
        }
    }

    private function applySuccessfulInvoicePayment(
        Application $application,
        PaymentTransaction $paymentTransaction,
        string $invoiceNumber,
        ?string $transactionId,
        ?string $payuPaymentId
    ): void {
        if (str_starts_with($invoiceNumber, 'BULK-')) {
            $this->applyBulkInvoicePayment($application, $invoiceNumber, $transactionId);

            return;
        }

        $invoice = Invoice::where('invoice_number', $invoiceNumber)
            ->where('application_id', $application->id)
            ->first();

        if (! $invoice) {
            return;
        }

        $alreadyProcessed = false;
        $paymentAmount = (float) $paymentTransaction->amount;

        $existingVerification = PaymentVerificationLog::where('application_id', $application->id)
            ->where('payment_id', $payuPaymentId ?? $transactionId)
            ->where('billing_period', $invoice->billing_period)
            ->first();

        if ($existingVerification) {
            $alreadyProcessed = true;
            Log::info('PayU - Payment already processed, skipping duplicate processing', [
                'invoice_number' => $invoiceNumber,
                'payment_id' => $payuPaymentId ?? $transactionId,
            ]);
        }

        if (! $alreadyProcessed) {
            $currentPaidAmount = (float) ($invoice->paid_amount ?? 0);

            if ($invoice->payment_status === 'paid' && $currentPaidAmount >= $invoice->total_amount) {
                $alreadyProcessed = true;
                Log::info('PayU - Invoice already fully paid, skipping duplicate processing', [
                    'invoice_number' => $invoiceNumber,
                    'paid_amount' => $currentPaidAmount,
                    'total_amount' => $invoice->total_amount,
                ]);
            }
        }

        $balanceAmount = 0.0;
        $invoicePaymentStatus = $invoice->payment_status;

        if (! $alreadyProcessed) {
            $currentPaidAmount = (float) ($invoice->paid_amount ?? 0);
            $newPaidAmount = $currentPaidAmount + $paymentAmount;
            $balanceAmount = max(0, (float) $invoice->total_amount - $newPaidAmount);

            $invoicePaymentStatus = 'pending';
            if ($newPaidAmount >= $invoice->total_amount) {
                $invoicePaymentStatus = 'paid';
                $balanceAmount = 0;
            } elseif ($newPaidAmount > 0) {
                $invoicePaymentStatus = 'partial';
            }

            $invoice->update([
                'paid_amount' => $newPaidAmount,
                'balance_amount' => $balanceAmount,
                'payment_status' => $invoicePaymentStatus,
                'status' => $invoicePaymentStatus === 'paid' ? 'paid' : $invoice->status,
                'paid_at' => $invoicePaymentStatus === 'paid' ? now('Asia/Kolkata') : $invoice->paid_at,
            ]);

            if ($invoicePaymentStatus === 'paid' && ($invoice->invoice_purpose ?? '') === 'reactivation') {
                ApplicationReactivationRequest::query()
                    ->where('invoice_id', $invoice->id)
                    ->whereIn('status', ['approved', 'invoiced', 'pending'])
                    ->update([
                        'status' => 'paid',
                        'paid_at' => now('Asia/Kolkata'),
                    ]);
            }
        }

        if (! $alreadyProcessed && $invoice->billing_period && $invoicePaymentStatus === 'paid') {
            $existingVerificationByPeriod = PaymentVerificationLog::where('application_id', $application->id)
                ->where('billing_period', $invoice->billing_period)
                ->first();

            if (! $existingVerificationByPeriod) {
                PaymentVerificationLog::create([
                    'application_id' => $application->id,
                    'verified_by' => null,
                    'verification_type' => 'recurring',
                    'billing_period' => $invoice->billing_period,
                    'amount' => $invoice->total_amount,
                    'amount_captured' => $paymentAmount,
                    'currency' => $invoice->currency,
                    'payment_method' => 'payu',
                    'payment_id' => $payuPaymentId ?? $transactionId,
                    'notes' => 'Payment verified automatically via PayU for invoice '.$invoiceNumber,
                    'verified_at' => now('Asia/Kolkata'),
                ]);

                ApplicationStatusHistory::log(
                    $application->id,
                    $application->status,
                    $application->status,
                    'system',
                    null,
                    "Payment automatically verified via PayU for billing period {$invoice->billing_period}"
                );

                Message::create([
                    'user_id' => $application->user_id,
                    'subject' => 'Payment Verified',
                    'message' => "Payment for invoice {$invoiceNumber} has been received and verified automatically. Thank you for your payment.",
                    'is_read' => false,
                    'sent_by' => 'system',
                ]);
            }
        } elseif ($invoicePaymentStatus === 'partial' && ! $alreadyProcessed) {
            Message::create([
                'user_id' => $application->user_id,
                'subject' => 'Partial Payment Received',
                'message' => 'Partial payment of ₹'.number_format($paymentAmount, 2)." has been received for invoice {$invoiceNumber}. Remaining balance: ₹".number_format($balanceAmount, 2).'. Please pay the remaining amount.',
                'is_read' => false,
                'sent_by' => 'system',
            ]);
        }
    }

    private function applyBulkInvoicePayment(
        Application $application,
        string $invoiceNumber,
        ?string $transactionId
    ): void {
        $invoiceIdsString = str_replace('BULK-', '', $invoiceNumber);
        $invoiceIds = array_filter(explode(',', $invoiceIdsString));

        Log::info('Processing bulk payment', [
            'transaction_id' => $transactionId,
            'invoice_ids' => $invoiceIds,
            'application_id' => $application->id,
        ]);

        $processedInvoices = [];
        $processedApplications = [];

        foreach ($invoiceIds as $invoiceId) {
            $invoice = Invoice::where('id', $invoiceId)
                ->with('application')
                ->first();

            if ($invoice && $invoice->application) {
                $invoiceApplication = $invoice->application;

                $alreadyProcessed = false;

                if ($invoice->payment_status === 'paid' && $invoice->paid_amount >= $invoice->total_amount) {
                    $alreadyProcessed = true;
                    Log::info('PayU - Bulk payment: Invoice already fully paid, skipping', [
                        'invoice_id' => $invoiceId,
                        'invoice_number' => $invoice->invoice_number,
                        'paid_amount' => $invoice->paid_amount,
                        'total_amount' => $invoice->total_amount,
                    ]);
                }

                if (! $alreadyProcessed) {
                    $invoiceAmount = (float) $invoice->total_amount;

                    $invoice->update([
                        'paid_amount' => $invoiceAmount,
                        'balance_amount' => 0,
                        'payment_status' => 'paid',
                        'status' => 'paid',
                        'paid_at' => now('Asia/Kolkata'),
                    ]);

                    Log::info('PayU - Bulk payment: Invoice updated', [
                        'invoice_id' => $invoiceId,
                        'invoice_number' => $invoice->invoice_number,
                        'paid_amount' => $invoiceAmount,
                        'total_amount' => $invoice->total_amount,
                    ]);
                }

                $processedInvoices[] = $invoice->invoice_number;

                if (! in_array($invoiceApplication->id, $processedApplications, true)) {
                    $processedApplications[] = $invoiceApplication->id;
                }

                if ($invoice->billing_period) {
                    $existingVerification = PaymentVerificationLog::where('application_id', $invoiceApplication->id)
                        ->where('billing_period', $invoice->billing_period)
                        ->first();

                    if (! $existingVerification) {
                        PaymentVerificationLog::create([
                            'application_id' => $invoiceApplication->id,
                            'verified_by' => null,
                            'verification_type' => 'recurring',
                            'billing_period' => $invoice->billing_period,
                            'amount' => $invoice->total_amount,
                            'currency' => $invoice->currency,
                            'payment_method' => 'payu',
                            'notes' => 'Payment verified automatically via PayU bulk payment for invoice '.$invoice->invoice_number,
                            'verified_at' => now('Asia/Kolkata'),
                        ]);

                        ApplicationStatusHistory::log(
                            $invoiceApplication->id,
                            $invoiceApplication->status,
                            $invoiceApplication->status,
                            'system',
                            null,
                            "Payment automatically verified via PayU for billing period {$invoice->billing_period}"
                        );
                    }
                }
            }
        }

        if (count($processedInvoices) > 0) {
            Message::create([
                'user_id' => $application->user_id,
                'subject' => 'Bulk Payment Verified',
                'message' => 'Bulk payment for '.count($processedInvoices).' invoice(s) ('.implode(', ', $processedInvoices).') has been received and verified automatically. Thank you for your payment.',
                'is_read' => false,
                'sent_by' => 'system',
            ]);

            Log::info('Bulk payment processed successfully', [
                'transaction_id' => $transactionId,
                'processed_invoices' => $processedInvoices,
                'processed_applications' => $processedApplications,
            ]);
        }
    }
}
