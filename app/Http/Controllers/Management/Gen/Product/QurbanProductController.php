<?php

namespace App\Http\Controllers\Management\Gen\Product;

use App\Helpers\DocumentHelper;
use App\Helpers\QueryFilterSearch;
use App\Http\Controllers\Controller;
use App\Http\Requests\Management\Gen\Product\StoreQurbanProduct;
use App\Http\Requests\Management\Gen\Product\UpdateQurbanProduct;
use App\Http\Resources\Management\Gen\Product\QurbanProductResource;
use App\Http\Resources\Templates\WithDataResource;
use App\Http\Resources\Templates\WithoutDataResource;
use App\Models\Animal;
use App\Models\QurbanProduct;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class QurbanProductController extends Controller
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

            $query = QurbanProduct::query()
                ->withTrashed()
                ->with([
                    'animals',
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
                    'Data produk qurban berhasil didapatkan.',
                    QueryFilterSearch::formatPaginationCollection($result, QurbanProductResource::class)
                ),
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            Log::channel('gen_qurban_product')->error('| Index | - Error function index : ' . $e->getMessage() . ' - Line : ' . $e->getLine());
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

    public function store(StoreQurbanProduct $request)
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
                        'Hewan qurban yang dipilih tidak tersedia.'
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
                        "Stok hewan qurban untuk kategori '{$categoryLabel}' dan ras '{$breedLabel}' sudah habis. Silakan pilih hewan lain yang masih tersedia."
                    ),
                    Response::HTTP_BAD_REQUEST
                );
            }

            $photoDocumentIds = [];

            if ($request->hasFile('photo_product_id') && is_array($request->file('photo_product_id'))) {
                $photoDocumentIds = DocumentHelper::uploadDocuments($request->file('photo_product_id'));
            }

            QurbanProduct::create([
                'animal_id'         => $request->animal_id,
                'name'              => $request->name,
                'description'       => $request->description,
                'price'             => $request->price,
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
                    "Data produk qurban '{$request->name}' berhasil ditambahkan."
                ),
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('gen_qurban_product')->error('| Store | - Error function store : ' . $e->getMessage() . ' - Line : ' . $e->getLine());
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

            $qurbanProduct = QurbanProduct::withTrashed()
                ->with(['animals.animal_categories', 'animals.animal_breeds'])
                ->find($id);
            if (!$qurbanProduct) {
                return response()->json(
                    new WithoutDataResource(
                        Response::HTTP_NOT_FOUND,
                        'DATA_NOT_FOUND',
                        'Data Tidak Ditemukan',
                        'Produk qurban dengan ID tersebut tidak ditemukan.',
                    ),
                    Response::HTTP_NOT_FOUND
                );
            }

            return response()->json(
                new WithDataResource(
                    Response::HTTP_OK,
                    'SUCCESS_GET_DATA',
                    'Berhasil Mengambil Data',
                    "Detail data produk qurban '{$qurbanProduct->name}' berhasil didapatkan.",
                    new QurbanProductResource($qurbanProduct)
                ),
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            Log::channel('gen_qurban_product')->error('| Detail | - Error function show : ' . $e->getMessage() . ' - Line : ' . $e->getLine());
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

    public function update(UpdateQurbanProduct $request, $id)
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

            $qurbanProduct = QurbanProduct::withTrashed()->find($id);
            if (!$qurbanProduct) {
                return response()->json(
                    new WithoutDataResource(
                        Response::HTTP_NOT_FOUND,
                        'DATA_NOT_FOUND',
                        'Data Tidak Ditemukan',
                        'Produk qurban dengan ID tersebut tidak ditemukan.',
                    ),
                    Response::HTTP_NOT_FOUND
                );
            }

            $data = $request->validated();

            $existingDocumentIds = $qurbanProduct->photo_product_id ?? [];
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
            $oldAnimalId = $qurbanProduct->animal_id;
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
                            "Stok hewan qurban untuk kategori '{$categoryLabel}' dan ras '{$breedLabel}' sudah habis. Silakan pilih hewan lain yang masih tersedia."
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

            $finalDocumentIds = array_merge($existingDocumentIds, $newDocumentIds);

            $qurbanProduct->update([
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
                    "Data produk qurban '{$qurbanProduct->name}' berhasil diperbarui."
                ),
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('gen_qurban_product')->error('| Update | - Error function update : ' . $e->getMessage() . ' - Line : ' . $e->getLine());
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

            $qurbanProduct = QurbanProduct::find($id);
            if (!$qurbanProduct) {
                return response()->json(
                    new WithoutDataResource(
                        Response::HTTP_NOT_FOUND,
                        'DATA_NOT_FOUND',
                        'Data Tidak Ditemukan',
                        'Produk qurban dengan ID tersebut tidak ditemukan.',
                    ),
                    Response::HTTP_NOT_FOUND
                );
            }

            $qurbanProduct->delete();

            DB::commit();
            return response()->json(
                new WithoutDataResource(
                    Response::HTTP_OK,
                    'SUCCESS_DELETE_DATA',
                    'Berhasil Menghapus Data',
                    "Data produk qurban '{$qurbanProduct->name}' berhasil dihapus."
                ),
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('gen_qurban_product')->error('| Destroy | - Error function destroy : ' . $e->getMessage() . ' - Line : ' . $e->getLine());
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

            $qurbanProduct = QurbanProduct::onlyTrashed()->find($id);
            if (!$qurbanProduct) {
                return response()->json(
                    new WithoutDataResource(
                        Response::HTTP_NOT_FOUND,
                        'DATA_NOT_FOUND',
                        'Data Tidak Ditemukan',
                        'Produk qurban dengan ID tersebut tidak ditemukan atau belum dihapus.',
                    ),
                    Response::HTTP_NOT_FOUND
                );
            }

            $qurbanProduct->restore();

            DB::commit();
            return response()->json(
                new WithoutDataResource(
                    Response::HTTP_OK,
                    'SUCCESS_RESTORE_DATA',
                    'Berhasil Mengembalikan Data',
                    "Data produk qurban '{$qurbanProduct->name}' berhasil dikembalikan."
                ),
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('gen_qurban_product')->error('| Restore | - Error function restore : ' . $e->getMessage() . ' - Line : ' . $e->getLine());
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
