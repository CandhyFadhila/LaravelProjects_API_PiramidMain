<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cities extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'province_id' => 'integer',
        'is_active' => 'integer',
    ];

    /**
     * Get the provinces that owns the Cities
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function provinces(): BelongsTo
    {
        return $this->belongsTo(Province::class, 'province_id', 'id');
    }
}
