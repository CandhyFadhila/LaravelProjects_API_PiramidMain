<?php

namespace App\Models;

use App\Traits\HasArrayRelations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AqiqahProduct extends Model
{
    use SoftDeletes, HasArrayRelations;

    protected $guarded = ['id'];

    protected $appends = ['documents'];

    protected $casts = [
        'animal_id' => 'integer',
        'photo_product_id' => 'array',
        'price' => 'float',
        'portion_count' => 'float',
        'gender' => 'integer',
    ];

    /**
     * Get the animals that owns the QurbanProduct
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function animals(): BelongsTo
    {
        return $this->belongsTo(Animal::class, 'animal_id', 'id');
    }

    public function animalWithRelations()
    {
        return $this->animals()->with(['animal_categories.animal_breeds']);
    }

    public function getDocumentsAttribute()
    {
        return $this->resolveArrayRelations(
            $this->photo_product_id,
            Document::class,
            ['uploaded_users', 'verified_users']
        );
    }
}
