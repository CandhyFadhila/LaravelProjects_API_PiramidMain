<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\Templates\WithDataResource;
use App\Http\Resources\Templates\WithoutDataResource;
use App\Models\DocumentStatus;
use App\Models\Mosque;
use App\Models\PaymentMethod;
use App\Models\PaymentStatus;
use App\Models\ServiceType;
use App\Models\TransactionStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
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
            Log::error('| Public Request | - Error function getServiceType : ' . $e->getMessage());
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
            Log::error('| Public Request | - Error function getPaymentStatus : ' . $e->getMessage());
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
            Log::error('| Public Request | - Error function getTransactionStatus : ' . $e->getMessage());
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
            Log::error('| Public Request | - Error function getPaymentMethod : ' . $e->getMessage());
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
                    $mosque
                ),
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            Log::error('| Public Request | - Error function getMosqueRelation : ' . $e->getMessage());
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
