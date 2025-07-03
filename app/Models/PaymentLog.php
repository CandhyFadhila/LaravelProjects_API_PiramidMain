<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentLog extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'payment_detail_id' => 'integer',
        'payload' => 'array',
    ];

    /**
     * Get the payment_details that owns the PaymentLog
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function payment_details(): BelongsTo
    {
        return $this->belongsTo(PaymentDetail::class, 'payment_detail_id', 'id');
    }
}
