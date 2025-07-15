<?php

namespace App\Http\Requests\Management\Gen\Mosque;

use App\Http\Resources\Templates\WithoutDataResource;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;

class UpdateMosque extends FormRequest
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
            'name'          => ['required', 'string'],
            'phone_number'  => ['required', 'string'],
            'wa_number'     => ['nullable', 'string'],
            'address'       => ['required', 'string'],
            'city'          => ['required', 'string', 'max:255'],
            'province'      => ['required', 'string', 'max:255'],
            'postal_code'   => ['required', 'string', 'max:20'],
            'country'       => ['required', 'string', 'max:255'],
            'latitude'      => ['required', 'string', 'max:100'],
            'longitude'     => ['required', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'         => 'Nama masjid tidak boleh kosong.',
            'phone_number.required' => 'Nomor telepon pengelola masjid tidak boleh kosong.',
            'address.required'      => 'Alamat pengiriman tidak boleh kosong.',
            'city.required'         => 'Kota tidak boleh kosong.',
            'province.required'     => 'Provinsi tidak boleh kosong.',
            'postal_code.required'  => 'Kode pos tidak boleh kosong.',
            'country.required'      => 'Negara tidak boleh kosong.',
            'latitude.required'     => 'Latitude tidak boleh kosong.',
            'longitude.required'    => 'Longitude tidak boleh kosong.',
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
