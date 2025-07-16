<?php

namespace App\Http\Requests\Auth;

use App\Http\Resources\Templates\WithoutDataResource;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;

class SignupRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'min:4', 'confirmed'],
            'phone_number' => ['nullable', 'string', 'min:6'],
            'wa_number' => ['nullable', 'string', 'min:6'],
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Nama lengkap tidak boleh kosong.',
            'email.required' => 'Silahkan masukkan email anda terlebih dahulu.',
            'email.email' => 'Format email yang anda masukkan tidak valid.',
            'email.unique' => 'Email yang anda masukkan sudah terdaftar.',
            'password.required' => 'Password tidak boleh kosong.',
            'password.min' => 'Password minimal terdiri dari 4 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak sesuai dengan password yang anda masukkan.',
            'phone_number.min' => 'Nomor telepon harus terdiri dari minimal 6 digit.',
            'wa_number.min' => 'Nomor WhatsApp harus terdiri dari minimal 6 digit.',
        ];
    }

    public function failedValidation(Validator $validator)
    {
        $messages = implode(' ', $validator->errors()->all());
        $response = new WithoutDataResource(
            Response::HTTP_BAD_REQUEST,
            'FAILED_VALIDATION',
            'Registrasi Gagal',
            $messages
        );

        throw new HttpResponseException(response()->json($response, Response::HTTP_BAD_REQUEST));
    }
}
