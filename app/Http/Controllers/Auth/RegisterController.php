<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SignupRequest;
use App\Http\Requests\Auth\VerifyOTPRequest;
use App\Http\Resources\Templates\WithoutDataResource;
use App\Mail\Auth\SendingOTPSignupMail;
use App\Models\Otp;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class RegisterController extends Controller
{
    public function signUp(SignupRequest $request)
    {
        try {
            DB::beginTransaction();

            $validatedData = $request->validated();

            $password = Hash::make($validatedData['password']);

            $user = User::create([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'password' => $password,
                'phone_number' => $validatedData['phone_number'] ?? null,
                'wa_number' => $validatedData['wa_number'] ?? null,
                'account_status' => 1,  // Akun defaultnya inactive
                'register_at' => now(),  // Waktu registrasi sekarang
            ]);

            $user->assignRole('Marketplace');

            Log::channel('auth_signup')->info('| Create User | - Success create user for email: ' . $user->email . ', at ' . Carbon::now());

            // Generate OTP
            $otp = rand(100000, 999999);
            $expiresAt = now()->addMinutes(30);

            // Simpan OTP ke dalam tabel
            Otp::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'otp' => Hash::make($otp),
                    'expired_date' => $expiresAt,
                ]
            );

            Mail::to($user->email)->send(new SendingOTPSignupMail($user->name, $otp));
            Log::channel('auth_signup')->info('| Send OTP | - Send OTP success for email: ' . $user->email . ', at ' . Carbon::now());

            DB::commit();
            return response()->json(
                new WithoutDataResource(
                    Response::HTTP_CREATED,
                    'SUCCESS_REGISTER',
                    'Pendaftaran Berhasil',
                    'Akun Anda berhasil didaftarkan. Silakan cek email Anda untuk aktivasi akun dengan kode OTP.'
                ),
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('auth_signup')->error('| Signup | - Error function signUp : ' . $e->getMessage());
            return response()->json(
                new WithoutDataResource(
                    Response::HTTP_INTERNAL_SERVER_ERROR,
                    'ERROR_GET_DATA',
                    'Gagal Mengambil Data',
                    'Terjadi kesalahan pada sistem, silahkan coba lagi nanti atau hubungi admin.',
                ),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function signUpVerifyOTP(VerifyOTPRequest $request)
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
        if (!$otpRecord || now()->greaterThan($otpRecord->expired_date)) {
            // Generate OTP baru
            $otp = rand(100000, 999999);
            $expiresAt = now()->addMinutes(30);

            Otp::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'otp' => Hash::make($otp),
                    'expired_date' => $expiresAt,
                ]
            );

            // Kirimkan OTP ke email pengguna
            Mail::to($user->email)->send(new SendingOTPSignupMail($user->name, $otp));

            Log::channel('auth_signup')->info('| Resend OTP | - OTP resent successfully for email: ' . $user->email . ', at ' . Carbon::now());

            return response()->json(
                new WithoutDataResource(
                    Response::HTTP_BAD_REQUEST,
                    'OTP_EXPIRED',
                    'OTP Kadaluarsa',
                    'Kode OTP yang anda masukkan sudah kadaluarsa. Kode OTP baru telah dikirimkan ke email anda.',
                ),
                Response::HTTP_BAD_REQUEST
            );
        }

        if (!Hash::check($credentials['otp'], $otpRecord->otp)) {
            Log::channel('auth_signup')->info('| Verify OTP | - Incorrect OTP for email: ' . $user->email);
            return response()->json(
                new WithoutDataResource(
                    Response::HTTP_UNAUTHORIZED,
                    'INVALID_OTP',
                    'OTP Tidak Valid',
                    'Kode OTP tidak sesuai. Silakan coba lagi dan pastikan kode OTP yang anda masukkan sesuai dengan yang kami kirim melalui email.',
                ),
                Response::HTTP_UNAUTHORIZED
            );
        }

        $user->update(['account_status' => 2]);

        Log::channel('auth_signup')->info('| Verify OTP | - Verify OTP success for email: ' . $user->email . ', at ' . Carbon::now());

        return response()->json(
            new WithoutDataResource(
                Response::HTTP_OK,
                'OTP_VERIFIED',
                'OTP Berhasil Diverifikasi',
                'Kode OTP anda berhasil diverifikasi. Silahkan lakukan login untuk melanjutkan.',
            ),
            Response::HTTP_OK
        );
    }
}
