<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransaction extends Model
{
    protected $fillable = [
        'wallet_id',
        'user_id',
        'transaction_type',
        'transaction_id',
        'amount',
        'balance_before',
        'balance_after',
        'payment_transaction_id',
        'application_id',
        'description',
        'payu_response',
        'status',
        'sync_source',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'payu_response' => 'array',
        'sync_source' => 'boolean',
    ];

    /**
     * Get the wallet that owns this transaction.
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * Get the user that owns this transaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(Registration::class, 'user_id');
    }

    /**
     * Get the payment transaction associated with this wallet transaction.
     */
    public function paymentTransaction(): BelongsTo
    {
        return $this->belongsTo(PaymentTransaction::class, 'payment_transaction_id');
    }

    /**
     * Get the application associated with this wallet transaction.
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * Record credit transaction (add money).
     */
    public static function recordCredit(
        Wallet $wallet,
        float $amount,
        float $balanceBefore,
        float $balanceAfter,
        string $transactionId,
        int $paymentTransactionId,
        ?string $description = null,
        ?array $payuResponse = null,
        string $status = 'success'
    ): self {
        return $wallet->recordTransaction(
            'credit',
            $amount,
            $balanceBefore,
            $balanceAfter,
            $transactionId,
            $paymentTransactionId,
            null,
            $description ?? "Wallet top-up of ₹{$amount}",
            $payuResponse,
            $status
        );
    }

    /**
     * Record debit transaction (payment).
     */
    public static function recordDebit(
        Wallet $wallet,
        float $amount,
        float $balanceBefore,
        float $balanceAfter,
        string $transactionId,
        int $paymentTransactionId,
        int $applicationId,
        ?string $description = null,
        ?array $payuResponse = null,
        string $status = 'success'
    ): self {
        return $wallet->recordTransaction(
            'debit',
            $amount,
            $balanceBefore,
            $balanceAfter,
            $transactionId,
            $paymentTransactionId,
            $applicationId,
            $description ?? "Payment of ₹{$amount}",
            $payuResponse,
            $status
        );
    }

    /**
     * Record refund transaction.
     */
    public static function recordRefund(
        Wallet $wallet,
        float $amount,
        float $balanceBefore,
        float $balanceAfter,
        string $transactionId,
        int $paymentTransactionId,
        ?string $description = null,
        ?array $payuResponse = null,
        string $status = 'success'
    ): self {
        return $wallet->recordTransaction(
            'refund',
            $amount,
            $balanceBefore,
            $balanceAfter,
            $transactionId,
            $paymentTransactionId,
            null,
            $description ?? "Refund of ₹{$amount}",
            $payuResponse,
            $status
        );
    }

    /**
     * Record wallet creation transaction.
     */
    public static function recordCreation(
        Wallet $wallet,
        ?string $description = null,
        ?array $payuResponse = null
    ): self {
        return $wallet->recordTransaction(
            'creation',
            0.00,
            0.00,
            0.00,
            null,
            null,
            null,
            $description ?? 'Wallet created',
            $payuResponse,
            'success'
        );
    }

    /**
     * Record balance sync operation.
     */
    public static function recordSync(
        Wallet $wallet,
        float $balanceBefore,
        float $balanceAfter,
        ?string $description = null,
        ?array $payuResponse = null
    ): self {
        return $wallet->recordTransaction(
            'sync',
            0.00,
            $balanceBefore,
            $balanceAfter,
            null,
            null,
            null,
            $description ?? 'Balance synced from PayU',
            $payuResponse,
            'success',
            true
        );
    }
}
