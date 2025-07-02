<?php

namespace App\Models;

use App\Traits\HasArrayRelations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SadaqahProduct extends Model
{
    use SoftDeletes, HasArrayRelations;

    protected $guarded = ['id'];

    protected $appends = ['documents', 'animals'];

    protected $casts = [
        'photo_product_id' => 'array',
        'animal_id' => 'array',
        'price' => 'float',
    ];

    public function getDocumentsAttribute()
    {
        return $this->resolveArrayRelations(
            $this->photo_product_id,
            Document::class,
            ['uploaded_users', 'verified_users']
        );
    }

    public function getAnimalsAttribute()
    {
        return $this->resolveArrayRelations(
            $this->animal_id,
            Animal::class,
            ['animal_categories', 'animal_breeds']
        );
    }
}
