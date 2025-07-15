<?php

namespace App\Http\Requests\Management\Gen\CoverageArea;

use App\Http\Resources\Templates\WithoutDataResource;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;

class StoreCitiesRequest extends FormRequest
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
            'province_id' => ['required', 'exists:provinces,id'],
            'name'        => ['required', 'string', 'max:255', 'unique:cities,name'],
            'is_active'   => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'province_id.required' => 'Provinsi tidak boleh kosong.',
            'province_id.exists'   => 'Provinsi tersebut tidak valid.',
            'name.required'        => 'Nama kota tidak boleh kosong.',
            'name.string'          => 'Nama kota harus berupa teks.',
            'name.max'             => 'Nama kota tidak boleh lebih dari 255 karakter.',
            'name.unique'          => 'Nama kota tersebut sudah pernah dibuat.',
            'is_active.boolean'    => 'Status aktif harus berupa 1 atau 0.',
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
