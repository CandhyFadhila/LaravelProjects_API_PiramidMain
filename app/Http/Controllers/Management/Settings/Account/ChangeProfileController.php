<?php

namespace App\Http\Controllers\Management\Settings\Account;

use App\Helpers\DocumentHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangeProfileRequest;
use App\Http\Resources\Templates\WithoutDataResource;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ChangeProfileController extends Controller
{
    public function updateProfileAdmin(ChangeProfileRequest $request)
    {
        return $this->handlePhotoProfile($request, 'Admin');
    }

    public function updateProfileUsers(ChangeProfileRequest $request)
    {
        return $this->handlePhotoProfile($request, 'User');
    }

    protected function handlePhotoProfile(ChangeProfileRequest $request, string $type)
    {
        DB::beginTransaction();

        try {
            $user = Auth::user();
            $existingDocumentId = $user->photo_profile_id ?? [];
            $newUploads = $request->file('photo_profile_id') ?? [];

            // Jika ada foto profil baru yang di-upload
            if (count($newUploads) > 0) {
                // ✅ Jika sudah ada foto profil lama, hapus dokumen lama
                if (!empty($existingDocumentId)) {
                    DocumentHelper::deleteDocuments($existingDocumentId);
                    $user->update(['photo_profile_id' => null]);
                }

                // ✅ Upload foto profil baru
                $newPhotoPath = DocumentHelper::uploadDocuments($newUploads);
                $user->update([
                    'photo_profile_id' => $newPhotoPath,
                ]);
            }

            DB::commit();

            return response()->json(
                new WithoutDataResource(
                    Response::HTTP_OK,
                    'SUCCESS_UPDATE_PROFILE',
                    'Berhasil Mengubah Profil',
                    'Foto profil Anda berhasil diperbarui.'
                ),
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('auth_profile')->error("| Update Profile {$type} | - Error function {$type} : " . $e->getMessage());
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
