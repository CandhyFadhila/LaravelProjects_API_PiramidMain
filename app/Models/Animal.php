<?php

namespace App\Models;

use App\Helpers\DateHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Animal extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'animal_category_id' => 'integer',
        'animal_breed_id' => 'integer',
        'average_weight' => 'integer',
        'stock' => 'integer',
    ];

    /**
     * Get the animal_categories that owns the Animal
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function animal_categories(): BelongsTo
    {
        return $this->belongsTo(AnimalCategory::class, 'animal_category_id', 'id');
    }

    /**
     * Get the animal_breeds that owns the Animal
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function animal_breeds(): BelongsTo
    {
        return $this->belongsTo(AnimalBreed::class, 'animal_breed_id', 'id');
    }

    public function setBirthDateAttribute($value)
    {
        $this->attributes['birth_date'] = DateHelper::formatTanggalIndonesia($value, 5);
    }
}
