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
        Schema::create('sadaqah_products', function (Blueprint $table) {
            $table->id();
            $table->json('photo_product_id')->nullable();
            $table->json('animal_id')->nullable();
            $table->string('name');
            $table->text('description');
            $table->bigInteger('price');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sadaqah_products');
    }
};
