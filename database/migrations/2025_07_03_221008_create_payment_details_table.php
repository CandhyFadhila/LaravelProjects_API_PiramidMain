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
        Schema::create('payment_details', function (Blueprint $table) {
            $table->id();
            // $table->foreignId('transaction_id')->nullable()->constrained('transactions')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('payment_status_id')->constrained('payment_statuses')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('payment_method_id')->constrained('payment_methods')->onUpdate('cascade')->onDelete('cascade');
            $table->string('payment_gateway_id');
            $table->timestamp('payment_date');
            $table->bigInteger('amount_paid');
            $table->string('transaction_ref');
            $table->string('currency');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_details');
    }
};
