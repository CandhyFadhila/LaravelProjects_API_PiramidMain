<?php

namespace App\Http\Controllers\Service;

use App\Helpers\MidtransHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Service\Payment\StorePayment;
use App\Http\Resources\Templates\WithDataResource;
use App\Http\Resources\Templates\WithoutDataResource;
use App\Models\PaymentDetail;
use App\Models\PaymentMethod;
use App\Models\PaymentStatus;
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

            Log::channel('transaction_payment_detail')->info('| createPayment | - PaymentDetail successfully created.', $paymentDetail->toArray());

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

            Log::channel('transaction')->info('| createPayment | - Transaction successfully created.', $transaction->toArray());

            $paymentDetail->update([
                'transaction_id' => $transaction->id,
            ]);

            // Gateway handler
            switch ($gateway) {
                case 'midtrans':
                    $response = $this->handleMidtransPayment($user, $data, $transaction, $paymentDetail);
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
            Log::channel('transaction')->error('| createPayment | - Error function createPayment : ' . $e->getMessage());
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

    public function updateStatusPayment()
    {
        // Ini untuk cek status pembayaran, jika sudah lunas, update status transaksi dan payment detail
        // $midtransStatus = MidtransHelper::getTransactionStatus($midtransOrderId);
        // $midtransTransactionId = $midtransStatus->transaction_id ?? null;
    }

    private function handleMidtransPayment($user, $data, $transaction, $paymentDetail)
    {
        // Midtrans
        $midtransOrderId = 'TRX-' . now()->format('Ymd') . '-' . $transaction->id;
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
                    'name' => 'Pembayaran Order #' . $data['order_detail_id'],
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
                ]
            ),
            Response::HTTP_CREATED
        );
    }

    private function handleCoinpaymentPayment() {}
}
