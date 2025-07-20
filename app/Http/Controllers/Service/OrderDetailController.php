<?php

namespace App\Http\Controllers\Service;

use App\Helpers\OrderDetailHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Service\Payment\StoreOrderDetail;
use App\Http\Requests\Service\Payment\UpdateOrderDetail;
use App\Http\Resources\Service\OrderDetailResource;
use App\Http\Resources\Templates\WithDataResource;
use App\Http\Resources\Templates\WithoutDataResource;
use App\Models\OrderDetail;
use App\Models\PaymentDetail;
use App\Models\PaymentMethod;
use App\Models\PaymentStatus;
use App\Models\ServiceType;
use App\Models\Transaction;
use App\Models\TransactionStatus;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class OrderDetailController extends Controller
{
    public function index()
    {
        try {
            if (!Gate::allows('transaction.view')) {
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

            $orderDetail = OrderDetail::with(['user', 'service_type'])
                ->where('user_id', Auth::id())
                ->get();
            if ($orderDetail->isEmpty()) {
                return response()->json(
                    new WithoutDataResource(
                        Response::HTTP_OK,
                        'DATA_NOT_FOUND',
                        'Data Tidak Ditemukan',
                        'Tidak ada data yang sesuai.'
                    ),
                    Response::HTTP_OK
                );
            }

            return response()->json(
                new WithDataResource(
                    Response::HTTP_OK,
                    'SUCCESS_GET_DATA',
                    'Berhasil Mengambil Data',
                    'Data order detail berhasil didapatkan.',
                    OrderDetailResource::collection($orderDetail)
                ),
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            Log::channel('transaction_order_detail')->error('| Index | - Error function index : ' . $e->getMessage() . ' - Line : ' . $e->getLine());
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

    public function store(StoreOrderDetail $request)
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

            $user = $request->user();

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

            // Ini step 1
            $orderDetail = OrderDetail::create([
                'user_id' => $user->id,
                'service_type_id' => $validatedData['service_type_id'],
                // 'detail' => $validatedData['detail'],
                // 'last_steps' => $validatedData['last_steps'] ?? 1,
            ]);

            $orderDetail->refresh();

            DB::commit();

            return response()->json(
                new WithDataResource(
                    Response::HTTP_CREATED,
                    'SUCCESS_CREATE_DATA',
                    'Berhasil Menyimpan Data',
                    "Detail order untuk layanan '{$serviceType->label}' berhasil ditambahkan.",
                    new OrderDetailResource($orderDetail)
                ),
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('transaction_order_detail')->error('| Store | - Error function storeOrderDetail : ' . $e->getMessage() . ' - Line : ' . $e->getLine());
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
            if (!Gate::allows('transaction.view')) {
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

            $orderDetail = OrderDetail::find($id);
            if (!$orderDetail) {
                return response()->json(
                    new WithoutDataResource(
                        Response::HTTP_NOT_FOUND,
                        'DATA_NOT_FOUND',
                        'Data Tidak Ditemukan',
                        'Order detail dengan ID tersebut tidak ditemukan.',
                    ),
                    Response::HTTP_NOT_FOUND
                );
            }

            $serviceType = ServiceType::find($orderDetail->service_type_id);

            return response()->json(
                new WithDataResource(
                    Response::HTTP_OK,
                    'SUCCESS_GET_DATA',
                    'Berhasil Mengambil Data',
                    "Detail order dengan layanan '{$serviceType->label}' berhasil didapatkan.",
                    new OrderDetailResource($orderDetail)
                ),
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            Log::channel('transaction_order_detail')->error('| Detail | - Error function show : ' . $e->getMessage() . ' - Line : ' . $e->getLine());
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

    public function update(UpdateOrderDetail $request, $id)
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

            if ($orderDetail->last_steps !== 1) {
                return response()->json(
                    new WithoutDataResource(
                        Response::HTTP_BAD_REQUEST,
                        'STEP_NOT_ALLOWED',
                        'Langkah Tidak Diizinkan',
                        'Data order hanya dapat diubah pada langkah pertama.'
                    ),
                    Response::HTTP_BAD_REQUEST
                );
            }

            $serviceType = ServiceType::find($orderDetail->service_type_id);
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

            $lastSteps = $validatedData['last_steps'] ?? $orderDetail->last_steps;

            $formattedDetail = OrderDetailHelper::formatOrderDetailByServiceType(
                $orderDetail->service_type_id,
                $validatedData['detail']
            );

            // Ini step 2
            $orderDetail->update([
                'detail' => $formattedDetail,
                'last_steps' => $lastSteps,
            ]);

            if ($lastSteps == 2) {
                $summaryOrderDetail = OrderDetailHelper::summarizeOrderDetail($orderDetail);
                $paymentStatusPendingId = PaymentStatus::where('label', 'First Payment')->value('id');
                $paymentMethodId = PaymentMethod::where('label', 'Fiat')->value('id');
                $transactionStatusPendingId = TransactionStatus::where('label', 'First Transaction')->value('id');

                $paymentDetail = PaymentDetail::create([
                    'transaction_id' => null,
                    'payment_status_id' => $paymentStatusPendingId,
                    'payment_method_id' => $paymentMethodId,
                    'payment_gateway_id' => null,
                    'payment_date' => null,
                    'amount_paid' => 0,
                    'transaction_ref' => null,
                    'currency' => null,
                ]);

                Transaction::create([
                    'user_id' => $orderDetail->user_id,
                    'order_detail_id' => $orderDetail->id,
                    'payment_detail_id' => $paymentDetail->id,
                    'transaction_status_id' => $transactionStatusPendingId,
                    'transaction_date' => null,
                    'settlement_date' => null,
                    'grand_total' => $summaryOrderDetail['price'],
                    'note' => null,
                ]);
            }

            DB::commit();

            return response()->json(
                new WithDataResource(
                    Response::HTTP_OK,
                    'SUCCESS_UPDATE_DATA',
                    'Berhasil Memperbarui Data',
                    "Detail order untuk layanan '{$serviceType->label}' berhasil diperbarui.",
                    new OrderDetailResource($orderDetail)
                ),
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('transaction_order_detail')->error('| Update | - Error function updateOrderDetail : ' . $e->getMessage() . ' - Line : ' . $e->getLine());
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
            if (!Gate::allows('transaction.delete')) {
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

            $orderDetail = OrderDetail::find($id);
            if (!$orderDetail) {
                return response()->json(
                    new WithoutDataResource(
                        Response::HTTP_NOT_FOUND,
                        'DATA_NOT_FOUND',
                        'Data Tidak Ditemukan',
                        'Data order detail dengan ID tersebut tidak ditemukan.',
                    ),
                    Response::HTTP_NOT_FOUND
                );
            }

            $transaction = Transaction::where('order_detail_id', $orderDetail->id)->first();
            if ($orderDetail->last_steps === 3) {
                return response()->json(
                    new WithoutDataResource(
                        Response::HTTP_BAD_REQUEST,
                        'STEP_NOT_ALLOWED',
                        'Langkah Tidak Diizinkan',
                        'Order detail ini tidak dapat dihapus karena sedang berada di langkah ketiga.'
                    ),
                    Response::HTTP_BAD_REQUEST
                );
            }

            // Hapus payment detail dan transaction
            if ($transaction) {
                $paymentDetail = PaymentDetail::find($transaction->payment_detail_id);
                if ($paymentDetail) {
                    $paymentDetail->delete();
                }
                $transaction->delete();
            }
            $orderDetail->delete();

            DB::commit();

            $serviceType = ServiceType::find($orderDetail->service_type_id);

            return response()->json(
                new WithoutDataResource(
                    Response::HTTP_OK,
                    'SUCCESS_DELETE_DATA',
                    'Berhasil Menghapus Data',
                    "Data order dengan layanan '{$serviceType->label}' berhasil dihapus."
                ),
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('transaction_order_detail')->error('| Destroy | - Error function destroy : ' . $e->getMessage() . ' - Line : ' . $e->getLine());
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
        // ! Khusus 'Super Admin'
        try {
            if (!Gate::allows('only-super-admin')) {
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

            $orderDetail = OrderDetail::onlyTrashed()->find($id);
            if (!$orderDetail) {
                return response()->json(
                    new WithoutDataResource(
                        Response::HTTP_NOT_FOUND,
                        'DATA_NOT_FOUND',
                        'Data Tidak Ditemukan',
                        'Data order detail dengan ID tersebut tidak ditemukan atau belum dihapus.',
                    ),
                    Response::HTTP_NOT_FOUND
                );
            }

            $orderDetail->restore();

            DB::commit();

            $serviceType = ServiceType::find($orderDetail->service_type_id);

            return response()->json(
                new WithoutDataResource(
                    Response::HTTP_OK,
                    'SUCCESS_RESTORE_DATA',
                    'Berhasil Mengembalikan Data',
                    "Data order dengan layanan '{$serviceType->label}' berhasil dikembalikan."
                ),
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('transaction_order_detail')->error('| Restore | - Error function restore : ' . $e->getMessage() . ' - Line : ' . $e->getLine());
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
