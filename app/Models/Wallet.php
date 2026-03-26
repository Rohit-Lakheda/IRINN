<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    protected $fillable = [
        'user_id',
        'wallet_id',
        'wallet_type',
        'status',
        'balance',
        'currency',
        'kyc_status',
        'payu_wallet_data',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'payu_wallet_data' => 'array',
    ];

    /**
     * Get the user that owns the wallet.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(Registration::class, 'user_id');
    }

    /**
     * Get all transactions for this wallet.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    /**
     * Get applications that used this wallet for payment.
     */
    public function applications()
    {
        return $this->hasManyThrough(
            Application::class,
            WalletTransaction::class,
            'wallet_id', // Foreign key on wallet_transactions table
            'id', // Foreign key on applications table
            'id', // Local key on wallets table
            'application_id' // Local key on wallet_transactions table
        )->distinct();
    }

    /**
     * Get current balance (syncs from PayU if needed).
     */
    public function getBalance(): float
    {
        return (float) $this->balance;
    }

    /**
     * Check if wallet can debit the specified amount.
     */
    public function canDebit(float $amount): bool
    {
        return $this->isActive() && $this->balance >= $amount;
    }

    /**
     * Check if wallet is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Sync balance from PayU API.
     */
    public function syncBalanceFromPayU(): bool
    {
        $walletService = app(\App\Services\WalletService::class);

        return $walletService->syncBalance($this);
    }

    /**
     * Helper to record any transaction type.
     */
    public function recordTransaction(
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
        return WalletTransaction::create([
            'wallet_id' => $this->id,
            'user_id' => $this->user_id,
            'transaction_type' => $type,
            'transaction_id' => $transactionId,
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'payment_transaction_id' => $paymentTransactionId,
            'application_id' => $applicationId,
            'description' => $description,
            'payu_response' => $payuResponse,
            'status' => $status,
            'sync_source' => $syncSource,
        ]);
    }
}
