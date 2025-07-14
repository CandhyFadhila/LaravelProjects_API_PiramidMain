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
        Schema::create('aqiqah_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('animal_id')->constrained('animals')->onUpdate('cascade');
            $table->json('photo_product_id')->nullable();
            $table->string('name');
            $table->text('description');
            $table->bigInteger('price');
            $table->bigInteger('portion_count'); // Untuk menentukan porsi
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aqiqah_products');
    }
};
