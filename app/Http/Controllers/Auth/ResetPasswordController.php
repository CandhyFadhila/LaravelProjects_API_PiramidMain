<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Resources\Templates\WithoutDataResource;
use App\Models\Otp;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class ResetPasswordController extends Controller
{
    public function resetPassword(ResetPasswordRequest $request)
    {
        $credentials = $request->validated();

        $user = User::where('email', $credentials['email'])->first();
        if (!$user) {
            return response()->json(
                new WithoutDataResource(
                    Response::HTTP_NOT_FOUND,
                    'DATA_NOT_FOUND',
                    'Akun Tidak Ditemukan',
                    "Akun dengan email '{$credentials['email']}' tidak ditemukan, pastikan anda sudah melakukan registrasi akun kedalam sistem kami dengan email tersebut.",
                ),
                Response::HTTP_NOT_FOUND
            );
        }

        $otpRecord = Otp::where('user_id', $user->id)->latest()->first();
        if (!$otpRecord) {
            return response()->json(
                new WithoutDataResource(
                    Response::HTTP_BAD_REQUEST,
                    'OTP_NOT_FOUND',
                    'OTP Tidak Ditemukan',
                    'Kode OTP tidak ditemukan. Silakan kirim ulang OTP.',
                ),
                Response::HTTP_BAD_REQUEST
            );
        }

        if (!Hash::check($credentials['otp'], $otpRecord->otp)) {
            return response()->json(
                new WithoutDataResource(
                    Response::HTTP_UNAUTHORIZED,
                    'INVALID_OTP',
                    'OTP Tidak Valid',
                    'Kode OTP yang anda masukkan salah. Silakan coba lagi atau kirim ulang OTP.',
                ),
                Response::HTTP_UNAUTHORIZED
            );
        }

        // Ubah password user
        $user->update([
            'password' => Hash::make($credentials['password']),
            'last_change_password' => Carbon::now()
        ]);

        Log::channel('auth_reset')->info('| Reset Password | - Reset Password success for email: ' . $user->email . ', at ' . Carbon::now());

        // Hapus OTP setelah digunakan
        $otpRecord->delete();

        return response()->json(
            new WithoutDataResource(
                Response::HTTP_OK,
                'PASSWORD_RESET_SUCCESS',
                'Password Berhasil Diubah',
                'Password anda berhasil diubah. Silahkan login menggunakan password baru anda.',
            ),
            Response::HTTP_OK
        );
    }
}
