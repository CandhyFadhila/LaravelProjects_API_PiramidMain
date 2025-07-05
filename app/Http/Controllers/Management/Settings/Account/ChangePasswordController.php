<?php

namespace App\Http\Controllers\Management\Settings\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Resources\Templates\WithoutDataResource;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class ChangePasswordController extends Controller
{
    public function updatePasswordAdmin(ChangePasswordRequest $request)
    {
        return $this->handlePasswordUpdate($request, 'Admin');
    }

    public function updatePasswordUsers(ChangePasswordRequest $request)
    {
        return $this->handlePasswordUpdate($request, 'User');
    }

    protected function handlePasswordUpdate(ChangePasswordRequest $request, string $type)
    {
        DB::beginTransaction();

        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            $data = $request->validated();

            if (isset($data['password'])) {
                $currentPassword = $data['current_password'];

                // Bandingkan password lama yang dimasukkan dengan password hash di DB
                if (!Hash::check($currentPassword, $user->password)) {
                    return response()->json(
                        new WithoutDataResource(
                            Response::HTTP_BAD_REQUEST,
                            'INVALID_CURRENT_PASSWORD',
                            'Reset Password Gagal',
                            'Kata sandi lama yang Anda masukkan tidak sesuai.'
                        ),
                        Response::HTTP_BAD_REQUEST
                    );
                }

                // Hash password baru sebelum disimpan
                $data['password'] = Hash::make($data['password']);
            }

            $user->fill($data)->save();

            DB::commit();

            return response()->json(
                new WithoutDataResource(
                    Response::HTTP_OK,
                    'SUCCESS_UPDATE_PASSWORD',
                    'Berhasil Mengubah Password',
                    'Kata sandi Anda berhasil diperbarui.'
                ),
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('auth_password')->error("| Update Password {$type} | - Error function {$type} : " . $e->getMessage());
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
}
