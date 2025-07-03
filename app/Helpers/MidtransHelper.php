<?php

namespace App\Helpers;

use GuzzleHttp\Client;
use App\Models\PaymentLog;
use App\Services\MidtransService;
use Illuminate\Support\Facades\Log;

class MidtransHelper
{
    // Fungsi untuk membuat Snap Token dan mengirimkannya ke piramid-main
    public static function sendSnapToken($params, $transactionId)
    {
        Log::channel('snap_token')->info('| MidtransHelper | - Request Snap Token for transaction_id: ' . $transactionId);

        // Kirim transaksi ke Midtrans untuk membuat Snap Token
        $midtransService = app(MidtransService::class);
        $snap = $midtransService->createTransaction($params);

        // Simpan log untuk Snap Token request
        PaymentLog::create([
            'payment_detail_id' => null,
            'name' => 'snap_created',
            'type' => 'request',
            'payload' => $params,
        ]);

        Log::channel('snap_token')->info('| MidtransHelper | - Snap token created for transaction_id: ' . $transactionId);

        // Kirim Snap Token dan data transaksi ke piramid-main
        // self::sendSnapTokenToMain($transactionId, $snap->token, $snap->redirect_url);

        // Return Snap Token
        return $snap;
    }
}
