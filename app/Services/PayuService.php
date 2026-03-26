<?php

namespace App\Services;

class PayuService
{
    protected string $merchantId;

    protected string $merchantKey;

    protected string $salt;

    protected string $paymentUrl;

    protected string $serviceProvider;

    public function __construct()
    {
        $this->merchantId = config('services.payu.merchant_id');
        $this->merchantKey = config('services.payu.merchant_key');
        $this->salt = config('services.payu.salt');
        $this->serviceProvider = config('services.payu.service_provider');
        $mode = config('services.payu.mode', 'test');
        $this->paymentUrl = $mode === 'test' ? config('services.payu.test_url') : config('services.payu.live_url');
    }

    /**
     * Generate hash for PayU payment.
     */
    public function generateHash(array $params): string
    {
        // Ensure all UDF fields are strings, even if empty
        $udf1 = trim((string) ($params['udf1'] ?? ''));
        $udf2 = trim((string) ($params['udf2'] ?? ''));
        $udf3 = trim((string) ($params['udf3'] ?? ''));
        $udf4 = trim((string) ($params['udf4'] ?? ''));
        $udf5 = trim((string) ($params['udf5'] ?? ''));

        // Ensure all required fields are strings and trimmed
        $txnid = trim((string) $params['txnid']);
        $amount = trim((string) $params['amount']);
        $productinfo = trim((string) $params['productinfo']);
        $firstname = trim((string) $params['firstname']);
        $email = trim((string) $params['email']);

        // Build hash string as per PayU documentation:
        // Reference: PayU Hosted Checkout API Documentation - Step 1.2
        // Formula: sha512(key|txnid|amount|productinfo|firstname|email|udf1|udf2|udf3|udf4|udf5||||||SALT)
        // Documentation shows: $udf5 . '||||||' . $salt (6 pipes)
        // However, PayU's actual validation requires: After udf5|, there must be exactly 5 pipes (|||||) before SALT
        // This has been verified through testing - PayU error messages confirm 5 pipes is correct
        // Format: udf1|udf2|udf3|udf4|udf5|||||SALT
        // When udf3, udf4, udf5 are empty: udf1|udf2| + | (udf3) + | (udf4) + | (udf5) + ||||| (5 pipes) = 9 pipes after udf2
        // Verified from PayU error: "38|||||||||" = 9 pipes after udf2
        $hashString = $this->merchantKey.'|'
            .$txnid.'|'
            .$amount.'|'
            .$productinfo.'|'
            .$firstname.'|'
            .$email.'|'
            .$udf1.'|'
            .$udf2.'|'
            .$udf3.'|'
            .$udf4.'|'
            .$udf5.'|'
            .'|||||'
            .$this->salt;

        // Temporary debug logging
        \Illuminate\Support\Facades\Log::info('PayU Hash Debug', [
            'hash_string' => $hashString,
            'hash_string_length' => strlen($hashString),
            'generated_hash' => strtolower(hash('sha512', $hashString)),
        ]);

        return strtolower(hash('sha512', $hashString));
    }

    /**
     * Verify hash from PayU response.
     */
    public function verifyHash(array $response): bool
    {
        // Ensure all fields are strings and handle missing fields
        $status = trim((string) ($response['status'] ?? ''));
        $udf1 = trim((string) ($response['udf1'] ?? ''));
        $udf2 = trim((string) ($response['udf2'] ?? ''));
        $udf3 = trim((string) ($response['udf3'] ?? ''));
        $udf4 = trim((string) ($response['udf4'] ?? ''));
        $udf5 = trim((string) ($response['udf5'] ?? ''));
        $email = trim((string) ($response['email'] ?? ''));
        $firstname = trim((string) ($response['firstname'] ?? ''));
        $productinfo = trim((string) ($response['productinfo'] ?? ''));
        $amount = trim((string) ($response['amount'] ?? ''));
        $txnid = trim((string) ($response['txnid'] ?? ''));
        $receivedHash = strtolower(trim((string) ($response['hash'] ?? '')));

        // Build hash string as per PayU documentation for response verification:
        // Reference: PayU Hosted Checkout API Documentation - Step 1.4.1
        // Formula: sha512(SALT|status||||||udf5|udf4|udf3|udf2|udf1|email|firstname|productinfo|amount|txnid|key)
        // Documentation shows 6 pipes (||||||), but actual validation requires 5 pipes (|||||) to match request format
        // After status|, there must be exactly 5 pipes (|||||) before udf5
        // This matches the request hash format (5 pipes after udf5 in request = 5 pipes before udf5 in response)
        $hashString = $this->salt.'|'
            .$status.'|'
            .'|||||'
            .$udf5.'|'
            .$udf4.'|'
            .$udf3.'|'
            .$udf2.'|'
            .$udf1.'|'
            .$email.'|'
            .$firstname.'|'
            .$productinfo.'|'
            .$amount.'|'
            .$txnid.'|'
            .$this->merchantKey;

        $calculatedHash = strtolower(hash('sha512', $hashString));

        // Log hash verification details for debugging
        \Illuminate\Support\Facades\Log::info('PayU Hash Verification', [
            'calculated_hash' => $calculatedHash,
            'received_hash' => $receivedHash,
            'hash_match' => hash_equals($calculatedHash, $receivedHash),
            'hash_string' => $hashString,
        ]);

        return hash_equals($calculatedHash, $receivedHash);
    }

    /**
     * Prepare payment data for PayU.
     * 
     * Mandatory parameters: key, txnid, amount, productinfo, firstname, email, phone, surl, furl, hash
     * Optional parameters: lastname, address1, address2, city, state, country, zipcode, 
     *                     enforced_payment, drop_category, custom_note, note_category
     * 
     * Reference: PayU Hosted Checkout API Documentation - Step 1.1
     */
    public function preparePaymentData(array $data): array
    {
        // Validate mandatory fields
        $requiredFields = ['transaction_id', 'amount', 'product_info', 'firstname', 'email', 'phone', 'success_url', 'failure_url'];
        $missingFields = array_diff($requiredFields, array_keys($data));
        
        if (! empty($missingFields)) {
            throw new \InvalidArgumentException('Missing required PayU parameters: '.implode(', ', $missingFields));
        }

        // Build payment data array with all mandatory fields
        $paymentData = [
            'key' => $this->merchantKey,
            'txnid' => $data['transaction_id'],
            'amount' => number_format((float) $data['amount'], 2, '.', ''),
            'productinfo' => $data['product_info'],
            'firstname' => $data['firstname'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'surl' => $data['success_url'],
            'furl' => $data['failure_url'],
        ];

        // Add optional parameters if provided
        if (isset($data['lastname']) && $data['lastname'] !== '') {
            $paymentData['lastname'] = $data['lastname'];
        }
        if (isset($data['address1']) && $data['address1'] !== '') {
            $paymentData['address1'] = $data['address1'];
        }
        if (isset($data['address2']) && $data['address2'] !== '') {
            $paymentData['address2'] = $data['address2'];
        }
        if (isset($data['city']) && $data['city'] !== '') {
            $paymentData['city'] = $data['city'];
        }
        if (isset($data['state']) && $data['state'] !== '') {
            $paymentData['state'] = $data['state'];
        }
        if (isset($data['country']) && $data['country'] !== '') {
            $paymentData['country'] = $data['country'];
        }
        if (isset($data['zipcode']) && $data['zipcode'] !== '') {
            $paymentData['zipcode'] = $data['zipcode'];
        }
        if (isset($data['enforced_payment']) && $data['enforced_payment'] !== '') {
            $paymentData['enforced_payment'] = $data['enforced_payment'];
        }
        if (isset($data['drop_category']) && $data['drop_category'] !== '') {
            $paymentData['drop_category'] = $data['drop_category'];
        }
        if (isset($data['custom_note']) && $data['custom_note'] !== '') {
            $paymentData['custom_note'] = $data['custom_note'];
        }
        if (isset($data['note_category']) && $data['note_category'] !== '') {
            $paymentData['note_category'] = $data['note_category'];
        }

        // UDF fields (user-defined fields)
        $paymentData['udf1'] = $data['udf1'] ?? '';
        $paymentData['udf2'] = $data['udf2'] ?? '';
        $paymentData['udf3'] = $data['udf3'] ?? '';
        $paymentData['udf4'] = $data['udf4'] ?? '';
        $paymentData['udf5'] = $data['udf5'] ?? '';

        // Note: service_provider parameter removed as per PayU requirements

        // Generate hash using the payment data (hash must be calculated before adding to form)
        $paymentData['hash'] = $this->generateHash($paymentData);

        // Log payment data preparation for debugging (without sensitive data)
        \Illuminate\Support\Facades\Log::info('PayU Payment Data Prepared', [
            'txnid' => $paymentData['txnid'],
            'amount' => $paymentData['amount'],
            'surl' => $paymentData['surl'],
            'furl' => $paymentData['furl'],
            'hash_length' => strlen($paymentData['hash']),
            'has_optional_params' => isset($data['lastname']) || isset($data['address1']) || isset($data['city']),
        ]);

        return $paymentData;
    }

    /**
     * Get payment gateway URL.
     */
    public function getPaymentUrl(): string
    {
        return $this->paymentUrl;
    }

    /**
     * Query PayU transaction status.
     * This can be used to check payment status when callback doesn't have parameters.
     */
    public function checkTransactionStatus(string $transactionId): ?array
    {
        $mode = config('services.payu.mode', 'test');
        $command = 'verify_payment';
        
        // Build hash for status check
        $hashString = $this->merchantKey.'|'.$command.'|'.$transactionId.'|'.$this->salt;
        $hash = strtolower(hash('sha512', $hashString));
        
        // PayU status check endpoint (Verify Payment API)
        // Reference: PayU Hosted Checkout API Documentation - Step 1.6
        $statusUrl = $mode === 'test' 
            ? 'https://test.payu.in/merchant/postservice.php?form=2'
            : 'https://info.payu.in/merchant/postservice.php?form=2';
        
        $postData = [
            'key' => $this->merchantKey,
            'command' => $command,
            'var1' => $transactionId,
            'hash' => $hash,
        ];
        
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $statusUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                \Illuminate\Support\Facades\Log::error('PayU Status Check cURL Error', [
                    'error' => $error,
                    'transaction_id' => $transactionId,
                ]);
                return null;
            }
            
            // Parse response according to PayU documentation
            // PayU Verify Payment API returns JSON format
            // Reference: PayU Hosted Checkout API Documentation - Step 1.6
            $result = [];
            $json = json_decode($response, true);
            
            if ($json && isset($json['status'])) {
                // Valid JSON response from PayU
                $result = $json;
                
                // Extract transaction details if available
                if (isset($json['transaction_details']) && is_array($json['transaction_details'])) {
                    // PayU returns transaction_details as an object with transaction ID as key
                    // Example: {"transaction_details": {"TXN123": {...}}}
                    // Get the first (and usually only) transaction detail
                    $txnDetails = reset($json['transaction_details']);
                    if ($txnDetails && is_array($txnDetails)) {
                        // Map PayU response fields to our format
                        $result['transaction_status'] = $txnDetails['status'] ?? null;
                        $result['mihpayid'] = $txnDetails['mihpayid'] ?? null;
                        $result['bank_ref_num'] = $txnDetails['bank_ref_num'] ?? null;
                        $result['amount'] = $txnDetails['amt'] ?? $txnDetails['transaction_amount'] ?? null;
                        $result['error_code'] = $txnDetails['error_code'] ?? null;
                        $result['error_message'] = $txnDetails['error_Message'] ?? $txnDetails['error_message'] ?? null;
                        $result['field9'] = $txnDetails['field9'] ?? null;
                        $result['mode'] = $txnDetails['mode'] ?? null;
                        $result['unmappedstatus'] = $txnDetails['unmappedstatus'] ?? null;
                        $result['card_type'] = $txnDetails['card_type'] ?? null;
                        $result['pg_type'] = $txnDetails['PG_TYPE'] ?? $txnDetails['pg_type'] ?? null;
                        $result['productinfo'] = $txnDetails['productinfo'] ?? null;
                        $result['firstname'] = $txnDetails['firstname'] ?? null;
                        $result['net_amount_debit'] = $txnDetails['net_amount_debit'] ?? null;
                        $result['discount'] = $txnDetails['disc'] ?? $txnDetails['discount'] ?? null;
                        $result['addedon'] = $txnDetails['addedon'] ?? null;
                        $result['payment_source'] = $txnDetails['payment_source'] ?? null;
                    }
                }
            } else {
                // Fallback: try to parse as pipe-separated (legacy format)
                if (strpos($response, '|') !== false) {
                    $parts = explode('|', $response);
                    $result = [
                        'status' => $parts[0] ?? '',
                        'message' => $parts[1] ?? '',
                        'transaction_id' => $transactionId,
                        'raw_response' => $response,
                    ];
                } else {
                    $result = [
                        'status' => 0,
                        'msg' => 'Invalid response format',
                        'raw_response' => $response,
                    ];
                }
            }
            
            \Illuminate\Support\Facades\Log::info('PayU Status Check Response', [
                'transaction_id' => $transactionId,
                'http_code' => $httpCode,
                'response' => $result,
            ]);
            
            return $result;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('PayU Status Check Exception', [
                'error' => $e->getMessage(),
                'transaction_id' => $transactionId,
            ]);
            return null;
        }
    }
}
