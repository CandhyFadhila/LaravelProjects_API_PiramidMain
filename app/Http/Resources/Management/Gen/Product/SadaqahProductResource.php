<?php

namespace App\Http\Resources\Management\Gen\Product;

use App\Http\Resources\Management\Gen\Animal\AnimalBreedResource;
use App\Http\Resources\Management\Gen\Animal\AnimalCategoryResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SadaqahProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'animal' => $this->animals->map(function ($animal) {
                return [
                    'id' => $animal->id,
                    'animal_category' => new AnimalCategoryResource($animal->animal_categories),
                    'animal_breed' => new AnimalBreedResource($animal->animal_breeds),
                    'average_weight' => $animal->average_weight,
                    'birth_date' => $animal->birth_date,
                    'stock' => $animal->stock,
                    'created_at' => $animal->created_at,
                    'updated_at' => $animal->updated_at,
                    'deleted_at' => $animal->deleted_at,
                ];
            }),
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'photo_product_id' => $this->documents,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at
        ];
    }
}
