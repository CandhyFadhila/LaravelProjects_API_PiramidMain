<?php

namespace App\Http\Controllers\Public;

use App\Helpers\QueryFilterSearch;
use App\Http\Controllers\Controller;
use App\Http\Resources\Management\Gen\Animal\AnimalBreedResource;
use App\Http\Resources\Management\Gen\Animal\AnimalCategoryResource;
use App\Http\Resources\Management\Gen\Animal\AnimalResource;
use App\Http\Resources\Management\Gen\CoverageArea\CitiesResource;
use App\Http\Resources\Management\Gen\CoverageArea\ProvinceResource;
use App\Http\Resources\Management\Gen\Mosque\MosqueResource;
use App\Http\Resources\Management\Gen\Product\QurbanProductResource;
use App\Http\Resources\Management\Gen\Product\AqiqahProductResource;
use App\Http\Resources\Management\Gen\Product\SadaqahProductResource;
use App\Http\Resources\Templates\WithDataResource;
use App\Http\Resources\Templates\WithoutDataResource;
use App\Models\Animal;
use App\Models\AnimalBreed;
use App\Models\AnimalCategory;
use App\Models\AqiqahProduct;
use App\Models\Cities;
use App\Models\Mosque;
use App\Models\PaymentMethod;
use App\Models\PaymentStatus;
use App\Models\Province;
use App\Models\QurbanProduct;
use App\Models\SadaqahProduct;
use App\Models\ServiceType;
use App\Models\TransactionStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class PublicRequestController extends Controller
{
    public function getServiceType()
    {
        try {
            $serviceType = ServiceType::all();
            if ($serviceType->isEmpty()) {
                return response()->json(
                    new WithoutDataResource(
                        Response::HTTP_NOT_FOUND,
                        'DATA_NOT_FOUND',
                        'Tidak Ada Data',
                        'Data service type tidak ditemukan.',
                    ),
                    Response::HTTP_NOT_FOUND
                );
            }

            return response()->json(
                new WithDataResource(
                    Response::HTTP_OK,
                    'SUCCESS_GET_DATA',
                    'Berhasil Mengambil Data',
                    'Berhasil mengambil data service type.',
                    $serviceType
                ),
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            Log::channel('public_request')->error('| Public Request | - Error function getServiceType : ' . $e->getMessage());
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

    public function getPaymentStatus()
    {
        try {
            $paymentStatus = PaymentStatus::all();
            if ($paymentStatus->isEmpty()) {
                return response()->json(
                    new WithoutDataResource(
                        Response::HTTP_NOT_FOUND,
                        'DATA_NOT_FOUND',
                        'Tidak Ada Data',
                        'Data payment status tidak ditemukan.',
                    ),
                    Response::HTTP_NOT_FOUND
                );
            }

            return response()->json(
                new WithDataResource(
                    Response::HTTP_OK,
                    'SUCCESS_GET_DATA',
                    'Berhasil Mengambil Data',
                    'Berhasil mengambil data payment status.',
                    $paymentStatus
                ),
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            Log::channel('public_request')->error('| Public Request | - Error function getPaymentStatus : ' . $e->getMessage());
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

    public function getTransactionStatus()
    {
        try {
            $transactionStatus = TransactionStatus::all();
            if ($transactionStatus->isEmpty()) {
                return response()->json(
                    new WithoutDataResource(
                        Response::HTTP_NOT_FOUND,
                        'DATA_NOT_FOUND',
                        'Tidak Ada Data',
                        'Data transaction status tidak ditemukan.',
                    ),
                    Response::HTTP_NOT_FOUND
                );
            }

            return response()->json(
                new WithDataResource(
                    Response::HTTP_OK,
                    'SUCCESS_GET_DATA',
                    'Berhasil Mengambil Data',
                    'Berhasil mengambil data transaction status.',
                    $transactionStatus
                ),
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            Log::channel('public_request')->error('| Public Request | - Error function getTransactionStatus : ' . $e->getMessage());
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

    public function getPaymentMethod()
    {
        try {
            $paymentMethod = PaymentMethod::all();
            if ($paymentMethod->isEmpty()) {
                return response()->json(
                    new WithoutDataResource(
                        Response::HTTP_NOT_FOUND,
                        'DATA_NOT_FOUND',
                        'Tidak Ada Data',
                        'Data metode pembayaran tidak ditemukan.',
                    ),
                    Response::HTTP_NOT_FOUND
                );
            }

            return response()->json(
                new WithDataResource(
                    Response::HTTP_OK,
                    'SUCCESS_GET_DATA',
                    'Berhasil Mengambil Data',
                    'Berhasil mengambil data metode pembayaran.',
                    $paymentMethod
                ),
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            Log::channel('public_request')->error('| Public Request | - Error function getPaymentMethod : ' . $e->getMessage());
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

    // Gen
    public function getMosqueRelation()
    {
        try {
            $mosque = Mosque::all();
            if ($mosque->isEmpty()) {
                return response()->json(
                    new WithoutDataResource(
                        Response::HTTP_NOT_FOUND,
                        'DATA_NOT_FOUND',
                        'Tidak Ada Data',
                        'Data relasi masjid tidak ditemukan.',
                    ),
                    Response::HTTP_NOT_FOUND
                );
            }

            return response()->json(
                new WithDataResource(
                    Response::HTTP_OK,
                    'SUCCESS_GET_DATA',
                    'Berhasil Mengambil Data',
                    'Berhasil mengambil Data relasi masjid.',
                    MosqueResource::collection($mosque)
                ),
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            Log::channel('public_request')->error('| Public Request | - Error function getMosqueRelation : ' . $e->getMessage());
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

    public function getAnimalCategory()
    {
        try {
            $animalCategory = AnimalCategory::all();
            if ($animalCategory->isEmpty()) {
                return response()->json(
                    new WithoutDataResource(
                        Response::HTTP_NOT_FOUND,
                        'DATA_NOT_FOUND',
                        'Tidak Ada Data',
                        'Data kategori hewan tidak ditemukan.',
                    ),
                    Response::HTTP_NOT_FOUND
                );
            }

            return response()->json(
                new WithDataResource(
                    Response::HTTP_OK,
                    'SUCCESS_GET_DATA',
                    'Berhasil Mengambil Data',
                    'Berhasil mengambil data kategori hewan.',
                    AnimalCategoryResource::collection($animalCategory)
                ),
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            Log::channel('public_request')->error('| Public Request | - Error function getAnimalCategory : ' . $e->getMessage());
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

    public function getAnimalBreed()
    {
        try {
            $animalBreed = AnimalBreed::all();
            if ($animalBreed->isEmpty()) {
                return response()->json(
                    new WithoutDataResource(
                        Response::HTTP_NOT_FOUND,
                        'DATA_NOT_FOUND',
                        'Tidak Ada Data',
                        'Data ras hewan tidak ditemukan.',
                    ),
                    Response::HTTP_NOT_FOUND
                );
            }

            return response()->json(
                new WithDataResource(
                    Response::HTTP_OK,
                    'SUCCESS_GET_DATA',
                    'Berhasil Mengambil Data',
                    'Berhasil mengambil data ras hewan.',
                    AnimalBreedResource::collection($animalBreed)
                ),
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            Log::channel('public_request')->error('| Public Request | - Error function getAnimalBreed : ' . $e->getMessage());
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

    public function getAnimal()
    {
        try {
            $animals = Animal::with(['animal_categories', 'animal_breeds'])->get();
            if ($animals->isEmpty()) {
                return response()->json(
                    new WithoutDataResource(
                        Response::HTTP_NOT_FOUND,
                        'DATA_NOT_FOUND',
                        'Tidak Ada Data',
                        'Data kategori hewan tidak ditemukan.',
                    ),
                    Response::HTTP_NOT_FOUND
                );
            }

            return response()->json(
                new WithDataResource(
                    Response::HTTP_OK,
                    'SUCCESS_GET_DATA',
                    'Berhasil Mengambil Data',
                    'Berhasil mengambil data kategori hewan.',
                    AnimalResource::collection($animals)
                ),
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            Log::channel('public_request')->error('| Public Request | - Error function getAnimal : ' . $e->getMessage());
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

    public function getCoverageProvince()
    {
        try {
            $managedProvince = Province::all();
            if ($managedProvince->isEmpty()) {
                return response()->json(
                    new WithoutDataResource(
                        Response::HTTP_NOT_FOUND,
                        'DATA_NOT_FOUND',
                        'Tidak Ada Data',
                        'Data nama provinsi tidak ditemukan.',
                    ),
                    Response::HTTP_NOT_FOUND
                );
            }

            return response()->json(
                new WithDataResource(
                    Response::HTTP_OK,
                    'SUCCESS_GET_DATA',
                    'Berhasil Mengambil Data',
                    'Berhasil mengambil data nama provinsi.',
                    ProvinceResource::collection($managedProvince)
                ),
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            Log::channel('public_request')->error('| Public Request | - Error function getCoverageProvince : ' . $e->getMessage());
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

    public function getCoverageCities()
    {
        try {
            $managedCities = Cities::all();
            if ($managedCities->isEmpty()) {
                return response()->json(
                    new WithoutDataResource(
                        Response::HTTP_NOT_FOUND,
                        'DATA_NOT_FOUND',
                        'Tidak Ada Data',
                        'Data nama provinsi tidak ditemukan.',
                    ),
                    Response::HTTP_NOT_FOUND
                );
            }

            return response()->json(
                new WithDataResource(
                    Response::HTTP_OK,
                    'SUCCESS_GET_DATA',
                    'Berhasil Mengambil Data',
                    'Berhasil mengambil data nama provinsi.',
                    CitiesResource::collection($managedCities)
                ),
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            Log::channel('public_request')->error('| Public Request | - Error function getCoverageCities : ' . $e->getMessage());
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

    public function getCitiesByProvince($provinceId)
    {
        try {
            $cities = Cities::where('province_id', $provinceId)
                ->where('is_active', true)
                ->get();
            if ($cities->isEmpty()) {
                return response()->json(
                    new WithoutDataResource(
                        Response::HTTP_NOT_FOUND,
                        'DATA_NOT_FOUND',
                        'Tidak Ada Data',
                        'Tidak ditemukan kota dengan provinsi ID tersebut.',
                    ),
                    Response::HTTP_NOT_FOUND
                );
            }

            return response()->json(
                new WithDataResource(
                    Response::HTTP_OK,
                    'SUCCESS_GET_DATA',
                    'Berhasil Mengambil Data',
                    'Data kota berdasarkan provinsi berhasil didapatkan.',
                    CitiesResource::collection($cities)
                ),
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            Log::channel('public_request')->error('| Public Request | - Error function getCitiesByProvince : ' . $e->getMessage());
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

    // Product
    public function getQurbanProduct(Request $request)
    {
        try {
            $query = QurbanProduct::query()
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
                // TODO: Filter bagian ini belum work
                'price_min' => function ($q, $val) {
                    $q->where('price', '>=', $val);  // Filter harga minimum
                },
                'price_max' => function ($q, $val) {
                    $q->where('price', '<=', $val);  // Filter harga maksimum
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
                        Response::HTTP_NOT_FOUND,
                        'DATA_NOT_FOUND',
                        'Data Tidak Ditemukan',
                        'Tidak ada data yang sesuai dengan filter atau pencarian.'
                    ),
                    Response::HTTP_NOT_FOUND
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
            Log::channel('public_request')->error('| Public Request | - Error function getQurbanProduct : ' . $e->getMessage());
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

    public function getAqiqahProduct(Request $request)
    {
        try {
            $query = AqiqahProduct::query()
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
                    $q->where('price', '>=', $val);  // Filter harga minimum
                },
                'price_max' => function ($q, $val) {
                    $q->where('price', '<=', $val);  // Filter harga maksimum
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
                        Response::HTTP_NOT_FOUND,
                        'DATA_NOT_FOUND',
                        'Data Tidak Ditemukan',
                        'Tidak ada data yang sesuai dengan filter atau pencarian.'
                    ),
                    Response::HTTP_NOT_FOUND
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
            Log::channel('public_request')->error('| Public Request | - Error function getAqiqahProduct : ' . $e->getMessage());
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

    public function getSadaqahProduct(Request $request)
    {
        try {
            $query = SadaqahProduct::query();

            $filterRules = [
                'price_min' => function ($q, $val) {
                    $q->where('price', '>=', $val);  // Filter harga minimum
                },
                'price_max' => function ($q, $val) {
                    $q->where('price', '<=', $val);  // Filter harga maksimum
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
                        Response::HTTP_NOT_FOUND,
                        'DATA_NOT_FOUND',
                        'Data Tidak Ditemukan',
                        'Tidak ada data yang sesuai dengan pencarian.'
                    ),
                    Response::HTTP_NOT_FOUND
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
            Log::channel('public_request')->error('| Public Request | - Error function getSadaqahProduct : ' . $e->getMessage());
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
