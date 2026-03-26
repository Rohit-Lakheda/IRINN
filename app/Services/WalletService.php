<?php

namespace App\Services;

use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\Log;

class WalletService
{
    protected PayuService $payuService;

    protected string $merchantKey;

    protected string $salt;

    protected string $apiBaseUrl;

    protected ?string $lastError = null;

    /**
     * Get the last error message.
     */
    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    public function __construct(PayuService $payuService)
    {
        $this->payuService = $payuService;
        $this->merchantKey = config('services.payu.merchant_key');
        $this->salt = config('services.payu.salt');
        $mode = config('services.payu.mode', 'test');

        // Get wallet API base URL with fallback defaults
        $walletConfig = config('services.payu.wallet', []);
        $this->apiBaseUrl = $mode === 'test'
            ? ($walletConfig['api_base_url_test'] ?? 'https://test.payu.in/merchant/')
            : ($walletConfig['api_base_url_live'] ?? 'https://secure.payu.in/merchant/');
    }

    /**
     * Generate hash for PayU wallet API calls.
     */
    protected function generateWalletHash(string $command, array $params): string
    {
        // Build hash string: key|command|var1|var2|...|salt
        $hashString = $this->merchantKey.'|'.$command;
        foreach ($params as $param) {
            $hashString .= '|'.$param;
        }
        $hashString .= '|'.$this->salt;

        return strtolower(hash('sha512', $hashString));
    }

    /**
     * Make API call to PayU wallet endpoint.
     */
    protected function makeApiCall(string $endpoint, array $postData): ?array
    {
        $url = $this->apiBaseUrl.$endpoint;

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
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
                Log::error('PayU Wallet API cURL Error', [
                    'error' => $error,
                    'endpoint' => $endpoint,
                    'post_data' => $postData,
                ]);

                return null;
            }

            $json = json_decode($response, true);
            if ($json === null) {
                // Try to parse as pipe-separated format
                if (strpos($response, '|') !== false) {
                    $parts = explode('|', $response);
                    $json = [
                        'status' => $parts[0] ?? 0,
                        'msg' => $parts[1] ?? '',
                        'raw_response' => $response,
                    ];
                } else {
                    $json = [
                        'status' => 0,
                        'msg' => 'Invalid response format',
                        'raw_response' => $response,
                    ];
                }
            }

            Log::info('PayU Wallet API Response', [
                'endpoint' => $endpoint,
                'http_code' => $httpCode,
                'response' => $json,
            ]);

            return $json;
        } catch (\Exception $e) {
            Log::error('PayU Wallet API Exception', [
                'error' => $e->getMessage(),
                'endpoint' => $endpoint,
            ]);

            return null;
        }
    }

    /**
     * Create wallet locally (PayU wallet creation may require different API or manual setup).
     * For closed-loop wallets, we can create locally and sync with PayU later.
     */
    public function createWallet(int $userId, string $walletType = 'closed_loop', array $userData = []): ?Wallet
    {
        try {
            // Generate a unique wallet ID for local tracking
            $walletId = 'WLT'.time().rand(1000, 9999);

            // For closed-loop wallets, we can create locally without PayU API call
            // PayU wallet creation might require dashboard setup or different API endpoints
            // The wallet_id will be updated when we sync with PayU or when PayU assigns one

            Log::info('Creating wallet locally', [
                'user_id' => $userId,
                'wallet_type' => $walletType,
                'local_wallet_id' => $walletId,
            ]);

            // Create wallet record locally
            $wallet = Wallet::create([
                'user_id' => $userId,
                'wallet_id' => $walletId, // Local wallet ID, will be updated when synced with PayU
                'wallet_type' => $walletType,
                'status' => 'active',
                'balance' => 0.00,
                'currency' => 'INR',
                'payu_wallet_data' => [
                    'created_locally' => true,
                    'note' => 'Wallet created locally. PayU wallet ID will be assigned when wallet is used for first transaction or when synced with PayU.',
                ],
            ]);

            // Automatically record creation transaction
            WalletTransaction::recordCreation($wallet, 'Wallet created locally', [
                'wallet_type' => $walletType,
                'created_locally' => true,
            ]);

            Log::info('Wallet Created Successfully', [
                'wallet_id' => $wallet->id,
                'local_wallet_id' => $walletId,
                'user_id' => $userId,
            ]);

            return $wallet;
        } catch (\Exception $e) {
            $this->lastError = 'Failed to create wallet: '.$e->getMessage();
            Log::error('Wallet Creation Exception', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    /**
     * Top-up wallet via PayU, automatically records credit transaction.
     */
    public function addMoney(Wallet $wallet, float $amount, string $transactionId, int $paymentTransactionId, ?array $payuResponse = null): bool
    {
        if (! $wallet->isActive()) {
            Log::error('Wallet is not active', ['wallet_id' => $wallet->id]);

            return false;
        }

        $balanceBefore = (float) $wallet->balance;
        $balanceAfter = $balanceBefore + $amount;

        // Update wallet balance directly (no PayU wallet API calls - just like invoice payments)
        $wallet->update(['balance' => $balanceAfter]);

        // Record credit transaction with PayU response
        WalletTransaction::recordCredit(
            $wallet,
            $amount,
            $balanceBefore,
            $balanceAfter,
            $transactionId,
            $paymentTransactionId,
            "Wallet top-up of ₹{$amount}",
            $payuResponse ?? ['payment_verified' => true, 'payment_source' => 'payu']
        );

        Log::info('Wallet Money Added', [
            'wallet_id' => $wallet->id,
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'transaction_id' => $transactionId,
        ]);

        return true;
    }

    /**
     * Debit wallet for payment, automatically records debit transaction.
     */
    public function debitWallet(Wallet $wallet, float $amount, string $transactionId, int $paymentTransactionId, int $applicationId, ?string $description = null): bool
    {
        if (! $wallet->canDebit($amount)) {
            Log::error('Wallet cannot debit amount', [
                'wallet_id' => $wallet->id,
                'amount' => $amount,
                'balance' => $wallet->balance,
            ]);

            return false;
        }

        $balanceBefore = (float) $wallet->balance;
        $balanceAfter = $balanceBefore - $amount;

        // Update wallet balance directly (no PayU wallet API calls - just like invoice payments)
        $wallet->update(['balance' => $balanceAfter]);

        // Record debit transaction
        WalletTransaction::recordDebit(
            $wallet,
            $amount,
            $balanceBefore,
            $balanceAfter,
            $transactionId,
            $paymentTransactionId,
            $applicationId,
            $description ?? "Payment of ₹{$amount}",
            ['payment_source' => 'wallet', 'payment_verified' => true]
        );

        Log::info('Wallet Debited', [
            'wallet_id' => $wallet->id,
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'transaction_id' => $transactionId,
        ]);

        return true;
    }

    /**
     * Fetch current balance from PayU API.
     */
    public function getBalance(Wallet $wallet): ?float
    {
        if (! $wallet->wallet_id) {
            return null;
        }

        $command = 'balance_inquiry';
        $params = [$wallet->wallet_id];

        $hash = $this->generateWalletHash($command, $params);

        $postData = [
            'key' => $this->merchantKey,
            'command' => $command,
            'var1' => $wallet->wallet_id,
            'hash' => $hash,
        ];

        $response = $this->makeApiCall(config('services.payu.wallet.balance_inquiry_endpoint'), $postData);

        if (! $response || ($response['status'] ?? 0) != 1) {
            Log::error('PayU Wallet Balance Inquiry Failed', [
                'wallet_id' => $wallet->id,
                'response' => $response,
            ]);

            return null;
        }

        return (float) ($response['balance'] ?? 0.00);
    }

    /**
     * Sync balance from PayU and update local database, records sync transaction.
     */
    public function syncBalance(Wallet $wallet): bool
    {
        $balanceBefore = (float) $wallet->balance;
        $payuBalance = $this->getBalance($wallet);

        if ($payuBalance === null) {
            return false;
        }

        // Update local balance
        $wallet->update(['balance' => $payuBalance]);

        // Record sync transaction if balance differs
        if (abs($balanceBefore - $payuBalance) > 0.01) {
            WalletTransaction::recordSync(
                $wallet,
                $balanceBefore,
                $payuBalance,
                "Balance synced from PayU: ₹{$balanceBefore} → ₹{$payuBalance}",
                ['payu_balance' => $payuBalance, 'local_balance' => $balanceBefore]
            );

            Log::info('PayU Wallet Balance Synced', [
                'wallet_id' => $wallet->id,
                'balance_before' => $balanceBefore,
                'balance_after' => $payuBalance,
            ]);
        }

        return true;
    }

    /**
     * Fetch wallet transactions from PayU and sync missing ones.
     */
    public function getTransactionHistory(Wallet $wallet, ?int $limit = null): array
    {
        if (! $wallet->wallet_id) {
            return [];
        }

        $command = 'transaction_history';
        $params = [$wallet->wallet_id];
        if ($limit) {
            $params[] = (string) $limit;
        }

        $hash = $this->generateWalletHash($command, $params);

        $postData = [
            'key' => $this->merchantKey,
            'command' => $command,
            'var1' => $wallet->wallet_id,
            'hash' => $hash,
        ];

        if ($limit) {
            $postData['var2'] = (string) $limit;
        }

        $response = $this->makeApiCall(config('services.payu.wallet.transaction_history_endpoint'), $postData);

        if (! $response || ($response['status'] ?? 0) != 1) {
            Log::error('PayU Wallet Transaction History Failed', [
                'wallet_id' => $wallet->id,
                'response' => $response,
            ]);

            return [];
        }

        return $response['transactions'] ?? [];
    }

    /**
     * Verify PayU wallet transaction.
     */
    public function verifyWalletTransaction(string $transactionId): ?array
    {
        $command = 'verify_wallet_transaction';
        $params = [$transactionId];

        $hash = $this->generateWalletHash($command, $params);

        $postData = [
            'key' => $this->merchantKey,
            'command' => $command,
            'var1' => $transactionId,
            'hash' => $hash,
        ];

        return $this->makeApiCall(config('services.payu.wallet.balance_inquiry_endpoint'), $postData);
    }

    /**
     * Central method to record ALL wallet transactions.
     */
    public function recordTransaction(
        Wallet $wallet,
        string $type,
        float $amount,
        float $balanceBefore,
        float $balanceAfter,
        ?string $transactionId = null,
        ?int $paymentTransactionId = null,
        ?int $applicationId = null,
        ?string $description = null,
        ?array $payuResponse = null,
        string $status = 'success',
        bool $syncSource = false
    ): WalletTransaction {
        return $wallet->recordTransaction(
            $type,
            $amount,
            $balanceBefore,
            $balanceAfter,
            $transactionId,
            $paymentTransactionId,
            $applicationId,
            $description,
            $payuResponse,
            $status,
            $syncSource
        );
    }

    /**
     * Fetch and record any missing transactions from PayU.
     */
    public function syncTransactionsFromPayU(Wallet $wallet): int
    {
        $payuTransactions = $this->getTransactionHistory($wallet);
        $syncedCount = 0;

        foreach ($payuTransactions as $payuTxn) {
            $transactionId = $payuTxn['transaction_id'] ?? null;
            if (! $transactionId) {
                continue;
            }

            // Check if transaction already exists
            $existing = WalletTransaction::where('transaction_id', $transactionId)
                ->where('wallet_id', $wallet->id)
                ->first();

            if ($existing) {
                continue;
            }

            // Record missing transaction
            $this->recordTransaction(
                $wallet,
                $payuTxn['type'] ?? 'credit',
                (float) ($payuTxn['amount'] ?? 0),
                (float) ($payuTxn['balance_before'] ?? 0),
                (float) ($payuTxn['balance_after'] ?? 0),
                $transactionId,
                null,
                null,
                $payuTxn['description'] ?? 'Synced from PayU',
                $payuTxn,
                $payuTxn['status'] ?? 'success',
                true
            );

            $syncedCount++;
        }

        Log::info('PayU Wallet Transactions Synced', [
            'wallet_id' => $wallet->id,
            'synced_count' => $syncedCount,
        ]);

        return $syncedCount;
    }
}
