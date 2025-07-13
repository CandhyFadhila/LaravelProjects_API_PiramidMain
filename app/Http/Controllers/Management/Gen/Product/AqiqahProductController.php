<?php

namespace App\Http\Controllers\Management\Gen\Product;

use App\Helpers\DocumentHelper;
use App\Helpers\QueryFilterSearch;
use App\Http\Controllers\Controller;
use App\Http\Requests\Management\Gen\Product\StoreAqiqahProduct;
use App\Http\Requests\Management\Gen\Product\UpdateAqiqahProduct;
use App\Http\Resources\Management\Gen\Product\AqiqahProductResource;
use App\Http\Resources\Templates\WithDataResource;
use App\Http\Resources\Templates\WithoutDataResource;
use App\Models\Animal;
use App\Models\AqiqahProduct;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class AqiqahProductController extends Controller
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

            $query = AqiqahProduct::query()
                ->withTrashed()
                ->with([
                    'animals.animal_categories',
                    'animals.animal_breeds',
                ]);

            // filter
            $filterRules = [
                'animal_category_id' => function ($q, $val) {
                    $q->whereHas('animals.animal_categories', function ($q2) use ($val) {
                        $q2->whereIn('id', (array) $val);
                    });
                },
                'animal_breed_id' => function ($q, $val) {
                    $q->whereHas('animals.animal_breeds', function ($q2) use ($val) {
                        $q2->whereIn('id', (array) $val);
                    });
                },
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
                        'Tidak ada data yang sesuai dengan filter atau pencarian.'
                    ),
                    Response::HTTP_OK
                );
            }

            return response()->json(
                new WithDataResource(
                    Response::HTTP_OK,
                    'SUCCESS_GET_DATA',
                    'Berhasil Mengambil Data',
                    'Data produk aqiqah berhasil didapatkan.',
                    QueryFilterSearch::formatPaginationCollection($result, AqiqahProductResource::class)
                ),
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            Log::channel('gen_aqiqah_product')->error('| Index | - Error function index : ' . $e->getMessage());
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

    public function store(StoreAqiqahProduct $request)
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

            // 🔍 Cek stok hewan dulu, Kunci baris hewan untuk mencegah race condition
            $animal = Animal::where('id', $request->animal_id)
                ->lockForUpdate()
                ->with(['animal_categories', 'animal_breeds'])
                ->first();

            if (!$animal) {
                DB::rollBack();
                return response()->json(
                    new WithoutDataResource(
                        Response::HTTP_NOT_FOUND,
                        'DATA_NOT_FOUND',
                        'Hewan Tidak Ditemukan',
                        'Hewan aqiqah yang dipilih tidak tersedia.'
                    ),
                    Response::HTTP_NOT_FOUND
                );
            }

            if ($animal->stock < 1) {
                $categoryLabel = optional($animal->animal_categories)->label ?? '-';
                $breedLabel = optional($animal->animal_breeds)->label ?? '-';

                return response()->json(
                    new WithoutDataResource(
                        Response::HTTP_BAD_REQUEST,
                        'EMPTY_STOCK',
                        'Stok Hewan Tidak Tersedia',
                        "Stok hewan aqiqah untuk kategori '{$categoryLabel}' dan ras '{$breedLabel}' sudah habis. Silakan pilih hewan lain yang masih tersedia."
                    ),
                    Response::HTTP_BAD_REQUEST
                );
            }

            $photoDocumentIds = [];

            if ($request->hasFile('photo_product_id') && is_array($request->file('photo_product_id'))) {
                $photoDocumentIds = DocumentHelper::uploadDocuments($request->file('photo_product_id'));
            }

            AqiqahProduct::create([
                'animal_id'         => $request->animal_id,
                'name'              => $request->name,
                'description'       => $request->description,
                'price'             => $request->price,
                'portion_count'     => $request->portion_count,
                'photo_product_id'  => $photoDocumentIds ?: null,
            ]);

            // Kurangi stok hewan
            $animal->decrement('stock');

            DB::commit();
            return response()->json(
                new WithoutDataResource(
                    Response::HTTP_CREATED,
                    'SUCCESS_CREATE_DATA',
                    'Berhasil Menyimpan Data',
                    "Data produk aqiqah '{$request->name}' berhasil ditambahkan."
                ),
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('gen_aqiqah_product')->error('| Store | - Error function store : ' . $e->getMessage());
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

            $aqiqahProduct = AqiqahProduct::withTrashed()
                ->with(['animals.animal_categories', 'animals.animal_breeds'])
                ->find($id);
            if (!$aqiqahProduct) {
                return response()->json(
                    new WithoutDataResource(
                        Response::HTTP_NOT_FOUND,
                        'DATA_NOT_FOUND',
                        'Data Tidak Ditemukan',
                        'Produk aqiqah dengan ID tersebut tidak ditemukan.',
                    ),
                    Response::HTTP_NOT_FOUND
                );
            }

            return response()->json(
                new WithDataResource(
                    Response::HTTP_OK,
                    'SUCCESS_GET_DATA',
                    'Berhasil Mengambil Data',
                    "Detail data produk aqiqah '{$aqiqahProduct->name}' berhasil didapatkan.",
                    new AqiqahProductResource($aqiqahProduct)
                ),
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            Log::channel('gen_aqiqah_product')->error('| Detail | - Error function show : ' . $e->getMessage());
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

    public function update(UpdateAqiqahProduct $request, $id)
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

            $aqiqahProduct = AqiqahProduct::withTrashed()->find($id);
            if (!$aqiqahProduct) {
                return response()->json(
                    new WithoutDataResource(
                        Response::HTTP_NOT_FOUND,
                        'DATA_NOT_FOUND',
                        'Data Tidak Ditemukan',
                        'Produk aqiqah dengan ID tersebut tidak ditemukan.',
                    ),
                    Response::HTTP_NOT_FOUND
                );
            }

            $data = $request->validated();

            $existingDocumentIds = $aqiqahProduct->photo_product_id ?? [];
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

            // ✅ Jika animal baru berbeda dengan yg lama, jangan lupa update stock
            $oldAnimalId = $aqiqahProduct->animal_id;
            $newAnimalId = $request->animal_id;

            $animalQuery = Animal::query()->with(['animal_categories', 'animal_breeds'])->lockForUpdate();

            // Ambil hewan yang lama dan baru sekaligus
            $animals = $animalQuery->whereIn('id', [$oldAnimalId, $newAnimalId])->get()->keyBy('id');

            $oldAnimal = $animals[$oldAnimalId] ?? null;
            $newAnimal = $animals[$newAnimalId] ?? null;

            if (!$newAnimal) {
                DB::rollBack();
                return response()->json(
                    new WithoutDataResource(
                        Response::HTTP_BAD_REQUEST,
                        'DATA_NOT_FOUND',
                        'Hewan Baru Tidak Ditemukan',
                        'Hewan baru tidak ditemukan di database.'
                    ),
                    Response::HTTP_BAD_REQUEST
                );
            }

            // ✅ Jika ganti animal_id, kurangi stok baru, tambahkan stok lama
            if ($oldAnimalId !== $newAnimalId) {
                if ($newAnimal->stock < 1) {
                    $categoryLabel = optional($newAnimal->animal_categories)->label ?? '-';
                    $breedLabel = optional($newAnimal->animal_breeds)->label ?? '-';

                    DB::rollBack();
                    return response()->json(
                        new WithoutDataResource(
                            Response::HTTP_BAD_REQUEST,
                            'OUT_OF_STOCK',
                            'Stok Hewan Baru Habis',
                            "Stok hewan aqiqah untuk kategori '{$categoryLabel}' dan ras '{$breedLabel}' sudah habis. Silakan pilih hewan lain yang masih tersedia."
                        ),
                        Response::HTTP_BAD_REQUEST
                    );
                }

                $newAnimal->decrement('stock');
                $oldAnimal?->increment('stock'); // aman kalau null
            }

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

            $photoDocumentIds = array_merge($existingDocumentIds, $newDocumentIds);

            $aqiqahProduct->update([
                'animal_id'         => $request->animal_id,
                'name'              => $request->name,
                'description'       => $request->description,
                'price'             => $request->price,
                'portion_count'     => $request->portion_count,
                'photo_product_id'  => $photoDocumentIds ?: null,
            ]);

            DB::commit();
            return response()->json(
                new WithoutDataResource(
                    Response::HTTP_OK,
                    'SUCCESS_UPDATE_DATA',
                    'Berhasil Memperbarui Data',
                    "Data produk aqiqah '{$aqiqahProduct->name}' berhasil diperbarui."
                ),
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('gen_aqiqah_product')->error('| Update | - Error function update : ' . $e->getMessage());
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

            $aqiqahProduct = AqiqahProduct::find($id);
            if (!$aqiqahProduct) {
                return response()->json(
                    new WithoutDataResource(
                        Response::HTTP_NOT_FOUND,
                        'DATA_NOT_FOUND',
                        'Data Tidak Ditemukan',
                        'Produk aqiqah dengan ID tersebut tidak ditemukan.',
                    ),
                    Response::HTTP_NOT_FOUND
                );
            }

            $aqiqahProduct->delete();

            DB::commit();
            return response()->json(
                new WithoutDataResource(
                    Response::HTTP_OK,
                    'SUCCESS_DELETE_DATA',
                    'Berhasil Menghapus Data',
                    "Data produk aqiqah '{$aqiqahProduct->name}' berhasil dihapus."
                ),
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('gen_aqiqah_product')->error('| Destroy | - Error function destroy : ' . $e->getMessage());
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

            $aqiqahProduct = AqiqahProduct::onlyTrashed()->find($id);
            if (!$aqiqahProduct) {
                return response()->json(
                    new WithoutDataResource(
                        Response::HTTP_NOT_FOUND,
                        'DATA_NOT_FOUND',
                        'Data Tidak Ditemukan',
                        'Produk aqiqah dengan ID tersebut tidak ditemukan atau belum dihapus.',
                    ),
                    Response::HTTP_NOT_FOUND
                );
            }

            $aqiqahProduct->restore();

            DB::commit();
            return response()->json(
                new WithoutDataResource(
                    Response::HTTP_OK,
                    'SUCCESS_RESTORE_DATA',
                    'Berhasil Mengembalikan Data',
                    "Data produk aqiqah '{$aqiqahProduct->name}' berhasil dikembalikan."
                ),
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('gen_aqiqah_product')->error('| Restore | - Error function restore : ' . $e->getMessage());
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
