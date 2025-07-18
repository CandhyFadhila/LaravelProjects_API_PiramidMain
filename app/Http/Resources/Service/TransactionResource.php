<?php

namespace App\Http\Resources\Service;

use App\Http\Resources\Required\TransactionStatusResource;
use App\Http\Resources\Required\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
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
            'user' => new UserResource($this->users),
            'order_detail' => new OrderDetailResource($this->order_details),
            'payment_detail' => new PaymentDetailResource($this->payment_details),
            'transaction_status' => new TransactionStatusResource($this->transaction_statuses),
            'transaction_date' => $this->transaction_date,
            'settlement_date' => $this->settlement_date,
            'grand_total' => $this->grand_total,
            'note' => $this->note,
            'snap_token' => $this->snap_token,
            'midtrans_order_id' => $this->midtrans_order_id,
            'coinpayment_order_id' => $this->coinpayment_order_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
