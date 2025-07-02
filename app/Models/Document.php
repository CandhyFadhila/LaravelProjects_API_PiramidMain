<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'uploaded_by' => 'integer',
        'verified_by' => 'integer',
    ];

    /**
     * Get the uploaded_users that owns the Document
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function uploaded_users(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by', 'id');
    }

    /**
     * Get the verified_users that owns the Document
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function verified_users(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by', 'id');
    }
}
