<?php

namespace App\Http\Requests\Service\Payment;

use App\Http\Resources\Templates\WithoutDataResource;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;

class StorePayment extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'order_detail_id'       => 'required|exists:order_details,id',
            'payment_gateway_id'    => 'required|string',
            'amount_paid'           => 'required|numeric|min:100',
            'currency'              => 'required|string',
            'note'                  => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'order_detail_id.required'      => 'Order detail tidak boleh kosong.',
            'order_detail_id.exists'        => 'Order detail tersebut tidak valid.',
            'payment_gateway_id.required'   => 'Metode pembayaran tidak boleh kosong.',
            'payment_gateway_id.string'     => 'Metode pembayaran harus berupa teks.',
            'amount_paid.required'          => 'Jumlah pembayaran tidak boleh kosong.',
            'amount_paid.numeric'           => 'Jumlah pembayaran harus berupa angka.',
            'amount_paid.min'               => 'Jumlah pembayaran minimal Rp100.',
            'currency.required'             => 'Mata uang tidak boleh kosong.',
            'note.string'                   => 'Catatan harus berupa teks.',
        ];
    }

    public function failedValidation(Validator $validator)
    {
        $messages = implode(' ', $validator->errors()->all());
        $response = new WithoutDataResource(
            Response::HTTP_BAD_REQUEST,
            'FAILED_VALIDATION',
            'Format Data Tidak Sesuai Ketentuan',
            $messages
        );

        throw new HttpResponseException(response()->json($response, Response::HTTP_BAD_REQUEST));
    }
}
