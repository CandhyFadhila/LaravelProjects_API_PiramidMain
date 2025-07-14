<?php

namespace App\Http\Resources\Service;

use App\Http\Resources\Required\ServiceType;
use App\Http\Resources\Required\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderDetail extends JsonResource
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
            'user' => new UserResource($this->user),
            'service_type' => new ServiceType($this->service_type),
            'detail' => $this->detail,
            'last_steps' => $this->last_steps,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
