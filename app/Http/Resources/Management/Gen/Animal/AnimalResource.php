<?php

namespace App\Http\Resources\Management\Gen\Animal;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnimalResource extends JsonResource
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
            'animal_category' => new AnimalCategoryResource($this->animal_categories),
            'animal_breed' => new AnimalBreedResource($this->animal_breeds),
            'average_weight' => $this->average_weight,
            'birth_date' => $this->birth_date,
            'stock' => $this->stock,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at
        ];
    }
}
