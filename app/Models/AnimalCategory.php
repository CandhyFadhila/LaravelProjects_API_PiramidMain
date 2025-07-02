<?php

namespace App\Models;

use App\Traits\HasArrayRelations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AnimalCategory extends Model
{
    use SoftDeletes, HasArrayRelations;

    protected $guarded = ['id'];

    protected $appends = ['documents'];

    protected $casts = [
        'icon_id' => 'array',
    ];

    public function getDocumentsAttribute()
    {
        return $this->resolveArrayRelations(
            $this->icon_id,
            Document::class,
            ['uploaded_users', 'verified_users']
        );
    }
}
