<?php

namespace App\Http\Resources\Service;

use App\Http\Resources\Required\PaymentMethodResource;
use App\Http\Resources\Required\PaymentStatusResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentDetailResource extends JsonResource
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
            // 'transaction' => new TransactionResource($this->transactions),
            'payment_status' => new PaymentStatusResource($this->payment_statuses),
            'payment_method' => new PaymentMethodResource($this->payment_methods),
            'payment_gateway_id' => $this->payment_gateway_id,
            'payment_order_id' => $this->payment_order_id, // order_id
            'payment_date' => $this->payment_date,
            'amount_paid' => $this->amount_paid,
            'transaction_ref' => $this->transaction_ref,
            'currency' => $this->currency,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
