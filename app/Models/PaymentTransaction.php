<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'application_id',
        'transaction_id',
        'payment_id',
        'payment_mode',
        'payment_status',
        'amount',
        'currency',
        'product_info',
        'response_message',
        'payu_response',
        'hash',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payu_response' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(Registration::class, 'user_id');
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
