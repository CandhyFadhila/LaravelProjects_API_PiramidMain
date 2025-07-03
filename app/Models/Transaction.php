<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'user_id' => 'integer',
        'order_detail_id' => 'integer',
        'payment_detail_id' => 'integer',
        'transaction_status_id' => 'integer',
        'grand_total' => 'float',
    ];

    /**
     * Get the user that owns the Transaction
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function users(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Get the order_detail that owns the Transaction
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function order_details(): BelongsTo
    {
        return $this->belongsTo(OrderDetail::class, 'order_detail_id', 'id');
    }

    /**
     * Get the payment_detail that owns the Transaction
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function payment_details(): BelongsTo
    {
        return $this->belongsTo(PaymentDetail::class, 'payment_detail_id', 'id');
    }

    /**
     * Get the transaction_status that owns the Transaction
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function transaction_statuses(): BelongsTo
    {
        return $this->belongsTo(TransactionStatus::class, 'transaction_status_id', 'id');
    }
}
