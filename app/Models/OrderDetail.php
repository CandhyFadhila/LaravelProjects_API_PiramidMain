<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderDetail extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'user_id' => 'integer',
        'service_type_id' => 'integer',
        'detail' => 'array',
        'last_steps' => 'integer',
    ];

    /**
     * Get the users that owns the OrderDetail
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Get the service_type that owns the OrderDetail
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function service_type(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class, 'service_type_id', 'id');
    }
}
