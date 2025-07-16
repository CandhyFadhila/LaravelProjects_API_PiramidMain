<?php

namespace App\Http\Requests\Service\Payment;

use App\Http\Resources\Templates\WithoutDataResource;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;

class StoreOrderDetail extends FormRequest
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
            'service_type_id' => ['required', 'exists:service_types,id'],
            // 'detail' => ['required', 'array'],
            // 'last_steps' => ['nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'service_type_id.required' => 'Tipe layanan tidak boleh kosong.',
            'service_type_id.exists' => 'Tipe layanan tidak valid.',
            'detail.required' => 'Detail order tidak boleh kosong.',
            'detail.array' => 'Detail order harus berupa array.',
            'last_steps.integer' => 'Langkah terakhir harus berupa angka.',
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
