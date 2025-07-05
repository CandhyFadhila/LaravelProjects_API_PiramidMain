<?php

namespace App\Http\Requests\Management\Settings\Account;

use App\Http\Resources\Templates\WithoutDataResource;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;

class UpdateAddress extends FormRequest
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
            'address'     => ['required', 'string'],
            'city'        => ['required', 'string', 'max:255'],
            'province'    => ['required', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'max:20'],
            'country'     => ['required', 'string', 'max:255'],
            'latidude'    => ['required', 'string', 'max:100'],
            'longitude'   => ['required', 'string', 'max:100'],
            'is_primary'  => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'address.required'      => 'Alamat pengiriman tidak boleh kosong.',
            'city.required'         => 'Kota tidak boleh kosong.',
            'province.required'     => 'Provinsi tidak boleh kosong.',
            'postal_code.required'  => 'Kode pos tidak boleh kosong.',
            'country.required'      => 'Negara tidak boleh kosong.',
            'latidude.required'     => 'Latitude tidak boleh kosong.',
            'longitude.required'    => 'Longitude tidak boleh kosong.',
            'is_primary.required'   => 'Status alamat utama harus diisi.',
            'is_primary.boolean'    => 'Status alamat utama harus berupa true atau false.',
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
