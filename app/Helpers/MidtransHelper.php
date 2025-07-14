<?php

namespace App\Helpers;

use App\Models\PaymentLog;
use App\Services\MidtransService;
use Illuminate\Support\Facades\Log;
use Midtrans\Transaction as MidtransTransaction;

class MidtransHelper
{
    // Fungsi untuk membuat Snap Token dan mengirimkannya ke piramid-main
    public static function sendSnapToken($params, $transactionId, $paymentDetailId)
    {
        Log::channel('snap_token')->info('| MidtransHelper | - Request Snap Token for transaction_id: ' . $transactionId);

        // Kirim transaksi ke Midtrans untuk membuat Snap Token
        $midtransService = app(MidtransService::class);
        $snap = $midtransService->createTransaction($params);

        // Simpan log untuk Snap Token request
        PaymentLog::create([
            'payment_detail_id' => $paymentDetailId,
            'name' => 'snap_created',
            'type' => 'request',
            'payload' => $params,
        ]);

        Log::channel('snap_token')->info('| MidtransHelper | - Snap token created for transaction_id: ' . $transactionId);

        // Return Snap Token
        return $snap;
    }

    // Fungsi untuk mendapatkan status transaksi berdasarkan order_id (custom id kamu: TRX-timestamp-id)
    public static function getTransactionStatus(string $orderId)
    {
        try {
            Log::channel('midtrans_payment')->info("| MidtransHelper | - Checking status for order_id: {$orderId}");

            $status = MidtransTransaction::status($orderId);

            Log::channel('midtrans_payment')->info('| MidtransHelper | - Status response: ' . json_encode($status));

            return $status;
        } catch (\Exception $e) {
            Log::channel('midtrans_payment')->error("| MidtransHelper | - Error checking transaction status for order_id {$orderId}: " . $e->getMessage() . ' - Line : ' . $e->getLine());
            throw $e;
        }
    }
}
