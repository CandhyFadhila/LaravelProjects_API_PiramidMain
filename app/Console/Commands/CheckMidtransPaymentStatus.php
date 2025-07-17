<?php

namespace App\Console\Commands;

use App\Helpers\MidtransHelper;
use App\Http\Controllers\Service\PaymentController;
use App\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckMidtransPaymentStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-midtrans-payment-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cek status pembayaran Midtrans untuk transaksi yang masih pending/menunggu';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Memulai pengecekan status pembayaran Midtrans...');

        $pendingTransactions = Transaction::whereHas('payment_details', function ($query) {
            $query->whereIn('transaction_status_id', [1, 2]);
        })->get();

        if ($pendingTransactions->isEmpty()) {
            $this->info('Tidak ada transaksi pending.');
            Log::channel('cronjob_midtrans_status')->info('| Cronjob | Tidak ada transaksi yang masih pending.');
            return;
        }

        foreach ($pendingTransactions as $transaction) {
            try {
                // Ambil order_id dari transaksi
                $orderId = $transaction->payment_details->payment_order_id;

                // Dapatkan status transaksi dari Midtrans
                $statusResponse = MidtransHelper::getTransactionStatus($orderId);

                // Panggil fungsi updateStatusMidtransPayment untuk memproses perubahan status
                $controller = app(PaymentController::class);
                $controller->handleUpdateStatusMidtransPayment($orderId, $statusResponse);
                Log::channel('cronjob_midtrans_status')->info('| Cronjob | Success update status transaction ' . $transaction->id . ' at ' . now('Asia/Jakarta')->format('Y-m-d H:i:s'));
            } catch (\Exception $e) {
                Log::channel('cronjob_midtrans_status')->error('| Cronjob | Error cek transaksi ' . $transaction->id . ' : ' . $e->getMessage());
            }
        }

        $this->info('Selesai pengecekan status.');
    }
}
