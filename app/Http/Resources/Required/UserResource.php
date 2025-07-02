<?php

namespace App\Http\Resources\Required;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'photo_profile_id' => $this->documents,
            'name' => $this->name,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'wa_number' => $this->wa_number,
            'account_status' => $this->account_status,
            'register_at' => $this->register_at,
            'deactivate_at' => $this->deactivate_at,
            'last_login' => $this->last_login,
            'last_change_password' => $this->last_change_password,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at
        ];
    }
}
