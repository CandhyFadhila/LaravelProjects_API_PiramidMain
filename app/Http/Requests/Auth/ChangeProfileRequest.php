<?php

namespace App\Http\Requests\Auth;

use App\Http\Resources\Templates\WithoutDataResource;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;

class ChangeProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
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
            'photo_profile_id' => ['required', 'array', 'max:1'],
            'photo_profile_id.*' => ['required', 'mimes:jpg,jpeg,png', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'photo_profile_id.required' => 'Foto profil tidak boleh kosong.',
            'photo_profile_id.max' => 'Maksimal dokumen foto profil yang diunggah adalah 1 dokumen.',
            'photo_profile_id.*.mimes' => 'Dokumen foto profil hanya boleh berupa JPG, JPEG, dan PNG.',
            'photo_profile_id.*.max' => 'Ukuran dokumen foto profil maksimal 10MB.',
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
