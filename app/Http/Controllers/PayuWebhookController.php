<?php

namespace App\Http\Controllers;

use App\Services\PayuGatewayPaymentProcessor;
use App\Services\PayuService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PayuWebhookController extends Controller
{
    public function __construct(
        protected PayuGatewayPaymentProcessor $payuGatewayPaymentProcessor
    ) {}

    public function handleWebhook(Request $request): JsonResponse
    {
        $response = $request->all();

        try {
            $payuService = new PayuService;

            Log::info('PayU S2S Webhook Received', [
                'all_params' => $response,
                'method' => $request->method(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'headers' => $request->headers->all(),
            ]);

            $requiredFields = ['txnid', 'status', 'hash'];
            $missingFields = array_diff($requiredFields, array_keys($response));

            if (! empty($missingFields)) {
                Log::error('PayU S2S Webhook - Missing required fields', [
                    'missing_fields' => $missingFields,
                    'received_fields' => array_keys($response),
                    'response' => $response,
                ]);

                return response()->json(['status' => 'error', 'message' => 'Missing required fields'], 400);
            }

            $payuResponseFields = $this->payuGatewayPaymentProcessor->extractPayuResponseFields($request);

            if (! $payuService->verifyHash($payuResponseFields)) {
                Log::warning('PayU S2S Webhook - Hash verification failed', [
                    'response' => $payuResponseFields,
                    'transaction_id' => $payuResponseFields['txnid'] ?? null,
                    'status' => $payuResponseFields['status'] ?? null,
                ]);

                return response()->json(['status' => 'error', 'message' => 'Hash verification failed'], 400);
            }

            $transactionId = $payuResponseFields['txnid'] ?? null;

            $paymentTransaction = $this->payuGatewayPaymentProcessor->resolvePaymentTransaction($payuResponseFields);

            if (! $paymentTransaction) {
                Log::error('PayU S2S Webhook - Payment transaction not found', [
                    'transaction_id' => $transactionId,
                    'payment_transaction_id' => $payuResponseFields['udf2'] ?? null,
                ]);

                return response()->json(['status' => 'error', 'message' => 'Transaction not found'], 404);
            }

            $status = $payuResponseFields['status'] ?? '';
            $payuPaymentId = $payuResponseFields['mihpayid'] ?? null;
            $unmappedStatus = $payuResponseFields['unmappedstatus'] ?? '';
            $bankRefNum = $payuResponseFields['bank_ref_num'] ?? null;
            $mode = $payuResponseFields['mode'] ?? null;
            $errorCode = $payuResponseFields['error_code'] ?? null;

            $paymentStatus = $this->payuGatewayPaymentProcessor->normalizePaymentStatus((string) $status);

            Log::info('PayU S2S Webhook - All Response Fields Captured', [
                'transaction_id' => $transactionId,
                'payment_transaction_id' => $paymentTransaction->id,
                'payu_fields_count' => count($payuResponseFields),
                'key_fields' => [
                    'mihpayid' => $payuPaymentId,
                    'status' => $status,
                    'unmappedstatus' => $unmappedStatus,
                    'mode' => $mode,
                    'bank_ref_num' => $bankRefNum,
                    'error_code' => $errorCode,
                ],
            ]);

            $this->payuGatewayPaymentProcessor->processAfterGatewayResponse(
                $request,
                $paymentTransaction,
                $payuResponseFields,
                $paymentStatus,
                's2s'
            );

            Log::info('PayU S2S Webhook Processed Successfully', [
                'transaction_id' => $transactionId,
                'payment_transaction_id' => $paymentTransaction->id,
                'payu_payment_id' => $payuPaymentId,
                'status' => $status,
                'payment_status' => $paymentStatus,
                'amount' => $request->input('amount'),
            ]);

            return response()->json(['status' => 'success'], 200);
        } catch (Exception $e) {
            Log::error('PayU S2S Webhook Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $response,
            ]);

            return response()->json(['status' => 'error', 'message' => 'Internal server error'], 500);
        }
    }
}
