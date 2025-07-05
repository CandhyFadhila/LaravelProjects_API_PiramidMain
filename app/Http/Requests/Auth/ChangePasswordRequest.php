<?php

namespace App\Http\Requests\Auth;

use App\Http\Resources\Templates\WithoutDataResource;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;

class ChangePasswordRequest extends FormRequest
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
            'current_password' => ['required', 'string', 'required_with:password'],
            'password' => ['required', 'string', 'min:4', 'confirmed'],
        ];
    }

    public function messages()
    {
        return [
            'current_password.required' => 'Kata sandi lama tidak diperbolehkan kosong.',
            'password.required' => 'Password tidak boleh kosong.',
            'password.min' => 'Password minimal terdiri dari 4 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak sesuai dengan password yang anda masukkan.',
        ];
    }

    public function failedValidation(Validator $validator)
    {
        $messages = implode(' ', $validator->errors()->all());
        $response = new WithoutDataResource(
            Response::HTTP_BAD_REQUEST,
            'FAILED_VALIDATION',
            'Reset Password Gagal',
            $messages
        );

        throw new HttpResponseException(response()->json($response, Response::HTTP_BAD_REQUEST));
    }
}
