<?php

namespace App\Models;

use App\Helpers\DateHelper;
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

    /**
     * Mutator untuk mengonversi format tanggal untuk beberapa kolom sekaligus.
     *
     * @param string $value
     * @return void
     */
    public function setDateAttribute($value)
    {
        // Looping untuk setiap kolom timestamp yang ada dalam model
        $timestampColumns = ['transaction_date', 'settlement_date'];

        foreach ($timestampColumns as $column) {
            if ($value && isset($this->attributes[$column])) {
                $this->attributes[$column] = DateHelper::formatTanggalIndonesia($value, 5);
            }
        }
    }
}
