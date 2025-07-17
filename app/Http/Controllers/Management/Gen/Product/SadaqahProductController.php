<?php

namespace App\Http\Controllers\Management\Gen\Product;

use App\Helpers\DocumentHelper;
use App\Helpers\QueryFilterSearch;
use App\Http\Controllers\Controller;
use App\Http\Requests\Management\Gen\Product\StoreSadaqahProduct;
use App\Http\Requests\Management\Gen\Product\UpdateSadaqahProduct;
use App\Http\Resources\Management\Gen\Product\SadaqahProductResource;
use App\Http\Resources\Templates\WithDataResource;
use App\Http\Resources\Templates\WithoutDataResource;
use App\Models\Animal;
use App\Models\SadaqahProduct;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class SadaqahProductController extends Controller
{
    public function index(Request $request)
    {
        try {
            if (!Gate::allows('masterdata.view')) {
                return response()->json(
                    new WithoutDataResource(
                        Response::HTTP_FORBIDDEN,
                        'NO_ACCESS',
                        'Tidak Memiliki Akses',
                        'Anda tidak memiliki akses untuk mengakses halaman ini.',
                    ),
                    Response::HTTP_FORBIDDEN
                );
            }

            $query = SadaqahProduct::query()->withTrashed();

            $filterRules = [
                'price_min' => function ($q, $val) {
                    $q->where('price', '>', $val);  // Filter harga minimum
                },
                'price_max' => function ($q, $val) {
                    $q->where('price', '<', $val);  // Filter harga maksimum
                },
            ];

            $filters = $request->except(['limit', 'search']);
            $query   = QueryFilterSearch::applyFilters($query, $filters, $filterRules);

            if ($request->has('search')) {
                $query = QueryFilterSearch::applySearch($query, $request->input('search'), [
                    'name',
                ]);
            }

            $result = QueryFilterSearch::applyPagination($query, $request);

            if ($result->isEmpty()) {
                return response()->json(
                    new WithoutDataResource(
                        Response::HTTP_OK,
                        'DATA_NOT_FOUND',
                        'Data Tidak Ditemukan',
                        'Tidak ada data yang sesuai dengan pencarian.'
                    ),
                    Response::HTTP_OK
                );
            }

            return response()->json(
                new WithDataResource(
                    Response::HTTP_OK,
                    'SUCCESS_GET_DATA',
                    'Berhasil Mengambil Data',
                    'Data produk sadaqah berhasil didapatkan.',
                    QueryFilterSearch::formatPaginationCollection($result, SadaqahProductResource::class)
                ),
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            Log::channel('gen_sadaqah_product')->error('| Index | - Error function index : ' . $e->getMessage() . ' - Line : ' . $e->getLine());
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

    public function store(StoreSadaqahProduct $request)
    {
        try {
            if (!Gate::allows('masterdata.create')) {
                return response()->json(
                    new WithoutDataResource(
                        Response::HTTP_FORBIDDEN,
                        'NO_ACCESS',
                        'Tidak Memiliki Akses',
                        'Anda tidak memiliki akses untuk mengakses halaman ini.',
                    ),
                    Response::HTTP_FORBIDDEN
                );
            }

            DB::beginTransaction();

            $duplicate = SadaqahProduct::where('name', $request->name)
                ->whereNull('deleted_at')
                ->exists();
            if ($duplicate) {
                return response()->json(
                    new WithoutDataResource(
                        Response::HTTP_CONFLICT,
                        'DUPLICATE_NAME',
                        'Duplikat Data',
                        "Nama produk sadaqah '{$request->name}' sudah digunakan oleh data lain yang aktif. Silakan gunakan nama lain."
                    ),
                    Response::HTTP_CONFLICT
                );
            }

            // 🔍 Cek stok hewan
            // $animalIds = $request->animal_id ?? [];
            // $animals = [];

            // if (!empty($animalIds)) {
            //     // 🔒 Lock semua hewan dalam array untuk mencegah race condition
            //     $animals = Animal::whereIn('id', $animalIds)
            //         ->with(['animal_categories', 'animal_breeds'])
            //         ->lockForUpdate()
            //         ->get()
            //         ->keyBy('id');

            //     // ✅ Cek semua stok harus ≥ 1
            //     foreach ($animalIds as $id) {
            //         $animal = $animals[$id] ?? null;
            //         if (!$animal) {
            //             DB::rollBack();
            //             return response()->json(
            //                 new WithoutDataResource(
            //                     Response::HTTP_BAD_REQUEST,
            //                     'DATA_NOT_FOUND',
            //                     'Hewan Tidak Ditemukan',
            //                     'Hewan yang dipilih tidak tersedia.'
            //                 ),
            //                 Response::HTTP_BAD_REQUEST
            //             );
            //         }

            //         if ($animal->stock < 1) {
            //             $categoryLabel = optional($animal->animal_categories)->label ?? '-';
            //             $breedLabel = optional($animal->animal_breeds)->label ?? '-';

            //             DB::rollBack();
            //             return response()->json(
            //                 new WithoutDataResource(
            //                     Response::HTTP_BAD_REQUEST,
            //                     'EMPTY_STOCK',
            //                     'Stok Hewan Tidak Tersedia',
            //                     "Stok hewan kategori '{$categoryLabel}' dan ras '{$breedLabel}' untuk di sadaqah kan sudah habis. Silakan pilih hewan lain yang masih tersedia."
            //                 ),
            //                 Response::HTTP_BAD_REQUEST
            //             );
            //         }
            //     }

            //     // 🟢 Jika semua stok valid, lakukan pengurangan
            //     foreach ($animals as $animal) {
            //         $animal->decrement('stock');
            //     }
            // }

            $photoDocumentIds = [];

            if ($request->hasFile('photo_product_id') && is_array($request->file('photo_product_id'))) {
                $photoDocumentIds = DocumentHelper::uploadDocuments($request->file('photo_product_id'));
            }

            SadaqahProduct::create([
                'animal_id'         => $request->animal_id,
                'name'              => $request->name,
                'description'       => $request->description,
                'price'             => $request->price,
                'photo_product_id'  => $photoDocumentIds ?: null,
            ]);

            DB::commit();
            return response()->json(
                new WithoutDataResource(
                    Response::HTTP_CREATED,
                    'SUCCESS_CREATE_DATA',
                    'Berhasil Menyimpan Data',
                    "Data produk sadaqah '{$request->name}' berhasil ditambahkan."
                ),
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('gen_sadaqah_product')->error('| Store | - Error function store : ' . $e->getMessage() . ' - Line : ' . $e->getLine());
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
            if (!Gate::allows('masterdata.view')) {
                return response()->json(
                    new WithoutDataResource(
                        Response::HTTP_FORBIDDEN,
                        'NO_ACCESS',
                        'Tidak Memiliki Akses',
                        'Anda tidak memiliki akses untuk mengakses halaman ini.',
                    ),
                    Response::HTTP_FORBIDDEN
                );
            }

            $sadaqahProduct = SadaqahProduct::withTrashed()->find($id);
            if (!$sadaqahProduct) {
                return response()->json(
                    new WithoutDataResource(
                        Response::HTTP_NOT_FOUND,
                        'DATA_NOT_FOUND',
                        'Data Tidak Ditemukan',
                        'Produk sadaqah dengan ID tersebut tidak ditemukan.',
                    ),
                    Response::HTTP_NOT_FOUND
                );
            }

            return response()->json(
                new WithDataResource(
                    Response::HTTP_OK,
                    'SUCCESS_GET_DATA',
                    'Berhasil Mengambil Data',
                    "Detail data produk sadaqah '{$sadaqahProduct->name}' berhasil didapatkan.",
                    new SadaqahProductResource($sadaqahProduct)
                ),
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            Log::channel('gen_sadaqah_product')->error('| Detail | - Error function show : ' . $e->getMessage() . ' - Line : ' . $e->getLine());
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

    public function update(UpdateSadaqahProduct $request, $id)
    {
        try {
            if (!Gate::allows('masterdata.edit')) {
                return response()->json(
                    new WithoutDataResource(
                        Response::HTTP_FORBIDDEN,
                        'NO_ACCESS',
                        'Tidak Memiliki Akses',
                        'Anda tidak memiliki akses untuk mengakses halaman ini.',
                    ),
                    Response::HTTP_FORBIDDEN
                );
            }

            $sadaqahProduct = SadaqahProduct::withTrashed()->find($id);
            if (!$sadaqahProduct) {
                return response()->json(
                    new WithoutDataResource(
                        Response::HTTP_NOT_FOUND,
                        'DATA_NOT_FOUND',
                        'Data Tidak Ditemukan',
                        'Produk sadaqah dengan ID tersebut tidak ditemukan.',
                    ),
                    Response::HTTP_NOT_FOUND
                );
            }

            $data = $request->validated();

            $duplicate = SadaqahProduct::where('name', $request->name)
                ->whereNull('deleted_at')
                ->where('id', '!=', $id)
                ->exists();
            if ($duplicate) {
                return response()->json(
                    new WithoutDataResource(
                        Response::HTTP_CONFLICT,
                        'DUPLICATE_NAME',
                        'Duplikat Data',
                        "Nama produk sadaqah '{$request->name}' sudah digunakan pada data yang sama."
                    ),
                    Response::HTTP_CONFLICT
                );
            }

            // 🔍 Cek stok hewan
            // $existingAnimalIds = $sadaqahProduct->animal_id ?? [];
            // $newAnimalIds = $data['animal_id'] ?? [];

            // // 🔄 Bandingkan animal_id lama vs baru
            // $toBeReleased = array_diff($existingAnimalIds, $newAnimalIds); // dikembalikan
            // $toBeTaken = array_diff($newAnimalIds, $existingAnimalIds);    // dipakai

            // // 🔒 Lock semua ID yang perlu update stok
            // $affectedIds = array_unique(array_merge($toBeReleased, $toBeTaken));
            // $animalMap = Animal::whereIn('id', $affectedIds)
            //     ->with(['animal_categories', 'animal_breeds'])
            //     ->lockForUpdate()
            //     ->get()
            //     ->keyBy('id');

            // // ✅ Validasi stok untuk yang mau dipakai
            // foreach ($toBeTaken as $id) {
            //     $animal = $animalMap[$id] ?? null;
            //     if (!$animal) {
            //         DB::rollBack();
            //         return response()->json(
            //             new WithoutDataResource(
            //                 Response::HTTP_BAD_REQUEST,
            //                 'DATA_NOT_FOUND',
            //                 'Hewan Tidak Ditemukan',
            //                 'Hewan sadaqah yang dipilih tidak tersedia.'
            //             ),
            //             Response::HTTP_BAD_REQUEST
            //         );
            //     }

            //     if ($animal->stock < 1) {
            //         $categoryLabel = optional($animal->animal_categories)->label ?? '-';
            //         $breedLabel = optional($animal->animal_breeds)->label ?? '-';

            //         DB::rollBack();
            //         return response()->json(
            //             new WithoutDataResource(
            //                 Response::HTTP_BAD_REQUEST,
            //                 'EMPTY_STOCK',
            //                 'Stok Hewan Tidak Tersedia',
            //                 "Stok hewan sadaqah untuk kategori '{$categoryLabel}' dan ras '{$breedLabel}' sudah habis. Silakan pilih hewan lain yang masih tersedia."
            //             ),
            //             Response::HTTP_BAD_REQUEST
            //         );
            //     }
            // }

            // // 🔄 Lakukan pengembalian stok & pengurangan stok
            // foreach ($toBeReleased as $id) {
            //     $animalMap[$id]->increment('stock');
            // }

            // foreach ($toBeTaken as $id) {
            //     $animalMap[$id]->decrement('stock');
            // }

            $existingDocumentIds = $sadaqahProduct->photo_product_id ?? [];
            $deleteIds = $data['delete_document_ids'] ?? [];
            $newUploads = $request->file('photo_product_id') ?? [];

            // ✅ Safety: jika delete kosong & dokumen baru full, asumsikan ingin overwrite semua
            if (empty($deleteIds) && count($newUploads) === 5 && !empty($existingDocumentIds)) {
                $deleteIds = $existingDocumentIds;
                $data['delete_document_ids'] = $deleteIds;
            }

            // ✅ Validasi jumlah total dokumen (existing - delete + new) ≤ 5
            $remainingDocs = array_values(array_diff($existingDocumentIds, $deleteIds));
            $totalAfter = count($remainingDocs) + count($newUploads);

            if ($totalAfter > 5) {
                return response()->json(
                    new WithoutDataResource(
                        Response::HTTP_BAD_REQUEST,
                        'TOO_MANY_DOCUMENTS',
                        'Terlalu Banyak Dokumen',
                        "Jumlah total dokumen setelah update melebihi batas maksimum (maksimal 5)."
                    ),
                    Response::HTTP_BAD_REQUEST
                );
            }

            DB::beginTransaction();

            // ✅ Hapus dokumen lama jika ada
            if (!empty($deleteIds)) {
                DocumentHelper::deleteDocuments($deleteIds);
                $existingDocumentIds = array_values(array_diff($existingDocumentIds, $deleteIds));
            }

            // ✅ Upload dokumen baru
            $newDocumentIds = [];
            if (!empty($newUploads)) {
                $newDocumentIds = DocumentHelper::uploadDocuments($newUploads);
            }

            $finalDocumentIds = array_merge($existingDocumentIds, $newDocumentIds);

            $sadaqahProduct->update([
                'animal_id'         => $request->animal_id,
                'name'              => $request->name,
                'description'       => $request->description,
                'price'             => $request->price,
                'photo_product_id'  => $finalDocumentIds ?: null,
            ]);

            DB::commit();
            return response()->json(
                new WithoutDataResource(
                    Response::HTTP_OK,
                    'SUCCESS_UPDATE_DATA',
                    'Berhasil Memperbarui Data',
                    "Data produk sadaqah '{$sadaqahProduct->name}' berhasil diperbarui."
                ),
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('gen_sadaqah_product')->error('| Update | - Error function update : ' . $e->getMessage() . ' - Line : ' . $e->getLine());
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
            if (!Gate::allows('masterdata.delete')) {
                return response()->json(
                    new WithoutDataResource(
                        Response::HTTP_FORBIDDEN,
                        'NO_ACCESS',
                        'Tidak Memiliki Akses',
                        'Anda tidak memiliki akses untuk mengakses halaman ini.',
                    ),
                    Response::HTTP_FORBIDDEN
                );
            }

            DB::beginTransaction();

            $sadaqahProduct = SadaqahProduct::find($id);
            if (!$sadaqahProduct) {
                return response()->json(
                    new WithoutDataResource(
                        Response::HTTP_NOT_FOUND,
                        'DATA_NOT_FOUND',
                        'Data Tidak Ditemukan',
                        'Produk sadaqah dengan ID tersebut tidak ditemukan.',
                    ),
                    Response::HTTP_NOT_FOUND
                );
            }

            $sadaqahProduct->delete();

            DB::commit();
            return response()->json(
                new WithoutDataResource(
                    Response::HTTP_OK,
                    'SUCCESS_DELETE_DATA',
                    'Berhasil Menghapus Data',
                    "Data produk sadaqah '{$sadaqahProduct->name}' berhasil dihapus."
                ),
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('gen_sadaqah_product')->error('| Destroy | - Error function destroy : ' . $e->getMessage() . ' - Line : ' . $e->getLine());
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
            if (!Gate::allows('masterdata.restore')) {
                return response()->json(
                    new WithoutDataResource(
                        Response::HTTP_FORBIDDEN,
                        'NO_ACCESS',
                        'Tidak Memiliki Akses',
                        'Anda tidak memiliki akses untuk mengakses halaman ini.',
                    ),
                    Response::HTTP_FORBIDDEN
                );
            }

            DB::beginTransaction();

            $sadaqahProduct = SadaqahProduct::onlyTrashed()->find($id);
            if (!$sadaqahProduct) {
                return response()->json(
                    new WithoutDataResource(
                        Response::HTTP_NOT_FOUND,
                        'DATA_NOT_FOUND',
                        'Data Tidak Ditemukan',
                        'Produk sadaqah dengan ID tersebut tidak ditemukan atau belum dihapus.',
                    ),
                    Response::HTTP_NOT_FOUND
                );
            }

            // Validasi unik
            $duplicate = SadaqahProduct::where('name', $sadaqahProduct->name)->whereNull('deleted_at')->exists();
            if ($duplicate) {
                return response()->json(
                    new WithoutDataResource(
                        Response::HTTP_CONFLICT,
                        'DUPLICATE_NAME',
                        'Duplikat Data',
                        "Nama produk sadaqah '{$sadaqahProduct->name}' sudah digunakan oleh entri aktif lain. Silakan ubah nama terlebih dahulu sebelum merestore."
                    ),
                    Response::HTTP_CONFLICT
                );
            }

            $sadaqahProduct->restore();

            DB::commit();
            return response()->json(
                new WithoutDataResource(
                    Response::HTTP_OK,
                    'SUCCESS_RESTORE_DATA',
                    'Berhasil Mengembalikan Data',
                    "Data produk sadaqah '{$sadaqahProduct->name}' berhasil dikembalikan."
                ),
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('gen_sadaqah_product')->error('| Restore | - Error function restore : ' . $e->getMessage() . ' - Line : ' . $e->getLine());
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
