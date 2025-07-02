<?php

namespace App\Http\Requests\Auth;

use App\Http\Resources\Templates\WithoutDataResource;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;

class ResetPasswordRequest extends FormRequest
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
            'email' => ['required', 'email', 'exists:users,email'],
            'otp' => ['required', 'numeric', 'digits:6'],
            'password' => ['required', 'min:8', 'confirmed'],
            'password_confirmation' => ['required', 'min:8'],
        ];
    }

    public function messages()
    {
        return [
            'email.required' => 'Silahkan masukkan email anda terlebih dahulu.',
            'email.email' => 'Silahkan masukkan email yang valid, dapat berupa @gmail.com atau yang lain.',
            'email.exists' => 'Email anda tidak ditemukan atau tidak terdaftar. Pastikan anda sudah melakukan registrasi akun dengan email tersebut.',
            'otp.required' => 'Silahkan masukkan kode OTP yang telah dikirim di email anda.',
            'otp.numeric' => 'Kode OTP yang valid harus berupa angka.',
            'otp.digits' => 'Kode OTP harus terdiri dari 6 digit.',
            'password.required' => 'Silahkan masukkan password baru anda terlebih dahulu.',
            'password.min' => 'Password minimal terdiri dari 8 karakter.',
            'password.confirmed' => 'Konfirmasi password anda tidak cocok. Pastikan password yang anda masukkan sudah sesuai.',
            'password_confirmation.required' => 'Silahkan masukkan konfirmasi password anda terlebih dahulu.',
            'password_confirmation.min' => 'Konfirmasi password minimal terdiri dari 8 karakter.',
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
