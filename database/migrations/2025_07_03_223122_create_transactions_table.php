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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('order_detail_id')->constrained('order_details')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('payment_detail_id')->constrained('payment_details')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('transaction_status_id')->constrained('transaction_statuses')->onUpdate('cascade')->onDelete('cascade');
            $table->timestamp('transaction_date');
            $table->timestamp('settlement_date');
            $table->bigInteger('grand_total');
            $table->string('note')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
