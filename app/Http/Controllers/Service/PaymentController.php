<?php

namespace App\Http\Controllers\Service;

use App\Http\Controllers\Controller;
use App\Http\Requests\Service\Payment\StoreOrderDetail;
use App\Http\Requests\Service\Payment\UpdateOrderDetail;
use App\Http\Resources\Templates\WithoutDataResource;
use App\Models\OrderDetail;
use App\Models\ServiceType;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function storeOrderDetail(StoreOrderDetail $request)
    {
        try {
            if (!Gate::allows('transaction.create')) {
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

            $validatedData = $request->validated();

            $serviceType = ServiceType::find($validatedData['service_type_id']);
            if (!$serviceType) {
                return response()->json(
                    new WithoutDataResource(
                        Response::HTTP_NOT_FOUND,
                        'DATA_NOT_FOUND',
                        'Data Tidak Ditemukan',
                        'Tidak ada data layanan yang ditemukan.'
                    ),
                    Response::HTTP_NOT_FOUND
                );
            }

            $orderDetail = OrderDetail::create([
                'service_type_id' => $validatedData['service_type_id'],
                'detail' => $validatedData['detail'],
                'last_steps' => $validatedData['last_steps'] ?? 1,
            ]);

            DB::commit();

            return response()->json(
                new WithoutDataResource(
                    Response::HTTP_CREATED,
                    'SUCCESS_CREATE_DATA',
                    'Berhasil Menyimpan Data',
                    "Detail order untuk layanan '{$serviceType->label}' berhasil ditambahkan."
                ),
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('transaction_order_detail')->error('| Store | - Error function storeOrderDetail : ' . $e->getMessage());
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

    public function updateOrderDetail(UpdateOrderDetail $request, $id)
    {
        try {
            if (!Gate::allows('transaction.edit')) {
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

            $validatedData = $request->validated();

            $orderDetail = OrderDetail::find($id);
            if (!$orderDetail) {
                return response()->json(
                    new WithoutDataResource(
                        Response::HTTP_NOT_FOUND,
                        'DATA_NOT_FOUND',
                        'Data Tidak Ditemukan',
                        'Detail order tidak ditemukan.'
                    ),
                    Response::HTTP_NOT_FOUND
                );
            }

            $serviceType = ServiceType::find($validatedData['service_type_id']);
            if (!$serviceType) {
                return response()->json(
                    new WithoutDataResource(
                        Response::HTTP_NOT_FOUND,
                        'DATA_NOT_FOUND',
                        'Data Tidak Ditemukan',
                        'Tidak ada data layanan yang ditemukan.'
                    ),
                    Response::HTTP_NOT_FOUND
                );
            }

            // Kalau last_steps tidak diisi, maka ambil last_steps yang ada di database
            $lastSteps = isset($validatedData['last_steps']) ? $validatedData['last_steps'] : $orderDetail->last_steps;

            $orderDetail->update([
                'service_type_id' => $validatedData['service_type_id'],
                'detail' => $validatedData['detail'],
                'last_steps' => $lastSteps,
            ]);

            DB::commit();

            return response()->json(
                new WithoutDataResource(
                    Response::HTTP_OK,
                    'SUCCESS_UPDATE_DATA',
                    'Berhasil Memperbarui Data',
                    "Detail order untuk layanan '{$serviceType->label}' berhasil diperbarui."
                ),
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('transaction_order_detail')->error('| Update | - Error function updateOrderDetail : ' . $e->getMessage());
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
