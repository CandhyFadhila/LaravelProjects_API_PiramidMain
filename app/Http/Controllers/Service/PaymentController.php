<?php

namespace App\Http\Controllers\Service;

use App\Helpers\MidtransHelper;
use App\Helpers\OrderDetailHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Service\Payment\StorePayment;
use App\Http\Requests\Service\Payment\UpdateAddressPayment;
use App\Http\Requests\Service\Payment\UpdateMosquePayment;
use App\Http\Requests\Service\Payment\UpdateNotesPayment;
use App\Http\Requests\Service\Payment\UpdatePayment;
use App\Http\Requests\Service\Payment\UpdatePaymentMethod;
use App\Http\Resources\Templates\WithDataResource;
use App\Http\Resources\Templates\WithoutDataResource;
use App\Models\Address;
use App\Models\OrderDetail;
use App\Models\PaymentDetail;
use App\Models\PaymentLog;
use App\Models\PaymentMethod;
use App\Models\PaymentStatus;
use App\Models\ProgressProduct;
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
    // ini v1
    // public function createPayment(StorePayment $request)
    // {
    //     try {
    //         if (!Gate::allows('transaction.create')) {
    //             return response()->json(
    //                 new WithoutDataResource(
    //                     Response::HTTP_FORBIDDEN,
    //                     'NO_ACCESS',
    //                     'Tidak Memiliki Akses',
    //                     'Anda tidak memiliki akses untuk mengakses halaman ini.',
    //                 ),
    //                 Response::HTTP_FORBIDDEN
    //             );
    //         }

    //         $data = $request->validated();


    //         $gateway = strtolower($data['payment_gateway_id']);

    //         // Validasi gateway
    //         // TODO: Kalau coinpayment sudah ready, pakek ini aja
    //         // if (!in_array($gateway, ['midtrans', 'coinpayment'])) {
    //         if (!in_array($gateway, ['midtrans'])) {
    //             return response()->json(
    //                 new WithoutDataResource(
    //                     Response::HTTP_UNPROCESSABLE_ENTITY,
    //                     'UNSUPPORTED_GATEWAY',
    //                     'Gateway Tidak Didukung',
    //                     "Payment gateway '{$data['payment_gateway_id']}' belum tersedia. Silakan pilih metode pembayaran lain."
    //                 ),
    //                 Response::HTTP_UNPROCESSABLE_ENTITY
    //             );
    //         }

    //         DB::beginTransaction();

    //         $user = $request->user();

    //         $orderDetail = OrderDetail::where('id', $data['order_detail_id'])
    //             ->where('user_id', $user->id)
    //             ->first();
    //         if (!$orderDetail) {
    //             return response()->json(
    //                 new WithoutDataResource(
    //                     Response::HTTP_OK,
    //                     'DATA_NOT_FOUND',
    //                     'Order Detail Tidak Ditemukan',
    //                     'Order detail tidak ditemukan atau bukan milik Anda.'
    //                 ),
    //                 Response::HTTP_OK
    //             );
    //         }

    //         if ($orderDetail->last_steps !== 2) {
    //             return response()->json(
    //                 new WithoutDataResource(
    //                     Response::HTTP_BAD_REQUEST,
    //                     'STEP_NOT_ALLOWED',
    //                     'Langkah Tidak Diizinkan',
    //                     'Data order hanya dapat diubah pada langkah kedua.'
    //                 ),
    //                 Response::HTTP_BAD_REQUEST
    //             );
    //         }

    //         $existingTransaction = Transaction::where('order_detail_id', $orderDetail->id)
    //             ->whereHas('payment_details', function ($query) {
    //                 $query->whereHas('payment_statuses', function ($q) {
    //                     $q->whereNotIn('label', ['Failed', 'Expired', 'Refunded']);
    //                 });
    //             })
    //             ->exists();
    //         if ($existingTransaction) {
    //             return response()->json(
    //                 new WithoutDataResource(
    //                     Response::HTTP_BAD_REQUEST,
    //                     'TRANSACTION_EXISTS',
    //                     'Transaksi Sudah Ada',
    //                     'Order detail ini sudah memiliki transaksi yang sedang atau sudah diproses. Tidak dapat membuat transaksi baru.'
    //                 ),
    //                 Response::HTTP_BAD_REQUEST
    //             );
    //         }

    //         // Validasi address_id milik user yang login
    //         $address = Address::where('id', $data['address_id'])
    //             ->where('user_id', $user->id)
    //             ->first();
    //         if (!$address) {
    //             return response()->json(
    //                 new WithoutDataResource(
    //                     Response::HTTP_OK,
    //                     'DATA_NOT_FOUND',
    //                     'Alamat Tidak Ditemukan',
    //                     'Alamat yang Anda pilih tidak ditemukan atau bukan milik Anda.'
    //                 ),
    //                 Response::HTTP_OK
    //             );
    //         }

    //         $orderDetail->update([
    //             'address_id' => $data['address_id'],
    //             'mosque_id' => $data['mosque_id'] ?? null,
    //             'last_steps' => $data['last_steps'],
    //         ]);

    //         $paymentStatusPendingId = PaymentStatus::where('label', 'Pending')->value('id');
    //         $transactionStatusPendingId = TransactionStatus::where('label', 'Pending')->value('id');
    //         $paymentMethodId = $gateway === 'midtrans'
    //             ? PaymentMethod::where('label', 'Fiat')->value('id')
    //             : PaymentMethod::where('label', 'Crypto')->value('id');

    //         $summaryOrderDetail = OrderDetailHelper::summarizeOrderDetail($orderDetail);
    //         // dd(
    //         //     "ammount paid : {$data['amount_paid']}",
    //         //     "summary order detail : {$summaryOrderDetail['price']}"
    //         // );
    //         if ((int) $data['amount_paid'] !== (int) $summaryOrderDetail['price']) {
    //             Log::channel('transaction')->warning("| Store | - Price mismatch for user_id: {$user->id}, order_detail_id: {$orderDetail->id}.", [
    //                 'expected_price' => $summaryOrderDetail['price'],
    //                 'provided_amount' => $data['amount_paid'],
    //             ]);

    //             return response()->json(
    //                 new WithDataResource(
    //                     Response::HTTP_BAD_REQUEST,
    //                     'PRICE_MISMATCH',
    //                     'Total Pembayaran Tidak Sesuai',
    //                     'Jumlah pembayaran yang dikirim tidak sesuai dengan total harga yang dihitung sistem.',
    //                     [
    //                         'amount_paid' => (int) $data['amount_paid'],
    //                         'expected_price' => (int) $summaryOrderDetail['price']
    //                     ]
    //                 ),
    //                 Response::HTTP_BAD_REQUEST
    //             );
    //         }

    //         $transaction = Transaction::where('order_detail_id', $orderDetail->id)->latest()->first();
    //         $paymentDetail = PaymentDetail::where('id', $transaction->payment_detail_id)->first();
    //         if (!$paymentDetail || !$transaction) {
    //             return response()->json(
    //                 new WithoutDataResource(
    //                     Response::HTTP_OK,
    //                     'DATA_NOT_FOUND',
    //                     'Data Tidak Ditemukan',
    //                     'Transaksi awal tidak ditemukan. Silakan hubungi admin.'
    //                 ),
    //                 Response::HTTP_OK
    //             );
    //         }

    //         // Create Payment Detail
    //         $paymentDetail = PaymentDetail::create([
    //             'transaction_id' => null, // Diisi setelah transaksi dibuat
    //             'payment_status_id' => $paymentStatusPendingId,
    //             'payment_method_id' => $paymentMethodId,
    //             'payment_gateway_id' => $data['payment_gateway_id'],
    //             'payment_date' => now(),
    //             'amount_paid' => $summaryOrderDetail['price'],
    //             'transaction_ref' => null,
    //             'currency' => $data['currency'],
    //         ]);

    //         Log::channel('transaction_payment_detail')->info('| Store | - PaymentDetail successfully created.', $paymentDetail->toArray());

    //         // Create Transaction
    //         $transaction = Transaction::create([
    //             'user_id' => $user->id,
    //             'order_detail_id' => $data['order_detail_id'],
    //             'payment_detail_id' => $paymentDetail->id,
    //             'transaction_status_id' => $transactionStatusPendingId,
    //             'transaction_date' => now(),
    //             'settlement_date' => now()->addDay(),
    //             'grand_total' => $summaryOrderDetail['price'],
    //             'note' => $data['note'] ?? null,
    //         ]);

    //         Log::channel('transaction')->info('| Store | - Transaction successfully created.', $transaction->toArray());

    //         $midtransOrderId = 'TRX-' . now('Asia/Jakarta')->format('Ymd-Hisv') . '-' . $transaction->id;

    //         $serviceType = ServiceType::find($transaction->order_details->service_type_id);

    //         // Gateway handler
    //         switch ($gateway) {
    //             case 'midtrans':
    //                 $response = $this->handleCreateMidtransPayment($user, $midtransOrderId, $transaction, $paymentDetail, $serviceType);
    //                 break;
    //             // TODO: Kalau coinpayment sudah ready
    //             // case 'coinpayment':
    //             //     $response = $this->handleCoinpaymentPayment();
    //             //     break;
    //             default:
    //                 throw new \Exception('Gateway tersebut tidak didukung, silahkan pilih metode pembayaran lain.');
    //         }

    //         // Update payment detail and transaction
    //         $paymentStatusProcessId = PaymentStatus::where('label', 'Processing')->value('id');
    //         $transactionStatusProcessId = TransactionStatus::where('label', 'Processing')->value('id');

    //         $paymentDetail->update([
    //             'transaction_id' => $transaction->id,
    //             'payment_status_id' => $paymentStatusProcessId,
    //             'payment_order_id' => $midtransOrderId,
    //         ]);

    //         $transaction->update([
    //             'transaction_status_id' => $transactionStatusProcessId,
    //         ]);

    //         // Kurangi stok hewan
    //         OrderDetailHelper::deductAnimalStock($orderDetail);

    //         DB::commit();
    //         return $response;
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         Log::channel('transaction')->error('| Store | - Error function createPayment : ' . $e->getMessage() . ' - Line : ' . $e->getLine());
    //         return response()->json(
    //             new WithoutDataResource(
    //                 Response::HTTP_INTERNAL_SERVER_ERROR,
    //                 'ERROR_GET_DATA',
    //                 'Gagal Mengambil Data',
    //                 'Terjadi kesalahan pada sistem, silahkan coba lagi nanti atau hubungi admin.',
    //             ),
    //             Response::HTTP_INTERNAL_SERVER_ERROR
    //         );
    //     }
    // }

    // Update pheriperal
    public function updateAddressPayment(UpdateAddressPayment $request, $id)
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

            $user = $request->user();

            $data = $request->validated();

            $orderDetail = OrderDetail::where('id', $id)
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

            $orderDetail->update($data);

            DB::commit();
            return response()->json(
                new WithoutDataResource(
                    Response::HTTP_OK,
                    'SUCCESS_UPDATE_DATA',
                    'Berhasil Memperbarui Data',
                    "Alamat pesanan berhasil diperbarui."
                ),
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('midtrans_payment')->error('| updateAddress | Error function updateAddress : ' . $e->getMessage() . ' - Line: ' . $e->getLine());
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

    public function updateNotesPayment(UpdateNotesPayment $request, $id)
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

            $user = $request->user();

            $data = $request->validated();

            // Ambil order detail yang sesuai dengan user
            $orderDetail = OrderDetail::where('id', $id)
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

            // Ambil transaksi berdasarkan order_detail_id
            $transaction = Transaction::where('order_detail_id', $id)->first();
            if (!$transaction) {
                return response()->json(
                    new WithoutDataResource(
                        Response::HTTP_OK,
                        'DATA_NOT_FOUND',
                        'Transaksi Tidak Ditemukan',
                        'Transaksi belum dibuat untuk pesanan ini.'
                    ),
                    Response::HTTP_OK
                );
            }

            $transaction->update($data);

            DB::commit();
            return response()->json(
                new WithoutDataResource(
                    Response::HTTP_OK,
                    'SUCCESS_UPDATE_DATA',
                    'Berhasil Memperbarui Data',
                    "Notes pesanan berhasil diperbarui."
                ),
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('midtrans_payment')->error('| updateNotes | Error function updateNotesOrder : ' . $e->getMessage() . ' - Line: ' . $e->getLine());
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

    public function updatePaymentMethod(UpdatePaymentMethod $request, $id)
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

            $user = $request->user();

            $data = $request->validated();

            // Ambil order detail yang sesuai dengan user
            $orderDetail = OrderDetail::where('id', $id)
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

            // Ambil transaksi berdasarkan order_detail_id
            $transaction = Transaction::where('order_detail_id', $id)->first();
            if (!$transaction) {
                return response()->json(
                    new WithoutDataResource(
                        Response::HTTP_OK,
                        'DATA_NOT_FOUND',
                        'Transaksi Tidak Ditemukan',
                        'Transaksi belum dibuat untuk pesanan ini.'
                    ),
                    Response::HTTP_OK
                );
            }

            $paymentDetail = PaymentDetail::find($transaction->payment_detail_id);
            if (!$paymentDetail) {
                return response()->json(
                    new WithoutDataResource(
                        Response::HTTP_OK,
                        'DATA_NOT_FOUND',
                        'Detail Pembayaran Tidak Ditemukan',
                        'Detail pembayaran tidak ditemukan.'
                    ),
                    Response::HTTP_OK
                );
            }

            // Ambil payment method dari ID yang diinput
            $paymentMethod = PaymentMethod::find($data['payment_method_id']);
            if (!$paymentMethod) {
                return response()->json(
                    new WithoutDataResource(
                        Response::HTTP_OK,
                        'DATA_NOT_FOUND',
                        'Metode Pembayaran Tidak Ditemukan',
                        'Metode pembayaran tidak valid.'
                    ),
                    Response::HTTP_OK
                );
            }

            // Tentukan payment_gateway_id dan currency berdasarkan tipe pembayaran
            $gatewayId = strtolower($paymentMethod->label) === 'fiat' ? 'midtrans' : 'coinpayment';
            $currency = $gatewayId === 'midtrans' ? 'IDR' : 'USD';

            // Update ke tabel payment_details
            $paymentDetail->update([
                'payment_method_id' => $data['payment_method_id'],
                'payment_gateway_id' => $gatewayId,
                'currency' => $currency,
            ]);

            DB::commit();
            return response()->json(
                new WithoutDataResource(
                    Response::HTTP_OK,
                    'SUCCESS_UPDATE_DATA',
                    'Berhasil Memperbarui Metode Pembayaran',
                    "Metode pembayaran berhasil diperbarui."
                ),
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('midtrans_payment')->error('| updatePaymentMethod | Error function updatePaymentMethod : ' . $e->getMessage() . ' - Line: ' . $e->getLine());
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

    public function updateMosquePayment(UpdateMosquePayment $request, $id)
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

            $user = $request->user();

            $data = $request->validated();

            $orderDetail = OrderDetail::where('id', $id)
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

            $orderDetail->update($data);

            DB::commit();
            return response()->json(
                new WithoutDataResource(
                    Response::HTTP_OK,
                    'SUCCESS_UPDATE_DATA',
                    'Berhasil Memperbarui Data',
                    "Masjid tempat pengiriman produk pesanan berhasil diperbarui."
                ),
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('midtrans_payment')->error('| updateMosque | Error function updateMosque : ' . $e->getMessage() . ' - Line: ' . $e->getLine());
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

    // Langsung create midtrans
    public function createSnapToken(Request $request)
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

            if (!$request->filled('order_detail_id')) {
                return response()->json(
                    new WithoutDataResource(
                        Response::HTTP_OK,
                        'ORDER_DETAIL_ID_REQUIRED',
                        'ID Order Detail Wajib Diisi',
                        'Silakan kirimkan order_detail_id terlebih dahulu.'
                    ),
                    Response::HTTP_OK
                );
            }

            $user = $request->user();
            $orderDetail = OrderDetail::where('id', $request->input('order_detail_id'))
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

            $orderDetail->update([
                'last_steps' => 3
            ]);

            $transaction = Transaction::where('order_detail_id', $orderDetail->id)->latest()->firstOrFail();
            $paymentDetail = PaymentDetail::findOrFail($transaction->payment_detail_id);

            $gateway = $paymentDetail->payment_gateway_id;

            $serviceType = ServiceType::findOrFail($transaction->order_details->service_type_id);
            $midtransOrderId = 'TRX-' . now('Asia/Jakarta')->format('Ymd-Hisv') . '-' . $transaction->id;

            $transaction->update([
                'transaction_date' => now(),
                'settlement_date' => now()->addDay(),
            ]);

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
            $paymentStatusProcessId = PaymentStatus::where('label', 'Pending')->value('id');
            $transactionStatusProcessId = TransactionStatus::where('label', 'Pending')->value('id');

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
            Log::channel('midtrans_payment')->error('| createSnapToken | Error function createSnapToken : ' . $e->getMessage() . ' - Line: ' . $e->getLine());
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

        $transaction->update([
            'snap_token' => $snapResponse->token,
            'midtrans_order_id' => $midtransOrderId
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
        $midtransGrossAmount = $statusResponse->gross_amount ?? null;
        $formattedGrossAmount = number_format((float) $midtransGrossAmount, 0, '.', '');

        Log::channel('midtrans_payment')->info('| Update | - Midtrans response', [
            'midtrans_order_id' => $orderId,
            'midtrans_transaction_status' => $midtransStatus,
            'midtrans_transaction_id' => $midtransTransactionId,
            'midtrans_status_message' => $midtransStatusMessage
        ]);

        if (empty($midtransStatus) || $midtransStatus === 'pending' || $midtransStatus === 'not_found') {
            return response()->json(
                new WithoutDataResource(
                    Response::HTTP_BAD_REQUEST,
                    'STATUS_UNSETTLED',
                    'Pembayaran Belum Diselesaikan',
                    'Status transaksi belum settlement: ' . ucfirst($midtransStatus),
                ),
                Response::HTTP_BAD_REQUEST
            );
        }

        $parts = explode('-', $orderId);
        $transactionId = end($parts);

        $transaction = Transaction::with(['payment_details', 'order_details'])->find($transactionId);
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
                    Response::HTTP_BAD_REQUEST,
                    'STATUS_NOT_HANDLED',
                    'Status Tidak Ditangani',
                    'Status Midtrans belum ditangani: ' . $midtransStatus,
                ),
                Response::HTTP_BAD_REQUEST
            );
        }

        $transactionStatusId = TransactionStatus::where('label', $mapStatus['transaction'])->value('id');
        $paymentStatusId = PaymentStatus::where('label', $mapStatus['payment'])->value('id');

        $transaction->update([
            'transaction_status_id' => $transactionStatusId,
            'settlement_date' => in_array($midtransStatus, ['settlement', 'capture', 'success']) ? now() : null,
        ]);

        $transaction->payment_details->update([
            'payment_date' => now(),
            'payment_status_id' => $paymentStatusId,
            'transaction_ref' => $midtransTransactionId,
            'amount_paid' => $formattedGrossAmount
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

        $settledStatuses = ['settlement', 'capture', 'success'];
        if (in_array($midtransStatus, $settledStatuses)) {
            // Tentukan deskripsi berdasarkan service_type_id di order_details
            $serviceTypeId = optional($transaction->order_details)->service_type_id;

            $descriptions = [
                1 => 'Transaksi Qurban telah selesai. Pesanan sedang diproses (penjadwalan pengiriman & tindak lanjut).',
                2 => 'Transaksi Aqiqah telah selesai. Persiapan aqiqah sedang diproses (penyembelihan & paket).',
                3 => 'Transaksi Sadaqah telah selesai. Penyaluran donasi sedang diproses sesuai ketentuan.',
            ];

            $description = $descriptions[$serviceTypeId] ?? 'Transaksi telah selesai dan produk sedang diproses.';

            $existingProgress = ProgressProduct::where('transaction_id', $transaction->id)->first();
            if (!$existingProgress) {
                $progress = ProgressProduct::create([
                    'transaction_id' => $transaction->id,
                    'status'         => 'processed',
                    'description'    => $description,
                ]);

                Log::channel('progress_product')->info('| Store | - ProgressProduct auto created when transaction settled.', [
                    'transaction_id'      => $transaction->id,
                    'progress_product_id' => $progress->id,
                ]);
            } else {
                Log::channel('progress_product')->info('| Store | - ProgressProduct auto created but already exists, when transaction settled.', [
                    'transaction_id'      => $transaction->id,
                    'progress_product_id' => $existingProgress->id,
                ]);
            }

            return response()->json(
                new WithoutDataResource(
                    Response::HTTP_OK,
                    'STATUS_SETTLED',
                    'Pembayaran Berhasil Diselesaikan',
                    'Status transaksi saat ini: ' . ucfirst($midtransStatus),
                ),
                Response::HTTP_OK
            );
        } else {
            return response()->json(
                new WithoutDataResource(
                    Response::HTTP_BAD_REQUEST,
                    'STATUS_UNSETTLED',
                    'Pembayaran Belum Diselesaikan',
                    'Status transaksi belum settlement: ' . ucfirst($midtransStatus),
                ),
                Response::HTTP_BAD_REQUEST
            );
        }
    }
}
