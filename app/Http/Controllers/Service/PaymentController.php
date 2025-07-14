<?php

namespace App\Http\Controllers\Service;

use App\Helpers\MidtransHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Service\Payment\StorePayment;
use App\Http\Resources\Templates\WithDataResource;
use App\Http\Resources\Templates\WithoutDataResource;
use App\Models\PaymentDetail;
use App\Models\PaymentLog;
use App\Models\PaymentMethod;
use App\Models\PaymentStatus;
use App\Models\ServiceType;
use App\Models\Transaction;
use App\Models\TransactionStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function createPayment(StorePayment $request)
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

            $data = $request->validated();

            $gateway = strtolower($data['payment_gateway_id']);

            // Validasi gateway
            // TODO: Kalau coinpayment sudah ready, pakek ini aja
            // if (!in_array($gateway, ['midtrans', 'coinpayment'])) {
            if (!in_array($gateway, ['midtrans'])) {
                return response()->json(
                    new WithoutDataResource(
                        Response::HTTP_UNPROCESSABLE_ENTITY,
                        'UNSUPPORTED_GATEWAY',
                        'Gateway Tidak Didukung',
                        "Payment gateway '{$data['payment_gateway_id']}' belum tersedia. Silakan pilih metode pembayaran lain."
                    ),
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }

            DB::beginTransaction();

            $user = $request->user();

            $paymentStatusId = PaymentStatus::where('label', 'Pending')->value('id');
            $transactionStatusId = TransactionStatus::where('label', 'Pending')->value('id');
            $paymentMethodId = $gateway === 'midtrans'
                ? PaymentMethod::where('label', 'Fiat')->value('id')
                : PaymentMethod::where('label', 'Crypto')->value('id');

            // Create Payment Detail
            $paymentDetail = PaymentDetail::create([
                'transaction_id' => null, // Diisi setelah transaksi dibuat
                'payment_status_id' => $paymentStatusId,
                'payment_method_id' => $paymentMethodId,
                'payment_gateway_id' => $data['payment_gateway_id'],
                'payment_date' => now(),
                'amount_paid' => $data['amount_paid'],
                'transaction_ref' => null,
                'currency' => $data['currency'],
            ]);

            Log::channel('transaction_payment_detail')->info('| Store | - PaymentDetail successfully created.', $paymentDetail->toArray());

            // Create Transaction
            $transaction = Transaction::create([
                'user_id' => $user->id,
                'order_detail_id' => $data['order_detail_id'],
                'payment_detail_id' => $paymentDetail->id,
                'transaction_status_id' => $transactionStatusId,
                'transaction_date' => now(),
                'settlement_date' => now()->addDay(),
                'grand_total' => $data['amount_paid'],
                'note' => $data['note'] ?? null,
            ]);

            Log::channel('transaction')->info('| Store | - Transaction successfully created.', $transaction->toArray());

            $midtransOrderId = 'TRX-' . now('Asia/Jakarta')->format('Ymd') . '-' . $transaction->id;

            $serviceType = ServiceType::find($transaction->order_details->service_type_id);

            $paymentDetail->update([
                'transaction_id' => $transaction->id,
                'payment_order_id' => $midtransOrderId
            ]);

            // Gateway handler
            switch ($gateway) {
                case 'midtrans':
                    $response = $this->handleCreateMidtransPayment($user, $data, $midtransOrderId, $transaction, $paymentDetail, $serviceType);
                    break;
                // TODO: Kalau coinpayment sudah ready
                // case 'coinpayment':
                //     $response = $this->handleCoinpaymentPayment();
                //     break;
                default:
                    throw new \Exception('Gateway tersebut tidak didukung, silahkan pilih metode pembayaran lain.');
            }

            DB::commit();
            return $response;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('transaction')->error('| Store | - Error function createPayment : ' . $e->getMessage() . ' - Line : ' . $e->getLine());
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

    public function updateStatusPayment(Request $request)
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

            $orderId = $request->order_id;
            if (!$request->filled('order_id')) {
                return response()->json(
                    new WithoutDataResource(
                        Response::HTTP_BAD_REQUEST,
                        'FAILED_VALIDATION',
                        'Permintaan Tidak Valid',
                        'Order ID tidak diperbolehkan kosong.'
                    ),
                    Response::HTTP_BAD_REQUEST
                );
            }

            // Ambil status dari Midtrans
            $statusResponse = MidtransHelper::getTransactionStatus($orderId);
            $midtransStatus = $statusResponse->transaction_status ?? null;
            $midtransTransactionId = $statusResponse->transaction_id ?? null;
            $midtransStatusMessage = $statusResponse->status_message ?? null;

            Log::channel('midtrans_payment')->info('| Update | - Midtrans response', [
                'midtrans_order_id' => $orderId,
                'midtrans_transaction_status' => $midtransStatus,
                'midtrans_transaction_id' => $midtransTransactionId,
                'midtrans_status_message' => $midtransStatusMessage
            ]);

            // Jika status tidak tersedia atau masih pending
            if (empty($midtransStatus) || $midtransStatus === 'pending' || $midtransStatus === 'not_found') {
                DB::commit();

                return response()->json(
                    new WithoutDataResource(
                        Response::HTTP_OK,
                        'STATUS_NOT_SETTLED',
                        'Pembayaran Belum Diselesaikan',
                        'Status transaksi saat ini: ' . ucfirst($midtransStatus ?? 'Unknown'),
                    ),
                    Response::HTTP_OK
                );
            }

            // Validasi order_id yang sesuai format: TRX-YYYYMMDD-ID
            $parts = explode('-', $orderId);
            $transactionId = end($parts);

            $transaction = Transaction::with('payment_details')->find($transactionId);
            if (!$transaction || !$transaction->payment_details) {
                return response()->json(
                    new WithoutDataResource(
                        Response::HTTP_NOT_FOUND,
                        'DATA_NOT_FOUND',
                        'Transaksi Tidak Ditemukan',
                        'Data transaksi berdasarkan Order ID Midtrans tidak ditemukan.',
                    ),
                    Response::HTTP_NOT_FOUND
                );
            }

            $successStatusId = TransactionStatus::where('label', 'Completed')->value('id');
            $paidStatusId = PaymentStatus::where('label', 'Paid')->value('id');

            if (in_array($midtransStatus, ['settlement', 'capture', 'success'])) {
                $transaction->update([
                    'transaction_status_id' => $successStatusId,
                    'settlement_date' => now(),
                ]);

                $transaction->payment_details->update([
                    'payment_status_id' => $paidStatusId,
                    'transaction_ref' => $midtransTransactionId,
                ]);

                PaymentLog::create([
                    'payment_detail_id' => $transaction->payment_details->id,
                    'name' => 'midtrans_status_success',
                    'type' => 'update',
                    'payload' => [
                        'order_id' => $orderId,
                        'status' => $midtransStatus,
                        'transaction_ref' => $midtransTransactionId,
                    ],
                ]);

                DB::commit();

                Log::channel('midtrans_payment')->info('| Update | - Status updated to success.', [
                    'transaction_id' => $transaction->id,
                    'payment_detail_id' => $transaction->payment_details->id,
                ]);

                return response()->json(
                    new WithoutDataResource(
                        Response::HTTP_OK,
                        'STATUS_UPDATED',
                        'Status Pembayaran Diperbarui',
                        'Status transaksi berhasil diperbarui menjadi sukses.'
                    ),
                    Response::HTTP_OK
                );
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('transaction')->error('| Update | - Error function updateStatusPayment : ' . $e->getMessage() . ' - Line : ' . $e->getLine());
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

    private function handleCreateMidtransPayment($user, $data, $midtransOrderId, $transaction, $paymentDetail, $serviceType)
    {
        // Midtrans
        $midtransParams = [
            'transaction_details' => [
                'order_id' => $midtransOrderId,
                'gross_amount' => $data['amount_paid'],
            ],
            'customer_details' => [
                'first_name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone_number ?? $user->wa_number,
            ],
            'item_details' => [
                [
                    'id' => 'ORDER-' . $data['order_detail_id'],
                    'name' => "Pembayaran layanan '{$serviceType->label}', Order-'{$data['order_detail_id']}",
                    'quantity' => 1,
                    'price' => $data['amount_paid'],
                ]
            ],
        ];

        $snapResponse = MidtransHelper::sendSnapToken($midtransParams, $transaction->id, $paymentDetail->id);

        Log::channel('midtrans_payment')->info('| createPayment | - PaymentDetail updated with Snap Token and Redirect URL', [
            'transaction_id' => $transaction->id,
            'payment_detail_id' => $paymentDetail->id,
            'snap_token' => $snapResponse->token,
            'redirect_url' => $snapResponse->redirect_url,
        ]);

        return response()->json(
            new WithDataResource(
                Response::HTTP_CREATED,
                'SUCCESS_CREATE_DATA',
                'Pembayaran Berhasil Dibuat',
                'Data pembayaran berhasil disimpan dan diproses.',
                [
                    'snap_token' => $snapResponse->token,
                    'redirect_url' => $snapResponse->redirect_url,
                    'order_id' => $midtransOrderId
                ]
            ),
            Response::HTTP_CREATED
        );
    }

    private function handleCreateCoinPayment() {}
}
