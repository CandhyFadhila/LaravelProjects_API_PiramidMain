<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Collection;

trait HasArrayRelations
{
    /**
     * Helper untuk ambil relasi dari field array of IDs
     *
     * @param  mixed  $value  Nilai array dari accessor
     * @param  string  $relatedModel  Contoh: App\Models\Facility::class
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function resolveArrayRelations($value, string $relatedModel, array $withRelations = []): Collection
    {
        if (!is_array($value)) {
            $value = json_decode($value, true) ?? [];
        }

        // Validasi hanya relasi yang benar-benar ada di model
        $validRelations = array_filter($withRelations, function ($relation) use ($relatedModel) {
            return method_exists(new $relatedModel, $relation);
        });

        return $relatedModel::with($validRelations)->whereIn('id', $value)->get();
    }
}
