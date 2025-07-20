<?php

namespace App\Models;

use App\Helpers\DateHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentDetail extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'transaction_id' => 'integer',
        'payment_status_id' => 'integer',
        'payment_method_id' => 'integer',
        'amount_paid' => 'float',
    ];

    /**
     * Get the transaction that owns the PaymentDetail
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function transactions(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_id', 'id');
    }

    /**
     * Get the payment_status that owns the PaymentDetail
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function payment_statuses(): BelongsTo
    {
        return $this->belongsTo(PaymentStatus::class, 'payment_status_id', 'id');
    }

    /**
     * Get the payment_method that owns the PaymentDetail
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function payment_methods(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id', 'id');
    }

    public function setPaymentDateAttribute($value)
    {
        $this->attributes['payment_date'] = $value
            ? DateHelper::formatTanggalIndonesia($value, 5)
            : null;
    }
}
