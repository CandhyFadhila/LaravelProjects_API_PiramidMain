<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('progress_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions')->onUpdate('cascade');
            $table->enum('status', [
                'in_queued',        // Dalam Antrian
                'in_scheduling',    // Dalam Penjadwalan
                'in_progress',      // Dalam Proses
                'completed',        // Selesai
                'delivered',        // Terkirim
                'in_shipping',      // Dalam Pengiriman
            ])->default('in_queued')->index();
            $table->json('photo_progress_id')->nullable();
            $table->text('description');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('progress_products');
    }
};
