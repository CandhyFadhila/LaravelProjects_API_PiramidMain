<?php

namespace App\Http\Resources\Management\Gen\Product;

use App\Http\Resources\Management\Gen\Animal\AnimalBreedResource;
use App\Http\Resources\Management\Gen\Animal\AnimalCategoryResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AqiqahProductResource extends JsonResource
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
            'animal' => $this->whenLoaded('animals', function () {
                return $this->animals ? [
                    'id' => $this->animals->id,
                    'animal_category' => new AnimalCategoryResource($this->animals->animal_categories),
                    'animal_breed' => new AnimalBreedResource($this->animals->animal_breeds),
                    'average_weight' => $this->animals->average_weight,
                    'birth_date' => $this->animals->birth_date,
                    'stock' => $this->animals->stock,
                    'created_at' => $this->animals->created_at,
                    'updated_at' => $this->animals->updated_at,
                    'deleted_at' => $this->animals->deleted_at
                ] : null;
            }),
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'portion_count' => $this->portion_count,
            'gender' => $this->gender,
            'photo_product_id' => $this->documents,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at
        ];
    }
}
