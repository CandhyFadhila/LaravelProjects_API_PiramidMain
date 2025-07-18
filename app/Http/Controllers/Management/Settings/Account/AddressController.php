<?php

namespace App\Http\Controllers\Management\Settings\Account;

use App\Helpers\QueryFilterSearch;
use App\Http\Controllers\Controller;
use App\Http\Requests\Management\Settings\Account\StoreAddress;
use App\Http\Requests\Management\Settings\Account\UpdateAddress;
use App\Http\Requests\Management\Settings\Account\UpdatePrimaryAddress;
use App\Http\Resources\Management\Settings\Account\AddressResource;
use App\Http\Resources\Templates\WithDataResource;
use App\Http\Resources\Templates\WithoutDataResource;
use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class AddressController extends Controller
{
    public function index()
    {
        try {
            $user = Auth::user();

            $addresses = Address::with(['users'])
                ->withTrashed()
                ->where('user_id', $user->id)
                ->orderByDesc('is_primary')
                ->orderByDesc('updated_at')
                ->get();
            if ($addresses->isEmpty()) {
                return response()->json(
                    new WithoutDataResource(
                        Response::HTTP_OK,
                        'DATA_NOT_FOUND',
                        'Data Tidak Ditemukan',
                        'Anda belum memiliki alamat yang tersimpan.'
                    ),
                    Response::HTTP_OK
                );
            }

            return response()->json(
                new WithDataResource(
                    Response::HTTP_OK,
                    'SUCCESS_GET_DATA',
                    'Berhasil Mengambil Data',
                    'Data alamat pengguna berhasil didapatkan.',
                    AddressResource::collection($addresses)
                ),
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            Log::channel('setting_address')->error('| Index | - Error function index : ' . $e->getMessage() . ' - Line : ' . $e->getLine());
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

    public function store(StoreAddress $request)
    {
        try {
            DB::beginTransaction();

            $user = Auth::user();

            // ✅ Cek jumlah address aktif milik user, maksimal 3
            $totalAddress = Address::where('user_id', $user->id)->withTrashed()->count();
            if ($totalAddress >= 3) {
                return response()->json(
                    new WithoutDataResource(
                        Response::HTTP_BAD_REQUEST,
                        'ADDRESS_LIMIT_REACHED',
                        'Batas Maksimal Alamat Tercapai',
                        "Anda sudah memiliki 3 alamat. Tidak dapat menambahkan lebih banyak alamat baru."
                    ),
                    Response::HTTP_BAD_REQUEST
                );
            }

            // Jika alamat yang ditambahkan adalah primary, unset yang lain dulu
            if ($request->boolean('is_primary')) {
                Address::where('user_id', $user->id)
                    ->where('is_primary', true)
                    ->update(['is_primary' => false]);
            }

            $address = Address::create([
                'user_id'     => $user->id,
                'address'     => $request->address,
                'city'        => $request->city,
                'province'    => $request->province,
                'postal_code' => $request->postal_code,
                'country'     => $request->country,
                'latitude'    => $request->latitude,
                'longitude'   => $request->longitude,
                'is_primary'  => $request->boolean('is_primary'),
            ]);

            // ✅ Jika alamat baru adalah primary
            if ($address->is_primary) {
                $user->update([
                    'primary_address' => $address->id,
                ]);
            }

            DB::commit();

            return response()->json(
                new WithoutDataResource(
                    Response::HTTP_CREATED,
                    'SUCCESS_CREATE_DATA',
                    'Berhasil Menyimpan Data',
                    "Data alamat baru berhasil ditambahkan."
                ),
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('setting_address')->error('| Store | - Error function store : ' . $e->getMessage() . ' - Line : ' . $e->getLine());
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

    public function show($id)
    {
        try {
            $address = Address::with(['users'])->withTrashed()->find($id);
            if (!$address) {
                return response()->json(
                    new WithoutDataResource(
                        Response::HTTP_NOT_FOUND,
                        'DATA_NOT_FOUND',
                        'Data Tidak Ditemukan',
                        'Alamat dengan ID tersebut tidak ditemukan.',
                    ),
                    Response::HTTP_NOT_FOUND
                );
            }

            return response()->json(
                new WithDataResource(
                    Response::HTTP_OK,
                    'SUCCESS_GET_DATA',
                    'Berhasil Mengambil Data',
                    "Detail data alamat berhasil didapatkan.",
                    new AddressResource($address)
                ),
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            Log::channel('setting_address')->error('| Detail | - Error function show : ' . $e->getMessage() . ' - Line : ' . $e->getLine());
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

    public function update(UpdateAddress $request, $id)
    {
        try {
            DB::beginTransaction();

            $address = Address::withTrashed()->find($id);
            if (!$address) {
                return response()->json(
                    new WithoutDataResource(
                        Response::HTTP_NOT_FOUND,
                        'DATA_NOT_FOUND',
                        'Data Tidak Ditemukan',
                        'Alamat dengan ID tersebut tidak ditemukan.',
                    ),
                    Response::HTTP_NOT_FOUND
                );
            }

            $data = $request->validated();

            // ✅ Jika ingin dijadikan primary, ubah semua address user lain jadi false
            if ($data['is_primary']) {
                Address::where('user_id', $address->user_id)
                    ->where('id', '!=', $address->id)
                    ->update(['is_primary' => false]);
            }

            // ✅ Update alamat
            $address->update([
                'address'     => $data['address'],
                'city'        => $data['city'],
                'province'    => $data['province'],
                'postal_code' => $data['postal_code'],
                'country'     => $data['country'],
                'latitude'    => $data['latitude'],
                'longitude'   => $data['longitude'],
                'is_primary'  => $data['is_primary'],
            ]);

            // ✅ Jika diset sebagai primary
            if ($data['is_primary']) {
                $address->users->update([
                    'primary_address' => $address->id,
                ]);
            }

            DB::commit();
            return response()->json(
                new WithoutDataResource(
                    Response::HTTP_OK,
                    'SUCCESS_UPDATE_DATA',
                    'Berhasil Memperbarui Data',
                    "Alamat berhasil diperbarui."
                ),
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('setting_address')->error('| Update | - Error function update : ' . $e->getMessage() . ' - Line : ' . $e->getLine());
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

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $address = Address::find($id);
            if (!$address) {
                return response()->json(
                    new WithoutDataResource(
                        Response::HTTP_NOT_FOUND,
                        'DATA_NOT_FOUND',
                        'Data Tidak Ditemukan',
                        'Alamat dengan ID tersebut tidak ditemukan.',
                    ),
                    Response::HTTP_NOT_FOUND
                );
            }

            $user = $address->users;

            if ($address->is_primary) {
                // ✅ STEP 1: Set current address jadi bukan primary
                $address->update(['is_primary' => false]);

                // ✅ STEP 2: Cari alamat lain user yang terbaru untuk jadi primary
                $newPrimaryAddress = Address::where('user_id', $user->id)
                    ->where('id', '!=', $address->id)
                    ->orderByDesc('updated_at')
                    ->first();

                if ($newPrimaryAddress) {
                    $newPrimaryAddress->update(['is_primary' => true]);

                    // ✅ STEP 3: Update kolom primary_address di users
                    $user->update([
                        'primary_address' => $newPrimaryAddress->id
                    ]);
                } else {
                    // Jika tidak ada address lain, kosongkan primary_address di users
                    $user->update([
                        'primary_address' => null
                    ]);
                }
            }

            $address->delete();

            DB::commit();
            return response()->json(
                new WithoutDataResource(
                    Response::HTTP_OK,
                    'SUCCESS_DELETE_DATA',
                    'Berhasil Menghapus Data',
                    "Data alamat berhasil dihapus."
                ),
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('setting_address')->error('| Destroy | - Error function destroy : ' . $e->getMessage() . ' - Line : ' . $e->getLine());
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

    public function restore($id)
    {
        try {
            DB::beginTransaction();

            $address = Address::onlyTrashed()->find($id);
            if (!$address) {
                return response()->json(
                    new WithoutDataResource(
                        Response::HTTP_NOT_FOUND,
                        'DATA_NOT_FOUND',
                        'Data Tidak Ditemukan',
                        'Alamat dengan ID tersebut tidak ditemukan atau belum dihapus.',
                    ),
                    Response::HTTP_NOT_FOUND
                );
            }

            $address->restore();

            DB::commit();
            return response()->json(
                new WithoutDataResource(
                    Response::HTTP_OK,
                    'SUCCESS_RESTORE_DATA',
                    'Berhasil Mengembalikan Data',
                    "Data alamat berhasil dikembalikan."
                ),
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('setting_address')->error('| Restore | - Error function restore : ' . $e->getMessage() . ' - Line : ' . $e->getLine());
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

    public function updatePrimaryAddress(UpdatePrimaryAddress $request)
    {
        try {
            DB::beginTransaction();

            $addressId = $request->validated();
            $user = Auth::user();

            // Validasi bahwa addressId milik user yang sedang login
            $newPrimary = Address::where('id', $addressId['primary_address'])
                ->where('user_id', $user->id)
                ->first();
            if (!$newPrimary) {
                return response()->json(
                    new WithoutDataResource(
                        Response::HTTP_NOT_FOUND,
                        'ADDRESS_NOT_FOUND',
                        'Alamat Tidak Ditemukan',
                        'Alamat yang ingin dijadikan primary tidak ditemukan atau bukan milik Anda.'
                    ),
                    Response::HTTP_NOT_FOUND
                );
            }

            // Jika alamat yang akan di-set primary bukan yang sudah primary
            if (!$newPrimary->is_primary) {
                // Unset yang lainnya
                Address::where('user_id', $user->id)
                    ->where('is_primary', true)
                    ->update(['is_primary' => false]);

                // Set alamat baru sebagai primary
                $newPrimary->update(['is_primary' => true]);
            }

            // Update kolom primary_address di tabel users (meskipun sama, tetap di-set agar sinkron)
            $user->update([
                'primary_address' => $addressId['primary_address']
            ]);

            DB::commit();
            return response()->json(
                new WithoutDataResource(
                    Response::HTTP_OK,
                    'SUCCESS_UPDATE_DATA',
                    'Berhasil Memperbarui Data',
                    "Alamat utama berhasil diperbarui."
                ),
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('setting_address')->error('| updatePrimaryAddress | - Error function update : ' . $e->getMessage() . ' - Line : ' . $e->getLine());
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
