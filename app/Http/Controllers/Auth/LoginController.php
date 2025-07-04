<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\DateHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginDashboardRequest;
use App\Http\Resources\Templates\WithDataResource;
use App\Http\Resources\Templates\WithoutDataResource;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LoginController extends Controller
{
    public function login(LoginDashboardRequest $request)
    {
        $credential = $request->validated();

        // check auth
        $loginSuccess = Auth::attempt([
            'email' => $credential['email'],
            'password' => $credential['password']
        ]);
        if (!$loginSuccess) {
            Log::channel('auth_login')->info("| Login | - Invalid credentials for email: {$credential['email']}, at " . Carbon::now());
            return response()->json(
                new WithoutDataResource(
                    Response::HTTP_UNAUTHORIZED,
                    'INVALID_CREDENTIALS',
                    'Login Gagal',
                    'Password atau email yang anda masukkan tidak valid, silahkan periksa kembali dan pastikan akun anda sudah terdaftar.',
                ),
                Response::HTTP_UNAUTHORIZED
            );
        }

        $user = Auth::user();

        // cek user aktif
        if (in_array($user->account_status, [1, 3])) {
            Auth::logout();
            Log::channel('auth_login')->info("| Login | - Login failed for email: {$credential['email']}, User is not active since {$user->deactivate_at}");

            if ($user->account_status == 1) {
                return response()->json(
                    new WithoutDataResource(
                        Response::HTTP_UNAUTHORIZED,
                        'ACCOUNT_NOT_ACTIVATED',
                        'Akun Belum Aktif',
                        "Kami mendeteksi bahwa akun anda belum diaktifkan sejak " . DateHelper::formatTanggalIndonesia($user->created_at, 1) .
                            ", silahkan hubungi admin untuk melakukan aktivasi.",
                    ),
                    Response::HTTP_UNAUTHORIZED
                );
            }

            return response()->json(
                new WithoutDataResource(
                    Response::HTTP_UNAUTHORIZED,
                    'ACCOUNT_DEACTIVATED',
                    'Akun Nonaktif',
                    "Kami mendeteksi bahwa akun anda telah dinonaktifkan sejak " . DateHelper::formatTanggalIndonesia($user->deactivate_at, 1) .
                        ", silahkan hubungi admin untuk melakukan aktivasi kembali.",
                ),
                Response::HTTP_UNAUTHORIZED
            );
        }

        $user->update(['last_login' => now()]);

        // login success
        Log::channel('auth_login')->info("| Login | - Login success for email: {$credential['email']}, at {$user->last_login}");

        $token = $user->createToken('create_token_' . Str::uuid())->plainTextToken;
        $filteredUser = $user->makeHidden(['password', 'remember_token', 'roles']);
        $roles = $user->roles->first();
        $filteredRoles = $roles ? $roles->makeHidden(['permissions']) : null;

        return response()->json(
            new WithDataResource(
                Response::HTTP_OK,
                'LOGIN_SUCCESS',
                'Login Berhasil',
                'Selamat datang ' . $user->name . '!, anda berhasil login kedalam sistem kami.',
                [
                    'token' => $token,
                    'user' => $filteredUser,
                    'role' => $filteredRoles,
                    'permission' => $filteredRoles ? $roles->permissions->pluck('id') : []
                ],
            ),
            Response::HTTP_OK
        );
    }

    public function getUserInfo()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(
                new WithoutDataResource(
                    Response::HTTP_UNAUTHORIZED,
                    'ACCOUNT_NOT_FOUND',
                    'Akses Ditolak',
                    'Maaf, akun pengguna terkait tidak ditemukan.',
                ),
                Response::HTTP_UNAUTHORIZED
            );
        }

        // Sembunyikan atribut sensitif
        $filteredUser = $user->makeHidden('roles');
        $roles = $user->roles->first();
        $filteredRoles = $roles ? $roles->makeHidden(['permissions']) : null;

        return response()->json(
            new WithDataResource(
                Response::HTTP_OK,
                'SUCCESS_GET_USER_INFO',
                'Berhasil Medapatkan Data',
                'Data pengguna ' . $user->name .' , berhasil didapatkan.',
                [
                    'user' => $filteredUser,
                    'role' => $filteredRoles,
                    'permissions' => $filteredRoles ? $roles->permissions->pluck('id') : []
                ],
            ),
            Response::HTTP_OK
        );
    }

    public function logout()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(
                new WithoutDataResource(
                    Response::HTTP_UNAUTHORIZED,
                    'NO_ACTIVE_SESSION',
                    'Logout Gagal',
                    'Anda tidak memiliki sesi login yang aktif.',
                ),
                Response::HTTP_UNAUTHORIZED
            );
        }

        // Hapus token akses saat ini jika ada
        if (method_exists($user->currentAccessToken(), 'delete')) {
            $user->currentAccessToken()->delete();
        }

        Auth::guard('web')->logout();

        $now = Carbon::now();

        Log::channel('auth_login')->info("| Logout | - Logout success for email: " . $user->email . ", at " . $now);

        return response()->json(
            new WithoutDataResource(
                Response::HTTP_OK,
                'LOGOUT_SUCCESS',
                'Logout Berhasil',
                'Anda berhasil melakukan logout.',
            ),
            Response::HTTP_OK
        );
    }
}
