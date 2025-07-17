<?php

namespace App\Http\Controllers\Service;

use App\Helpers\MidtransHelper;
use App\Helpers\OrderDetailHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Service\Payment\StorePayment;
use App\Http\Requests\Service\Payment\UpdatePayment;
use App\Http\Resources\Templates\WithDataResource;
use App\Http\Resources\Templates\WithoutDataResource;
use App\Models\Address;
use App\Models\OrderDetail;
use App\Models\PaymentDetail;
use App\Models\PaymentLog;
use App\Models\PaymentMethod;
use App\Models\PaymentStatus;
use App\Models\ServiceType;
use App\Models\Transaction;
use App\Models\TransactionStatus;
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

            $orderDetail = OrderDetail::where('id', $data['order_detail_id'])
                ->where('user_id', $user->id)
                ->first();
            if (!$orderDetail) {
                return response()->json(
                    new WithoutDataResource(
                        Response::HTTP_OK,
                        'DATA_NOT_FOUND',
                        'Order Detail Tidak Ditemukan',
                        'Order detail tidak ditemukan atau bukan milik Anda.'
                    ),
                    Response::HTTP_OK
                );
            }

            if ($orderDetail->last_steps !== 2) {
                return response()->json(
                    new WithoutDataResource(
                        Response::HTTP_BAD_REQUEST,
                        'STEP_NOT_ALLOWED',
                        'Langkah Tidak Diizinkan',
                        'Data order hanya dapat diubah pada langkah kedua.'
                    ),
                    Response::HTTP_BAD_REQUEST
                );
            }

            $existingTransaction = Transaction::where('order_detail_id', $orderDetail->id)
                ->whereHas('payment_details', function ($query) {
                    $query->whereHas('payment_statuses', function ($q) {
                        $q->whereNotIn('label', ['Failed', 'Expired', 'Refunded']);
                    });
                })
                ->exists();
            if ($existingTransaction) {
                return response()->json(
                    new WithoutDataResource(
                        Response::HTTP_BAD_REQUEST,
                        'TRANSACTION_EXISTS',
                        'Transaksi Sudah Ada',
                        'Order detail ini sudah memiliki transaksi yang sedang atau sudah diproses. Tidak dapat membuat transaksi baru.'
                    ),
                    Response::HTTP_BAD_REQUEST
                );
            }

            // Validasi address_id milik user yang login
            $address = Address::where('id', $data['address_id'])
                ->where('user_id', $user->id)
                ->first();
            if (!$address) {
                return response()->json(
                    new WithoutDataResource(
                        Response::HTTP_OK,
                        'DATA_NOT_FOUND',
                        'Alamat Tidak Ditemukan',
                        'Alamat yang Anda pilih tidak ditemukan atau bukan milik Anda.'
                    ),
                    Response::HTTP_OK
                );
            }

            $orderDetail->update([
                'address_id' => $data['address_id'],
                'mosque_id' => $data['mosque_id'] ?? null,
                'last_steps' => $data['last_steps'],
            ]);

            $paymentStatusPendingId = PaymentStatus::where('label', 'Pending')->value('id');
            $transactionStatusPendingId = TransactionStatus::where('label', 'Pending')->value('id');
            $paymentMethodId = $gateway === 'midtrans'
                ? PaymentMethod::where('label', 'Fiat')->value('id')
                : PaymentMethod::where('label', 'Crypto')->value('id');
            $summaryOrderDetail = OrderDetailHelper::summarizeOrderDetail($orderDetail);

            // Create Payment Detail
            $paymentDetail = PaymentDetail::create([
                'transaction_id' => null, // Diisi setelah transaksi dibuat
                'payment_status_id' => $paymentStatusPendingId,
                'payment_method_id' => $paymentMethodId,
                'payment_gateway_id' => $data['payment_gateway_id'],
                'payment_date' => now(),
                'amount_paid' => $summaryOrderDetail['price'],
                'transaction_ref' => null,
                'currency' => $data['currency'],
            ]);

            Log::channel('transaction_payment_detail')->info('| Store | - PaymentDetail successfully created.', $paymentDetail->toArray());

            // Create Transaction
            $transaction = Transaction::create([
                'user_id' => $user->id,
                'order_detail_id' => $data['order_detail_id'],
                'payment_detail_id' => $paymentDetail->id,
                'transaction_status_id' => $transactionStatusPendingId,
                'transaction_date' => now(),
                'settlement_date' => now()->addDay(),
                'grand_total' => $summaryOrderDetail['price'],
                'note' => $data['note'] ?? null,
            ]);

            Log::channel('transaction')->info('| Store | - Transaction successfully created.', $transaction->toArray());

            $midtransOrderId = 'TRX-' . now('Asia/Jakarta')->format('Ymd-Hisv') . '-' . $transaction->id;

            $serviceType = ServiceType::find($transaction->order_details->service_type_id);

            // Gateway handler
            switch ($gateway) {
                case 'midtrans':
                    $response = $this->handleCreateMidtransPayment($user, $midtransOrderId, $transaction, $paymentDetail, $serviceType);
                    break;
                // TODO: Kalau coinpayment sudah ready
                // case 'coinpayment':
                //     $response = $this->handleCoinpaymentPayment();
                //     break;
                default:
                    throw new \Exception('Gateway tersebut tidak didukung, silahkan pilih metode pembayaran lain.');
            }

            // Update payment detail and transaction
            $paymentStatusProcessId = PaymentStatus::where('label', 'Processing')->value('id');
            $transactionStatusProcessId = TransactionStatus::where('label', 'Processing')->value('id');

            $paymentDetail->update([
                'transaction_id' => $transaction->id,
                'payment_status_id' => $paymentStatusProcessId,
                'payment_order_id' => $midtransOrderId,
            ]);

            $transaction->update([
                'transaction_status_id' => $transactionStatusProcessId,
            ]);

            // Kurangi stok hewan
            OrderDetailHelper::deductAnimalStock($orderDetail);

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

    public function updateStatusPayment(UpdatePayment $request)
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
                        'Order ID tidak boleh kosong.'
                    ),
                    Response::HTTP_BAD_REQUEST
                );
            }

            // Ambil status dari Midtrans
            $statusResponse = MidtransHelper::getTransactionStatus($orderId);

            $response = $this->handleUpdateStatusMidtransPayment($orderId, $statusResponse);

            DB::commit();
            return $response;
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

    private function handleCreateMidtransPayment($user, $midtransOrderId, $transaction, $paymentDetail, $serviceType)
    {
        // Ambil order detail & summary
        $orderDetail = $transaction->order_details;
        $summaryOrderDetail = OrderDetailHelper::summarizeOrderDetail($orderDetail);

        $itemDetails = [];

        foreach ($orderDetail->detail as $item) {
            foreach (['qurban_product', 'aqiqah_product', 'sadaqah_product'] as $productType) {
                if (!empty($item[$productType])) {
                    $product = $item[$productType];

                    $itemDetails[] = [
                        'id' => strtoupper('ORDER-' . $productType . '-' . '#' . $product['id']),
                        'name' => "Payment for order '{$serviceType->label}', #$productType}",
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                    ];

                    break;
                }
            }
        }

        // Midtrans
        $midtransParams = [
            'transaction_details' => [
                'order_id' => $midtransOrderId,
                'gross_amount' => $summaryOrderDetail['price'],
            ],
            'customer_details' => [
                'first_name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone_number ?? $user->wa_number,
            ],
            'item_details' => $itemDetails,
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

    public function handleUpdateStatusMidtransPayment(string $orderId, object $statusResponse)
    {
        $midtransStatus = $statusResponse->transaction_status ?? null;
        $midtransTransactionId = $statusResponse->transaction_id ?? null;
        $midtransStatusMessage = $statusResponse->status_message ?? null;

        Log::channel('midtrans_payment')->info('| Update | - Midtrans response', [
            'midtrans_order_id' => $orderId,
            'midtrans_transaction_status' => $midtransStatus,
            'midtrans_transaction_id' => $midtransTransactionId,
            'midtrans_status_message' => $midtransStatusMessage
        ]);

        if (empty($midtransStatus) || $midtransStatus === 'pending' || $midtransStatus === 'not_found') {
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

        // Pemetaan status Midtrans ke internal status
        $map = [
            'pending'               => ['transaction' => 'Pending',     'payment' => 'Pending'],
            'authorize'             => ['transaction' => 'Processing',  'payment' => 'Processing'],
            'capture'               => ['transaction' => 'Completed',   'payment' => 'Paid'],
            'settlement'            => ['transaction' => 'Completed',   'payment' => 'Paid'],
            'success'               => ['transaction' => 'Completed',   'payment' => 'Paid'],
            'deny'                  => ['transaction' => 'Failed',      'payment' => 'Failed'],
            'cancel'                => ['transaction' => 'Cancelled',   'payment' => 'Failed'],
            'expire'                => ['transaction' => 'Cancelled',   'payment' => 'Expired'],
            'failure'               => ['transaction' => 'Failed',      'payment' => 'Failed'],
            'refund'                => ['transaction' => 'Refunded',    'payment' => 'Refunded'],
            'partial_refund'        => ['transaction' => 'Refunded',    'payment' => 'Refunded'],
            'chargeback'            => ['transaction' => 'Refunded',    'payment' => 'Refunded'],
            'partial_chargeback'    => ['transaction' => 'Refunded',    'payment' => 'Refunded'],
        ];

        $mapStatus = $map[$midtransStatus] ?? null;

        if (!$mapStatus) {
            return response()->json(
                new WithoutDataResource(
                    Response::HTTP_OK,
                    'STATUS_NOT_HANDLED',
                    'Status Tidak Ditangani',
                    'Status Midtrans belum ditangani: ' . $midtransStatus,
                ),
                Response::HTTP_OK
            );
        }

        $transactionStatusId = TransactionStatus::where('label', $mapStatus['transaction'])->value('id');
        $paymentStatusId = PaymentStatus::where('label', $mapStatus['payment'])->value('id');

        $transaction->update([
            'transaction_status_id' => $transactionStatusId,
            'settlement_date' => in_array($midtransStatus, ['settlement', 'capture', 'success']) ? now() : null,
        ]);

        $transaction->payment_details->update([
            'payment_status_id' => $paymentStatusId,
            'transaction_ref' => $midtransTransactionId,
        ]);

        // Restore stock jika status pembayaran gagal, dibatalkan, atau refund
        if (in_array($midtransStatus, ['deny', 'cancel', 'expire', 'failure', 'refund', 'partial_refund', 'chargeback', 'partial_chargeback'])) {
            foreach ($transaction->order_details as $orderDetail) {
                if (!$orderDetail->stock_restored) {
                    OrderDetailHelper::restoreAnimalStock($orderDetail);
                }
            }
        }

        PaymentLog::create([
            'payment_detail_id' => $transaction->payment_details->id,
            'name' => 'midtrans_status_' . $midtransStatus,
            'type' => 'update',
            'payload' => [
                'order_id' => $orderId,
                'status' => $midtransStatus,
                'transaction_ref' => $midtransTransactionId,
            ],
        ]);

        Log::channel('midtrans_payment')->info('| Update | - Status updated.', [
            'transaction_id' => $transaction->id,
            'payment_detail_id' => $transaction->payment_details->id,
            'transaction_status' => $mapStatus['transaction'],
            'payment_status' => $mapStatus['payment'],
        ]);

        return response()->json(
            new WithoutDataResource(
                Response::HTTP_OK,
                'STATUS_UPDATED',
                'Status Pembayaran Diperbarui',
                'Status transaksi saat ini: ' . ucfirst($midtransStatus ?? 'Unknown'),
            ),
            Response::HTTP_OK
        );
    }
}
