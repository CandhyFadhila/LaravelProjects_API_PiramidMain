<?php

namespace App\Http\Resources\Management\Gen\Product;

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
            'animal' => $this->animals,
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
